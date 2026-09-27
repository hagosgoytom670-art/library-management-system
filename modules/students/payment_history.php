<?php
include("../../includes/auth.php");
include("../../db.php");

$user = isset($_SESSION['username']) ? $_SESSION['username'] : null;
if (!$user) {
    header("Location: ../../login.php");
    exit;
}

/* Get student ID */
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'student'");
if (!$stmt) {
    die("DB error: " . $conn->error);
}
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
if (!$row) {
    die("Student account not found.");
}
$student_id = (int)$row['id'];

/* Inspect payments table to find a date column */
$colsRes = $conn->query("SHOW COLUMNS FROM payments");
if ($colsRes === false) {
    die("Database error: " . $conn->error);
}
$cols = [];
while ($c = $colsRes->fetch_assoc()) {
    $cols[] = $c['Field'];
}

/* Choose a date column that exists */
$dateCol = null;
foreach (['payment_date', 'paid_at', 'created_at', 'date', 'timestamp'] as $candidate) {
    if (in_array($candidate, $cols, true)) {
        $dateCol = $candidate;
        break;
    }
}

/* Build SELECT list depending on whether a date column exists */
if ($dateCol) {
    $dateSelect = "p.`$dateCol` AS payment_date";
    $orderBy = "p.`$dateCol` DESC";
} else {
    $dateSelect = "NULL AS payment_date";
    $orderBy = "p.id DESC";
}

/* Prepare and execute the main query
   Note: join uses br.id because borrow_records primary key is usually id */
$sql = "SELECT $dateSelect, p.amount, b.title
        FROM payments p
        JOIN fines f ON p.fine_id = f.id
        JOIN borrow_records br ON f.borrow_id = br.id
        JOIN books b ON br.book_id = b.id
        WHERE p.user_id = ?
        ORDER BY $orderBy";

$query = $conn->prepare($sql);
if (!$query) {
    die("Prepare failed: " . $conn->error);
}
$query->bind_param("i", $student_id);
$query->execute();
$res = $query->get_result();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Payment History</title>
  <link rel="stylesheet" href="../../styles.css">
</head>
<body>
  <h2>Payment History</h2>

  <?php if ($res && $res->num_rows > 0): ?>
    <table>
      <tr><th>Book</th><th>Amount</th><th>Date</th></tr>
      <?php while ($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['amount'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['payment_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  <?php else: ?>
    <p>No payments recorded yet.</p>
  <?php endif; ?>

  <?php if (!$dateCol): ?>
    <p style="color:orange;">Note: No payment date column found in the payments table. Dates are unavailable.</p>
  <?php endif; ?>

  <p><a href="../../dashboards/student.php">Back</a></p>
</body>
</html>
<?php
$query->close();
$conn->close();
?>
