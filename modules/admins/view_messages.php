<?php
include("../../includes/auth.php"); // restrict to admins if needed
include("../../db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php'; // adjust path if needed

/* -------------------------------
   Handle Reply (PHPMailer + SMTP)
--------------------------------- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_id'])) {
    $reply_id = intval($_POST['reply_id']);
    $reply_message = trim($_POST['reply_message']);
    
    if (empty($reply_message)) {
        $error_msg = "Reply message cannot be empty.";
    } else {
        // Fetch recipient email and name
        $stmt = $conn->prepare("SELECT id, name, email, message FROM contact_messages WHERE id=?");
        $stmt->bind_param("i", $reply_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $recipient = $row['email'];
        $recipient_name = $row['name'];
        $original_message = $row['message'];

        // Configure PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'hagosjeb@gmail.com';       // your Gmail
            $mail->Password   = 'woujcppxivbczopy';         // Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Optional: Disable SSL verification (not recommended for production)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom('hagosjeb@gmail.com', 'Library Management System');
            $mail->addAddress($recipient, $recipient_name);
            $mail->addReplyTo('hagosjeb@gmail.com', 'Library Admin');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Reply from Library Admin - Message #' . $reply_id;
            
            // HTML Email Template
            $email_body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4CAF50; color: white; padding: 10px; text-align: center; }
                    .content { background: #f9f9f9; padding: 20px; border-radius: 5px; }
                    .original-message { background: #fff; border-left: 3px solid #4CAF50; padding: 10px; margin: 15px 0; }
                    .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                    .reply { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Library Management System</h2>
                    </div>
                    <div class='content'>
                        <p>Dear <strong>" . htmlspecialchars($recipient_name) . "</strong>,</p>
                        <div class='reply'>
                            <h3>Our Response:</h3>
                            <p>" . nl2br(htmlspecialchars($reply_message)) . "</p>
                        </div>
                        <div class='original-message'>
                            <h4>Your Original Message:</h4>
                            <p>" . nl2br(htmlspecialchars($original_message)) . "</p>
                        </div>
                        <p>Thank you for contacting us. If you have any further questions, please don't hesitate to reach out.</p>
                        <p>Best regards,<br><strong>Library Administration Team</strong></p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated response from the Library Management System.</p>
                        <p>&copy; " . date('Y') . " Library Management System. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->Body    = $email_body;
            $mail->AltBody = "Dear $recipient_name,\n\nOur Response:\n$reply_message\n\nYour Original Message:\n$original_message\n\nThank you for contacting us.\n\nLibrary Administration Team";

            $mail->send();

            // Get admin info from session or set default
            $admin_email = $_SESSION['email'] ?? 'hagosjeb@gmail.com';
            $admin_name = $_SESSION['name'] ?? 'Library Admin';
            
            // Log reply into admin_replies table
            $log = $conn->prepare("INSERT INTO admin_replies (message_id, admin_email, admin_name, reply_text, replied_at) VALUES (?, ?, ?, ?, NOW())");
            $log->bind_param("isss", $reply_id, $admin_email, $admin_name, $reply_message);
            $log->execute();
            
            // Update contact message status
            $update_status = $conn->prepare("UPDATE contact_messages SET status = 'replied' WHERE id = ?");
            $update_status->bind_param("i", $reply_id);
            $update_status->execute();
            
            $success_msg = "✓ Reply sent successfully to $recipient and logged in the system.";
        } catch (Exception $e) {
            $error_msg = "Mailer Error: " . $mail->ErrorInfo;
        }
    }
}

/* -------------------------------
   Handle Delete
--------------------------------- */
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if message exists
    $check = $conn->prepare("SELECT id FROM contact_messages WHERE id=?");
    $check->bind_param("i", $delete_id);
    $check->execute();
    $check_result = $check->get_result();
    
    if ($check_result->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id=?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $success_msg = "Message ID $delete_id has been deleted successfully.";
        } else {
            $error_msg = "Failed to delete message.";
        }
    } else {
        $error_msg = "Message not found.";
    }
}

/* -------------------------------
   Handle Mark as Read
--------------------------------- */
if (isset($_GET['mark_read_id'])) {
    $read_id = intval($_GET['mark_read_id']);
    $update = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
    $update->bind_param("i", $read_id);
    $update->execute();
    $success_msg = "Message marked as read.";
}

/* -------------------------------
   Fetch Messages with Search/Filter
--------------------------------- */
$where = "";
$params = [];
$types = "";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
    if (!empty($_GET['search_name'])) {
        $where .= " AND (name LIKE ? OR email LIKE ?)";
        $search_term = "%" . $_GET['search_name'] . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "ss";
    }
    if (!empty($_GET['status'])) {
        $where .= " AND status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }
    if (!empty($_GET['date_from'])) {
        $where .= " AND DATE(submitted_at) >= ?";
        $params[] = $_GET['date_from'];
        $types .= "s";
    }
    if (!empty($_GET['date_to'])) {
        $where .= " AND DATE(submitted_at) <= ?";
        $params[] = $_GET['date_to'];
        $types .= "s";
    }
}

$sql = "SELECT id, name, email, message, submitted_at, status 
        FROM contact_messages 
        WHERE 1=1 $where
        ORDER BY 
            CASE WHEN status = 'unread' THEN 0 ELSE 1 END,
            submitted_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch reply counts for each message
