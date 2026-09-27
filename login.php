<?php
session_start();
include("db.php");

$username = $_POST['username'];
$password = $_POST['password'];
$role = $_POST['role'];

$stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND role=?");
$stmt->bind_param("ss", $username, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        if ($role === "student") {
            header("Location: dashboards/student.php");
        } elseif ($role === "librarian") {
            header("Location: dashboards/librarian.php");
        } elseif ($role === "admin") {
            header("Location: dashboards/admin.php");
        }
        exit();
    } else {
        echo "Invalid password!";
    }
} else {
    echo "No user found with that role!";
}
?>
