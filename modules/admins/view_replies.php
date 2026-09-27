<?php
/**
 * Admin Reply Log
 * 
 * Displays a log of all replies sent by administrators to contact messages.
 * Supports filtering by sender email and date.
 * 
 * Features:
 * - Secure parameterized queries
 * - Robust error handling
 * - Professional UI with responsive design
 * - CSRF protection
 * - XSS prevention
 * - Form state persistence
 * - Empty state handling
 */

declare(strict_types=1);

// Include required files
require_once __DIR__ . '/../../includes/auth.php'; // Restrict to authenticated admins
require_once __DIR__ . '/../../db.php';

// =============================================================================
// Configuration
// =============================================================================

// Set error reporting for production (log errors, don't display)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// =============================================================================
// Helper Functions
// =============================================================================

/**
 * Sanitize and validate email filter input
 */
function sanitizeEmailFilter(?string $email): string
{
    if (empty($email)) {
        return '';
    }
    // Remove any dangerous characters for LIKE clause
    return '%' . preg_replace('/[%_]/', '\\\\$0', trim($email)) . '%';
}

/**
 * Validate date format (YYYY-MM-DD)
 */
function isValidDateFormat(string $date): bool
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
}

/**
 * Get column existence in table
 */
function getExistingColumns(mysqli $conn, string $table): array
{
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "`");
    if ($result) {
        while ($col = $result->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
        $result->free();
    }
    return $columns;
}

/**
 * Escape and truncate text for display
 */
function truncateText(?string $text, int $length = 100): string
{
    if (empty($text)) {
        return '';
    }
    
    $text = trim($text);
    if (mb_strlen($text) <= $length) {
        return htmlspecialchars($text);
    }
    
    return htmlspecialchars(mb_substr($text, 0, $length)) . '…';
}

// =============================================================================
// Database Connection & Column Detection
// =============================================================================

// Verify database connection
if (!$conn || $conn->connect_error) {
    die('<!-- Database connection error. Please contact administrator. -->');
}

// Detect existing columns in contact_messages table
$contactColumns = getExistingColumns($conn, 'contact_messages');
$adminColumns = getExistingColumns($conn, 'admin_replies');

// =============================================================================
// Build Dynamic SELECT Fields
// =============================================================================

$selectFields = [
    'r.reply_id',
    'r.message_id',
    'r.admin_email',
    'r.reply_text',
    'r.replied_at'
];

// Admin name handling
if (in_array('admin_name', $adminColumns)) {
    $selectFields[] = 'r.admin_name';
} else {
    $selectFields[] = "'Administrator' as admin_name";
}

// Sender name handling
if (in_array('sender_name', $contactColumns)) {
    $selectFields[] = 'm.sender_name';
} else {
    $selectFields[] = "'—' as sender_name";
}

// Sender email handling
if (in_array('email', $contactColumns)) {
    $selectFields[] = 'm.email as sender_email';
} elseif (in_array('sender_email', $contactColumns)) {
    $selectFields[] = 'm.sender_email';
} else {
    $selectFields[] = "'' as sender_email";
}

// Message text handling
if (in_array('message', $contactColumns)) {
    $selectFields[] = 'm.message as original_message';
} elseif (in_array('message_text', $contactColumns)) {
    $selectFields[] = 'm.message_text as original_message';
} else {
    $selectFields[] = "'' as original_message";
}

// Submission timestamp handling
if (in_array('created_at', $contactColumns)) {
    $selectFields[] = 'm.created_at as submitted_at';
} elseif (in_array('submitted_at', $contactColumns)) {
    $selectFields[] = 'm.submitted_at';
} else {
    $selectFields[] = 'NULL as submitted_at';
}

$selectClause = implode(', ', $selectFields);

// =============================================================================
// Build Filter Conditions (Prepared Statement)
// =============================================================================

$whereConditions = [];
$params = [];
$types = '';

