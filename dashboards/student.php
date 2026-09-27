<?php

// Include auth (which may already check login) and DB connection
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../db.php';

// Ensure user is logged in
if (empty($_SESSION['username'])) {
    header('Location: ../login.php');
    exit;
}

// Determine identifier stored in session (try numeric user_id first, then id_number, then username)
$userId     = $_SESSION['user_id'] ?? null;
$idNumber   = $_SESSION['id_number'] ?? null;
$username   = $_SESSION['username'] ?? 'Student';

// Default avatar and uploads directory (paths relative to this file)
$uploadsDir     = __DIR__ . '/../uploads/students/';
$uploadsWebPath = '../uploads/students/';
$defaultAvatar   = '../assets/img/default_avatar.png';

// Account status variables
$account_status = 'active';
$status_message = '';
$suspension_end_date = null;
$suspension_reason = null;
$borrowed_count = 0;
$overdue_count = 0;
$total_borrowed_history = 0;

try {
    // Get the student ID from session or database
    $student_id = null;
    
    if (!empty($userId)) {
        $student_id = $userId;
        $stmt = $conn->prepare("SELECT profile_picture, username, is_suspended, suspension_end_date, suspension_reason, id_number, email FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
    } elseif (!empty($idNumber)) {
        $stmt = $conn->prepare("SELECT id, profile_picture, username, is_suspended, suspension_end_date, suspension_reason, id_number, email FROM users WHERE id_number = ? LIMIT 1");
        $stmt->bind_param("s", $idNumber);
    } else {
        $stmt = $conn->prepare("SELECT id, profile_picture, username, is_suspended, suspension_end_date, suspension_reason, id_number, email FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
    }

    if (isset($stmt)) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (!empty($row['profile_picture'])) {
                $profilePicture = $row['profile_picture'];
            }
            if (!empty($row['username'])) {
                $username = $row['username'];
            }
            if (empty($student_id) && !empty($row['id'])) {
                $student_id = $row['id'];
            }
            if (empty($idNumber) && !empty($row['id_number'])) {
                $idNumber = $row['id_number'];
            }
            
            // Get account status
            $is_suspended = $row['is_suspended'];
            $suspension_end_date = $row['suspension_end_date'];
            $suspension_reason = $row['suspension_reason'];
            
            // Check if suspension has expired
            if ($is_suspended == 1 && $suspension_end_date && new DateTime() > new DateTime($suspension_end_date)) {
                $update = $conn->prepare("UPDATE users SET is_suspended=0, suspension_end_date=NULL, suspension_reason=NULL WHERE id=?");
                $update->bind_param("i", $row['id']);
                $update->execute();
                $update->close();
                $is_suspended = 0;
            }
            
            // Set account status
            if ($is_suspended == 1) {
                $account_status = 'suspended';
                $status_message = "Account Suspended until " . date('M d, Y', strtotime($suspension_end_date));
                if ($suspension_reason) {
                    $status_message .= "<br><small>Reason: " . htmlspecialchars($suspension_reason) . "</small>";
                }
            } else {
                $account_status = 'active';
                $status_message = "Account Active - You can borrow books";
            }
        }
        $stmt->close();
    }
    
    // Get borrowed, overdue, and total history counts if we have a student ID
    if ($student_id) {
        // Get currently borrowed books count
        $borrowStmt = $conn->prepare("SELECT COUNT(*) FROM borrow_records WHERE user_id = ? AND status = 'borrowed'");
        $borrowStmt->bind_param("i", $student_id);
        $borrowStmt->execute();
        $borrowStmt->bind_result($borrowed_count);
        $borrowStmt->fetch();
        $borrowStmt->close();
        
        // Get overdue books count (borrowed and due date passed)
        $overdueStmt = $conn->prepare("SELECT COUNT(*) FROM borrow_records WHERE user_id = ? AND status = 'borrowed' AND due_date < CURDATE()");
        $overdueStmt->bind_param("i", $student_id);
        $overdueStmt->execute();
        $overdueStmt->bind_result($overdue_count);
        $overdueStmt->fetch();
        $overdueStmt->close();
        
        // Get total borrow history count (all borrowed books including returned)
        $historyStmt = $conn->prepare("SELECT COUNT(*) FROM borrow_records WHERE user_id = ?");
        $historyStmt->bind_param("i", $student_id);
        $historyStmt->execute();
        $historyStmt->bind_result($total_borrowed_history);
        $historyStmt->fetch();
        $historyStmt->close();
    }
    
} catch (Exception $e) {
    error_log("Error fetching student data: " . $e->getMessage());
}

