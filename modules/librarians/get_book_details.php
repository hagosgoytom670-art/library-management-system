<?php
include("../../db.php");
session_start();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'No book ID provided']);
    exit;
}

$book_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($book = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'id' => $book['id'],
        'title' => $book['title'],
        'author' => $book['author'] ?? '',
        'category' => $book['category'] ?? '',
        'isbn' => $book['isbn'] ?? '',
        'publisher' => $book['publisher'] ?? '',
        'publication_year' => $book['publication_year'] ?? '',
        'edition' => $book['edition'] ?? '',
        'pages' => $book['pages'] ?? '',
        'language' => $book['language'] ?? '',
        'location' => $book['location'] ?? '',
        'description' => $book['description'] ?? '',
        'available' => $book['available'],
        'total_copies' => $book['total_copies']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Book not found']);
}

$stmt->close();
$conn->close();
?>