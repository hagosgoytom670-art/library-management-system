<?php
/**
 * Admin Purchases Dashboard
 * Professional purchase management system with advanced reporting
 * Enhanced with Edit, Delete, and View functionality
 */

// No session_start() here - handled by auth.php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../db.php';

// Verify admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>Access Denied</h1><p>You do not have permission to access this page.</p><a href="../../login.php">Return to Login</a></body></html>');
}

// =============================================================================
// Handle AJAX requests for get_purchase
// =============================================================================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_purchase' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $adminId = getAdminId($conn, $_SESSION['username'] ?? '');
    $purchaseId = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT id, item, category, individual_cost, amount, total_cost, purchase_date, created_at FROM purchases WHERE id = ? AND admin_id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $purchaseId, $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        $purchase = $result->fetch_assoc();
        
        if ($purchase) {
            echo json_encode([
                'success' => true,
                'id' => $purchase['id'],
                'item' => htmlspecialchars($purchase['item']),
                'category' => htmlspecialchars($purchase['category']),
                'individual_cost' => $purchase['individual_cost'],
                'individual_cost_formatted' => 'ብር ' . number_format($purchase['individual_cost'], 2),
                'amount' => $purchase['amount'],
                'total_cost' => $purchase['total_cost'],
                'total_cost_formatted' => 'ብር ' . number_format($purchase['total_cost'], 2),
                'purchase_date' => $purchase['purchase_date'],
                'purchase_date_formatted' => date('M d, Y', strtotime($purchase['purchase_date'])),
                'created_at_formatted' => date('M d, Y g:i A', strtotime($purchase['created_at']))
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Purchase not found']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    $conn->close();
    exit;
}

// =============================================================================
// Ethiopian Calendar Helper Functions
// =============================================================================

/**
 * Get Ethiopian months list
 */
function getEthiopianMonths(): array
{
    return [
        1 => ['name' => 'Meskeram', 'gregorian' => 'September', 'full' => 'Meskeram (September)'],
        2 => ['name' => 'Tikimt', 'gregorian' => 'October', 'full' => 'Tikimt (October)'],
        3 => ['name' => 'Hidar', 'gregorian' => 'November', 'full' => 'Hidar (November)'],
        4 => ['name' => 'Tahsas', 'gregorian' => 'December', 'full' => 'Tahsas (December)'],
        5 => ['name' => 'Tir', 'gregorian' => 'January', 'full' => 'Tir (January)'],
        6 => ['name' => 'Yekatit', 'gregorian' => 'February', 'full' => 'Yekatit (February)'],
        7 => ['name' => 'Megabit', 'gregorian' => 'March', 'full' => 'Megabit (March)'],
        8 => ['name' => 'Miazia', 'gregorian' => 'April', 'full' => 'Miazia (April)'],
        9 => ['name' => 'Ginbot', 'gregorian' => 'May', 'full' => 'Ginbot (May)'],
        10 => ['name' => 'Sene', 'gregorian' => 'June', 'full' => 'Sene (June)'],
        11 => ['name' => 'Hamle', 'gregorian' => 'July', 'full' => 'Hamle (July)'],
        12 => ['name' => 'Nehasie', 'gregorian' => 'August', 'full' => 'Nehasie (August)']
    ];
}

/**
 * Convert Ethiopian month to Gregorian date range
 */
function ethiopianMonthToGregorianRange(int $ethiopianYear, int $ethiopianMonth): array
{
    $monthMapping = [
        1 => ['start_month' => 9, 'start_day' => 11, 'end_month' => 10, 'end_day' => 10],
        2 => ['start_month' => 10, 'start_day' => 11, 'end_month' => 11, 'end_day' => 10],
        3 => ['start_month' => 11, 'start_day' => 11, 'end_month' => 12, 'end_day' => 10],
        4 => ['start_month' => 12, 'start_day' => 11, 'end_month' => 1, 'end_day' => 9],
        5 => ['start_month' => 1, 'start_day' => 10, 'end_month' => 2, 'end_day' => 8],
        6 => ['start_month' => 2, 'start_day' => 9, 'end_month' => 3, 'end_day' => 9],
        7 => ['start_month' => 3, 'start_day' => 10, 'end_month' => 4, 'end_day' => 9],
        8 => ['start_month' => 4, 'start_day' => 10, 'end_month' => 5, 'end_day' => 9],
        9 => ['start_month' => 5, 'start_day' => 10, 'end_month' => 6, 'end_day' => 8],
        10 => ['start_month' => 6, 'start_day' => 9, 'end_month' => 7, 'end_day' => 9],
        11 => ['start_month' => 7, 'start_day' => 10, 'end_month' => 8, 'end_day' => 9],
        12 => ['start_month' => 8, 'start_day' => 10, 'end_month' => 9, 'end_day' => 10]
    ];
    
    $mapping = $monthMapping[$ethiopianMonth];
    $gregorianYear = $ethiopianYear + 8;
    
    $startYear = $gregorianYear;
    $endYear = $gregorianYear;
    
    if ($mapping['start_month'] > $mapping['end_month']) {
        $endYear++;
    }
    
    return [
        'start' => sprintf('%d-%02d-%02d', $startYear, $mapping['start_month'], $mapping['start_day']),
        'end' => sprintf('%d-%02d-%02d', $endYear, $mapping['end_month'], $mapping['end_day'])
    ];
}

// =============================================================================
// Helper Functions
// =============================================================================

function formatCurrency($amount): string
{
    if (is_string($amount)) $amount = (float)$amount;
    elseif ($amount === null) $amount = 0;
    return 'ብር ' . number_format($amount, 2);
}

function formatNumber($value, int $decimals = 0): string
{
    if (is_string($value)) $value = (float)$value;
    elseif ($value === null) $value = 0;
    return number_format((float)$value, $decimals);
}

function getAdminId(mysqli $conn, string $username): ?int
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['id'] ?? null;
}

function validatePurchaseData(array $data): array
{
    $errors = [];
    $item = trim($data['item'] ?? '');
    if (empty($item)) $errors[] = 'Item name is required.';
    elseif (strlen($item) > 255) $errors[] = 'Item name cannot exceed 255 characters.';
    
    $category = trim($data['category'] ?? '');
    if (empty($category)) $errors[] = 'Category is required.';
    
    $individualCost = filter_var($data['individual_cost'] ?? 0, FILTER_VALIDATE_FLOAT);
    if ($individualCost === false || $individualCost < 0) $errors[] = 'Individual cost must be a valid positive number.';
    
    $amount = filter_var($data['amount'] ?? 0, FILTER_VALIDATE_INT);
    if ($amount === false || $amount < 1) $errors[] = 'Amount must be a positive integer.';
    
    $date = $data['purchase_date'] ?? '';
    if (empty($date)) $errors[] = 'Purchase date is required.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Invalid date format.';
    elseif (strtotime($date) > time()) $errors[] = 'Purchase date cannot be in the future.';
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'item' => $item,
        'category' => $category,
        'individual_cost' => $individualCost,
        'amount' => $amount,
        'date' => $date
    ];
}

