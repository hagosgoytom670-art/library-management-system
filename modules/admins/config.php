<?php
include("../../includes/auth.php");
include("../../db.php");

if ($_SESSION['role'] !== "admin") {
    die("Access denied");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST as $setting => $value) {
        $stmt = $conn->prepare("UPDATE config SET value=? WHERE setting=?");
        $stmt->bind_param("ss", $value, $setting);
        $stmt->execute();
    }
    echo "<p>Configuration updated successfully!</p>";
}

// Fetch current settings
$res = $conn->query("SELECT * FROM config");
$configs = [];
while ($row = $res->fetch_assoc()) {
    $configs[$row['setting']] = $row['value'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Config</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <h2>System Configuration</h2>
    <form method="POST">
        <label>Library Name:</label><br>
        <input type="text" name="library_name" value="<?= $configs['library_name'] ?>"><br><br>

        <label>Max Borrow Days:</label><br>
        <input type="number" name="max_borrow_days" value="<?= $configs['max_borrow_days'] ?>"><br><br>

        <label>Fine Per Day:</label><br>
        <input type="number" name="fine_per_day" value="<?= $configs['fine_per_day'] ?>"><br><br>

        <input type="submit" value="Update Config">
    </form>
    <br>
    <a href="../../dashboards/admin.php">Back</a>
</body>
</html>
