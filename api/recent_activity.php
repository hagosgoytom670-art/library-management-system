<?php
/**
 * Recent Activity API
 * Returns recent activities for admin dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Include database connection
require_once __DIR__ . '/../db.php';

// Check database connection
if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get admin ID
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

$activities = [];

// Get recent purchases
if ($admin_id) {
    $purchaseQuery = "SELECT item, total_cost, purchase_date FROM purchases WHERE admin_id = ? ORDER BY purchase_date DESC LIMIT 5";
    $stmt = $conn->prepare($purchaseQuery);
    if ($stmt) {
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $purchases = $stmt->get_result();
        
        while ($row = $purchases->fetch_assoc()) {
            $activities[] = [
                'icon' => '🛒',
                'title' => "Purchased: " . htmlspecialchars($row['item']) . " - " . number_format($row['total_cost'], 2) . " ብር",
                'time' => date('M d, Y g:i A', strtotime($row['purchase_date']))
            ];
        }
        $stmt->close();
    }
}

// Get recent messages
$messageQuery = "SELECT name, message, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 5";
$messages = $conn->query($messageQuery);
if ($messages) {
    while ($row = $messages->fetch_assoc()) {
        $activities[] = [
            'icon' => '💬',
            'title' => "New message from: " . htmlspecialchars($row['name']),
            'time' => date('M d, Y g:i A', strtotime($row['created_at']))
        ];
    }
}

// Get recent librarian creations
$librarianQuery = "SELECT username, created_at FROM users WHERE role = 'librarian' ORDER BY created_at DESC LIMIT 5";
$librarians = $conn->query($librarianQuery);
if ($librarians) {
    while ($row = $librarians->fetch_assoc()) {
        $activities[] = [
            'icon' => '👤',
            'title' => "New librarian created: " . htmlspecialchars($row['username']),
            'time' => date('M d, Y g:i A', strtotime($row['created_at']))
        ];
    }
}

// Check if schedules table exists and get recent schedules
$scheduleCheck = $conn->query("SHOW TABLES LIKE 'schedules'");
if ($scheduleCheck && $scheduleCheck->num_rows > 0) {
    $scheduleQuery = "SELECT title, created_at FROM schedules ORDER BY created_at DESC LIMIT 3";
    $schedules = $conn->query($scheduleQuery);
    if ($schedules) {
        while ($row = $schedules->fetch_assoc()) {
            $activities[] = [
                'icon' => '📅',
                'title' => "New schedule created: " . htmlspecialchars($row['title']),
                'time' => date('M d, Y g:i A', strtotime($row['created_at']))
            ];
        }
    }
}

// Sort by time (most recent first)
usort($activities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

// Get only first 10 activities
$activities = array_slice($activities, 0, 10);

// If no activities found
if (empty($activities)) {
    $activities[] = [
        'icon' => '📭',
        'title' => 'No recent activity found',
        'time' => 'Start by adding purchases or creating librarians'
    ];
}

echo json_encode([
    'success' => true,
    'activities' => $activities,
    'lastUpdated' => date('Y-m-d H:i:s')
]);

$conn->close();
?>