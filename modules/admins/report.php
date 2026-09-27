<?php
/**
 * Professional Purchase Report Generator with Excel Export
 */

include("../../includes/auth.php");
include("../../db.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    exit("Access denied");
}

// Handle Excel Export
if (isset($_GET['export_excel'])) {
    // Get admin ID
    $admin_id = $_SESSION['user_id'] ?? null;
    if (!$admin_id) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'admin'");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $admin_id = $user['id'] ?? null;
        $stmt->close();
    }
    
    // Get filter parameters for export
    $filterType = $_GET['filter_type'] ?? '';
    $searchItem = $_GET['search_item'] ?? '';
    $categoryText = $_GET['category_text'] ?? '';
    $selectedMonth = $_GET['month'] ?? '';
    $selectedYear = $_GET['year'] ?? date('Y') - 8;
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    
    // Build query for export
    $sql = "SELECT item, category, individual_cost, amount, total_cost, purchase_date FROM purchases WHERE admin_id = ?";
    $params = [$admin_id];
    $types = "i";
    
    if ($filterType === 'month' && !empty($selectedMonth)) {
        $monthMapping = [
            1 => ['start' => '09-11', 'end' => '10-10'], 2 => ['start' => '10-11', 'end' => '11-10'],
            3 => ['start' => '11-11', 'end' => '12-10'], 4 => ['start' => '12-11', 'end' => '01-09'],
            5 => ['start' => '01-10', 'end' => '02-08'], 6 => ['start' => '02-09', 'end' => '03-09'],
            7 => ['start' => '03-10', 'end' => '04-09'], 8 => ['start' => '04-10', 'end' => '05-09'],
            9 => ['start' => '05-10', 'end' => '06-08'], 10 => ['start' => '06-09', 'end' => '07-09'],
            11 => ['start' => '07-10', 'end' => '08-09'], 12 => ['start' => '08-10', 'end' => '09-10']
        ];
        $gregorianYear = $selectedYear + 8;
        $startYear = $gregorianYear;
        $endYear = $gregorianYear;
        if ($monthMapping[$selectedMonth]['start'] > $monthMapping[$selectedMonth]['end']) {
            $endYear++;
        }
        $startDate = $startYear . '-' . $monthMapping[$selectedMonth]['start'];
        $endDate = $endYear . '-' . $monthMapping[$selectedMonth]['end'];
        $sql .= " AND purchase_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ss';
    } elseif ($filterType === 'category' && !empty($categoryText)) {
        $sql .= " AND category = ?";
        $params[] = $categoryText;
        $types .= 's';
    } elseif ($filterType === 'item' && !empty($searchItem)) {
        $sql .= " AND item LIKE ?";
        $params[] = "%{$searchItem}%";
        $types .= 's';
    } elseif ($filterType === 'date_range' && !empty($startDate) && !empty($endDate)) {
        $sql .= " AND purchase_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= 'ss';
    }
    
    $sql .= " ORDER BY purchase_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Get totals
    $totalSpent = 0;
    $totalItems = 0;
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $totalSpent += $row['total_cost'];
        $totalItems += $row['amount'];
        $data[] = $row;
    }
    
    // Set Excel headers
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="purchase_report_' . date('Y-m-d_H-i') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Create Excel content
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>Purchase Report</title>';
    echo '<style>';
    echo 'th { background-color: #4CAF50; color: white; padding: 8px; }';
    echo 'td { padding: 6px; }';
    echo '.total-row { background-color: #f0f0f0; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<h2>Purchase Report</h2>';
    echo '<p>Generated on: ' . date('F j, Y g:i A') . '</p>';
    echo '<p>Filter: ' . ($filterType ?: 'All Purchases') . '</p>';
    echo '<br>';
    
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>Item</th>';
    echo '<th>Category</th>';
    echo '<th>Unit Cost (ብር)</th>';
    echo '<th>Quantity</th>';
    echo '<th>Total Cost (ብር)</th>';
    echo '<th>Purchase Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $counter = 1;
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>' . $counter++ . '</td>';
        echo '<td>' . htmlspecialchars($row['item']) . '</td>';
        echo '<td>' . htmlspecialchars($row['category']) . '</td>';
        echo '<td>' . number_format($row['individual_cost'], 2) . '</td>';
        echo '<td>' . $row['amount'] . '</td>';
        echo '<td>' . number_format($row['total_cost'], 2) . '</td>';
        echo '<td>' . date('M d, Y', strtotime($row['purchase_date'])) . '</td>';
        echo '</tr>';
    }
    
    echo '<tr class="total-row">';
    echo '<td colspan="5" align="right"><strong>TOTAL:</strong></td>';
    echo '<td colspan="2"><strong>' . number_format($totalSpent, 2) . ' ብር</strong></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td colspan="7"><br><strong>Summary Statistics:</strong><br>';
    echo 'Total Transactions: ' . count($data) . '<br>';
    echo 'Total Items Purchased: ' . $totalItems . '<br>';
    echo 'Total Amount Spent: ' . number_format($totalSpent, 2) . ' ብር<br>';
    echo '</td>';
    echo '</tr>';
    
    echo '</tbody>';
    echo '</table>';
    echo '</body>';
    echo '</html>';
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

