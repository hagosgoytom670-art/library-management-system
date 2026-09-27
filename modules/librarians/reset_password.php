<?php
include("../../db.php");

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verify token
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if ($password === $confirm_password) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Update password and clear token
                $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
                $update->bind_param("ss", $hashedPassword, $token);
                $update->execute();

                echo "<p>Password has been reset successfully! <a href='../../index.php'>Login here</a></p>";
            } else {
                echo "<p>Passwords do not match!</p>";
            }
        }
    } else {
        echo "<p>Invalid or expired token.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Reset Password</title>
</head>
<body>
  <h2>Reset Password</h2>
  <form method="POST">
    <label>New Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Confirm Password:</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <input type="submit" value="Reset Password">
  </form>
   <a href="../../dashboards/librarian.php">Back</a>
</body>
</html>
