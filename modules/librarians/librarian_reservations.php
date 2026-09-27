<?php
include("../../db.php");
session_start();

if ($_SESSION['role'] !== 'librarian') {
    die("Access denied");
}

$message = "";
$message_type = "";

/* =====================
   AUTO EXPIRE REQUESTS (48 hours)
===================== */
$expired_count = $conn->query("
UPDATE reservations 
SET status='rejected', response_date=NOW()
WHERE status='pending' 
AND request_date < NOW() - INTERVAL 48 HOUR
");

/* =====================
   HANDLE ACTION
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];

    // Get book details
    $res = $conn->query("SELECT book_id, student_id FROM reservations WHERE id = $id");
    $row = $res->fetch_assoc();
    $book_id = $row['book_id'];
    $student_id = $row['student_id'];

    if ($action === "approve") {
        // Check stock with lock
        $check = $conn->query("SELECT title, available, total_copies FROM books WHERE id = $book_id");
        $book = $check->fetch_assoc();

        if ($book['available'] > 0) {
            // Approve the reservation
            $conn->query("
                UPDATE reservations 
                SET status='approved', response_date=NOW()
                WHERE id = $id
            ");
            
            // Reduce available copies
            $conn->query("
                UPDATE books 
                SET available = available - 1
                WHERE id = $book_id AND available > 0
            ");
            
            $message = "✅ Reservation approved for '{$book['title']}'";
            $message_type = "success";
        } else {
            // Reject if no stock
            $conn->query("
                UPDATE reservations 
                SET status='rejected', response_date=NOW()
                WHERE id = $id
            ");
            
            $message = "❌ Cannot approve - No copies available for '{$book['title']}'";
            $message_type = "error";
        }
    } elseif ($action === "reject") {
        $conn->query("
            UPDATE reservations 
            SET status='rejected', response_date=NOW()
            WHERE id = $id
        ");
        
        $message = "✅ Reservation rejected successfully";
        $message_type = "success";
    }
}

/* =====================
   GET FILTERS
===================== */
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

/* =====================
   GET ALL REQUESTS WITH FILTERS
===================== */
$sql = "
SELECT r.id, u.username, u.email, u.id_number, b.title, b.author, b.available, 
       r.status, r.request_date, r.response_date
FROM reservations r
JOIN users u ON r.student_id = u.id
JOIN books b ON r.book_id = b.id
WHERE 1=1
";

if ($status_filter != 'all') {
    $sql .= " AND r.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if (!empty($search_filter)) {
    $sql .= " AND (u.username LIKE '%$search_filter%' OR u.id_number LIKE '%$search_filter%' OR b.title LIKE '%$search_filter%')";
}

$sql .= " ORDER BY 
    CASE r.status 
        WHEN 'pending' THEN 1 
        WHEN 'approved' THEN 2 
        ELSE 3 
    END,
    r.request_date DESC";

$result = $conn->query($sql);

// Get statistics
$stats = [];
$stats['pending'] = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status='pending'")->fetch_assoc()['count'];
$stats['approved'] = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status='approved'")->fetch_assoc()['count'];
$stats['rejected'] = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status='rejected'")->fetch_assoc()['count'];
$stats['total'] = $stats['pending'] + $stats['approved'] + $stats['rejected'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reservations - Librarian Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.2s;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-card.pending { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.approved { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.rejected { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card.total { background: linear-gradient(135deg, #3498db, #2980b9); }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        /* Filter Section */
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .filter-grid {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 5px;
            color: #495057;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #2c3e50;
            color: white;
            font-weight: 600;
            font-size: 13px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Buttons */
        .btn-approve {
            background: #28a745;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .btn-approve:hover {
            background: #218838;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn-reject:hover {
            background: #c82333;
        }
        
        .stock-info {
            font-size: 11px;
            color: #6c757d;
            margin-top: 3px;
        }
        
        .stock-available {
            color: #27ae60;
            font-weight: bold;
        }
        
        .stock-unavailable {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .no-results {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .info-box {
            background: #ecf0f1;
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 13px;
        }
        
        .info-box ul {
            margin-left: 20px;
            margin-top: 8px;
        }
        
        .info-box li {
            margin: 5px 0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .filter-grid {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            th, td {
                padding: 8px;
                font-size: 12px;
            }
            
            .btn-approve, .btn-reject {
                padding: 4px 10px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="../../dashboards/librarian.php" class="back-link">← Back to Dashboard</a>
        <h1>📚 Reservation Management</h1>
        <p class="subtitle">Manage and process student book reservation requests</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card total" onclick="window.location.href='?status=all'">
            <div class="stat-icon">📊</div>
            <div class="stat-number"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Requests</div>
        </div>
        <div class="stat-card pending" onclick="window.location.href='?status=pending'">
            <div class="stat-icon">⏳</div>
            <div class="stat-number"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card approved" onclick="window.location.href='?status=approved'">
            <div class="stat-icon">✅</div>
            <div class="stat-number"><?php echo $stats['approved']; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card rejected" onclick="window.location.href='?status=rejected'">
            <div class="stat-icon">❌</div>
            <div class="stat-number"><?php echo $stats['rejected']; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" id="filterForm">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>🔍 Search</label>
                    <input type="text" name="search" placeholder="Student name, ID, or book title..." 
                           value="<?php echo htmlspecialchars($search_filter); ?>">
                </div>
                <div class="filter-group">
                    <label>📋 Status</label>
                    <select name="status">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Requests</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button type="submit" class="btn-primary">🔍 Apply Filter</button>
                    <a href="manage_reservations.php" class="btn-secondary">🔄 Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Reservations Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Student Info</th>
                    <th>Book Details</th>
                    <th>Stock Status</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['username']); ?></strong><br>
                                <small style="color: #666;">ID: <?php echo htmlspecialchars($row['id_number']); ?></small><br>
                                <small style="color: #666;">📧 <?php echo htmlspecialchars($row['email']); ?></small>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['title']); ?></strong><br>
                                <?php if (!empty($row['author'])): ?>
                                    <small style="color: #666;">By: <?php echo htmlspecialchars($row['author']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['available'] > 0): ?>
                                    <span class="stock-available">✅ <?php echo $row['available']; ?> copy(s) available</span>
                                <?php else: ?>
                                    <span class="stock-unavailable">❌ No copies available</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span title="<?php echo date('F d, Y - h:i A', strtotime($row['request_date'])); ?>">
                                    <?php echo date('M d, Y', strtotime($row['request_date'])); ?>
                                </span>
                                <br>
                                <small><?php echo date('h:i A', strtotime($row['request_date'])); ?></small>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $row['status']; ?>">
                                    <?php 
                                        if ($row['status'] === 'pending') echo "⏳ Pending";
                                        elseif ($row['status'] === 'approved') echo "✅ Approved";
                                        else echo "❌ Rejected";
                                    ?>
                                </span>
                                <?php if ($row['status'] === 'approved' && $row['response_date']): ?>
                                    <br><small>Processed: <?php echo date('M d', strtotime($row['response_date'])); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn-approve" 
                                            <?php echo $row['available'] <= 0 ? 'disabled title="No copies available"' : ''; ?>>
                                            ✅ Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn-reject" 
                                            onclick="return confirm('Reject this reservation request?')">
                                            ❌ Reject
                                        </button>
                                    </form>
                                    <?php if ($row['available'] <= 0): ?>
                                        <div class="stock-info">⚠️ Cannot approve - no stock</div>
                                    <?php endif; ?>
                                <?php elseif ($row['status'] === 'approved'): ?>
                                    <span style="color: #27ae60;">✓ Processed</span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">✗ Closed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-results">
                            <div>📭 No reservation requests found</div>
                            <div style="font-size: 12px; margin-top: 10px;">
                                <?php if ($status_filter != 'all'): ?>
                                    Try changing your filter to see more results
                                <?php else: ?>
                                    No students have made reservation requests yet
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Information Box -->
    <div class="info-box">
        <strong>ℹ️ Reservation Guidelines:</strong>
        <ul>
            <li>Reservations automatically expire after <strong>48 hours</strong> if not processed</li>
            <li>Approving a reservation reduces the available book stock by 1</li>
            <li>Students can only have <strong>3 active reservation requests</strong> at a time</li>
            <li>Approved reservations are valid for <strong>24 hours</strong> for the student to collect</li>
            <li>If no stock is available, the approve button will be disabled</li>
        </ul>
    </div>
</div>

<script>
// Auto-submit on filter change
document.querySelectorAll('#filterForm select').forEach(select => {
    select.addEventListener('change', () => {
        document.getElementById('filterForm').submit();
    });
});
</script>
</body>
</html>