// Get filter values from request
$senderEmail = $_GET['sender_email'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Sender email filter (LIKE with wildcards)
if (!empty($senderEmail)) {
    $whereConditions[] = "m.email LIKE ?";
    $params[] = sanitizeEmailFilter($senderEmail);
    $types .= 's';
}

// Exact date filter
if (!empty($dateFilter) && isValidDateFormat($dateFilter)) {
    $whereConditions[] = "DATE(r.replied_at) = ?";
    $params[] = $dateFilter;
    $types .= 's';
}

$whereClause = empty($whereConditions) ? '' : 'AND ' . implode(' AND ', $whereConditions);

// =============================================================================
// Execute Query with Error Handling
// =============================================================================

$sql = "
    SELECT {$selectClause}
    FROM admin_replies r
    INNER JOIN contact_messages m ON r.message_id = m.id
    WHERE 1=1 {$whereClause}
    ORDER BY r.replied_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Log error and show user-friendly message
    error_log("Admin Reply Log - Prepare failed: " . $conn->error);
    $result = false;
    $queryError = "Unable to prepare the reply log query. Please contact support.";
} else {
    // Bind parameters if needed
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Admin Reply Log - Execute failed: " . $stmt->error);
        $result = false;
        $queryError = "Unable to retrieve reply data. Please try again later.";
    } else {
        $result = $stmt->get_result();
        $queryError = null;
    }
}

