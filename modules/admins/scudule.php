<?php
// FULLY FIXED - Librarian Schedule Management with Working Emojis
// ✅ Overlap prevention
// ✅ Email notification with working emojis (UTF-8)
// ✅ Role-based (admin/librarian)
// ✅ Edit / Delete
// ✅ Ethiopian Time (EAT - East Africa Time)

include("../../includes/auth.php");
include("../../db.php");

// Include PHPMailer
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
include("../../db.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set timezone to Ethiopian time
date_default_timezone_set('Africa/Addis_Ababa');

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? '';
if (!$user_id) exit("Session error");

$message = "";

// ================= PHPMailer FUNCTION WITH WORKING EMOJIS =================
function sendEmailNotification($to, $to_name, $date, $start_time, $end_time, $task, $action) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hagosjeb@gmail.com';
        $mail->Password   = 'woujcppxivbczopy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // CRITICAL: Force UTF-8 encoding for emojis to work properly
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'quoted-printable';
        
        // Sender info
        $mail->setFrom('hagosjeb@gmail.com', 'RU Digital Central Library');
        $mail->addReplyTo('library@ru.edu.et', 'Library Admin');
        $mail->addAddress($to, $to_name);
        
        // Format times
        $formattedStart = date('h:i A', strtotime($start_time));
        $formattedEnd = date('h:i A', strtotime($end_time));
        $formattedDate = date('l, F j, Y', strtotime($date));
        
        // Email subject
        $emailSubject = ($action == 'created') ? "📚 New Schedule Assigned - " . $formattedDate : "✏️ Schedule Updated - " . $formattedDate;
        $mail->Subject = $emailSubject;
        
        // Email body with working emojis
        $emailBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #0a4b8c, #003d6b); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
                .schedule-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #27ae60; border-radius: 5px; }
                .detail-row { margin: 10px 0; }
                .detail-label { font-weight: bold; color: #0a4b8c; }
                .footer { text-align: center; padding: 15px; font-size: 12px; color: #666; }
                .btn { display: inline-block; padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                .time-badge { background: #e8f0fe; padding: 3px 8px; border-radius: 5px; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>📚 RU Digital Central Library</h2>
                    <p>Schedule Notification</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($to_name) . "</strong>,</p>
                    
                    <p>" . ($action == 'created' ? "A new schedule has been assigned to you:" : "Your schedule has been updated:") . "</p>
                    
                    <div class='schedule-details'>
                        <div class='detail-row'>
                            <span class='detail-label'>📅 Date:</span> 
                            <span>" . $formattedDate . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>🕐 Time (Ethiopian Time):</span> 
                            <span class='time-badge'>" . $formattedStart . " - " . $formattedEnd . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>📝 Task:</span> 
                            <span>" . nl2br(htmlspecialchars($task)) . "</span>
                        </div>
                    </div>
                    
                    <p>Please log in to your dashboard to view all your schedules.</p>
    
                    
                    <p style='margin-top: 20px; font-size: 13px; color: #666;'>
                        <em>This is an automated notification. Please do not reply to this email.</em>
                    </p>
                </div>
                <div class='footer'>
                    © " . date('Y') . " RU Digital Central Library | Ethiopian Time: " . date('h:i A') . "<br>
                    Main Campus Library Building
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->isHTML(true);
        $mail->Body = $emailBody;
        $mail->AltBody = "RU Library Schedule\n\nDate: $formattedDate\nTime: $formattedStart - $formattedEnd\nTask: $task\n\nPlease log in to your dashboard.";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// ================= DELETE SCHEDULE =================
if (isset($_GET['delete']) && $role === 'admin') {
    $id = intval($_GET['delete']);
    
    // Get schedule details before deleting
    $scheduleInfo = $conn->query("SELECT librarian_id, schedule_date, start_time, end_time, task FROM librarian_schedule WHERE id=$id")->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM librarian_schedule WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Send deletion notification
        if ($scheduleInfo && $scheduleInfo['librarian_id']) {
            $librarian = $conn->query("SELECT email, username FROM users WHERE id={$scheduleInfo['librarian_id']}")->fetch_assoc();
            if ($librarian && $librarian['email']) {
                $formattedDateDel = date('l, F j, Y', strtotime($scheduleInfo['schedule_date']));
                
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'hagosjeb@gmail.com';
                    $mail->Password = 'woujcppxivbczopy';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'quoted-printable';
                    
                    $mail->setFrom('hagosjeb@gmail.com', 'RU Digital Central Library');
                    $mail->addAddress($librarian['email'], $librarian['username']);
                    $mail->Subject = "🗑️ Schedule Cancelled - " . $formattedDateDel;
                    
                    $deleteBody = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
                        <style>
                            body { font-family: 'Segoe UI', Arial, sans-serif; }
                            .container { max-width: 600px; margin: auto; }
                            .header { background: linear-gradient(135deg, #0a4b8c, #003d6b); color: white; padding: 20px; text-align: center; }
                            .content { padding: 20px; background: #f8f9fa; }
                            .cancelled-box { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #e74c3c; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>📚 RU Digital Central Library</h2>
                                <p>Schedule Cancellation Notice</p>
                            </div>
                            <div class='content'>
                                <p>Dear <strong>{$librarian['username']}</strong>,</p>
                                <p>🗑️ A schedule has been cancelled/removed from your calendar:</p>
                                <div class='cancelled-box'>
                                    <p><strong>📅 Date:</strong> $formattedDateDel</p>
                                    <p><strong>🕐 Time:</strong> " . date('h:i A', strtotime($scheduleInfo['start_time'])) . " - " . date('h:i A', strtotime($scheduleInfo['end_time'])) . "</p>
                                    <p><strong>📝 Task:</strong> " . htmlspecialchars($scheduleInfo['task']) . "</p>
                                </div>
                                <p>Please check your dashboard for updated schedule information.</p>
                                <p>Thank you.</p>
                            </div>
                            <div class='footer' style='text-align: center; padding: 15px; font-size: 12px; color: #666;'>
                                © " . date('Y') . " RU Digital Central Library
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    $mail->isHTML(true);
                    $mail->Body = $deleteBody;
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Delete email failed: " . $mail->ErrorInfo);
                }
            }
        }
        $message = "✅ Schedule deleted successfully (Email notification sent)";
    } else {
        $message = "❌ Error deleting schedule";
    }
}

// ================= EDIT LOAD =================
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT id AS schedule_id, librarian_id, schedule_date, start_time, end_time, task FROM librarian_schedule WHERE id=$id");
    $edit = $res->fetch_assoc();
}

// ================= SAVE SCHEDULE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lid = intval($_POST['librarian_id']);
    $date = $_POST['schedule_date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $task = trim($_POST['task']);
    $id = intval($_POST['schedule_id'] ?? 0);
    $action = $id ? 'updated' : 'created';

    if ($end <= $start) {
        $message = "❌ End time must be after start time";
    } else {

        // OVERLAP CHECK
        $sql = "SELECT id FROM librarian_schedule WHERE librarian_id=? AND schedule_date=? AND NOT (end_time<=? OR start_time>=?)";
        if ($id) $sql .= " AND id!=?";

        $stmt = $conn->prepare($sql);
        if ($id)
            $stmt->bind_param("isssi", $lid, $date, $start, $end, $id);
        else
            $stmt->bind_param("isss", $lid, $date, $start, $end);

        $stmt->execute();
        $r = $stmt->get_result();

        if ($r->num_rows > 0) {
            $message = "⚠️ Time conflict: This librarian already has a schedule at this time";
        } else {

            if ($id) {
                $stmt = $conn->prepare("UPDATE librarian_schedule SET librarian_id=?, schedule_date=?, start_time=?, end_time=?, task=? WHERE id=?");
                $stmt->bind_param("issssi", $lid, $date, $start, $end, $task, $id);
                $stmt->execute();
                $message = "✅ Schedule updated successfully";
            } else {
                $stmt = $conn->prepare("INSERT INTO librarian_schedule(librarian_id, schedule_date, start_time, end_time, task, created_by) VALUES(?,?,?,?,?,?)");
                $stmt->bind_param("issssi", $lid, $date, $start, $end, $task, $user_id);
                $stmt->execute();
                $message = "✅ Schedule created successfully";
            }

            // SEND EMAIL TO LIBRARIAN
            $librarian = $conn->query("SELECT email, username FROM users WHERE id=$lid")->fetch_assoc();
            
            if ($librarian && !empty($librarian['email'])) {
                $emailSent = sendEmailNotification(
                    $librarian['email'],
                    $librarian['username'],
                    $date,
                    $start,
                    $end,
                    $task,
                    $action
                );
                
                if ($emailSent) {
                    $message .= " 📧 Email notification sent to " . htmlspecialchars($librarian['username']);
                } else {
                    $message .= " ⚠️ Could not send email (SMTP error)";
                }
            } else {
                $message .= " ⚠️ No email address found for this librarian";
            }
        }
    }
}

// ================= QUERY =================
if ($role === 'librarian') {
    $query = "SELECT id AS schedule_id, schedule_date, start_time, end_time, task FROM librarian_schedule WHERE librarian_id=$user_id ORDER BY schedule_date DESC, start_time ASC";
} else {
    $query = "SELECT ls.id AS schedule_id, ls.schedule_date, ls.start_time, ls.end_time, ls.task, u.username, u.email
              FROM librarian_schedule ls 
              JOIN users u ON ls.librarian_id=u.id 
              ORDER BY ls.schedule_date DESC, ls.start_time ASC";
}

$data = $conn->query($query);

// Get upcoming schedules
$upcoming_query = "SELECT COUNT(*) as upcoming FROM librarian_schedule 
                   WHERE schedule_date >= CURDATE() 
                   AND schedule_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$upcoming_result = $conn->query($upcoming_query);
$upcoming_count = $upcoming_result->fetch_assoc()['upcoming'];

$current_ethiopian_time = date('h:i A, l, F j, Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Schedule Management - RU Library</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #0a4b8c, #003d6b);
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 25px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 { font-size: 1.5rem; font-weight: 600; }
        .header h1 small { font-size: 0.8rem; opacity: 0.8; }
        
        .ethiopia-time {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: monospace;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .back-btn:hover { background: rgba(255, 255, 255, 0.35); }
        
        .message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .message.error { background: #fef2f2; color: #c0392b; border-left: 4px solid #e74c3c; }
        .message.warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number { font-size: 2rem; font-weight: bold; color: #0a4b8c; }
        .stat-label { color: #666; font-size: 0.85rem; margin-top: 5px; }
        
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .form-card h3 {
            margin-bottom: 15px;
            color: #1a2c3e;
            border-bottom: 2px solid #0a4b8c;
            padding-bottom: 8px;
            display: inline-block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .form-group { margin-bottom: 5px; }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            font-size: 0.85rem;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1.5px solid #e0e4e8;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #0a4b8c;
            box-shadow: 0 0 0 3px rgba(10, 75, 140, 0.1);
        }
        
        textarea { resize: vertical; min-height: 80px; }
        
        .btn-submit {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .btn-submit:hover { background: #219a52; transform: translateY(-1px); }
        
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e4e8; }
        th { background: #f8f9fa; font-weight: 600; color: #1a2c3e; }
        tr:hover { background: #f8f9fa; }
        
        .action-links a {
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
        }
        
        .edit-link { background: #3498db; color: white; }
        .delete-link { background: #e74c3c; color: white; }
        .time-badge { background: #e8f0fe; padding: 3px 8px; border-radius: 5px; font-family: monospace; font-size: 12px; }
        .empty-state { text-align: center; padding: 40px; color: #7f8c8d; }
        .email-notice { background: #e8f0fe; padding: 8px 12px; border-radius: 6px; font-size: 12px; color: #0a4b8c; margin-top: 10px; }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            th, td { font-size: 12px; padding: 8px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <div>
            <h1>📅 Librarian Schedule Management</h1>
            <small>Manage librarian shifts with email notifications</small>
        </div>
        <div class="ethiopia-time">
            🕐 Ethiopian Time: <?php echo $current_ethiopian_time; ?>
        </div>
        <a href="../../dashboards/<?php echo $role; ?>.php" class="back-btn">← Back to Dashboard</a>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $data->num_rows; ?></div>
            <div class="stat-label">Total Schedules</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $upcoming_count; ?></div>
            <div class="stat-label">Upcoming (7 days)</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">📧</div>
            <div class="stat-label">Gmail SMTP Active</div>
        </div>
    </div>

    <!-- Message Display -->
    <?php if (!empty($message)): ?>
        <div class="message <?php 
            if (strpos($message, '✅') !== false) echo 'success';
            elseif (strpos($message, '⚠️') !== false) echo 'warning';
            elseif (strpos($message, '❌') !== false) echo 'error';
            else echo 'success'; 
        ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Form (Admin Only) -->
    <?php if ($role === 'admin'): ?>
    <div class="form-card">
        <h3><?php echo $edit ? '✏️ Edit Schedule' : '➕ Add New Schedule'; ?></h3>
        <form method="POST">
            <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($edit['schedule_id'] ?? ''); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>📚 Select Librarian</label>
                    <select name="librarian_id" required>
                        <option value="">-- Select Librarian --</option>
                        <?php
                        $res = $conn->query("SELECT id, username, email FROM users WHERE role='librarian' ORDER BY username");
                        while ($r = $res->fetch_assoc()):
                            $sel = ($edit && $edit['librarian_id'] == $r['id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo $sel; ?>>
                                <?php echo htmlspecialchars($r['username']); ?> (<?php echo htmlspecialchars($r['email']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>📅 Date</label>
                    <input type="date" name="schedule_date" value="<?php echo htmlspecialchars($edit['schedule_date'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>🕐 Start Time</label>
                    <input type="time" name="start_time" value="<?php echo htmlspecialchars($edit['start_time'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>🕐 End Time</label>
                    <input type="time" name="end_time" value="<?php echo htmlspecialchars($edit['end_time'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>📝 Task Description</label>
                <textarea name="task" placeholder="Describe the librarian's duties for this shift..."><?php echo htmlspecialchars($edit['task'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit"><?php echo $edit ? '💾 Update Schedule' : '➕ Create Schedule'; ?></button>
            
            <div class="email-notice">
                📧 An email notification will be sent to the librarian's email address automatically.
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Schedules Table -->
    <div class="table-container">
        <h3>📋 All Schedules</h3>
        
        <?php if ($data->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <?php if ($role === 'admin'): ?>
                            <th>Librarian</th>
                            <th>Email</th>
                        <?php endif; ?>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Task</th>
                        <?php if ($role === 'admin'): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $data->fetch_assoc()): ?>
                        <tr>
                            <?php if ($role === 'admin'): ?>
                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                <td><small><?php echo htmlspecialchars($row['email']); ?></small></td>
                            <?php endif; ?>
                            <td><?php echo date('D, M j, Y', strtotime($row['schedule_date'])); ?></td>
                            <td><span class="time-badge">🕐 <?php echo date('h:i A', strtotime($row['start_time'])); ?></span></td>
                            <td><span class="time-badge">🕐 <?php echo date('h:i A', strtotime($row['end_time'])); ?></span></td>
                            <td><?php echo nl2br(htmlspecialchars($row['task'])); ?></td>
                            <?php if ($role === 'admin'): ?>
                                <td class="action-links">
                                    <a href="?edit=<?php echo $row['schedule_id']; ?>" class="edit-link">✏️ Edit</a>
                                    <a href="?delete=<?php echo $row['schedule_id']; ?>" class="delete-link" onclick="return confirm('Delete this schedule? The librarian will receive an email notification.')">🗑️ Delete</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>📭 No schedules found.</p>
                <?php if ($role === 'admin'): ?>
                    <p>Use the form above to create your first schedule.</p>
                <?php else: ?>
                    <p>No schedules have been assigned to you yet. Please contact the administrator.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Real-time Ethiopian Time Clock -->
<script>
    function updateEthiopianTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        const dateString = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        const timeElement = document.querySelector('.ethiopia-time');
        if (timeElement) {
            timeElement.innerHTML = `🕐 Ethiopian Time: ${timeString}, ${dateString}`;
        }
    }
    
    setInterval(updateEthiopianTime, 1000);
    updateEthiopianTime();
    
    // Auto-hide message after 5 seconds
    setTimeout(function() {
        const message = document.querySelector('.message');
        if (message) {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => { if(message) message.style.display = 'none'; }, 500);
        }
    }, 5000);
</script>

</body>
</html>