// Check if purchases exist
$check_sql = "SELECT COUNT(*) as count FROM purchases WHERE admin_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $admin_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$purchase_count = $check_result->fetch_assoc()['count'];
$check_stmt->close();

// Ethiopian Calendar Functions
function getEthiopianMonths(): array {
    return [
        1 => 'Meskeram (September)',
        2 => 'Tikimt (October)',
        3 => 'Hidar (November)',
        4 => 'Tahsas (December)',
        5 => 'Tir (January)',
        6 => 'Yekatit (February)',
        7 => 'Megabit (March)',
        8 => 'Miazia (April)',
        9 => 'Ginbot (May)',
        10 => 'Sene (June)',
        11 => 'Hamle (July)',
        12 => 'Nehasie (August)'
    ];
}

function ethiopianMonthToGregorianRange(int $ethiopianYear, int $ethiopianMonth): array {
    $monthMapping = [
        1 => ['start' => '09-11', 'end' => '10-10'], 2 => ['start' => '10-11', 'end' => '11-10'],
        3 => ['start' => '11-11', 'end' => '12-10'], 4 => ['start' => '12-11', 'end' => '01-09'],
        5 => ['start' => '01-10', 'end' => '02-08'], 6 => ['start' => '02-09', 'end' => '03-09'],
        7 => ['start' => '03-10', 'end' => '04-09'], 8 => ['start' => '04-10', 'end' => '05-09'],
        9 => ['start' => '05-10', 'end' => '06-08'], 10 => ['start' => '06-09', 'end' => '07-09'],
        11 => ['start' => '07-10', 'end' => '08-09'], 12 => ['start' => '08-10', 'end' => '09-10']
    ];
    
    $gregorianYear = $ethiopianYear + 8;
    $startYear = $gregorianYear;
    $endYear = $gregorianYear;
    
    if ($monthMapping[$ethiopianMonth]['start'] > $monthMapping[$ethiopianMonth]['end']) {
        $endYear++;
    }
    
    return [
        'start' => $startYear . '-' . $monthMapping[$ethiopianMonth]['start'],
        'end' => $endYear . '-' . $monthMapping[$ethiopianMonth]['end']
    ];
}

function formatCurrency($amount): string {
    return 'ብር ' . number_format((float)$amount, 2);
}

// Get filter parameters
$filterType = $_GET['filter_type'] ?? '';
$searchItem = $_GET['search_item'] ?? '';
$categoryText = $_GET['category_text'] ?? '';
$selectedMonth = $_GET['month'] ?? '';
$selectedYear = $_GET['year'] ?? date('Y') - 8;
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Build query
$sql = "SELECT id, item, category, individual_cost, amount, total_cost, purchase_date FROM purchases WHERE admin_id = ?";
$params = [$admin_id];
$types = "i";

if ($filterType === 'month' && !empty($selectedMonth)) {
    $dateRange = ethiopianMonthToGregorianRange((int)$selectedYear, (int)$selectedMonth);
    $sql .= " AND purchase_date BETWEEN ? AND ?";
    $params[] = $dateRange['start'];
    $params[] = $dateRange['end'];
    $types .= 'ss';
    $filterDescription = "Ethiopian Month: " . getEthiopianMonths()[$selectedMonth] . " " . $selectedYear;
    
} elseif ($filterType === 'category' && !empty($categoryText)) {
    $sql .= " AND category = ?";
    $params[] = $categoryText;
    $types .= 's';
    $filterDescription = "Category: " . htmlspecialchars($categoryText);
    
} elseif ($filterType === 'item' && !empty($searchItem)) {
    $sql .= " AND item LIKE ?";
    $params[] = "%{$searchItem}%";
    $types .= 's';
    $filterDescription = "Item contains: " . htmlspecialchars($searchItem);
    
} elseif ($filterType === 'date_range' && !empty($startDate) && !empty($endDate)) {
    $sql .= " AND purchase_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';
    $filterDescription = "Date Range: " . date('M d, Y', strtotime($startDate)) . " - " . date('M d, Y', strtotime($endDate));
}

