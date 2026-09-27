<?php
include("../../includes/auth.php");
include("../../db.php");

$user = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username=? AND role='student'");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$student_id = $row['id'];

$filter = $_POST['filter'] ?? 'all';

$sql = "SELECT b.title, br.borrow_date, br.due_date, br.return_date, br.status, br.fine
        FROM borrow_records br
        JOIN books b ON br.book_id = b.id
        WHERE br.student_id=?";

if ($filter === 'not_returned') {
    $sql .= " AND br.return_date IS NULL";
} elseif ($filter === 'returned') {
    $sql .= " AND br.return_date IS NOT NULL";
}

$sql .= " ORDER BY br.borrow_date DESC";

$query = $conn->prepare($sql);
$query->bind_param("i", $student_id);
$query->execute();
$res = $query->get_result();

// Counters
$total = 0;
$returned = 0;
$notReturned = 0;

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=borrow_history.csv');

$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, ['Book Title', 'Borrow Date', 'Due Date', 'Return Date', 'Status', 'Fine']);

while($r = $res->fetch_assoc()) {
    $total++;
    if ($r['return_date']) {
        $returned++;
    } else {
        $notReturned++;
    }
    fputcsv($output, [
        $r['title'],
        $r['borrow_date'],
        $r['due_date'] ?: '-',
        $r['return_date'] ?: 'Not Returned',
        $r['status'],
        $r['fine']
    ]);
}

// Add summary row at bottom
fputcsv($output, []); // blank line
fputcsv($output, ["Summary", "Total Borrowed: $total", "Returned: $returned", "Not Returned: $notReturned"]);

fclose($output);
exit();



