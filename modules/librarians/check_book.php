<?php
include("../../db.php");
header('Content-Type: application/json');

if (isset($_GET['identifier'])) {
    $identifier = trim($_GET['identifier']);
    
    // Search by ID OR title
    if (is_numeric($identifier)) {
        $stmt = $conn->prepare("SELECT id, title, available, total_copies FROM books WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $identifier);
    } else {
        $stmt = $conn->prepare("SELECT id, title, available, total_copies FROM books WHERE title LIKE ? LIMIT 1");
        $like = "%$identifier%";
        $stmt->bind_param("s", $like);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($book = $result->fetch_assoc()) {
        echo json_encode([
            'exists' => true,
            'id' => $book['id'],
            'title' => $book['title'],
            'available' => (int)$book['available'],
            'total_copies' => (int)$book['total_copies']
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    $stmt->close();
} else {
    echo json_encode(['exists' => false]);
}
?>