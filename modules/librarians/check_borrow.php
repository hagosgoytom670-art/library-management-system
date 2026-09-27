<?php
include("../../db.php");
header('Content-Type: application/json');

if (isset($_GET['borrow_id'])) {
    $borrow_id = (int)$_GET['borrow_id'];
    
    $stmt = $conn->prepare("SELECT b.id, b.status, b.due_date, u.username AS student, bk.title AS book 
                            FROM borrow_records b 
                            JOIN users u ON b.user_id = u.id 
                            JOIN books bk ON b.book_id = bk.id 
                            WHERE b.id = ?");
    $stmt->bind_param("i", $borrow_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($borrow = $result->fetch_assoc()) {
        $is_overdue = ($borrow['status'] == 'borrowed' && new DateTime($borrow['due_date']) < new DateTime());
        echo json_encode([
            'exists' => true,
            'status' => $borrow['status'],
            'student' => $borrow['student'],
            'book' => $borrow['book'],
            'due_date' => date('M d, Y', strtotime($borrow['due_date'])),
            'is_overdue' => $is_overdue
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    $stmt->close();
} else {
    echo json_encode(['exists' => false]);
}
?>