// =============================================================================
// Main Logic
// =============================================================================

$adminId = getAdminId($conn, $_SESSION['username'] ?? '');
$message = '';
$messageType = '';

// Handle reset filters
if (isset($_GET['reset'])) {
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_purchase']) && isset($_POST['purchase_id'])) {
    $purchaseId = (int)$_POST['purchase_id'];
    $deleteStmt = $conn->prepare("DELETE FROM purchases WHERE id = ? AND admin_id = ?");
    if ($deleteStmt) {
        $deleteStmt->bind_param('ii', $purchaseId, $adminId);
        if ($deleteStmt->execute()) {
            $message = '✓ Purchase record deleted successfully!';
            $messageType = 'success';
        } else {
            $message = '✗ Failed to delete record.';
            $messageType = 'error';
        }
        $deleteStmt->close();
    }
}

// Handle Edit Action - Update purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_purchase']) && isset($_POST['edit_id'])) {
    $editId = (int)$_POST['edit_id'];
    $validation = validatePurchaseData($_POST);
    if ($validation['valid']) {
        $totalCost = $validation['individual_cost'] * $validation['amount'];
        $updateStmt = $conn->prepare("UPDATE purchases SET item = ?, category = ?, individual_cost = ?, amount = ?, total_cost = ?, purchase_date = ? WHERE id = ? AND admin_id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param('ssdidsii', $validation['item'], $validation['category'], $validation['individual_cost'], $validation['amount'], $totalCost, $validation['date'], $editId, $adminId);
            if ($updateStmt->execute()) {
                $message = '✓ Purchase record updated successfully!';
                $messageType = 'success';
            } else {
                $message = '✗ Database error: ' . htmlspecialchars($updateStmt->error);
                $messageType = 'error';
            }
            $updateStmt->close();
        }
    } else {
        $message = '✗ Please correct the errors.';
        $messageType = 'error';
    }
}

