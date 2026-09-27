<?php
/**
 * Dashboard Statistics API - Debug Version
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in', 'session' => $_SESSION]);
    exit;
}

// Include database connection
require_once __DIR__ . '/../db.php';

// Check database connection
if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown')]);
    exit;
}

// Get admin ID from session or database
$admin_id = $_SESSION['user_id'] ?? null;

if (!$admin_id) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $admin_id = $user['id'] ?? null;
    $_SESSION['user_id'] = $admin_id;
    $stmt->close();
}

// Debug information
$debug = [
    'session_username' => $_SESSION['username'] ?? 'not set',
    'admin_id' => $admin_id,
    'admin_id_found' => $admin_id ? 'yes' : 'no'
];

// Initialize default values
$totalPurchases = 0;
$totalSpent = 0;

// Get total purchases and total spent
if ($admin_id) {
    $purchaseQuery = "SELECT COUNT(*) as total, COALESCE(SUM(total_cost), 0) as total_spent FROM purchases WHERE admin_id = ?";
    $stmt = $conn->prepare($purchaseQuery);
    if ($stmt) {
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $purchaseResult = $stmt->get_result();
        if ($purchaseResult && $purchaseResult->num_rows > 0) {
            $row = $purchaseResult->fetch_assoc();
            $totalPurchases = $row['total'] ?? 0;
            $totalSpent = $row['total_spent'] ?? 0;
        }
        $stmt->close();
    }
    
    // Debug: Check if purchases table has data for this admin
    $checkQuery = "SELECT COUNT(*) as all_purchases FROM purchases WHERE admin_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $admin_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $debug['purchases_in_db_for_admin'] = $checkResult->fetch_assoc()['all_purchases'] ?? 0;
    $checkStmt->close();
} else {
    $debug['error'] = 'No admin_id found';
}

// Get total librarians
$totalLibrarians = 0;
$librarianQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'librarian'";
$librarianResult = $conn->query($librarianQuery);
if ($librarianResult) {
    $totalLibrarians = $librarianResult->fetch_assoc()['total'] ?? 0;
    $debug['librarians_in_db'] = $totalLibrarians;
}

// Get unread messages
$unreadMessages = 0;
$messageQuery = "SELECT COUNT(*) as total FROM contact_messages WHERE is_read = 0 OR is_read IS NULL";
$messageResult = $conn->query($messageQuery);
if ($messageResult) {
    $unreadMessages = $messageResult->fetch_assoc()['total'] ?? 0;
    $debug['messages_in_db'] = $unreadMessages;
}

// Get total active schedules
$totalSchedules = 0;
$scheduleCheck = $conn->query("SHOW TABLES LIKE 'schedules'");
if ($scheduleCheck && $scheduleCheck->num_rows > 0) {
    $scheduleCountQuery = "SELECT COUNT(*) as total FROM schedules WHERE status = 'active' OR status IS NULL";
    $scheduleResult = $conn->query($scheduleCountQuery);
    if ($scheduleResult) {
        $totalSchedules = $scheduleResult->fetch_assoc()['total'] ?? 0;
        $debug['schedules_table_exists'] = true;
        $debug['schedules_in_db'] = $totalSchedules;
    }
} else {
    $debug['schedules_table_exists'] = false;
}

// Return JSON response with debug info
echo json_encode([
    'success' => true,
    'totalPurchases' => $totalPurchases,
    'totalLibrarians' => $totalLibrarians,
    'unreadMessages' => $unreadMessages,
    'totalSchedules' => $totalSchedules,
    'totalSpent' => $totalSpent,
    'lastUpdated' => date('Y-m-d H:i:s'),
    'debug' => $debug  // This will help us see what's wrong
]);

$conn->close();
?>