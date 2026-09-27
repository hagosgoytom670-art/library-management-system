<?php
include("../../includes/auth.php");
include("../../db.php");

header('Content-Type: application/json');

if ($_SESSION['role'] !== "librarian" && $_SESSION['role'] !== "admin") {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

// Check for active borrows
$check_sql = "SELECT COUNT(*) as count FROM borrow_records WHERE book_id = ? AND status = 'borrowed'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_row = $check_result->fetch_assoc();

if ($check_row['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete: Book has active borrows']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>