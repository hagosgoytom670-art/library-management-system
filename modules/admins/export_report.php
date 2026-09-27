<?php
include("../../includes/auth.php");
include("../../db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    exit("Access denied");
}

// Get admin ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username=? AND role='admin'");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$admin_id = $row['id'] ?? null;

// Fetch purchases
$purchases = $conn->prepare("SELECT item, category, individual_cost, amount, total_cost, purchase_date 
                             FROM purchases WHERE admin_id=? ORDER BY purchase_date DESC");
$purchases->bind_param("i", $admin_id);
$purchases->execute();
$res = $purchases->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=purchase_report.csv');

$output = fopen("php://output", "w");
fputcsv($output, ['Item', 'Category', 'Individual Cost', 'Amount', 'Total Cost', 'Date']);

while($row = $res->fetch_assoc()) {
    fputcsv($output, $row);
}
fclose($output);
exit;