// Handle form submission for new purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_purchase']) && $adminId && !isset($_POST['edit_id'])) {
    $validation = validatePurchaseData($_POST);
    if ($validation['valid']) {
        $totalCost = $validation['individual_cost'] * $validation['amount'];
        $insert = $conn->prepare("INSERT INTO purchases (admin_id, item, category, individual_cost, amount, total_cost, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($insert) {
            $insert->bind_param('issdids', $adminId, $validation['item'], $validation['category'], $validation['individual_cost'], $validation['amount'], $totalCost, $validation['date']);
            if ($insert->execute()) {
                $message = '✓ Purchase recorded successfully!';
                $messageType = 'success';
            } else {
                $message = '✗ Database error: ' . htmlspecialchars($insert->error);
                $messageType = 'error';
            }
            $insert->close();
        }
    } else {
        $message = '✗ Please correct the errors.';
        $messageType = 'error';
    }
}

// Handle filters
$filterCategory = $_GET['filter_category'] ?? '';
$filterItem = $_GET['filter_item'] ?? '';
$filterEthiopianMonth = $_GET['filter_ethiopian_month'] ?? '';
$filterEthiopianYear = $_GET['filter_ethiopian_year'] ?? date('Y') - 8;

// Build WHERE conditions
$whereConditions = ["admin_id = ?"];
$params = [$adminId];
$types = "i";

if (!empty($filterCategory)) {
    $whereConditions[] = "category = ?";
    $params[] = $filterCategory;
    $types .= 's';
}

if (!empty($filterItem)) {
    $whereConditions[] = "item LIKE ?";
    $params[] = "%{$filterItem}%";
    $types .= 's';
}

if (!empty($filterEthiopianMonth) && !empty($filterEthiopianYear)) {
    $dateRange = ethiopianMonthToGregorianRange((int)$filterEthiopianYear, (int)$filterEthiopianMonth);
    $whereConditions[] = "purchase_date BETWEEN ? AND ?";
    $params[] = $dateRange['start'];
    $params[] = $dateRange['end'];
    $types .= 'ss';
}

$whereClause = implode(' AND ', $whereConditions);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM purchases WHERE {$whereClause}");
$totalCount = 0;
if ($countStmt) {
    if (!empty($params)) $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $result = $countStmt->get_result();
    if ($result) $totalCount = $result->fetch_assoc()['total'] ?? 0;
    $countStmt->close();
}
$totalPages = $totalCount > 0 ? ceil($totalCount / $perPage) : 1;

// Fetch purchases
$sql = "SELECT id, item, category, individual_cost, amount, total_cost, purchase_date FROM purchases WHERE {$whereClause} ORDER BY purchase_date DESC, id DESC LIMIT ? OFFSET ?";
$historyStmt = $conn->prepare($sql);
$purchases = null;
if ($historyStmt) {
    $allParams = array_merge($params, [$perPage, $offset]);
    $allTypes = $types . 'ii';
    if (!empty($allParams)) $historyStmt->bind_param($allTypes, ...$allParams);
    $historyStmt->execute();
    $purchases = $historyStmt->get_result();
}

// Calculate grand total
$totalStmt = $conn->prepare("SELECT COALESCE(SUM(total_cost), 0) as grand_total FROM purchases WHERE {$whereClause}");
$grandTotal = 0;
if ($totalStmt) {
    if (!empty($params)) $totalStmt->bind_param($types, ...$params);
    $totalStmt->execute();
    $result = $totalStmt->get_result();
    if ($result) $grandTotal = (float)($result->fetch_assoc()['grand_total'] ?? 0);
    $totalStmt->close();
}

// Get categories for filter
$catListStmt = $conn->prepare("SELECT DISTINCT category FROM purchases WHERE admin_id = ? ORDER BY category");
$categoriesList = null;
if ($catListStmt) {
    $catListStmt->bind_param('i', $adminId);
    $catListStmt->execute();
    $categoriesList = $catListStmt->get_result();
}

// Category summary
$catStmt = $conn->prepare("SELECT category, COUNT(*) as count, SUM(total_cost) as category_total FROM purchases WHERE {$whereClause} GROUP BY category ORDER BY category_total DESC");
$categories = null;
if ($catStmt) {
    if (!empty($params)) $catStmt->bind_param($types, ...$params);
    $catStmt->execute();
    $categories = $catStmt->get_result();
}

