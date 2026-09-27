<?php
include("../../includes/auth.php");
include("../../db.php");

use Dompdf\Dompdf;   // ✅ must be at the top

if ($_SESSION['role'] !== "librarian") {
    exit("Access denied");
}

$librarian_id = $_SESSION['user_id'];

// --- Export Handling ---
if (isset($_GET['export']) && in_array($_GET['export'], ['csv','pdf'])) {
    $stmt = $conn->prepare("
        SELECT schedule_date, start_time, end_time, task
        FROM librarian_schedule
        WHERE librarian_id = ?
        ORDER BY schedule_date, start_time
    ");
    $stmt->bind_param("i", $librarian_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($_GET['export'] === 'csv') {
       header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="schedule.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date','Start','End','Task']);
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [$r['schedule_date'],$r['start_time'],$r['end_time'],$r['task']]);
        }
        fclose($out);
        exit;
    }

    if ($_GET['export'] === 'pdf') {
        require_once('../../vendor/autoload.php'); // Dompdf via Composer
        $dompdf = new Dompdf();

        $html = '<h2>Librarian Schedule</h2><table border="1" cellspacing="0" cellpadding="5">
                    <tr><th>Date</th><th>Start</th><th>End</th><th>Task</th></tr>';
        while ($r = $res->fetch_assoc()) {
            $html .= '<tr>
                        <td>'.htmlspecialchars($r['schedule_date']).'</td>
                        <td>'.htmlspecialchars($r['start_time']).'</td>
                        <td>'.htmlspecialchars($r['end_time']).'</td>
                        <td>'.htmlspecialchars($r['task']).'</td>
                      </tr>';
        }
        $html .= '</table>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("schedule.pdf", ["Attachment" => true]);
        exit;
    }
}

// --- Normal Page View ---
$stmt = $conn->prepare("
    SELECT schedule_date, start_time, end_time, task
    FROM librarian_schedule
    WHERE librarian_id = ?
    ORDER BY schedule_date, start_time
");
$stmt->bind_param("i", $librarian_id);
$stmt->execute();
$res = $stmt->get_result();

// Helper: assign CSS class based on task keyword
function taskClass($task) {
    $task = strtolower($task);
    if (strpos($task, 'meeting') !== false) return 'task-meeting';
    if (strpos($task, 'admin') !== false) return 'task-admin';
    if (strpos($task, 'event') !== false) return 'task-event';
    if (strpos($task, 'training') !== false) return 'task-training';
    return 'task-other';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Librarian Schedule</title>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2rem;
            background: #f0f4f8;
            color: #333;
        }
        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }
        .export {
            text-align: center;
            margin: 1rem;
        }
        .export a {
            margin: 0 10px;
            padding: 0.5rem 1rem;
            background: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        .export a:hover {
            background: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th {
            padding: 0.75rem;
            background: #27ae60;
            color: #fff;
            text-align: left;
        }
        td {
            padding: 0.75rem;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        tr:hover {
            background: #ffeaa7;
        }
        .no-data {
            text-align: center;
            padding: 1rem;
            font-style: italic;
            color: #e74c3c;
        }
      
        .legend {
            margin: 1rem auto;
            width: 80%;
            background: #fff;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .legend span {
            display: inline-block;
            margin: 0.5rem 1rem;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            color: #333;
        }
        .legend .meeting { background: #d6eaf8; }
        .legend .admin   { background: #f9e79f; }
        .legend .event   { background: #f5b7b1; }
        .legend .training{ background: #d5f5e3; }
        .legend .other   { background: #e8daef; }
        
    </style>
</head>
<body>
    <div class="container">
    <div class="topbar">
        <!-- Back button: prefer history.back(), fallback to dashboard -->
     <a href="../../dashboards/librarian.php" 
     style="padding:8px 15px; background:#555; color:white; text-decoration:none; 
     border-radius:4px; margin-right:8px;">
        ← Back</a>
    <h2>My Daily Schedule</h2>

    <div class="export">
        <a href="?export=csv">Export CSV</a>
        
    </div>

    <!-- Legend -->
   

    <table>
        <tr><th>Date</th><th>Start</th><th>End</th><th>Task</th></tr>
        <?php if ($res->num_rows > 0): ?>
            <?php while ($r = $res->fetch_assoc()): ?>
                <tr class="<?= taskClass($r['task']) ?>">
                    <td><?= htmlspecialchars($r['schedule_date']) ?></td>
                    <td><?= htmlspecialchars($r['start_time']) ?></td>
                    <td><?= htmlspecialchars($r['end_time']) ?></td>
                    <td><?= htmlspecialchars($r['task']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" class="no-data">No scheduled tasks found.</td></tr>
        <?php endif; ?>
    </table>
</body>
</html>
