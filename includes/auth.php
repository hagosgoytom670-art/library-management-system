<?php
session_start();

// go up one level from /includes to reach /db.php at root
include(__DIR__ . "/../db.php");

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch user details from DB
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE username=?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['role'] = $row['role'];
} else {
    session_destroy();
    header("Location: ../login.php");
    exit();
}
?>


