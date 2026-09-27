<?php
include("../../includes/auth.php");
include("../../db.php");

header('Content-Type: application/json');

if ($_SESSION['role'] !== "librarian" && $_SESSION['role'] !== "admin") {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    exit;
}

// Check for active borrows in borrow_records table
$borrowers = [];

$sql = "SELECT br.*, u.full_name 
        FROM borrow_records br 
        JOIN users u ON br.user_id = u.id 
        WHERE br.book_id = ? AND br.status = 'borrowed'";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $borrowers[] = [
            'full_name' => $row['full_name'],
            'borrow_date' => $row['borrow_date'],
            'due_date' => $row['due_date']
        ];
    }
    $stmt->close();
}

$has_active_borrows = count($borrowers) > 0;

echo json_encode([
    'success' => true,
    'has_active_borrows' => $has_active_borrows,
    'borrow_count' => count($borrowers),
    'borrowers' => $borrowers
]);
?>