// Resolve image source for HTML
$profileSrc = $defaultAvatar;
if (!empty($profilePicture)) {
    $filePath = $uploadsDir . $profilePicture;
    if (is_file($filePath)) {
        $profileSrc = $uploadsWebPath . rawurlencode($profilePicture);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Dashboard - Library System</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{
    --panel-bg: rgba(255,255,255,0.92);
    --muted: #555;
  }
  html,body{height:100%;margin:0;padding:0;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#111;}
  body {
    background-image: url('../assets/images/student.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    display:flex;
    align-items:flex-start;
    justify-content:center;
    padding:40px 20px;
    box-sizing:border-box;
  }
  .panel {
    width:100%;
    max-width:980px;
    background: linear-gradient(180deg, var(--panel-bg), rgba(255,255,255,0.85));
    border-radius:12px;
    box-shadow:0 8px 30px rgba(0,0,0,0.25);
    backdrop-filter: blur(2px);
    padding:28px;
    position:relative;
    overflow:hidden;
  }
  
  .profile-section {
    background: rgba(255,255,255,0.95);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  
  .profile-block {
    display:flex;
    align-items:center;
    gap:15px;
    flex-wrap: wrap;
  }
  
  .profile-block img {
    width:80px;
    height:80px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #3498db;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  }
  
  .profile-info {
    flex: 1;
  }
  
  .profile-info .greet {
    font-weight:700;
    color:#222;
    font-size:18px;
    margin-bottom: 5px;
  }
  
  .profile-info .student-id {
    font-size:13px;
    color:#666;
    margin-bottom: 8px;
  }
  
  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: bold;
    margin-top: 10px;
  }
  
  .status-active {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }
  
  .status-suspended {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }
  
  .status-icon {
    font-size: 16px;
  }
  
  /* Stats Cards - 3 Cards Grid */
  .stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }
  
  .stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 25px 20px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
  }
  
  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
  }
  
  .stat-card.borrowed {
    background: linear-gradient(135deg, #f39c12, #e67e22);
  }
  
  .stat-card.overdue {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
  }
  
  .stat-card.history {
    background: linear-gradient(135deg, #3498db, #2980b9);
  }
  
  .stat-number {
    font-size: 42px;
    font-weight: bold;
    color: white;
    margin-bottom: 10px;
  }
  
  .stat-label {
    font-size: 14px;
    color: white;
    opacity: 0.95;
    font-weight: 500;
  }
  
  .stat-icon {
    font-size: 36px;
    margin-bottom: 10px;
    display: block;
  }
  
  /* Menu Items */
  h2 { margin:25px 0 15px 0; font-size:22px; text-align:center; color:#2c3e50; }
  .welcome { text-align:center; margin-top:8px; color:#333; font-size:16px; }
  
  ul.menu { list-style:none; padding:0; margin:18px auto 0 auto; max-width:520px; display:grid; gap:8px; }
  ul.menu li { 
    background:#f7f7f7; 
    border-radius:8px; 
    padding:12px 14px; 
    box-shadow:0 2px 6px rgba(0,0,0,0.06);
    transition: all 0.2s;
  }
  ul.menu li:hover { 
    background:#e8e8e8;
    transform: translateX(5px);
  }
  ul.menu li a { 
    color:#0b5ed7; 
    text-decoration:none; 
    font-weight:600;
    display: block;
  }
  ul.menu li a:hover {
    text-decoration: underline;
  }
  
  .logout { 
    display:inline-block; 
    margin-top:18px; 
    padding:10px 20px; 
    background:#d9534f; 
    color:#fff; 
    text-decoration:none; 
    border-radius:8px;
    font-weight: bold;
    transition: background 0.2s;
  }
  .logout:hover { background:#c9302c; }
  
  @media (max-width:640px){
    .profile-block { flex-direction: column; text-align: center; }
    .profile-block img { width:70px; height:70px; }
    .panel { padding:18px; }
    ul.menu { max-width:100%; }
    .stats-container { grid-template-columns: 1fr; }
    .stat-number { font-size: 36px; }
  }
</style>
</head>
<body>
  <div class="panel" role="main" aria-labelledby="dashboardTitle">
    
    <!-- Profile Section with Status -->
    <div class="profile-section">
      <div class="profile-block">
        <img src="<?= htmlspecialchars($profileSrc, ENT_QUOTES, 'UTF-8') ?>"
             alt="Profile picture of <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
        <div class="profile-info">
          <div class="greet">Welcome, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>!</div>
          <div class="student-id">📋 Student ID: <?= htmlspecialchars($idNumber ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div>
          
          <!-- Account Status Display -->
          <div class="status-badge <?= $account_status == 'active' ? 'status-active' : 'status-suspended' ?>">
            <span class="status-icon"><?= $account_status == 'active' ? '✅' : '⚠️' ?></span>
            <span><?= $status_message ?></span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Statistics Cards - All Clickable -->
    <div class="stats-container">
      <div class="stat-card borrowed" onclick="window.location.href='../modules/students/borrow_history.php?filter=borrowed'">
        <div class="stat-icon">📚</div>
        <div class="stat-number"><?= $borrowed_count ?></div>
        <div class="stat-label">Currently Borrowed</div>
        <div style="font-size: 11px; margin-top: 8px; opacity: 0.8;">Click to view →</div>
      </div>
      
      <div class="stat-card overdue" onclick="window.location.href='../modules/students/borrow_history.php?filter=overdue'">
        <div class="stat-icon">⚠️</div>
        <div class="stat-number"><?= $overdue_count ?></div>
        <div class="stat-label">Overdue Books</div>
        <div style="font-size: 11px; margin-top: 8px; opacity: 0.8;">Click to view →</div>
      </div>
      
      <div class="stat-card history" onclick="window.location.href='../modules/students/borrow_history.php'">
        <div class="stat-icon">📋</div>
        <div class="stat-number"><?= $total_borrowed_history ?></div>
        <div class="stat-label">Total Borrow History</div>
        <div style="font-size: 11px; margin-top: 8px; opacity: 0.8;">Click to view all →</div>
      </div>
    </div>

    <h2 id="dashboardTitle">📚 Student Dashboard</h2>
    <p class="welcome">Hello <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>, choose an action below.</p>

    <ul class="menu" role="navigation" aria-label="Student actions">
      <li><a href="../modules/students/browse_books.php">📖 Browse Books</a></li>
      
      <li><a href="../modules/students/download_course_file.php">📚 Download Course Materials</a></li>
      <li><a href="../modules/students/student_reservations.php">📝 Request Reservations</a></li>
    </ul>

    <div style="text-align: center;">
      <a class="logout" href="../logout.php">🚪 Logout</a>
    </div>
  </div>
</body>
</html>