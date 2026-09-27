<?php
include("../../includes/auth.php");
include("../../db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['fine_id'])) {
        $fine_id = intval($_POST['fine_id']);
        $user_id = $_SESSION['user_id']; // assuming you store this in session

        // First, get the fine amount
        $stmt = $conn->prepare("SELECT amount FROM fines WHERE id=? AND user_id=? AND paid=0");
        $stmt->bind_param("ii", $fine_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $amount = $row['amount'];

            // Mark fine as paid
            $update = $conn->prepare("UPDATE fines SET paid=1 WHERE id=?");
            $update->bind_param("i", $fine_id);
            $update->execute();

            // Log payment in payments table
            $log = $conn->prepare("INSERT INTO payments (fine_id, user_id, amount) VALUES (?, ?, ?)");
            $log->bind_param("iid", $fine_id, $user_id, $amount);
            $log->execute();

            header("Location: pay_fine.php?status=success");
            exit();
        } else {
            header("Location: pay_fine.php?status=error");
            exit();
        }
    }
}
?>
