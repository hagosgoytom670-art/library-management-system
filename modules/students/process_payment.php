<?php
include("../../includes/auth.php");
include("../../db.php");

$user = $_SESSION['username'];

// Get student ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username=? AND role='student'");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$student_id = $row['id'];

// Fetch fines
$sql = "SELECT b.title, br.borrow_date, br.due_date, br.return_date, br.fine
        FROM borrow_records br
        JOIN books b ON br.book_id = b.id
        WHERE br.student_id=? AND br.fine > 0
        ORDER BY br.borrow_date DESC";

$query = $conn->prepare($sql);
$query->bind_param("i", $student_id);
$query->execute();
$res = $query->get_result();

// Counters
$totalFine = 0;
?>

<link rel="stylesheet" href="../../assets/css/fine.css">

<h2>Fine History</h2>

<table>
<tr>
  <th>Book</th>
  <th>Borrow Date</th>
  <th>Due Date</th>
  <th>Return Date</th>
  <th>Fine</th>
</tr>

<?php if ($res->num_rows > 0): ?>
  <?php while($r = $res->fetch_assoc()): ?>
  <?php $totalFine += $r['fine']; ?>
  <tr>
    <td><?= htmlspecialchars($r['title']) ?></td>
    <td><?= htmlspecialchars($r['borrow_date']) ?></td>
    <td><?= htmlspecialchars($r['due_date']) ?></td>
    <td><?= $r['return_date'] ? htmlspecialchars($r['return_date']) : "Not Returned" ?></td>
    <td><?= htmlspecialchars($r['fine']) ?></td>
  </tr>
  <?php endwhile; ?>
<?php else: ?>
  <tr><td colspan="5">No fines found for your account.</td></tr>
<?php endif; ?>

<!-- Summary row -->
<tr style="font-weight:bold; background-color:#f1f1f1;">
  <td colspan="5">Total Fine: <?= $totalFine ?></td>
</tr>
</table>

<a href="../../dashboards/student.php">Back</a>