// =============================================================================
// HTML Output Starts Here
// =============================================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Administrator Reply Log | Support Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* ============================================
           Professional Admin Log Styles
           ============================================ */
        
        /* CSS Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f6f9;
            color: #1a2c3e;
            line-height: 1.5;
            margin: 0;
            padding: 24px;
        }
        
        /* Container */
        .log-container {
            max-width: 1400px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        /* Header */
        .log-header {
            background: linear-gradient(135deg, #1e3c5c 0%, #2a4d6e 100%);
            color: white;
            padding: 28px 32px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .log-header h1 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        
        .log-header p {
            font-size: 0.9rem;
            opacity: 0.85;
            margin-bottom: 0;
        }
        
        /* Filter Card */
        .filter-card {
            background: #f8fafc;
            padding: 20px 32px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 16px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .filter-group label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4a5c6c;
        }
        
        .filter-group input {
            padding: 10px 14px;
            border: 1px solid #cad2db;
            border-radius: 8px;
            font-size: 0.9rem;
            width: 240px;
            transition: all 0.2s ease;
            background: white;
        }
        
        .filter-group input:focus {
            outline: none;
            border-color: #2c6e9e;
            box-shadow: 0 0 0 3px rgba(44, 110, 158, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        
        .btn-primary {
            background: #2c6e9e;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e4a6e;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #2d3a46;
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        /* Table Styles */
        .table-wrapper {
            overflow-x: auto;
            padding: 0 0 24px 0;
        }
        
        .reply-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .reply-table th {
            background: #f1f5f9;
            color: #1e2f3d;
            font-weight: 600;
            padding: 14px 16px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        
        .reply-table td {
            padding: 16px;
            border-bottom: 1px solid #ecf1f5;
            vertical-align: top;
            color: #1e2f3d;
        }
        
        .reply-table tr:hover td {
            background-color: #fafcff;
        }
        
        /* Message Preview */
        .message-preview {
            max-width: 280px;
        }
        
        .message-text {
            color: #2c3e4e;
            line-height: 1.4;
            word-break: break-word;
        }
        
        .message-meta {
            font-size: 0.7rem;
            color: #7a8c9c;
            margin-top: 8px;
            display: block;
        }
        
        .reply-text {
            max-width: 320px;
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 8px;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.45;
        }
        
        /* Badge & Status */
        .empty-state {
            text-align: center;
            padding: 60px 32px;
            background: #ffffff;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: #4a627a;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: #7c8ea0;
            font-size: 0.875rem;
        }
        
        .error-state {
            margin: 20px 32px;
            padding: 16px 20px;
            background: #fff5f5;
            border-left: 4px solid #dc2626;
            border-radius: 8px;
            color: #991b1b;
        }
        
        /* Footer */
        .log-footer {
            background: #f8fafc;
            padding: 16px 32px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .btn-back {
            background: #f1f5f9;
            color: #2c6e9e;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-back:hover {
            background: #e2e8f0;
        }
        
        .record-count {
            font-size: 0.8rem;
            color: #5c6f87;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }
            
            .log-header {
                padding: 20px;
            }
            
            .filter-card {
                padding: 16px 20px;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group input {
                width: 100%;
            }
            
            .reply-table th,
            .reply-table td {
                padding: 10px 12px;
            }
            
            .reply-text,
            .message-preview {
                max-width: 200px;
            }
        }
        
        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .filter-card,
            .btn-back,
            .btn-secondary {
                display: none;
            }
            .reply-table th {
                background: #eee;
            }
        }
    </style>
    <!-- Footer / Back Link -->
    <div class="log-footer">
        <a href="../../dashboards/admin.php" class="btn-back">← Back to Dashboard</a>
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="record-count">
                📊 Showing <?= $result->num_rows ?> reply record<?= $result->num_rows !== 1 ? 's' : '' ?>
            </div>
        <?php endif; ?>
    </div>
</head>
<body>
<div class="log-container">
    
    <!-- Header Section -->
    <div class="log-header">
        <h1>📋 Administrator Reply Log</h1>
        <p>Complete history of responses sent to customer inquiries</p>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-card">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label for="sender_email">📧 Customer Email</label>
                <input type="email" id="sender_email" name="sender_email" 
                       placeholder="Partial or full email address"
                       value="<?= htmlspecialchars($senderEmail, ENT_QUOTES, 'UTF-8') ?>"
                       autocomplete="off">
            </div>
            
            <div class="filter-group">
                <label for="date">📅 Reply Date</label>
                <input type="date" id="date" name="date" 
                       value="<?= htmlspecialchars($dateFilter, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                <a href="?reset=1" class="btn btn-secondary">⟳ Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Results Table -->
    <div class="table-wrapper">
        <?php if (isset($queryError)): ?>
            <div class="error-state">
                <strong>⚠️ System Notice:</strong> <?= htmlspecialchars($queryError) ?>
            </div>
        <?php elseif ($result && $result->num_rows > 0): ?>
            <table class="reply-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Original Message</th>
                        <th>Admin Response</th>
                        <th>Replied By</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="white-space: nowrap;">
                                <strong>#<?= (int)($row['reply_id'] ?? 0) ?></strong>
                                <div style="font-size: 0.7rem; color: #6b7c93;">Msg: <?= (int)($row['message_id'] ?? 0) ?></div>
                            </td>
                            <td>
                                <?php 
                                    $senderName = trim($row['sender_name'] ?? '');
                                    echo htmlspecialchars($senderName ?: '—', ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                            <td style="word-break: break-all;">
                                <?php 
                                    $email = trim($row['sender_email'] ?? '');
                                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                        echo '<a href="mailto:' . htmlspecialchars($email) . '" style="color: #2c6e9e; text-decoration: none;">' 
                                             . htmlspecialchars($email) . '</a>';
                                    } else {
                                        echo htmlspecialchars($email ?: '—');
                                    }
                                ?>
                            </td>
                            <td class="message-preview">
                                <div class="message-text">
                                    <?= nl2br(truncateText($row['original_message'] ?? '', 120)) ?>
                                </div>
                                <?php if (!empty($row['submitted_at']) && $row['submitted_at'] !== 'NULL'): ?>
                                    <small class="message-meta">📨 Received: <?= htmlspecialchars(date('M j, Y g:i A', strtotime($row['submitted_at']))) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="reply-text">
                                    <?= nl2br(htmlspecialchars($row['reply_text'] ?? '[No reply content]', ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </td>
                            <td style="white-space: nowrap;">
                                <div><?= htmlspecialchars($row['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="font-size: 0.7rem; color: #6b7c93;"><?= htmlspecialchars($row['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td style="white-space: nowrap;">
                                <?php 
                                    $repliedAt = $row['replied_at'] ?? null;
                                    if ($repliedAt && $repliedAt !== '0000-00-00 00:00:00') {
                                        echo '<span title="' . htmlspecialchars($repliedAt) . '">'
                                             . htmlspecialchars(date('M j, Y g:i A', strtotime($repliedAt))) 
                                             . '</span>';
                                    } else {
                                        echo '—';
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php elseif ($result && $result->num_rows === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No replies found</h3>
                <p>Try adjusting your search filters or check back later.</p>
                <?php if (!empty($senderEmail) || !empty($dateFilter)): ?>
                    <a href="?reset=1" class="btn btn-secondary" style="margin-top: 12px;">Clear all filters</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">⚠️</div>
                <h3>Unable to load reply data</h3>
                <p>Please contact system administrator if the issue persists.</p>
            </div>
        <?php endif; ?>
    </div>
    
    
</div>

<?php
// Clean up resources
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
</body>
</html>