// Recent purchases
$recentStmt = $conn->prepare("SELECT COUNT(*) as recent_count, COALESCE(SUM(total_cost), 0) as recent_total FROM purchases WHERE admin_id = ? AND purchase_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$recentData = ['recent_count' => 0, 'recent_total' => 0];
if ($recentStmt) {
    $recentStmt->bind_param('i', $adminId);
    $recentStmt->execute();
    $result = $recentStmt->get_result();
    if ($result) $recentData = $result->fetch_assoc();
    $recentStmt->close();
}

// Ethiopian years
$currentEthiopianYear = (int)date('Y') - 8;
$ethiopianYears = range($currentEthiopianYear - 2, $currentEthiopianYear + 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases Dashboard | Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1e293b;
            --gray: #6c7f96;
            --light: #f8fafc;
            --border: #e2e8f0;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9eef3 100%);
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #1a2c3e 0%, #0f1e2c 100%);
            color: white;
            width: 280px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            padding: 24px 0;
            box-shadow: 2px 0 12px rgba(0,0,0,0.08);
            z-index: 100;
            overflow-y: auto;
        }
        
        .sidebar h3 {
            text-align: center;
            font-weight: 500;
            font-size: 0.85rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #8ba3bc;
            padding: 0 20px 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 24px;
            transition: all 0.2s;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
        }
        
        .sidebar a:hover {
            background: rgba(255,255,255,0.08);
            color: white;
            border-left-color: var(--primary);
        }
        
        /* Main Content */
        .main {
            margin-left: 280px;
            padding: 28px 32px;
        }
        
        /* Header */
        .page-header {
            margin-bottom: 28px;
        }
        
        .page-header h1 {
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header h1:before { content: "🛒"; font-size: 1.8rem; }
        .page-header p { color: var(--gray); margin-top: 6px; font-size: 0.9rem; }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; color: var(--gray); margin-bottom: 8px; }
        .stat-value { font-size: 1.9rem; font-weight: 700; color: var(--dark); }
        .stat-sub { font-size: 0.7rem; color: #8ca3bc; margin-top: 8px; }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 16px;
            margin-bottom: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        
        .card-header {
            background: var(--light);
            padding: 18px 28px;
            border-bottom: 1px solid var(--border);
        }
        
        .card-header h2 { font-size: 1.25rem; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 24px 28px; }
        
        /* Filter Section */
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: 600; font-size: 0.7rem; text-transform: uppercase; color: var(--gray); }
        .filter-group input, .filter-group select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        
        .filter-actions { display: flex; gap: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border); }
        .btn-filter, .btn-reset, .btn-report { padding: 10px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 0.85rem; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-filter { background: var(--primary); color: white; }
        .btn-filter:hover { background: var(--primary-dark); }
        .btn-reset { background: var(--border); color: var(--dark); }
        .btn-reset:hover { background: #cbd5e1; }
        .btn-report { background: var(--success); color: white; }
        .btn-report:hover { background: #059669; }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-view, .btn-edit, .btn-delete {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        .btn-view { background: #e0f2fe; color: #0369a1; }
        .btn-view:hover { background: #bae6fd; }
        .btn-edit { background: #fef3c7; color: #b45309; }
        .btn-edit:hover { background: #fde68a; }
        .btn-delete { background: #fee2e2; color: #b91c1c; }
        .btn-delete:hover { background: #fecaca; }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            padding: 20px 24px;
            background: var(--light);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { font-size: 1.25rem; font-weight: 600; color: var(--dark); }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
            transition: color 0.2s;
        }
        .modal-close:hover { color: var(--danger); }
        .modal-body { padding: 24px; }
        .modal-footer {
            padding: 16px 24px;
            background: var(--light);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* View Details */
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }
        .detail-label {
            width: 120px;
            font-weight: 600;
            color: var(--dark);
        }
        .detail-value {
            flex: 1;
            color: var(--gray);
        }
        .detail-value strong { color: var(--dark); font-size: 1.1rem; }
        
        /* Delete Confirmation */
        .delete-warning {
            text-align: center;
            padding: 20px;
        }
        .delete-warning-icon {
            font-size: 3rem;
            margin-bottom: 16px;
        }
        .delete-warning h4 { color: var(--danger); margin-bottom: 12px; }
        .delete-warning p { color: var(--gray); margin-bottom: 20px; }
        .btn-confirm-delete {
            background: var(--danger);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-confirm-delete:hover { background: #dc2626; }
        .btn-cancel {
            background: var(--border);
            color: var(--dark);
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        /* Report Summary */
        .report-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 24px;
        }
        
        .report-summary h3 { margin-bottom: 16px; font-size: 1.2rem; }
        .report-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .report-stat { text-align: center; }
        .report-stat-value { font-size: 1.8rem; font-weight: 700; }
        .report-stat-label { font-size: 0.75rem; opacity: 0.9; margin-top: 4px; }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-weight: 600; font-size: 0.8rem; color: var(--dark); }
        .form-group input { padding: 11px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 0.9rem; }
        .form-group input:focus { outline: none; border-color: var(--primary); }
        .btn-submit { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 28px; width: 100%; }
        .btn-update { background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%); }
        
        /* Tables */
        .table-responsive { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .data-table th { background: var(--light); padding: 14px 16px; text-align: left; font-weight: 600; color: var(--dark); border-bottom: 2px solid var(--border); font-size: 0.7rem; text-transform: uppercase; }
        .data-table td { padding: 14px 16px; border-bottom: 1px solid var(--border); color: var(--dark); }
        .data-table tr:hover td { background: #fafcff; }
        .summary-row td { background: #fefce8; font-weight: 700; border-top: 2px solid var(--border); }
        
        /* Badges */
        .category-badge { display: inline-block; padding: 4px 12px; background: #eef2ff; border-radius: 20px; font-size: 0.7rem; font-weight: 500; color: #4338ca; }
        .total-badge { font-size: 1.1rem; font-weight: 700; color: var(--success); }
        
        /* Alerts */
        .alert { padding: 14px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: #ecfdf5; border-left: 4px solid var(--success); color: #065f46; }
        .alert-error { background: #fef2f2; border-left: 4px solid var(--danger); color: #991b1b; }
        
        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 8px 14px; background: var(--light); border-radius: 8px; text-decoration: none; color: var(--dark); font-size: 0.85rem; }
        .pagination a:hover { background: var(--border); }
        .pagination .current { background: var(--primary); color: white; }
        
        .empty-state { text-align: center; padding: 48px; color: var(--gray); }
        .active-filter-badge { display: inline-block; background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; margin-top: 12px; }
        
        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .report-stats { grid-template-columns: repeat(2, 1fr); }
            .action-buttons { flex-direction: column; gap: 4px; }
        }
        
        @media print {
            .sidebar, .filter-card, .btn-submit, .pagination, .no-print, .action-buttons { display: none; }
            .main { margin-left: 0; padding: 0; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>📋 PURCHASE SYSTEM</h3>
    <a href="../../dashboards/admin.php">🏠 Dashboard</a>
    <a href="report.php">📊 Advanced Report</a>
    <a href="../../logout.php">🚪 Logout</a>
</div>

<div class="main">
    
    <div class="page-header">
        <h1>Purchases Management</h1>
        <p>Record purchases, track spending, and generate detailed reports by month or category</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <span><?php echo $messageType === 'success' ? '✓' : '⚠️'; ?></span>
            <div><?php echo $message; ?></div>
        </div>
    <?php endif; ?>
    
    <!-- Advanced Filter & Report Section -->
    <div class="card filter-card">
        <div class="card-header">
            <h2>🔍 Filter by ....</h2>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>📦 Filter by Item</label>
                        <input type="text" name="filter_item" placeholder="Search item name..." value="<?php echo htmlspecialchars($filterItem); ?>">
                    </div>
                    <div class="filter-group">
                        <label>🏷️ Filter by Category</label>
                        <select name="filter_category">
                            <option value="">All Categories</option>
                            <?php if ($categoriesList && $categoriesList->num_rows > 0): ?>
                                <?php while($cat = $categoriesList->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $filterCategory === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>📅 Ethiopian Month</label>
                        <select name="filter_ethiopian_month">
                            <option value="">All Months</option>
                            <?php foreach(getEthiopianMonths() as $num => $month): ?>
                                <option value="<?php echo $num; ?>" <?php echo $filterEthiopianMonth == $num ? 'selected' : ''; ?>>
                                    <?php echo $month['full']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>📅 Ethiopian Year</label>
                        <select name="filter_ethiopian_year">
                            <?php foreach($ethiopianYears as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $filterEthiopianYear == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?> (፲<?php echo $year - 2000; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">🔍 Apply Filters</button>
                    <a href="?reset=1" class="btn-reset">⟳ Clear Filters</a>
                </div>
            </form>
            
            <?php if (!empty($filterCategory) || !empty($filterItem) || !empty($filterEthiopianMonth)): ?>
                <div class="active-filter-badge">
                    📌 Active filters: 
                    <?php if(!empty($filterItem)) echo " Item: " . htmlspecialchars($filterItem); ?>
                    <?php if(!empty($filterCategory)) echo " | Category: " . htmlspecialchars($filterCategory); ?>
                    <?php if(!empty($filterEthiopianMonth) && !empty($filterEthiopianYear)) echo " | Ethiopian: " . getEthiopianMonths()[$filterEthiopianMonth]['full'] . " " . $filterEthiopianYear; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Report Summary Section (shown when filters are applied) -->
    <?php if (!empty($filterCategory) || !empty($filterEthiopianMonth) || !empty($filterItem)): ?>
        <div class="report-summary">
            <h3>📊 Report Summary</h3>
            <div class="report-stats">
                <div class="report-stat">
                    <div class="report-stat-value"><?php echo formatNumber($totalCount); ?></div>
                    <div class="report-stat-label">Total Transactions</div>
                </div>
                <div class="report-stat">
                    <div class="report-stat-value"><?php echo formatCurrency($grandTotal); ?></div>
                    <div class="report-stat-label">Total Spending</div>
                </div>
                <div class="report-stat">
                    <div class="report-stat-value"><?php echo formatNumber($categories ? $categories->num_rows : 0); ?></div>
                    <div class="report-stat-label">Categories</div>
                </div>
                <div class="report-stat">
                    <div class="report-stat-value"><?php echo $grandTotal > 0 ? round(($grandTotal / ($totalCount ?: 1)), 2) : 0; ?></div>
                    <div class="report-stat-label">Avg per Transaction</div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Spent</div>
            <div class="stat-value"><?php echo formatCurrency($grandTotal); ?></div>
            <div class="stat-sub">With current filters</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Last 30 Days</div>
            <div class="stat-value"><?php echo formatCurrency($recentData['recent_total'] ?? 0); ?></div>
            <div class="stat-sub"><?php echo formatNumber($recentData['recent_count'] ?? 0); ?> purchase(s)</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Items</div>
            <div class="stat-value"><?php echo formatNumber($totalCount); ?></div>
            <div class="stat-sub">Purchased items</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Categories</div>
            <div class="stat-value"><?php echo formatNumber($categories ? $categories->num_rows : 0); ?></div>
            <div class="stat-sub">Unique categories</div>
        </div>
    </div>
    
    <!-- Add Purchase Form -->
    <div class="card">
        <div class="card-header">
            <h2>➕ Record New Purchase</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="save_purchase" value="1">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="item">Item Name *</label>
                        <input type="text" id="item" name="item" placeholder="e.g., Office Chair, Printer Paper" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <input type="text" id="category" name="category" placeholder="e.g., Furniture, Supplies, Software" required>
                    </div>
                    <div class="form-group">
                        <label for="individual_cost">Individual Cost (ብር) *</label>
                        <input type="number" step="0.01" id="individual_cost" name="individual_cost" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="amount">Quantity *</label>
                        <input type="number" id="amount" name="amount" placeholder="Number of items" value="1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="purchase_date">Purchase Date *</label>
                        <input type="date" id="purchase_date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn-submit">💾 Save Purchase Record</button>
            </form>
        </div>
    </div>
    
    <!-- Purchase History Table -->
    <div class="card">
        <div class="card-header">
            <h2>📜 Purchase History</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Unit Cost</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($purchases && $purchases->num_rows > 0): ?>
                            <?php while ($purchase = $purchases->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($purchase['item']); ?></strong></td>
                                    <td><span class="category-badge"><?php echo htmlspecialchars($purchase['category']); ?></span></td>
                                    <td><?php echo formatCurrency($purchase['individual_cost']); ?></td>
                                    <td><?php echo formatNumber($purchase['amount']); ?></td>
                                    <td class="total-badge"><?php echo formatCurrency($purchase['total_cost']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-view" onclick="openViewModal(<?php echo $purchase['id']; ?>)">👁️ View</button>
                                            <button class="btn-edit" onclick="openEditModal(<?php echo $purchase['id']; ?>)">✏️ Edit</button>
                                            <button class="btn-delete" onclick="openDeleteModal(<?php echo $purchase['id']; ?>, '<?php echo htmlspecialchars(addslashes($purchase['item'])); ?>')">🗑️ Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <tr class="summary-row">
                                <td colspan="5" style="text-align: right; font-weight: 600;">Grand Total:</td>
                                <td colspan="2" style="font-weight: 700;"><?php echo formatCurrency($grandTotal); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">No purchase records found. Use the form above to add your first purchase.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&filter_category=<?php echo urlencode($filterCategory); ?>&filter_item=<?php echo urlencode($filterItem); ?>&filter_ethiopian_month=<?php echo $filterEthiopianMonth; ?>&filter_ethiopian_year=<?php echo $filterEthiopianYear; ?>">← Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&filter_category=<?php echo urlencode($filterCategory); ?>&filter_item=<?php echo urlencode($filterItem); ?>&filter_ethiopian_month=<?php echo $filterEthiopianMonth; ?>&filter_ethiopian_year=<?php echo $filterEthiopianYear; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&filter_category=<?php echo urlencode($filterCategory); ?>&filter_item=<?php echo urlencode($filterItem); ?>&filter_ethiopian_month=<?php echo $filterEthiopianMonth; ?>&filter_ethiopian_year=<?php echo $filterEthiopianYear; ?>">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Category Spending Breakdown -->
    <div class="card">
        <div class="card-header">
            <h2>📊 Spending by Category</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Transactions</th>
                            <th>Total Spent</th>
                            <th>% of Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories && $categories->num_rows > 0): ?>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <?php 
                                    $categoryTotal = (float)($category['category_total'] ?? 0);
                                    $percentage = $grandTotal > 0 ? round(($categoryTotal / $grandTotal) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><span class="category-badge"><?php echo htmlspecialchars($category['category']); ?></span></td>
                                    <td><?php echo formatNumber($category['count']); ?></td>
                                    <td><?php echo formatCurrency($categoryTotal); ?></td>
                                    <td>
                                        <?php echo $percentage; ?>%
                                        <div style="width: 100%; background: #e2e8f0; border-radius: 10px; margin-top: 6px; height: 6px; overflow: hidden;">
                                            <div style="width: <?php echo $percentage; ?>%; background: var(--primary); height: 6px;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">No category data available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Ethiopian Calendar Reference -->
    <div class="card no-print">
        <div class="card-header">
            <h2>📖 Ethiopian Calendar Reference</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
                <?php foreach(getEthiopianMonths() as $num => $month): ?>
                    <div style="padding: 8px; background: var(--light); border-radius: 8px; text-align: center;">
                        <strong><?php echo $num; ?></strong><br>
                        <?php echo $month['name']; ?><br>
                        <small style="color: var(--gray);">(<?php echo $month['gregorian']; ?>)</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📄 Purchase Details</h3>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewModalBody">
            <div class="detail-row">
                <div class="detail-label">Item:</div>
                <div class="detail-value" id="viewItem"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Category:</div>
                <div class="detail-value" id="viewCategory"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Unit Cost:</div>
                <div class="detail-value" id="viewUnitCost"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Quantity:</div>
                <div class="detail-value" id="viewQuantity"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Total Cost:</div>
                <div class="detail-value"><strong id="viewTotalCost"></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Purchase Date:</div>
                <div class="detail-value" id="viewDate"></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Recorded On:</div>
                <div class="detail-value" id="viewCreatedAt"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <form method="POST" action="">
            <input type="hidden" name="update_purchase" value="1">
            <input type="hidden" name="edit_id" id="editId">
            <div class="modal-header">
                <h3>✏️ Edit Purchase</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" name="item" id="editItem" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <input type="text" name="category" id="editCategory" required>
                    </div>
                    <div class="form-group">
                        <label>Individual Cost (ብር) *</label>
                        <input type="number" step="0.01" name="individual_cost" id="editIndividualCost" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity *</label>
                        <input type="number" name="amount" id="editAmount" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Purchase Date *</label>
                        <input type="date" name="purchase_date" id="editPurchaseDate" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-submit btn-update">💾 Update Purchase</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <form method="POST" action="">
            <input type="hidden" name="delete_purchase" value="1">
            <input type="hidden" name="purchase_id" id="deleteId">
            <div class="modal-header">
                <h3>🗑️ Delete Purchase</h3>
                <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <div class="delete-warning-icon">⚠️</div>
                    <h4>Are you sure?</h4>
                    <p>You are about to delete "<strong id="deleteItemName"></strong>". This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn-confirm-delete">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    // View Modal Functions
    function openViewModal(purchaseId) {
        // Show loading state
        document.getElementById('viewItem').innerHTML = '<span class="loading-spinner"></span> Loading...';
        document.getElementById('viewCategory').innerHTML = '';
        document.getElementById('viewUnitCost').innerHTML = '';
        document.getElementById('viewQuantity').innerHTML = '';
        document.getElementById('viewTotalCost').innerHTML = '';
        document.getElementById('viewDate').innerHTML = '';
        document.getElementById('viewCreatedAt').innerHTML = '';
        document.getElementById('viewModal').classList.add('active');
        
        fetch(window.location.pathname + '?ajax=get_purchase&id=' + purchaseId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('viewItem').innerHTML = data.item;
                    document.getElementById('viewCategory').innerHTML = data.category_formatted || '<span class="category-badge">' + data.category + '</span>';
                    document.getElementById('viewUnitCost').innerHTML = data.individual_cost_formatted;
                    document.getElementById('viewQuantity').innerHTML = data.amount;
                    document.getElementById('viewTotalCost').innerHTML = data.total_cost_formatted;
                    document.getElementById('viewDate').innerHTML = data.purchase_date_formatted;
                    document.getElementById('viewCreatedAt').innerHTML = data.created_at_formatted;
                } else {
                    alert('Error: ' + (data.error || 'Could not load purchase details'));
                    closeViewModal();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load purchase details. Please try again.');
                closeViewModal();
            });
    }
    
    function closeViewModal() {
        document.getElementById('viewModal').classList.remove('active');
    }
    
    // Edit Modal Functions
    function openEditModal(purchaseId) {
        // Show loading state
        document.getElementById('editItem').value = 'Loading...';
        document.getElementById('editItem').disabled = true;
        document.getElementById('editCategory').value = 'Loading...';
        document.getElementById('editCategory').disabled = true;
        document.getElementById('editIndividualCost').value = '';
        document.getElementById('editIndividualCost').disabled = true;
        document.getElementById('editAmount').value = '';
        document.getElementById('editAmount').disabled = true;
        document.getElementById('editPurchaseDate').value = '';
        document.getElementById('editPurchaseDate').disabled = true;
        document.getElementById('editModal').classList.add('active');
        
        fetch(window.location.pathname + '?ajax=get_purchase&id=' + purchaseId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editId').value = data.id;
                    document.getElementById('editItem').value = data.item;
                    document.getElementById('editItem').disabled = false;
                    document.getElementById('editCategory').value = data.category;
                    document.getElementById('editCategory').disabled = false;
                    document.getElementById('editIndividualCost').value = data.individual_cost;
                    document.getElementById('editIndividualCost').disabled = false;
                    document.getElementById('editAmount').value = data.amount;
                    document.getElementById('editAmount').disabled = false;
                    document.getElementById('editPurchaseDate').value = data.purchase_date;
                    document.getElementById('editPurchaseDate').disabled = false;
                } else {
                    alert('Error: ' + (data.error || 'Could not load purchase details for editing'));
                    closeEditModal();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load purchase details. Please try again.');
                closeEditModal();
            });
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
    }
    
    // Delete Modal Functions
    function openDeleteModal(purchaseId, itemName) {
        document.getElementById('deleteId').value = purchaseId;
        document.getElementById('deleteItemName').textContent = itemName;
        document.getElementById('deleteModal').classList.add('active');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    }
    
    // Keep filters when navigating pagination with modals
    function preserveFilters() {
        const filterParams = new URLSearchParams(window.location.search);
        const paginationLinks = document.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            let url = new URL(link.href, window.location.origin);
            filterParams.forEach((value, key) => {
                if (key !== 'page' && !url.searchParams.has(key)) {
                    url.searchParams.append(key, value);
                }
            });
            link.href = url.toString();
        });
    }
    
    document.addEventListener('DOMContentLoaded', preserveFilters);
</script>

<?php
if (isset($historyStmt)) $historyStmt->close();
if (isset($conn) && $conn instanceof mysqli) $conn->close();
?>
</body>
</html>