$sql .= " ORDER BY purchase_date DESC";

// Execute query
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $purchases = $stmt->get_result();
} else {
    $purchases = null;
}

// Get statistics
$statsSql = "SELECT COUNT(*) as total_transactions, COALESCE(SUM(amount), 0) as total_items, 
                    COALESCE(SUM(total_cost), 0) as total_spent, COALESCE(AVG(individual_cost), 0) as avg_cost,
                    COUNT(DISTINCT category) as unique_categories
             FROM purchases WHERE admin_id = ?";
$statsParams = [$admin_id];
$statsTypes = "i";

if ($filterType === 'month' && !empty($selectedMonth)) {
    $dateRange = ethiopianMonthToGregorianRange((int)$selectedYear, (int)$selectedMonth);
    $statsSql .= " AND purchase_date BETWEEN ? AND ?";
    $statsParams[] = $dateRange['start'];
    $statsParams[] = $dateRange['end'];
    $statsTypes .= 'ss';
} elseif ($filterType === 'category' && !empty($categoryText)) {
    $statsSql .= " AND category = ?";
    $statsParams[] = $categoryText;
    $statsTypes .= 's';
} elseif ($filterType === 'item' && !empty($searchItem)) {
    $statsSql .= " AND item LIKE ?";
    $statsParams[] = "%{$searchItem}%";
    $statsTypes .= 's';
} elseif ($filterType === 'date_range' && !empty($startDate) && !empty($endDate)) {
    $statsSql .= " AND purchase_date BETWEEN ? AND ?";
    $statsParams[] = $startDate;
    $statsParams[] = $endDate;
    $statsTypes .= 'ss';
}

$statsStmt = $conn->prepare($statsSql);
$statsStmt->bind_param($statsTypes, ...$statsParams);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// Get category breakdown
$catSql = "SELECT category, COUNT(*) as count, SUM(total_cost) as total 
           FROM purchases WHERE admin_id = ?";
$catParams = [$admin_id];
$catTypes = "i";

if ($filterType === 'month' && !empty($selectedMonth)) {
    $dateRange = ethiopianMonthToGregorianRange((int)$selectedYear, (int)$selectedMonth);
    $catSql .= " AND purchase_date BETWEEN ? AND ?";
    $catParams[] = $dateRange['start'];
    $catParams[] = $dateRange['end'];
    $catTypes .= 'ss';
} elseif ($filterType === 'category' && !empty($categoryText)) {
    $catSql .= " AND category = ?";
    $catParams[] = $categoryText;
    $catTypes .= 's';
} elseif ($filterType === 'item' && !empty($searchItem)) {
    $catSql .= " AND item LIKE ?";
    $catParams[] = "%{$searchItem}%";
    $catTypes .= 's';
} elseif ($filterType === 'date_range' && !empty($startDate) && !empty($endDate)) {
    $catSql .= " AND purchase_date BETWEEN ? AND ?";
    $catParams[] = $startDate;
    $catParams[] = $endDate;
    $catTypes .= 'ss';
}

$catSql .= " GROUP BY category ORDER BY total DESC";
$catStmt = $conn->prepare($catSql);
$catStmt->bind_param($catTypes, ...$catParams);
$catStmt->execute();
$categoryBreakdown = $catStmt->get_result();

// Get all items for autocomplete
$itemsList = $conn->prepare("SELECT DISTINCT item FROM purchases WHERE admin_id = ? ORDER BY item LIMIT 100");
$itemsList->bind_param("i", $admin_id);
$itemsList->execute();
$itemsResult = $itemsList->get_result();

// Get all categories for autocomplete
$catsList = $conn->prepare("SELECT DISTINCT category FROM purchases WHERE admin_id = ? ORDER BY category LIMIT 100");
$catsList->bind_param("i", $admin_id);
$catsList->execute();
$categoriesResult = $catsList->get_result();

