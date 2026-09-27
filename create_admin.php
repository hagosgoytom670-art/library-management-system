<?php
include("db.php");

// Change these values for the account you want to create
$username = "Tekle Haylekiros";          // username
$password = "tekle@123";    // plain password
$role     = "admin";           // role: admin, librarian, or student

// Hash the password securely
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table
$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $hashedPassword, $role);

if ($stmt->execute()) {
    echo "User '$username' with role '$role' created successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

