<?php
// Absolute path for includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/lmsPro/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lmsPro/db.php';

// Ensure user is logged in
if (empty($_SESSION['username'])) {
    header('Location: /lmsPro/login.php');
    exit;
}

// Get student ID from session
$student_id = $_SESSION['user_id'] ?? null;

// If user_id not in session, get it from id_number or username
if (!$student_id) {
    $idNumber = $_SESSION['id_number'] ?? null;
    $username = $_SESSION['username'] ?? null;
    
    if ($idNumber) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id_number = ? LIMIT 1");
        $stmt->bind_param("s", $idNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $student_id = $row['id'];
        }
        $stmt->close();
    } elseif ($username) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $student_id = $row['id'];
        }
        $stmt->close();
    }
}

if (!$student_id) {
    die("Error: Student not found. Please contact administrator.");
}

// Get filter from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$sql = "SELECT b.id, bk.title AS book_title, b.borrow_date, b.due_date, b.return_date, b.status 
        FROM borrow_records b 
        JOIN books bk ON b.book_id = bk.id 
        WHERE b.user_id = ?";

if ($filter == 'borrowed') {
    $sql .= " AND b.status = 'borrowed'";
} elseif ($filter == 'overdue') {
    $sql .= " AND b.status = 'borrowed' AND b.due_date < CURDATE()";
} elseif ($filter == 'returned') {
    $sql .= " AND b.status = 'returned'";
}

$sql .= " ORDER BY b.borrow_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$borrow_records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned,
    SUM(CASE WHEN status = 'borrowed' AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue
    FROM borrow_records WHERE user_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $student_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$student_name = $_SESSION['username'] ?? 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Borrow History - Library System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #f4f6f8; 
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            border-left: 4px solid #3498db;
            padding-left: 15px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 20px;
            padding-left: 19px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-card.all { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-card.borrowed { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.returned { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.overdue { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-number { font-size: 28px; font-weight: bold; }
        .stat-label { font-size: 12px; opacity: 0.9; margin-top: 5px; }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        .filter-tab {
            padding: 8px 20px;
            background: #ecf0f1;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s;
        }
        .filter-tab:hover {
            background: #bdc3c7;
        }
        .filter-tab.active {
            background: #3498db;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #2c3e50;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .status-borrowed {
            color: #f39c12;
            font-weight: bold;
        }
        .status-returned {
            color: #27ae60;
            font-weight: bold;
        }
        .overdue {
            color: #e74c3c;
            font-weight: bold;
        }
        .no-records {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            th, td {
                padding: 8px;
                font-size: 12px;
            }
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="/lmsPro/dashboards/student.php" class="back-link">← Back to Dashboard</a>
    <h1>📋 My Borrow History</h1>
    <p class="subtitle">View all books you have borrowed - <?= htmlspecialchars($student_name) ?></p>
    
    <div class="stats-container">
        <div class="stat-card all" onclick="window.location.href='?filter=all'">
            <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
            <div class="stat-label">Total Transactions</div>
        </div>
        <div class="stat-card borrowed" onclick="window.location.href='?filter=borrowed'">
            <div class="stat-number"><?= $stats['borrowed'] ?? 0 ?></div>
            <div class="stat-label">Currently Borrowed</div>
        </div>
        <div class="stat-card returned" onclick="window.location.href='?filter=returned'">
            <div class="stat-number"><?= $stats['returned'] ?? 0 ?></div>
            <div class="stat-label">Returned</div>
        </div>
        <div class="stat-card overdue" onclick="window.location.href='?filter=overdue'">
            <div class="stat-number"><?= $stats['overdue'] ?? 0 ?></div>
            <div class="stat-label">Overdue</div>
        </div>
    </div>
    
    <div class="filter-tabs">
        <a href="?filter=all" class="filter-tab <?= $filter == 'all' ? 'active' : '' ?>">All Records</a>
        <a href="?filter=borrowed" class="filter-tab <?= $filter == 'borrowed' ? 'active' : '' ?>">Currently Borrowed</a>
        <a href="?filter=returned" class="filter-tab <?= $filter == 'returned' ? 'active' : '' ?>">Returned</a>
        <a href="?filter=overdue" class="filter-tab <?= $filter == 'overdue' ? 'active' : '' ?>">Overdue</a>
    </div>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Book Title</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($borrow_records) && count($borrow_records) > 0): ?>
                    <?php foreach ($borrow_records as $index => $record): 
                        $is_overdue = ($record['status'] == 'borrowed' && strtotime($record['due_date']) < time());
                    ?>
                        <tr style="<?= $is_overdue ? 'background-color: #fff3cd;' : '' ?>">
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($record['book_title']) ?></td>
                            <td><?= date('M d, Y', strtotime($record['borrow_date'])) ?></td>
                            <td class="<?= $is_overdue ? 'overdue' : '' ?>">
                                <?= date('M d, Y', strtotime($record['due_date'])) ?>
                                <?php if ($is_overdue): ?>
                                    <span class="overdue"> ⚠️</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $record['return_date'] ? date('M d, Y', strtotime($record['return_date'])) : '-' ?>
                            </td>
                            <td class="<?= $record['status'] == 'borrowed' ? 'status-borrowed' : 'status-returned' ?>">
                                <?= ucfirst($record['status']) ?>
                                <?php if ($is_overdue): ?>
                                    <span class="overdue"> (OVERDUE!)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-records">
                            📭 No borrow records found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>