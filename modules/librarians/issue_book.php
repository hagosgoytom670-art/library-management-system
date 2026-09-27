<?php
// ==================== INITIALIZATION ====================
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
include("../../db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ==================== SMTP CONFIGURATION ====================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'hagosjeb@gmail.com');
define('SMTP_PASS', 'woujcppxivbczopy');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('FROM_EMAIL', 'hagosjeb@gmail.com');
define('FROM_NAME', 'Library System');

// Get current librarian ID from session
$current_librarian_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

// ==================== FUNCTION TO CHECK SUSPENSION ====================
function isStudentSuspended($user_id, $conn) {
    $stmt = $conn->prepare("SELECT is_suspended, suspension_end_date FROM users WHERE id=? AND role='student'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($is_suspended, $suspension_end_date);
    $stmt->fetch();
    $stmt->close();
    
    if ($is_suspended == 1 && $suspension_end_date && new DateTime() > new DateTime($suspension_end_date)) {
        $update = $conn->prepare("UPDATE users SET is_suspended=0, suspension_end_date=NULL, suspension_reason=NULL WHERE id=?");
        $update->bind_param("i", $user_id);
        $update->execute();
        $update->close();
        return false;
    }
    
    return ($is_suspended == 1);
}

// ==================== FUNCTION TO GET STUDENT BY ID NUMBER OR EMAIL ====================
function getStudentByIdentifier($identifier, $conn) {
    $stmt = $conn->prepare("SELECT id, username, email, profile_picture, is_suspended, suspension_end_date, id_number 
                            FROM users 
                            WHERE (id_number = ? OR email = ?) AND role = 'student' 
                            LIMIT 1");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    return $student;
}

// ==================== FUNCTION TO GET BOOK BY TITLE OR ID ====================
function getBookByIdentifier($identifier, $conn) {
    if (is_numeric($identifier)) {
        $stmt = $conn->prepare("SELECT id, title, available, total_copies FROM books WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $identifier);
    } else {
        $stmt = $conn->prepare("SELECT id, title, available, total_copies FROM books WHERE title LIKE ? LIMIT 1");
        $like = "%$identifier%";
        $stmt->bind_param("s", $like);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();
    return $book;
}

// ==================== FUNCTION TO GET STUDENT BORROWED BOOKS COUNT ====================
function getStudentBorrowedCount($user_id, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM borrow_records WHERE user_id=? AND status='borrowed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

// ==================== CORE NOTIFICATION FUNCTION ====================
function sendNotification($student_id, $student_email, $subject, $body, $conn, $sender_id) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($student_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();

        $stmt = $conn->prepare("INSERT INTO notifications (student_id, sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, ?, NOW())");
        $receiver_id = $student_id;
        $full_message = "<strong>" . $subject . "</strong><br><br>" . $body;
        $stmt->bind_param("iiis", $student_id, $sender_id, $receiver_id, $full_message);
        $stmt->execute();
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

// ==================== EMAIL TEMPLATES ====================
function getEmailTemplate($templateName, $data = []) {
    switch ($templateName) {
        case 'issued':
            return "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .container { padding: 20px; background: #f4f6f8; }
                        .content { background: white; padding: 20px; border-radius: 10px; }
                        .header { color: #2c3e50; border-left: 4px solid #27ae60; padding-left: 15px; }
                        .due-date { color: #e74c3c; font-weight: bold; font-size: 18px; }
                        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 12px; color: #7f8c8d; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='content'>
                            <h2 class='header'>📚 Book Issued Successfully</h2>
                            <p>Dear <strong>{$data['student_name']}</strong>,</p>
                            <p>Your book has been issued successfully.</p>
                            <p><strong>Book Title:</strong> {$data['book_title']}</p>
                            <p><strong>Issue Date:</strong> {$data['issue_date']}</p>
                            <p><strong>Due Date:</strong> <span class='due-date'>{$data['due_date']}</span></p>
                            <p><strong>Remaining Copies:</strong> {$data['remaining_copies']}</p>
                            <p>Please return the book on or before the due date to avoid account suspension.</p>
                            <div class='footer'>
                                <p>Thank you,<br>Library Team</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ";

        case 'returned':
            $suspensionMessage = isset($data['suspended']) && $data['suspended'] 
                ? "<p style='color: #e74c3c;'><strong>⚠️ Your account has been suspended for 30 days due to late return.</strong> You will not be able to borrow books until {$data['activation_date']}.</p>" 
                : "<p style='color: #27ae60;'><strong>✅ Thank you for returning the book on time!</strong></p>";
            
            return "
                <html>
                <body>
                    <div class='container'>
                        <div class='content'>
                            <h2 class='header'>📖 Book Returned</h2>
                            <p>Dear <strong>{$data['student_name']}</strong>,</p>
                            <p>Thank you for returning the book.</p>
                            <p><strong>Book Title:</strong> {$data['book_title']}</p>
                            <p><strong>Return Date:</strong> {$data['return_date']}</p>
                            <p><strong>Due Date:</strong> {$data['due_date']}</p>
                            <p><strong>Days Overdue:</strong> {$data['days_overdue']} days</p>
                            <p><strong>Available Copies Now:</strong> {$data['available_copies']}</p>
                            {$suspensionMessage}
                            <div class='footer'>
                                <p>Regards,<br>Library Team</p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
        default:
            return "<p>Unknown template</p>";
    }
}

// ==================== MAIN PAGE LOGIC ====================

$message = "";
$message_type = "";

// Get filter parameter
$filter_status = isset($_GET['filter']) ? $_GET['filter'] : 'all';

/* -------------------------
   ISSUE BOOK
------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue'])) {
    $student_identifier = trim($_POST['student_identifier']);
    $book_identifier = trim($_POST['book_identifier']);

    if (empty($student_identifier) || empty($book_identifier)) {
        $message = "Please enter both student ID number/email and book title/ID.";
        $message_type = "error";
    } else {
        $student = getStudentByIdentifier($student_identifier, $conn);
        
        if (!$student) {
            $message = "❌ Student not found! Please check the ID number or email address.";
            $message_type = "error";
        } else {
            $user_id = $student['id'];
            $student_name = $student['username'];
            $student_email = $student['email'];
            
            if (isStudentSuspended($user_id, $conn)) {
                $message = "❌ Cannot issue book. Student account is suspended for 30 days!";
                $message_type = "error";
            } else {
                $book = getBookByIdentifier($book_identifier, $conn);
                
                if (!$book) {
                    $message = "❌ Book not found! Please check the book title or ID.";
                    $message_type = "error";
                } else {
                    $book_id = $book['id'];
                    $book_title = $book['title'];
                    $availableCopies = $book['available'];
                    $totalCopies = $book['total_copies'];
                    
                    if ($availableCopies <= 0) {
                        $message = "❌ No available copies of '{$book_title}'. All {$totalCopies} copies are borrowed.";
                        $message_type = "error";
                    } else {
                        $borrow_count = getStudentBorrowedCount($user_id, $conn);
                        
                        if ($borrow_count >= 3) {
                            $message = "❌ Student already has 3 borrowed books. Maximum limit reached!";
                            $message_type = "error";
                        } else {
                            try {
                                $conn->begin_transaction();
                                
                                $due_date = date('Y-m-d', strtotime('+14 days'));
                                $insert = $conn->prepare("INSERT INTO borrow_records (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, CURDATE(), ?, 'borrowed')");
                                $insert->bind_param("iis", $user_id, $book_id, $due_date);
                                
                                if (!$insert->execute()) {
                                    throw new Exception("Insert failed");
                                }
                                $insert->close();
                                
                                $upd = $conn->prepare("UPDATE books SET available = available - 1 WHERE id = ?");
                                $upd->bind_param("i", $book_id);
                                if (!$upd->execute()) {
                                    throw new Exception("Update failed");
                                }
                                $upd->close();
                                
                                $conn->commit();
                                
                                $remaining = $availableCopies - 1;
                                $due_date_formatted = date('M d, Y', strtotime($due_date));
                                $message = "✅ Book issued successfully!<br>Student: {$student_name}<br>Book: {$book_title}<br>Due date: {$due_date_formatted}<br>Remaining copies: {$remaining}";
                                $message_type = "success";
                                
                                if (!empty($student_email)) {
                                    $body = getEmailTemplate('issued', [
                                        'student_name' => $student_name,
                                        'book_title' => $book_title,
                                        'issue_date' => date('M d, Y'),
                                        'due_date' => $due_date_formatted,
                                        'remaining_copies' => $remaining
                                    ]);
                                    sendNotification($user_id, $student_email, "Book Issued Successfully", $body, $conn, $current_librarian_id);
                                }
                                
                                echo "<script>
                                    document.getElementById('student_identifier').value = '';
                                    document.getElementById('book_identifier').value = '';
                                    document.getElementById('studentInfo').style.display = 'none';
                                    document.getElementById('bookInfo').style.display = 'none';
                                </script>";
                                
                            } catch (Exception $e) {
                                $conn->rollback();
                                $message = "An error occurred while issuing the book.";
                                $message_type = "error";
                            }
                        }
                    }
                }
            }
        }
    }
}

/* -------------------------
   RETURN BOOK - FIXED
------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return'])) {
    $borrow_id = isset($_POST['borrow_id']) ? (int)$_POST['borrow_id'] : null;
    
    if (!$borrow_id) {
        $message = "Invalid borrow ID.";
        $message_type = "error";
    } else {
        try {
            $conn->begin_transaction();

            // Get borrow record details
            $stmt = $conn->prepare("SELECT b.book_id, b.user_id, b.due_date, bk.title, bk.available 
                                    FROM borrow_records b 
                                    JOIN books bk ON b.book_id = bk.id
                                    WHERE b.id=? AND b.status='borrowed' LIMIT 1 FOR UPDATE");
            $stmt->bind_param("i", $borrow_id);
            $stmt->execute();
            $stmt->bind_result($book_id, $user_id, $due_date, $book_title, $current_available);
            $stmt->fetch();
            $stmt->close();

            if (empty($book_id)) {
                throw new Exception("Borrow record not found or already returned");
            }
            
            // Calculate if late
            $is_late = false;
            $days_overdue = 0;
            $current_date = new DateTime();
            $due_date_obj = new DateTime($due_date);
            
            if ($current_date > $due_date_obj) {
                $is_late = true;
                $interval = $due_date_obj->diff($current_date);
                $days_overdue = (int)$interval->format('%a');
            }

            // Update borrow record to returned
            $upd = $conn->prepare("UPDATE borrow_records SET status='returned', return_date=CURDATE() WHERE id=?");
            $upd->bind_param("i", $borrow_id);
            if (!$upd->execute()) {
                throw new Exception("Update borrow record failed");
            }
            $upd->close();

            // Increase available copies
            $new_available = $current_available + 1;
            $inc = $conn->prepare("UPDATE books SET available = available + 1 WHERE id = ?");
            $inc->bind_param("i", $book_id);
            if (!$inc->execute()) {
                throw new Exception("Update book availability failed");
            }
            $inc->close();
            
            // Suspend account for 30 days if late
            $suspended = false;
            if ($is_late) {
                $suspension_end = date('Y-m-d', strtotime('+30 days'));
                $reason = "Returned book '{$book_title}' late by {$days_overdue} days. Suspended for 30 days.";
                $suspend = $conn->prepare("UPDATE users SET is_suspended=1, suspension_end_date=?, suspension_reason=? WHERE id=?");
                $suspend->bind_param("ssi", $suspension_end, $reason, $user_id);
                $suspend->execute();
                $suspend->close();
                $suspended = true;
            }
            
            $conn->commit();

            // Set success message
            if ($is_late) {
                $message = "⚠️ Book '{$book_title}' returned LATE by {$days_overdue} days! Student account suspended for 30 days. Available copies: {$new_available}";
                $message_type = "warning";
            } else {
                $message = "✅ Book '{$book_title}' returned on time! Available copies: {$new_available}";
                $message_type = "success";
            }

            // Send notification
            $emailStmt = $conn->prepare("SELECT email, username FROM users WHERE id=? LIMIT 1");
            $emailStmt->bind_param("i", $user_id);
            $emailStmt->execute();
            $emailStmt->bind_result($student_email, $student_name);
            $emailStmt->fetch();
            $emailStmt->close();

            if (!empty($student_email)) {
                $activation_date = date('M d, Y', strtotime('+30 days'));
                $body = getEmailTemplate('returned', [
                    'student_name' => $student_name,
                    'book_title' => $book_title,
                    'return_date' => date('M d, Y'),
                    'due_date' => date('M d, Y', strtotime($due_date)),
                    'days_overdue' => $days_overdue,
                    'suspended' => $suspended,
                    'activation_date' => $activation_date,
                    'available_copies' => $new_available
                ]);
                sendNotification($user_id, $student_email, "Book Returned", $body, $conn, $current_librarian_id);
            }
            
            // Redirect to refresh the page and show updated data
            echo "<script>
                setTimeout(function() {
                    window.location.href = window.location.pathname + '?filter=borrowed';
                }, 2000);
            </script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error returning book: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Auto-activate expired suspensions
$conn->query("UPDATE users SET is_suspended=0, suspension_end_date=NULL, suspension_reason=NULL 
              WHERE is_suspended=1 AND suspension_end_date <= CURDATE()");

// Get all borrow records for display
$sql = "SELECT b.id AS borrow_id, u.username AS student, u.id_number, 
               bk.title AS book, bk.available AS available_copies,
               b.borrow_date, b.due_date, b.return_date, b.status
        FROM borrow_records b
        JOIN users u ON b.user_id = u.id
        JOIN books bk ON b.book_id = bk.id";

if ($filter_status == 'borrowed') {
    $sql .= " WHERE b.status = 'borrowed'";
} elseif ($filter_status == 'returned') {
    $sql .= " WHERE b.status = 'returned'";
} elseif ($filter_status == 'overdue') {
    $sql .= " WHERE b.status = 'borrowed' AND b.due_date < CURDATE()";
}

$sql .= " ORDER BY b.borrow_date DESC";
$result = $conn->query($sql);
$borrows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get statistics
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM borrow_records")->fetch_assoc()['count'];
$stats['borrowed'] = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE status='borrowed'")->fetch_assoc()['count'];
$stats['returned'] = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE status='returned'")->fetch_assoc()['count'];
$stats['overdue'] = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE status='borrowed' AND due_date < CURDATE()")->fetch_assoc()['count'];
$stats['suspended'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student' AND is_suspended=1")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Issue / Return Book - Library System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f8; padding: 20px; }
        .container { max-width: 1400px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 20px; border-left: 4px solid #3498db; padding-left: 15px; }
        h2 { color: #34495e; margin: 25px 0 15px 0; font-size: 1.5em; }
        .top-actions { text-align: right; margin-bottom: 20px; }
        .btn-back, .btn-logout { display: inline-block; padding: 8px 15px; text-decoration: none; border-radius: 5px; margin-left: 10px; font-weight: bold; }
        .btn-back { background: #3498db; color: white; }
        .btn-back:hover { background: #2980b9; }
        .btn-logout { background: #e74c3c; color: white; }
        .btn-logout:hover { background: #c0392b; }
        .msg { margin: 15px 0; padding: 15px; border-radius: 5px; font-weight: bold; }
        .msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .msg.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        
        .two-columns { display: flex; gap: 30px; margin-bottom: 30px; flex-wrap: wrap; }
        .column { flex: 1; min-width: 300px; background: #f8f9fa; padding: 25px; border-radius: 10px; }
        .column h3 { margin-bottom: 20px; color: #2c3e50; border-left: 3px solid #3498db; padding-left: 10px; }
        
        .form-group { margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 8px; color: #2c3e50; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        input:focus { outline: none; border-color: #3498db; box-shadow: 0 0 5px rgba(52,152,219,0.3); }
        button { background: #27ae60; color: white; border: none; padding: 12px 30px; cursor: pointer; border-radius: 5px; font-size: 16px; font-weight: bold; margin-top: 10px; width: 100%; }
        button:hover { background: #229954; }
        button:disabled { background: #95a5a6; cursor: not-allowed; }
        
        .info-display { background: white; padding: 12px; border-radius: 5px; margin-top: 10px; font-size: 13px; border-left: 3px solid #3498db; }
        .help-text { font-size: 12px; color: #6c757d; margin-top: 5px; }
        
        .filter-tabs { margin: 20px 0; display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-tab { padding: 10px 20px; background: #ecf0f1; color: #2c3e50; text-decoration: none; border-radius: 5px; transition: all 0.3s; font-weight: bold; }
        .filter-tab:hover { background: #bdc3c7; }
        .filter-tab.active { background: #3498db; color: white; }
        .filter-tab .count { background: rgba(0,0,0,0.2); padding: 2px 6px; border-radius: 10px; margin-left: 8px; font-size: 12px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-card.total { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-card.borrowed { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.returned { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.overdue { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card.suspended { background: linear-gradient(135deg, #8e44ad, #6c3483); }
        .stat-number { font-size: 32px; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 13px; opacity: 0.9; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .return-btn { background: #e67e22; padding: 6px 15px; font-size: 13px; width: auto; margin: 0; cursor: pointer; border: none; border-radius: 5px; color: white; }
        .return-btn:hover { background: #d35400; }
        .status-borrowed { color: #f39c12; font-weight: bold; }
        .status-returned { color: #27ae60; font-weight: bold; }
        .overdue { color: #e74c3c; font-weight: bold; }
        
        .rules-box { background: #ecf0f1; padding: 15px; border-radius: 5px; margin-top: 20px; margin-bottom: 20px; }
        .rules-box ul { margin-left: 20px; margin-top: 5px; }
        .rules-box li { margin: 5px 0; }
        .small-text { font-size: 11px; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <h1>📚 Library Management System - Issue/Return Books</h1>
    <div class="top-actions">
        <a href="../../dashboards/librarian.php" class="btn-back">← Back to Dashboard</a>
        <a href="../../logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="msg <?php echo $message_type; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card total"><div class="stat-number"><?php echo $stats['total']; ?></div><div class="stat-label">Total Transactions</div></div>
        <div class="stat-card borrowed"><div class="stat-number"><?php echo $stats['borrowed']; ?></div><div class="stat-label">Currently Borrowed</div></div>
        <div class="stat-card returned"><div class="stat-number"><?php echo $stats['returned']; ?></div><div class="stat-label">Returned Books</div></div>
        <div class="stat-card overdue"><div class="stat-number"><?php echo $stats['overdue']; ?></div><div class="stat-label">Overdue Books</div></div>
        <div class="stat-card suspended"><div class="stat-number"><?php echo $stats['suspended']; ?></div><div class="stat-label">Suspended Accounts</div></div>
    </div>

    <!-- Two Column Layout -->
    <div class="two-columns">
        <!-- LEFT COLUMN: Issue Book -->
        <div class="column">
            <h3>📖 Issue New Book</h3>
            <form method="POST" id="issueForm">
                <div class="form-group">
                    <label>Student ID Number or Email:</label>
                    <input type="text" name="student_identifier" id="student_identifier" 
                           placeholder="Enter student ID number or email address" required>
                    <div class="help-text">💡 Enter student's ID number (e.g., 07453/15) or email address</div>
                    <div id="studentInfo" class="info-display" style="display: none;"></div>
                </div>

                <div class="form-group">
                    <label>Book Title or ID:</label>
                    <input type="text" name="book_identifier" id="book_identifier" 
                           placeholder="Enter book title or book ID" required>
                    <div class="help-text">💡 Enter book title (e.g., ccna, ip networking) or book ID</div>
                    <div id="bookInfo" class="info-display" style="display: none;"></div>
                </div>
                
                <button type="submit" name="issue" id="issueBtn">✅ Issue Book</button>
            </form>
        </div>

        <!-- RIGHT COLUMN: Quick Return -->
        <div class="column">
            <h3>↩️ Quick Return Book</h3>
            <div class="form-group">
                <label>Enter Borrow ID to Return:</label>
                <input type="text" id="return_borrow_id" placeholder="Enter Borrow ID number (e.g., 7, 8, 6)">
                <div class="help-text">💡 Find Borrow ID from the Borrow ID column in the table below</div>
                <div id="returnInfo" class="info-display" style="display: none;"></div>
            </div>
            <form method="POST" id="returnForm" style="display: none;">
                <input type="hidden" name="borrow_id" id="return_borrow_id_hidden">
                <button type="submit" name="return" id="returnBtn">↩️ Confirm Return</button>
            </form>
        </div>
    </div>

    <!-- Rules Box -->
    <div class="rules-box">
        <strong>📋 Library Rules & Guidelines:</strong>
        <ul>
            <li>Books are issued for <strong>14 days</strong> from the issue date</li>
            <li>Maximum <strong>3 books</strong> per student at a time</li>
            <li><strong style="color: #e74c3c;">Late return = 30 DAYS account suspension</strong></li>
            <li>Suspended accounts are automatically activated after 30 days</li>
            <li>You can search by student ID number, email, book title, or book ID</li>
        </ul>
    </div>

    <!-- Filter Tabs -->
    <h2>📋 Borrowed Books Records</h2>
    <div class="filter-tabs">
        <a href="?filter=all" class="filter-tab <?= $filter_status == 'all' ? 'active' : '' ?>">All <span class="count"><?= $stats['total'] ?></span></a>
        <a href="?filter=borrowed" class="filter-tab <?= $filter_status == 'borrowed' ? 'active' : '' ?>">Borrowed <span class="count"><?= $stats['borrowed'] ?></span></a>
        <a href="?filter=overdue" class="filter-tab <?= $filter_status == 'overdue' ? 'active' : '' ?>">Overdue <span class="count"><?= $stats['overdue'] ?></span></a>
        <a href="?filter=returned" class="filter-tab <?= $filter_status == 'returned' ? 'active' : '' ?>">Returned <span class="count"><?= $stats['returned'] ?></span></a>
    </div>

    <!-- Borrowed Books Table with FIXED Return Buttons -->
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Borrow ID</th>
                    <th>Student (ID Number)</th>
                    <th>Book</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Days Left/Overdue</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($borrows)): ?>
                    <?php foreach ($borrows as $borrow): 
                        $today = new DateTime();
                        $due = new DateTime($borrow['due_date']);
                        $days_diff = $today->diff($due)->format('%r%a');
                        $is_overdue = ($borrow['status'] == 'borrowed' && $days_diff < 0);
                        $days_display = $is_overdue ? abs($days_diff) . ' days OVERDUE' : $days_diff . ' days left';
                    ?>
                        <tr style="<?= $is_overdue ? 'background-color: #fff3cd;' : '' ?>">
                            <td><strong><?= $borrow['borrow_id']; ?></strong></td>
                            <td>
                                <?= htmlspecialchars($borrow['student']); ?>
                                <br><span class="small-text">ID: <?= htmlspecialchars($borrow['id_number']); ?></span>
                            </td>
                            <td><?= htmlspecialchars($borrow['book']); ?> <span class="small-text">(<?= $borrow['available_copies'] ?> copies left)</span></td>
                            <td><?= date('M d, Y', strtotime($borrow['borrow_date'])); ?></td>
                            <td><?= date('M d, Y', strtotime($borrow['due_date'])); ?></td>
                            <td class="<?= $is_overdue ? 'overdue' : '' ?>"><?= $days_display ?></td>
                            <td class="<?= $borrow['status'] === 'borrowed' ? 'status-borrowed' : 'status-returned' ?>">
                                <?= ucfirst($borrow['status']); ?>
                                <?php if ($is_overdue): ?><span class="overdue"> ⚠️ 30 DAY SUSPENSION</span><?php endif; ?>
                            <td>
                            <td>
                                <?php if ($borrow['status'] === 'borrowed'): ?>
                                    <form method="POST" onsubmit="return confirm('Return \"<?= addslashes($borrow['book']); ?>\"?\n\n⚠️ WARNING: Late return will result in 30 DAYS SUSPENSION!');">
                                        <input type="hidden" name="borrow_id" value="<?= $borrow['borrow_id']; ?>">
                                        <button type="submit" name="return" class="return-btn">↩️ Return</button>
                                    </form>
                                <?php else: ?>
                                    ✅ Returned on <?= date('M d, Y', strtotime($borrow['return_date'])); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center; padding: 40px;">📭 No records found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const studentInput = document.getElementById('student_identifier');
    const bookInput = document.getElementById('book_identifier');
    const studentInfo = document.getElementById('studentInfo');
    const bookInfo = document.getElementById('bookInfo');
    const issueBtn = document.getElementById('issueBtn');
    const returnInput = document.getElementById('return_borrow_id');
    const returnInfo = document.getElementById('returnInfo');
    const returnForm = document.getElementById('returnForm');
    const returnBorrowIdHidden = document.getElementById('return_borrow_id_hidden');
    
    let studentCheckTimeout, bookCheckTimeout;
    
    studentInput.addEventListener('input', function() {
        clearTimeout(studentCheckTimeout);
        const identifier = this.value.trim();
        if (identifier.length >= 2) {
            studentCheckTimeout = setTimeout(() => checkStudent(identifier), 500);
        } else {
            studentInfo.style.display = 'none';
        }
    });
    
    bookInput.addEventListener('input', function() {
        clearTimeout(bookCheckTimeout);
        const identifier = this.value.trim();
        if (identifier.length >= 1) {
            bookCheckTimeout = setTimeout(() => checkBook(identifier), 500);
        } else {
            bookInfo.style.display = 'none';
        }
    });
    
    returnInput.addEventListener('input', function() {
        const borrowId = this.value.trim();
        if (borrowId.length > 0 && !isNaN(borrowId)) {
            checkBorrowRecord(borrowId);
        } else {
            returnInfo.style.display = 'none';
            returnForm.style.display = 'none';
        }
    });
    
    function checkStudent(identifier) {
        fetch(`check_student.php?identifier=${encodeURIComponent(identifier)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    let statusHtml = '';
                    if (data.is_suspended) {
                        statusHtml = `<span style="color:#e74c3c;">🔒 SUSPENDED until ${new Date(data.suspension_end_date).toLocaleDateString()}</span>`;
                        issueBtn.disabled = true;
                    } else if (data.borrowed_count >= 3) {
                        statusHtml = `<span style="color:#e74c3c;">⚠️ MAXIMUM BOOKS REACHED (3/3)</span>`;
                        issueBtn.disabled = true;
                    } else {
                        statusHtml = `<span style="color:#27ae60;">✅ ELIGIBLE - Can borrow ${3 - data.borrowed_count} more book(s)</span>`;
                        issueBtn.disabled = false;
                    }
                    studentInfo.innerHTML = `<strong>✅ Student Found:</strong><br>Name: ${data.username}<br>ID: ${data.id_number}<br>Email: ${data.email}<br>Borrowed: ${data.borrowed_count}/3<br>${statusHtml}`;
                    studentInfo.style.display = 'block';
                } else {
                    studentInfo.innerHTML = `<span style="color:#e74c3c;">❌ Student not found!</span>`;
                    studentInfo.style.display = 'block';
                    issueBtn.disabled = true;
                }
            });
    }
    
    function checkBook(identifier) {
        fetch(`check_book.php?identifier=${encodeURIComponent(identifier)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    let statusHtml = data.available > 0 ? 
                        `<span style="color:#27ae60;">✅ ${data.available} of ${data.total_copies} copies available</span>` : 
                        `<span style="color:#e74c3c;">❌ NO COPIES AVAILABLE</span>`;
                    bookInfo.innerHTML = `<strong>✅ Book Found:</strong><br>Title: ${data.title}<br>Book ID: ${data.id}<br>Available: ${data.available}/${data.total_copies}<br>${statusHtml}`;
                    bookInfo.style.display = 'block';
                } else {
                    bookInfo.innerHTML = `<span style="color:#e74c3c;">❌ Book not found!</span>`;
                    bookInfo.style.display = 'block';
                }
            });
    }
    
    function checkBorrowRecord(borrowId) {
        fetch(`check_borrow.php?borrow_id=${borrowId}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists && data.status === 'borrowed') {
                    let warningHtml = data.is_overdue ? '<span style="color:#e74c3c;">⚠️ WARNING: This book is OVERDUE! Returning will result in 30 DAYS SUSPENSION.</span><br>' : '';
                    returnInfo.innerHTML = `<strong>✅ Borrow Record Found:</strong><br>Student: ${data.student}<br>Book: ${data.book}<br>Due Date: ${data.due_date}<br>${warningHtml}<span style="color:#f39c12;">Click confirm to return this book.</span>`;
                    returnInfo.style.display = 'block';
                    returnBorrowIdHidden.value = borrowId;
                    returnForm.style.display = 'block';
                } else if (data.exists && data.status === 'returned') {
                    returnInfo.innerHTML = `<span style="color:#e74c3c;">❌ This book has already been returned!</span>`;
                    returnInfo.style.display = 'block';
                    returnForm.style.display = 'none';
                } else {
                    returnInfo.innerHTML = `<span style="color:#e74c3c;">❌ Invalid Borrow ID! Please check the Borrow ID column.</span>`;
                    returnInfo.style.display = 'block';
                    returnForm.style.display = 'none';
                }
            });
    }
</script>
</body>
</html>