// Ethiopian years
$currentEthiopianYear = (int)date('Y') - 8;
$ethiopianYears = range($currentEthiopianYear - 2, $currentEthiopianYear + 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Report | Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #1e3c5c 0%, #2a4d6e 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        
        .sidebar {
            background: #1e293b;
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: #334155;
            border-radius: 8px;
        }
        .sidebar a:hover { background: #3b82f6; }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .filter-header {
            background: #f8fafc;
            padding: 15px 25px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
        }
        .filter-body { padding: 25px; }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 8px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 600;
            color: #64748b;
            border-radius: 8px;
        }
        .filter-tab.active {
            background: #3b82f6;
            color: white;
        }
        
        .filter-panel { display: none; }
        .filter-panel.active { display: block; }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-weight: 600;
            font-size: 13px;
            color: #334155;
        }
        .form-group input, .form-group select {
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }
        .filter-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        .btn-primary, .btn-secondary, .btn-success, .btn-excel {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #e2e8f0; color: #1e293b; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-excel { background: #1f7b4d; color: white; }
        .btn-excel:hover { background: #166534; }
        
        .report-result {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 20px 25px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        .stat-label {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .table-responsive {
            overflow-x: auto;
            padding: 0 25px 20px 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }
        tr:hover td { background: #fafcff; }
        .total-row { background: #fefce8; font-weight: 700; }
        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #eef2ff;
            border-radius: 20px;
            font-size: 12px;
            color: #4338ca;
        }
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #64748b;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            padding: 20px 25px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }
        
        .debug-info {
            background: #fef3c7;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            border-left: 4px solid #f59e0b;
        }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .action-buttons { flex-direction: column; }
            .action-buttons .btn-primary,
            .action-buttons .btn-success,
            .action-buttons .btn-excel,
            .action-buttons .btn-secondary {
                text-align: center;
            }
        }
        
        @media print {
            .filter-card, .sidebar, .no-print, .action-buttons { display: none; }
            body { background: white; padding: 0; }
        }
    </style>
</head>
<body>
<div class="container">
    
    <div class="sidebar no-print">
        <a href="purchase.php">🛒 Purchases</a>
        <a href="../../dashboards/admin.php">🏠 Dashboard</a>
        <a href="../../logout.php">🚪 Logout</a>
    </div>
    
    <div class="header">
        <h1>📊 Purchase Report Generator</h1>
        <p>Generate reports by Ethiopian month, category, item, or date range. Export to Excel or Print.</p>
    </div>
    
    <!-- Debug Info (remove after testing) -->
    <?php if($purchase_count == 0): ?>
    <div class="debug-info no-print">
        <strong>⚠️ No purchases found!</strong> Please add purchases in <a href="purchase.php">Purchases page</a> first.
    </div>
    <?php endif; ?>
    
    <!-- Filter Section -->
    <div class="filter-card no-print">
        <div class="filter-header">🔍 Filter Options</div>
        <div class="filter-body">
            <div class="filter-tabs">
                <button class="filter-tab active" data-tab="month">📅 Ethiopian Month</button>
                <button class="filter-tab" data-tab="category">🏷️ Category</button>
                <button class="filter-tab" data-tab="item">📦 Item Search</button>
                <button class="filter-tab" data-tab="date">📆 Date Range</button>
            </div>
            
            <form method="GET" action="" id="reportForm">
                <input type="hidden" name="filter_type" id="filter_type" value="month">
                
                <div class="filter-panel active" id="panel-month">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label>Select Ethiopian Month</label>
                            <select name="month">
                                <option value="">-- Select Month --</option>
                                <?php foreach(getEthiopianMonths() as $num => $name): ?>
                                    <option value="<?php echo $num; ?>" <?php echo $selectedMonth == $num ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ethiopian Year</label>
                            <select name="year">
                                <?php foreach($ethiopianYears as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $selectedYear == $year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="filter-panel" id="panel-category">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label>Enter Category Name</label>
                            <input type="text" name="category_text" 
                                   placeholder="e.g., Furniture, Supplies, Software..."
                                   value="<?php echo htmlspecialchars($categoryText); ?>"
                                   list="categories-list">
                            <datalist id="categories-list">
                                <?php while($cat = $categoriesResult->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                <?php endwhile; ?>
                            </datalist>
                        </div>
                    </div>
                </div>
                
                <div class="filter-panel" id="panel-item">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label>Search by Item Name</label>
                            <input type="text" name="search_item" 
                                   placeholder="Enter item name..."
                                   value="<?php echo htmlspecialchars($searchItem); ?>"
                                   list="items-list">
                            <datalist id="items-list">
                                <?php while($item = $itemsResult->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($item['item']); ?>">
                                <?php endwhile; ?>
                            </datalist>
                        </div>
                    </div>
                </div>
                
                <div class="filter-panel" id="panel-date">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $startDate; ?>">
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" value="<?php echo $endDate; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-primary">🔍 Generate Report</button>
                    <a href="report.php" class="btn-secondary">⟳ Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Results -->
    <div class="report-result">
        <?php if ($purchases && $purchases->num_rows > 0): ?>
            <div class="report-header">
                <h3>📋 Purchase Report</h3>
                <p><?php echo $filterDescription ?? 'All Purchases'; ?></p>
                <p style="font-size: 12px; margin-top: 5px;">Generated: <?php echo date('F j, Y g:i A'); ?></p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_transactions'] ?? 0); ?></div>
                    <div class="stat-label">Transactions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_items'] ?? 0); ?></div>
                    <div class="stat-label">Items Purchased</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo formatCurrency($stats['total_spent'] ?? 0); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo formatCurrency($stats['avg_cost'] ?? 0); ?></div>
                    <div class="stat-label">Average Cost</div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Unit Cost</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; while($row = $purchases->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['item']); ?></strong></td>
                            <td><span class="category-badge"><?php echo htmlspecialchars($row['category']); ?></span></td>
                            <td><?php echo formatCurrency($row['individual_cost']); ?></td>
                            <td><?php echo $row['amount']; ?></td>
                            <td><?php echo formatCurrency($row['total_cost']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['purchase_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <tr class="total-row">
                            <td colspan="5" style="text-align: right;"><strong>GRAND TOTAL:</strong></td>
                            <td colspan="2"><strong><?php echo formatCurrency($stats['total_spent'] ?? 0); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Category Breakdown -->
            <?php if ($categoryBreakdown && $categoryBreakdown->num_rows > 1): ?>
            <div style="padding: 0 25px 25px 25px;">
                <h4 style="margin-bottom: 15px;">📊 Spending by Category</h4>
                <?php while($cat = $categoryBreakdown->fetch_assoc()): 
                    $percentage = ($stats['total_spent'] ?? 0) > 0 ? round(($cat['total'] / $stats['total_spent']) * 100, 1) : 0;
                ?>
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span><?php echo htmlspecialchars($cat['category']); ?></span>
                        <span><?php echo formatCurrency($cat['total']); ?> (<?php echo $percentage; ?>%)</span>
                    </div>
                    <div style="background: #e2e8f0; border-radius: 10px; height: 8px;">
                        <div style="width: <?php echo $percentage; ?>%; background: #3b82f6; height: 8px; border-radius: 10px;"></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons - Print and Excel Export -->
            <div class="action-buttons no-print">
                <button onclick="window.print()" class="btn-success">🖨️ Print / Save as PDF</button>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export_excel' => '1'])); ?>" class="btn-excel">📊 Download Excel</a>
                <a href="purchase.php" class="btn-secondary">← Back to Purchases</a>
            </div>
            
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($filterType != '')): ?>
            <div class="empty-state">
                <h3>📭 No purchases found</h3>
                <p>No purchase records match your filter criteria.</p>
                <p style="margin-top: 15px;">
                    <a href="report.php" class="btn-secondary">Clear Filters</a>
                    <a href="purchase.php" class="btn-primary" style="margin-left: 10px;">Add Purchase</a>
                </p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>📊 Select filters to generate report</h3>
                <p>Use the filters above to generate purchase reports.</p>
                <?php if($purchase_count == 0): ?>
                    <p style="margin-top: 10px; color: #dc2626;">
                        ⚠️ No purchases found in the database. Please add purchases first.
                    </p>
                    <a href="purchase.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">➕ Add Your First Purchase</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<script>
    // Tab switching functionality
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.filter-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const tabName = this.getAttribute('data-tab');
            document.getElementById('panel-' + tabName).classList.add('active');
            document.getElementById('filter_type').value = tabName;
        });
    });
    
    // Preserve active tab after form submission
    const urlParams = new URLSearchParams(window.location.search);
    const filterType = urlParams.get('filter_type');
    if (filterType) {
        document.querySelectorAll('.filter-tab').forEach(tab => {
            if (tab.getAttribute('data-tab') === filterType) {
                tab.click();
            }
        });
    }
</script>

</body>
</html>

<?php
// Clean up
if (isset($stmt)) $stmt->close();
if (isset($statsStmt)) $statsStmt->close();
if (isset($catStmt)) $catStmt->close();
$conn->close();
?>