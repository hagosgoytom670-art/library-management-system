<?php
include("../../nav.php");
include("../../db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    // Check if user exists in database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // valid for 1 hour

        // Store token in DB
        $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE username = ? AND email = ?");
        $update->bind_param("ssss", $token, $expires, $username, $email);
        $update->execute();

        // Create reset link (adjust domain/path to your project)
        $resetLink = "http://localhost/lmspro/modules/students/reset_password.php?token=" . $token;

        echo "<p>Password reset link has been generated for <b>$username</b>. <br> 
              <a href='$resetLink'>Click here to reset your password</a></p>";
    } else {
        echo "<p>No account found with that username and email.</p>";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Forgot Password</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <h2>Forgot Password</h2>
  <form method="POST">
    <label>Enter your username:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Enter your email:</label><br>
    <input type="email" name="email" required><br><br>

    <input type="submit" value="Reset Password">
  </form>
  <br>
</body>
</html>



