<?php
include("../../includes/auth.php");
include("../../db.php");

if ($_SESSION['role'] !== "librarian") {
    die("Access denied");
}

// Fetch config values
$configRes = $conn->query("SELECT setting, value FROM config");
$config = [];
while ($row = $configRes->fetch_assoc()) {
    $config[$row['setting']] = $row['value'];
}
$maxDays = (int)$config['max_borrow_days'];
$finePerDay = (int)$config['fine_per_day'];

// Handle return submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $borrow_id = $_POST['borrow_id'];

    // Get borrow record
    $borrowQuery = $conn->prepare("SELECT book_id, user_id, borrow_date FROM borrow_records WHERE id=? AND status='borrowed'");
    $borrowQuery->bind_param("i", $borrow_id);
    $borrowQuery->execute();
    $borrowRes = $borrowQuery->get_result();

    if ($borrowRes->num_rows === 1) {
        $borrowRow = $borrowRes->fetch_assoc();
        $book_id = $borrowRow['book_id'];
        $student_id = $borrowRow['user_id'];
        $borrow_date = new DateTime($borrowRow['borrow_date']);
        $return_date = new DateTime(); // today

        // Calculate days borrowed
        $interval = $borrow_date->diff($return_date);
        $daysBorrowed = $interval->days;

        // Update borrow record
        $stmt = $conn->prepare("UPDATE borrow_records SET status='returned', return_date=CURDATE() WHERE id=?");
        $stmt->bind_param("i", $borrow_id);
        $stmt->execute();

        // Increase available copies
        $updateBook = $conn->prepare("UPDATE books SET available = available + 1 WHERE id=?");
        $updateBook->bind_param("i", $book_id);
        $updateBook->execute();

        // Fine calculation
        if ($daysBorrowed > $maxDays) {
            $overdueDays = $daysBorrowed - $maxDays;
            $fineAmount = $overdueDays * $finePerDay;

            $fineStmt = $conn->prepare("INSERT INTO fines (user_id, borrow_id, amount, paid) VALUES (?, ?, ?, 0)");
            $fineStmt->bind_param("iid", $student_id, $borrow_id, $fineAmount);
            $fineStmt->execute();

            // Send notification to student
            $notifMsg = "You have been fined $fineAmount for overdue book (Borrow ID: $borrow_id).";
            $notifStmt = $conn->prepare("INSERT INTO notifications (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            $notifStmt->bind_param("iis", $_SESSION['user_id'], $student_id, $notifMsg);
            $notifStmt->execute();

            echo "<p>Book returned successfully! Fine imposed: $fineAmount</p>";
        } else {
            echo "<p>Book returned successfully! No fine.</p>";
        }
    } else {
        echo "<p>Invalid borrow ID or already returned.</p>";
    }
}

// Fetch currently borrowed books
$query = $conn->query("SELECT br.id, u.username, b.title, br.borrow_date 
                       FROM borrow_records br
                       JOIN users u ON br.user_id = u.id
                       JOIN books b ON br.book_id = b.id
                       WHERE br.status='borrowed'
                       ORDER BY br.borrow_date ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Return Book</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <h2>Return Book</h2>
    <form method="POST">
        <label>Borrow ID:</label><br>
        <input type="number" name="borrow_id" required><br><br>
        <input type="submit" value="Mark as Returned">
    </form>

    <h2>Currently Borrowed Books</h2>
    <table border="1">
        <tr><th>Borrow ID</th><th>Student</th><th>Book</th><th>Borrow Date</th></tr>
        <?php while($r = $query->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($r['id']) ?></td>
            <td><?= htmlspecialchars($r['username']) ?></td>
            <td><?= htmlspecialchars($r['title']) ?></td>
            <td><?= htmlspecialchars($r['borrow_date']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <br>
    <a href="../../dashboards/librarian.php">Back to Librarian Dashboard</a>
</body>
</html>

