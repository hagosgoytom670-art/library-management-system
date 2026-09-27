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

$query = $conn->prepare("SELECT f.amount, f.paid, br.borrow_date, b.title 
                         FROM fines f 
                         JOIN borrow_records br ON f.borrow_id = br.borrow_id 
                         JOIN books b ON br.book_id = b.id 
                         WHERE f.user_id=?");
$query->bind_param("i", $student_id);
$query->execute();
$res = $query->get_result();
?>

<h2>Fine History</h2>
<?php if ($res->num_rows > 0): ?>
<table border="1">
<tr><th>Book</th><th>Borrow Date</th><th>Fine Amount</th><th>Paid</th></tr>
<?php while($r = $res->fetch_assoc()): ?>
<tr>
  <td><?= htmlspecialchars($r['title']) ?></td>
  <td><?= htmlspecialchars($r['borrow_date']) ?></td>
  <td><?= htmlspecialchars($r['amount']) ?></td>
  <td><?= $r['paid'] ? "Yes" : "No" ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<p>No fines found for your account.</p>
<?php endif; ?>

<a href="../../dashboards/student.php">Back</a>

