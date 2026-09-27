<?php
// pay_fine.php
// Complete, self-contained page to list fines for the logged-in student and process payments.

include("../../includes/auth.php");
include("../../db.php");

// Ensure user is logged in and is a student
if (!isset($_SESSION['username'])) {
    header("Location: ../../login.php");
    exit;
}
$user = $_SESSION['username'];

// Helper: send user back with status
function redirect_with_status($status) {
    header("Location: pay_fine.php?status=" . urlencode($status));
    exit;
}

/* ---------- Handle POST (process payment) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fine_id'], $_POST['csrf_token'])) {
    // Basic CSRF check
    if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirect_with_status('error');
    }

    $fine_id = (int)$_POST['fine_id'];
    if ($fine_id <= 0) {
        redirect_with_status('error');
    }

    // Get student id to ensure the fine belongs to this user
    $stmt = $conn->prepare("SELECT u.id FROM users u WHERE u.username = ? AND u.role = 'student'");
    if (!$stmt) {
        redirect_with_status('error');
    }
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();
    $urow = $res->fetch_assoc();
    $stmt->close();
    if (!$urow) {
        redirect_with_status('error');
    }
    $student_id = (int)$urow['id'];

    // Verify the fine exists, belongs to this user, and is unpaid
    $check = $conn->prepare("SELECT paid FROM fines WHERE id = ? AND user_id = ?");
    if (!$check) {
        redirect_with_status('error');
    }
    $check->bind_param("ii", $fine_id, $student_id);
    $check->execute();
    $cres = $check->get_result();
    $crow = $cres->fetch_assoc();
    $check->close();

    if (!$crow) {
        // fine not found or not owned by user
        redirect_with_status('error');
    }
    if ((int)$crow['paid'] === 1) {
        // already paid
        redirect_with_status('success');
    }

    // Mark fine as paid (simple example; in real app record payment details)
    $upd = $conn->prepare("UPDATE fines SET paid = 1 WHERE id = ? AND user_id = ?");
    if (!$upd) {
        redirect_with_status('error');
    }
    $upd->bind_param("ii", $fine_id, $student_id);
    $ok = $upd->execute();
    $upd->close();

    if ($ok) {
        redirect_with_status('success');
    } else {
        redirect_with_status('error');
    }
}

/* ---------- GET: show fines ---------- */
/* Create or refresh CSRF token */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['csrf_token'];

/* Get student ID */
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'student'");
if (!$stmt) {
    die("Database error: " . $conn->error);
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

/* Fetch fines with book and borrow info
   Note: join uses br.id because borrow_records primary key is id */
$sql = "SELECT f.id AS fine_id, f.amount, f.paid, b.title, br.borrow_date
        FROM fines f
        JOIN borrow_records br ON f.borrow_id = br.id
        JOIN books b ON br.book_id = b.id
        WHERE f.user_id = ?
        ORDER BY br.borrow_date DESC, f.id DESC";

$query = $conn->prepare($sql);
if (!$query) {
    die("Database error: " . $conn->error);
}
$query->bind_param("i", $student_id);
$query->execute();
$res = $query->get_result();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Pay Fine</title>
  <link rel="stylesheet" href="../../styles.css">
  <style>
    table { border-collapse: collapse; width: 100%; max-width: 1000px; margin: 12px 0; }
    th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
    .status-badge { padding: 4px 8px; border-radius: 4px; font-weight: 600; display:inline-block; }
    .status-paid { background:#e6ffed; color:#0a7a2a; }
    .status-unpaid { background:#ffecec; color:#a10a0a; }
    .pay-button { background:#007bff; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; }
    .pay-button:hover { background:#0069d9; }
    .message { margin: 8px 0; padding: 8px; border-radius: 4px; }
    .message.success { background: #e6ffed; color: #0a7a2a; }
    .message.error { background: #ffecec; color: #a10a0a; }
  </style>
</head>
<body>
  <h2>Pay Fine</h2>

  <?php if (isset($_GET['status'])): ?>
      <?php if ($_GET['status'] === 'success'): ?>
          <div class="message success">Fine payment successful!</div>
      <?php elseif ($_GET['status'] === 'error'): ?>
          <div class="message error">Error processing payment. Please try again.</div>
      <?php endif; ?>
  <?php endif; ?>

  <?php if ($res && $res->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>Book</th>
          <th>Borrow Date</th>
          <th>Fine Amount</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['borrow_date'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['amount'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php if ((int)$r['paid'] === 1): ?>
              <span class="status-badge status-paid">✔ Paid</span>
            <?php else: ?>
              <span class="status-badge status-unpaid">✖ Unpaid</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ((int)$r['paid'] === 0): ?>
              <form method="POST" action="pay_fine.php" style="display:inline;">
                <input type="hidden" name="fine_id" value="<?= (int)$r['fine_id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="pay-button">💳 Pay Now</button>
              </form>
            <?php else: ?>
              <span class="status-badge status-paid">✔ Paid</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No fines found for your account.</p>
  <?php endif; ?>

  <p><a href="../../dashboards/student.php">Back</a></p>
</body>
</html>
<?php
$query->close();
$conn->close();
?>
