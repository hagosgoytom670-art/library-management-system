<?php
include("../../db.php"); // connect to database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $id_number = trim($_POST['id_number']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepared statement to insert student
    $stmt = $conn->prepare("INSERT INTO users (username, email, id_number, password, role) VALUES (?, ?, ?, ?, 'student')");
    $stmt->bind_param("ssss", $username, $email, $id_number, $hashedPassword);

    if ($stmt->execute()) {
        echo "Student registered successfully! <a href='../../index.php'>Login here</a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Signup</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <h2>Student Signup</h2>
    <form method="POST" action="">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>ID Number:</label><br>
        <input type="text" name="id_number" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Confirm Password:</label><br>
        <input type="password" name="confirm_password" required><br><br>

        <input type="submit" value="Sign Up">
    </form>
</body>
</html>