$reply_counts = [];
$reply_count_query = "SELECT message_id, COUNT(*) as count FROM admin_replies GROUP BY message_id";
$rc_result = $conn->query($reply_count_query);
while($rc = $rc_result->fetch_assoc()) {
    $reply_counts[$rc['message_id']] = $rc['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages Management</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        
        h3 {
            color: #555;
            margin: 20px 0 10px 0;
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            animation: slideDown 0.5s ease;
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
        
        /* Filter Section */
        .filter-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            font-size: 14px;
        }
        
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-group button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .filter-group button:hover {
            background: #45a049;
        }
        
        .filter-group a {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-group a:hover {
            background: #5a6268;
        }
        
        /* Stats Cards */
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.unread { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.replied { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 14px;
            margin-top: 5px;
            opacity: 0.9;
        }
        
        /* Table Styles */
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th {
            background: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: top;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .unread-row {
            background: #fff3cd;
            font-weight: bold;
        }
        
        .message-preview {
            max-width: 300px;
            word-wrap: break-word;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-unread {
            background: #dc3545;
            color: white;
        }
        
        .status-read {
            background: #28a745;
            color: white;
        }
        
        .status-replied {
            background: #17a2b8;
            color: white;
        }
        
        .reply-count {
            background: #007bff;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        /* Reply Form */
        .reply-form {
            margin-top: 10px;
        }
        
        .reply-form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: monospace;
        }
        
        .reply-form button {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 5px;
        }
        
        .reply-form button:hover {
            background: #0056b3;
        }
        
        .action-link {
            display: inline-block;
            margin: 5px 0;
            padding: 4px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .delete-link {
            background: #dc3545;
            color: white;
        }
        
        .delete-link:hover {
            background: #c82333;
        }
        
        .read-link {
            background: #28a745;
            color: white;
        }
        
        .read-link:hover {
            background: #218838;
        }
        
        .view-replies {
            background: #17a2b8;
            color: white;
            margin-top: 5px;
            text-align: center;
        }
        
        .view-replies:hover {
            background: #138496;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .back-link:hover {
            background: #5a6268;
        }
        
        hr {
            margin: 30px 0 20px;
            border: none;
            border-top: 2px solid #e0e0e0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .stats {
                flex-direction: column;
            }
            
            th, td {
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <a href="../../dashboards/admin.php" class="back-link">← Back to Dashboard</a>
<div class="container">
    <h2>📬 Contact Messages Management</h2>
    
    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <?php
    $total = $conn->query("SELECT COUNT(*) as count FROM contact_messages")->fetch_assoc()['count'];
    $unread = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'")->fetch_assoc()['count'];
    $replied = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'replied'")->fetch_assoc()['count'];
    ?>
    <div class="stats">
        <div class="stat-card total">
            <div class="stat-number"><?= $total ?></div>
            <div class="stat-label">Total Messages</div>
        </div>
        <div class="stat-card unread">
            <div class="stat-number"><?= $unread ?></div>
            <div class="stat-label">Unread</div>
        </div>
        <div class="stat-card replied">
            <div class="stat-number"><?= $replied ?></div>
            <div class="stat-label">Replied</div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label for="search_name">Search by Name/Email</label>
                <input type="text" id="search_name" name="search_name" 
                       value="<?= htmlspecialchars($_GET['search_name'] ?? '') ?>" 
                       placeholder="Enter name or email...">
            </div>
            
            <div class="filter-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    <option value="unread" <?= ($_GET['status'] ?? '') == 'unread' ? 'selected' : '' ?>>Unread</option>
                    <option value="read" <?= ($_GET['status'] ?? '') == 'read' ? 'selected' : '' ?>>Read</option>
                    <option value="replied" <?= ($_GET['status'] ?? '') == 'replied' ? 'selected' : '' ?>>Replied</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date_from">Date From</label>
                <input type="date" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            
            <div class="filter-group">
                <label for="date_to">Date To</label>
                <input type="date" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            
            <div class="filter-group">
                <button type="submit" name="search" value="1">🔍 Search</button>
            </div>
            
            <div class="filter-group">
                <a href="?">🔄 Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Messages Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name/Email</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="<?= $row['status'] == 'unread' ? 'unread-row' : '' ?>">
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                                <small><?= htmlspecialchars($row['email']) ?></small>
                            </td>
                            <td class="message-preview">
                                <?= nl2br(htmlspecialchars(substr($row['message'], 0, 150))) ?>
                                <?php if(strlen($row['message']) > 150): ?>...<?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $row['status'] ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                                <?php if(isset($reply_counts[$row['id']])): ?>
                                    <span class="reply-count"><?= $reply_counts[$row['id']] ?> replies</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['submitted_at']) ?></td>
                            <td style="min-width: 250px;">
                                <a href="?mark_read_id=<?= $row['id'] ?>" 
                                   class="action-link read-link"
                                   onclick="return confirm('Mark this message as read?');">
                                   ✓ Mark Read
                                </a>
                                <a href="?delete_id=<?= $row['id'] ?>" 
                                   class="action-link delete-link"
                                   onclick="return confirm('⚠️ Are you sure you want to delete this message permanently?');">
                                   🗑 Delete
                                </a>
                                <a href="view_replies.php?message_id=<?= $row['id'] ?>" 
                                   class="action-link view-replies"
                                   target="_blank">
                                   📋 View Replies
                                </a>
                                
                                <!-- Reply Form -->
                                <form method="POST" action="" class="reply-form">
                                    <input type="hidden" name="reply_id" value="<?= $row['id'] ?>">
                                    <textarea name="reply_message" rows="3" 
                                              placeholder="Type your reply here... (Email will be sent to user)" 
                                              required></textarea>
                                    <button type="submit">📧 Send Reply</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            📭 No messages found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <hr>
    
    
</div>

<?php
$conn->close();
?>
</body>
</html>


