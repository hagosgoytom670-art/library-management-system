<?php
include("../../includes/auth.php");
include("../../db.php");

if ($_SESSION['role'] !== "librarian" && $_SESSION['role'] !== "admin") {
    die("Access Denied");
}

// =============================================================================
// Handle AJAX Requests
// =============================================================================

// Get book details for view modal
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_book' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $bookId = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    if ($book) {
        echo json_encode([
            'success' => true,
            'id' => $book['id'],
            'title' => $book['title'],
            'author' => $book['author'] ?? 'N/A',
            'category' => $book['category'] ?? 'General',
            'isbn' => $book['isbn'] ?? 'N/A',
            'publisher' => $book['publisher'] ?? 'N/A',
            'publication_year' => $book['publication_year'] ?? 'N/A',
            'shelf_location' => $book['shelf_location'] ?? 'A-01',
            'available' => $book['available'] ?? 0,
            'total_copies' => $book['total_copies'] ?? 0,
            'description' => $book['description'] ?? 'No description',
            'edition' => $book['edition'] ?? 'N/A'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Book not found']);
    }
    $conn->close();
    exit;
}

// Check book borrows for edit/delete
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_borrows' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $bookId = (int)$_GET['id'];
    
    $borrowStmt = $conn->prepare("
        SELECT b.*, u.full_name 
        FROM borrows b
        JOIN users u ON b.user_id = u.id
        WHERE b.book_id = ? AND b.status = 'borrowed'
    ");
    $borrowStmt->bind_param("i", $bookId);
    $borrowStmt->execute();
    $borrowResult = $borrowStmt->get_result();
    
    $borrowers = [];
    while ($row = $borrowResult->fetch_assoc()) {
        $borrowers[] = [
            'full_name' => $row['full_name'],
            'borrow_date' => date('M d, Y', strtotime($row['borrow_date'])),
            'due_date' => date('M d, Y', strtotime($row['due_date']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'has_active_borrows' => count($borrowers) > 0,
        'borrow_count' => count($borrowers),
        'borrowers' => $borrowers
    ]);
    $conn->close();
    exit;
}

// Handle delete book
if (isset($_POST['ajax']) && $_POST['ajax'] === 'delete_book' && isset($_POST['id'])) {
    header('Content-Type: application/json');
    $bookId = (int)$_POST['id'];
    
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM borrows WHERE book_id = ? AND status = 'borrowed'");
    $checkStmt->bind_param("i", $bookId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $borrowCount = $checkResult->fetch_assoc()['count'];
    
    if ($borrowCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Book is currently borrowed']);
        $conn->close();
        exit;
    }
    
    $deleteStmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $deleteStmt->bind_param("i", $bookId);
    
    if ($deleteStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }
    $conn->close();
    exit;
}

// =============================================================================
// Main Page Logic
// =============================================================================

$selected_dept = isset($_GET['department']) ? $_GET['department'] : '';
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$isbn_filter = $_GET['isbn'] ?? '';
$availability_filter = $_GET['availability'] ?? '';

$sql = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = "";

if (!empty($selected_dept)) {
    $sql .= " AND category = ?";
    $params[] = $selected_dept;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR category LIKE ? OR isbn LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if (!empty($category_filter)) {
    $sql .= " AND category LIKE ?";
    $params[] = "%$category_filter%";
    $types .= "s";
}

if (!empty($isbn_filter)) {
    $sql .= " AND isbn LIKE ?";
    $params[] = "%$isbn_filter%";
    $types .= "s";
}

if ($availability_filter == 'available') {
    $sql .= " AND available > 0";
} elseif ($availability_filter == 'unavailable') {
    $sql .= " AND available = 0";
}

$sql .= " ORDER BY title ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_available = $conn->query("SELECT SUM(available) as total FROM books")->fetch_assoc()['total'];
$total_borrowed = $conn->query("SELECT SUM(total_copies - available) as total FROM books")->fetch_assoc()['total'];

$departments = [
    ['name' => 'IT', 'icon' => '💻', 'color' => '#3498db'],
    ['name' => 'CS', 'icon' => '🖥️', 'color' => '#2ecc71'],
    ['name' => 'Medical', 'icon' => '🏥', 'color' => '#e91e63'],
    ['name' => 'Nursing', 'icon' => '🩺', 'color' => '#00bcd4'],
    ['name' => 'Management', 'icon' => '📊', 'color' => '#34495e'],
    ['name' => 'Geography', 'icon' => '🌍', 'color' => '#2c3e50'],
    ['name' => 'ECE', 'icon' => '📡', 'color' => '#e74c3c'],
    ['name' => 'ME', 'icon' => '⚙️', 'color' => '#f39c12'],
    ['name' => 'AE', 'icon' => '✈️', 'color' => '#1abc9c'],
    ['name' => 'Accounting', 'icon' => '💰', 'color' => '#16a085'],
    ['name' => 'Economics', 'icon' => '📈', 'color' => '#27ae60'],
    ['name' => 'Midwifery', 'icon' => '🤰', 'color' => '#9b59b6'],
    ['name' => 'Agro Economics', 'icon' => '🌾', 'color' => '#8e44ad'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Catalog - Library System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #6c757d;
            font-size: 14px;
        }
        
        .selected-dept-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .selected-dept-badge a {
            color: white;
            margin-left: 10px;
            text-decoration: none;
        }
        
        .selected-dept-badge a:hover {
            text-decoration: underline;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            color: white;
        }
        
        .stat-card.books { background: linear-gradient(135deg, #3498db, #2980b9); }
        .stat-card.available { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.borrowed { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .dept-section {
            margin-bottom: 30px;
        }
        
        .dept-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .dept-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 15px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .dept-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--dept-color);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .dept-card:hover::before {
            transform: scaleX(1);
        }
        
        .dept-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .dept-card.active {
            border-color: #3498db;
            background: linear-gradient(135deg, #3498db15, #2980b915);
        }
        
        .dept-icon {
            font-size: 40px;
            margin-bottom: 8px;
        }
        
        .dept-name {
            font-weight: 600;
            font-size: 13px;
            color: #2c3e50;
        }
        
        .dept-count {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 5px;
            color: #495057;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #2c3e50;
            color: white;
            font-weight: 600;
            font-size: 13px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-available {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-low-stock {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-out-stock {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-view {
            background: #3498db;
            color: white;
        }
        
        .btn-view:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-edit {
            background: #f39c12;
            color: white;
        }
        
        .btn-edit:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            width: 90%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header.warning {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .close, .close-warning {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover, .close-warning:hover {
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .detail-label {
            font-weight: bold;
            width: 130px;
            color: #2c3e50;
        }
        
        .detail-value {
            flex: 1;
            color: #555;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .warning-box i {
            color: #ffc107;
            margin-right: 10px;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 20px;
        }
        
        .loading-spinner i {
            font-size: 40px;
            color: #3498db;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .no-results {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        .borrowers-list {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
        }
        
        .borrower-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .toast-message {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #333;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .toast-message.success { background: #27ae60; }
        .toast-message.error { background: #e74c3c; }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .dept-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .detail-label {
                width: 100px;
                font-size: 12px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="../../dashboards/librarian.php" class="back-link">← Back to Dashboard</a>
        <h1>📚 Book Catalog</h1>
        <p class="subtitle">Browse books by department or search the complete library collection</p>
        <?php if($selected_dept): ?>
            <div class="selected-dept-badge">
                📁 Currently viewing: <?php echo htmlspecialchars($selected_dept); ?> Department
                <a href="catalog_book.php">✖ Clear</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card books">
            <div class="stat-number"><?php echo $total_books; ?></div>
            <div class="stat-label">Total Books</div>
        </div>
        <div class="stat-card available">
            <div class="stat-number"><?php echo $total_available ?: 0; ?></div>
            <div class="stat-label">Available Copies</div>
        </div>
        <div class="stat-card borrowed">
            <div class="stat-number"><?php echo $total_borrowed ?: 0; ?></div>
            <div class="stat-label">Currently Borrowed</div>
        </div>
    </div>
    
    <div class="dept-section">
        <div class="dept-title">
            <span>🏛️</span>
            <span>Browse by Department</span>
        </div>
        <div class="dept-grid">
            <?php foreach($departments as $dept): 
                $count_sql = "SELECT COUNT(*) as count FROM books WHERE category = ?";
                $count_stmt = $conn->prepare($count_sql);
                $count_stmt->bind_param("s", $dept['name']);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $book_count = $count_result->fetch_assoc()['count'];
                $count_stmt->close();
            ?>
                <div class="dept-card <?php echo ($selected_dept == $dept['name']) ? 'active' : ''; ?>" 
                     style="--dept-color: <?php echo $dept['color']; ?>"
                     onclick="window.location.href='catalog_book.php?department=<?php echo urlencode($dept['name']); ?>'">
                    <div class="dept-icon"><?php echo $dept['icon']; ?></div>
                    <div class="dept-name"><?php echo htmlspecialchars($dept['name']); ?></div>
                    <div class="dept-count"><?php echo $book_count; ?> books</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="filter-section">
        <form method="GET" id="filterForm">
            <?php if($selected_dept): ?>
                <input type="hidden" name="department" value="<?php echo htmlspecialchars($selected_dept); ?>">
            <?php endif; ?>
            <div class="filter-grid">
                <div class="filter-group">
                    <label>🔍 Search</label>
                    <input type="text" name="search" placeholder="Title, Author, Category, ISBN..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>📂 Category</label>
                    <input type="text" name="category" placeholder="Category..." 
                           value="<?php echo htmlspecialchars($category_filter); ?>">
                </div>
                <div class="filter-group">
                    <label>📖 ISBN</label>
                    <input type="text" name="isbn" placeholder="ISBN..." 
                           value="<?php echo htmlspecialchars($isbn_filter); ?>">
                </div>
                <div class="filter-group">
                    <label>📊 Availability</label>
                    <select name="availability">
                        <option value="">All Books</option>
                        <option value="available" <?php echo $availability_filter == 'available' ? 'selected' : ''; ?>>Available Now</option>
                        <option value="unavailable" <?php echo $availability_filter == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>
                <div class="filter-group button-group">
                    <button type="submit" class="btn-primary">🔍 Apply</button>
                    <a href="catalog_book.php<?php echo $selected_dept ? '?department=' . urlencode($selected_dept) : ''; ?>" class="btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Shelf</th>
                    <th>ISBN</th>
                    <th>Available</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res && $res->num_rows > 0): ?>
                    <?php while($r = $res->fetch_assoc()): 
                        $status_class = $r['available'] <= 0 ? 'badge-out-stock' : ($r['available'] <= ($r['total_copies'] / 3) ? 'badge-low-stock' : 'badge-available');
                        $status_text = $r['available'] <= 0 ? 'Out of Stock' : ($r['available'] <= ($r['total_copies'] / 3) ? 'Low Stock' : 'Available');
                    ?>
                        <tr>
                            <td><?php echo $r['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($r['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['author'] ?? 'N/A'); ?></td>
                            <td><span style="background:#e3f2fd; padding:4px 8px; border-radius:12px; font-size:12px;"><?php echo htmlspecialchars($r['category'] ?? 'General'); ?></span></td>
                            <td><span style="background:#f3e5f5; padding:4px 8px; border-radius:12px; font-size:12px;"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($r['shelf_location'] ?? 'A-01'); ?></span></td>
                            <td><?php echo htmlspecialchars($r['isbn'] ?? 'N/A'); ?></td>
                            <td><?php echo $r['available']; ?> / <?php echo $r['total_copies']; ?></td>
                            <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon btn-view" onclick="showBookDetails(<?php echo $r['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="btn-icon btn-edit" onclick="checkAndEditBook(<?php echo $r['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-icon btn-delete" onclick="checkAndDeleteBook(<?php echo $r['id']; ?>, '<?php echo addslashes($r['title']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="no-results">📭 No books found matching your criteria</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="bookModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-book"></i> Book Details</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></div>
        </div>
    </div>
</div>

<div id="warningModal" class="modal">
    <div class="modal-content">
        <div class="modal-header warning">
            <h3><i class="fas fa-exclamation-triangle"></i> Cannot Perform Action</h3>
            <span class="close-warning">&times;</span>
        </div>
        <div class="modal-body" id="warningModalBody"></div>
    </div>
</div>

<script>
    var modal = document.getElementById('bookModal');
    var warningModal = document.getElementById('warningModal');
    var closeBtn = document.querySelector('#bookModal .close');
    var closeWarningBtn = document.querySelector('#warningModal .close-warning');
    
    if(closeBtn) closeBtn.onclick = function() { modal.style.display = 'none'; }
    if(closeWarningBtn) closeWarningBtn.onclick = function() { warningModal.style.display = 'none'; }
    
    window.onclick = function(event) {
        if (event.target == modal) modal.style.display = 'none';
        if (event.target == warningModal) warningModal.style.display = 'none';
    }
    
    function showToast(message, type) {
        var toast = document.createElement('div');
        toast.className = 'toast-message ' + type;
        toast.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i> ' + message;
        document.body.appendChild(toast);
        setTimeout(function() {
            if(toast && toast.remove) toast.remove();
        }, 3000);
    }
    
    function showBookDetails(bookId) {
        modal.style.display = 'block';
        document.getElementById('modalBody').innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><p>Loading...</p></div>';
        
        var xhr = new XMLHttpRequest();
        xhr.open('GET', window.location.pathname + '?ajax=get_book&id=' + bookId, true);
        xhr.timeout = 10000;
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        let statusHtml = data.available <= 0 ? '<span style="color:#e74c3c;">Out of Stock</span>' : 
                                       (data.available <= (data.total_copies / 3) ? '<span style="color:#f39c12;">Low Stock</span>' : 
                                       '<span style="color:#27ae60;">Available</span>');
                        
                        document.getElementById('modalBody').innerHTML = `
                            <div class="detail-row"><div class="detail-label">Title:</div><div class="detail-value"><strong>${escapeHtml(data.title)}</strong></div></div>
                            <div class="detail-row"><div class="detail-label">Author:</div><div class="detail-value">${escapeHtml(data.author) || 'N/A'}</div></div>
                            <div class="detail-row"><div class="detail-label">Category:</div><div class="detail-value">${escapeHtml(data.category) || 'General'}</div></div>
                            <div class="detail-row"><div class="detail-label">ISBN:</div><div class="detail-value">${escapeHtml(data.isbn) || 'N/A'}</div></div>
                            <div class="detail-row"><div class="detail-label">Publisher:</div><div class="detail-value">${escapeHtml(data.publisher) || 'N/A'}</div></div>
                            <div class="detail-row"><div class="detail-label">Year:</div><div class="detail-value">${escapeHtml(data.publication_year) || 'N/A'}</div></div>
                            <div class="detail-row"><div class="detail-label">Edition:</div><div class="detail-value">${escapeHtml(data.edition) || 'N/A'}</div></div>
                            <div class="detail-row"><div class="detail-label">Shelf Location:</div><div class="detail-value">${escapeHtml(data.shelf_location) || 'A-01'}</div></div>
                            <div class="detail-row"><div class="detail-label">Copies:</div><div class="detail-value">${data.available} available / ${data.total_copies} total</div></div>
                            <div class="detail-row"><div class="detail-label">Status:</div><div class="detail-value">${statusHtml}</div></div>
                            <div class="detail-row"><div class="detail-label">Description:</div><div class="detail-value">${escapeHtml(data.description) || 'No description'}</div></div>
                        `;
                    } else {
                        document.getElementById('modalBody').innerHTML = '<div style="color:#e74c3c; text-align:center;">❌ Unable to load book details</div>';
                    }
                } catch(e) {
                    document.getElementById('modalBody').innerHTML = '<div style="color:#e74c3c; text-align:center;">❌ Unable to load book details</div>';
                }
            } else {
                document.getElementById('modalBody').innerHTML = '<div style="color:#e74c3c; text-align:center;">❌ Unable to load book details</div>';
            }
        };
        
        xhr.onerror = function() {
            document.getElementById('modalBody').innerHTML = '<div style="color:#e74c3c; text-align:center;">❌ Unable to load book details</div>';
        };
        
        xhr.ontimeout = function() {
            document.getElementById('modalBody').innerHTML = '<div style="color:#e74c3c; text-align:center;">❌ Request timed out. Please try again.</div>';
        };
        
        xhr.send();
    }
    
    function checkAndEditBook(bookId) {
        var loadingDiv = document.createElement('div');
        loadingDiv.id = 'loadingMsg';
        loadingDiv.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:10px; z-index:9999; box-shadow:0 0 20px rgba(0,0,0,0.3);';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking book status...';
        document.body.appendChild(loadingDiv);
        
        var xhr = new XMLHttpRequest();
        xhr.open('GET', window.location.pathname + '?ajax=check_borrows&id=' + bookId, true);
        xhr.timeout = 10000;
        
        xhr.onload = function() {
            if(document.getElementById('loadingMsg')) document.getElementById('loadingMsg').remove();
            
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success && data.has_active_borrows) {
                        var borrowersList = '';
                        if (data.borrowers && data.borrowers.length > 0) {
                            borrowersList = '<div class="borrowers-list">';
                            for(var i = 0; i < data.borrowers.length; i++) {
                                var b = data.borrowers[i];
                                borrowersList += '<div class="borrower-item">• <strong>' + escapeHtml(b.full_name) + '</strong><br><small>📅 Borrowed: ' + b.borrow_date + '<br>⏰ Due: ' + b.due_date + '</small></div>';
                            }
                            borrowersList += '</div>';
                        } else {
                            borrowersList = '<p>Currently borrowed</p>';
                        }
                        
                        document.getElementById('warningModalBody').innerHTML = `
                            <div class="warning-box"><i class="fas fa-exclamation-triangle"></i> <strong>Cannot Edit Book!</strong></div>
                            <p>This book has <strong>${data.borrow_count}</strong> active borrow(s) and cannot be edited.</p>
                            <p>Please wait until all copies are returned before making changes.</p>
                            <div class="warning-box"><i class="fas fa-users"></i> <strong>Current Borrowers:</strong><br>${borrowersList}</div>
                            <div style="text-align:center; margin-top:20px;">
                                <button class="btn-icon" onclick="closeWarningModal()" style="background:#3498db; color:white; border:none; padding:8px 20px; border-radius:5px; cursor:pointer;">OK, Got it</button>
                            </div>
                        `;
                        warningModal.style.display = 'block';
                    } else if (data.success && !data.has_active_borrows) {
                        if(confirm('✏️ Do you want to edit this book?')) {
                            window.location.href = 'edit_book.php?id=' + bookId;
                        }
                    } else {
                        if(confirm('⚠️ Unable to verify book status.\n\nDo you want to edit this book anyway?')) {
                            window.location.href = 'edit_book.php?id=' + bookId;
                        }
                    }
                } catch(e) {
                    if(confirm('⚠️ Unable to verify book status.\n\nDo you want to edit this book anyway?')) {
                        window.location.href = 'edit_book.php?id=' + bookId;
                    }
                }
            } else {
                if(confirm('⚠️ Unable to verify book status.\n\nDo you want to edit this book anyway?')) {
                    window.location.href = 'edit_book.php?id=' + bookId;
                }
            }
        };
        
        xhr.onerror = function() {
            if(document.getElementById('loadingMsg')) document.getElementById('loadingMsg').remove();
            if(confirm('⚠️ Unable to verify book status.\n\nDo you want to edit this book anyway?')) {
                window.location.href = 'edit_book.php?id=' + bookId;
            }
        };
        
        xhr.ontimeout = function() {
            if(document.getElementById('loadingMsg')) document.getElementById('loadingMsg').remove();
            if(confirm('⚠️ Unable to verify book status.\n\nDo you want to edit this book anyway?')) {
                window.location.href = 'edit_book.php?id=' + bookId;
            }
        };
        
        xhr.send();
    }
    
    function checkAndDeleteBook(bookId, bookTitle) {
        var loadingDiv = document.createElement('div');
        loadingDiv.id = 'loadingMsg';
        loadingDiv.style.cssText = 'position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:10px; z-index:9999; box-shadow:0 0 20px rgba(0,0,0,0.3);';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking book status...';
        document.body.appendChild(loadingDiv);
        
        var xhr = new XMLHttpRequest();
        xhr.open('GET', window.location.pathname + '?ajax=check_borrows&id=' + bookId, true);
        xhr.timeout = 10000;
        
        xhr.onload = function() {
            if(document.getElementById('loadingMsg')) document.getElementById('loadingMsg').remove();
            
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success && data.has_active_borrows) {
                        var borrowersList = '';
                        if (data.borrowers && data.borrowers.length > 0) {
                            borrowersList = '<div class="borrowers-list">';
                            for(var i = 0; i < data.borrowers.length; i++) {
                                var b = data.borrowers[i];
                                borrowersList += '<div class="borrower-item">• <strong>' + escapeHtml(b.full_name) + '</strong><br><small>📅 Borrowed: ' + b.borrow_date + '<br>⏰ Due: ' + b.due_date + '</small></div>';
                            }
                            borrowersList += '</div>';
                        } else {
                            borrowersList = '<p>Currently borrowed</p>';
                        }
                        
                        document.getElementById('warningModalBody').innerHTML = `
                            <div class="warning-box"><i class="fas fa-exclamation-triangle"></i> <strong>Cannot Delete Book!</strong></div>
                            <p>The book <strong>"${escapeHtml(bookTitle)}"</strong> has <strong>${data.borrow_count}</strong> active borrow(s) and cannot be deleted.</p>
                            <p>Please wait until all copies are returned before deleting.</p>
                            <div class="warning-box"><i class="fas fa-users"></i> <strong>Current Borrowers:</strong><br>${borrowersList}</div>
                            <div style="text-align:center; margin-top:20px;">
                                <button class="btn-icon" onclick="closeWarningModal()" style="background:#3498db; color:white; border:none; padding:8px 20px; border-radius:5px; cursor:pointer;">OK, Got it</button>
                            </div>
                        `;
                        warningModal.style.display = 'block';
                    } else if (data.success && !data.has_active_borrows) {
                        if(confirm('⚠️ Are you sure you want to delete "' + bookTitle + '"?\n\nThis action cannot be undone!')) {
                            var formData = new FormData();
                            formData.append('ajax', 'delete_book');
                            formData.append('id', bookId);
                            
                            var deleteXhr = new XMLHttpRequest();
                            deleteXhr.open('POST', window.location.pathname, true);
                            deleteXhr.onload = function() {
                                if (deleteXhr.status === 200) {
                                    try {
                                        var deleteData = JSON.parse(deleteXhr.responseText);
                                        if(deleteData.success) { 
                                            showToast('✅ Book deleted successfully!', 'success');
                                            setTimeout(function() { location.reload(); }, 1500);
                                        } else { 
                                            showToast('❌ ' + (deleteData.message || 'Could not delete'), 'error');
                                        }
                                    } catch(e) {
                                        showToast('❌ Could not delete', 'error');
                                    }
                                } else {
                                    showToast('❌ Could not delete', 'error');
                                }
                            };
                            deleteXhr.onerror = function() {
                                showToast('❌ Could not delete', 'error');
                            };
                            deleteXhr.send(formData);
                        }
                    } else {
                        if(confirm('⚠️ Unable to verify book status for "' + bookTitle + '".\n\nDo you want to delete it anyway?')) {
                            var formData = new FormData();
                            formData.append('ajax', 'delete_book');
                            formData.append('id', bookId);
                            
                            var deleteXhr = new XMLHttpRequest();
                            deleteXhr.open('POST', window.location.pathname, true);
                            deleteXhr.onload = function() {
                                if (deleteXhr.status === 200) {
                                    try {
                                        var deleteData = JSON.parse(deleteXhr.responseText);
                                        if(deleteData.success) { 
                                            showToast('✅ Book deleted successfully!', 'success');
                                            setTimeout(function() { location.reload(); }, 1500);
                                        } else { 
                                            showToast('❌ ' + (deleteData.message || 'Could not delete'), 'error');
                                        }
                                    } catch(e) {
                                        showToast('❌ Could not delete', 'error');
                                    }
                                } else {
                                    showToast('❌ Could not delete', 'error');
                                }
                            };
                            deleteXhr.onerror = function() {
                                showToast('❌ Could not delete', 'error');
                            };
                            deleteXhr.send(formData);
                        }
                    }
                } catch(e) {
                    if(confirm('⚠️ Unable to verify book status for "' + bookTitle + '".\n\nDo you want to delete it anyway?')) {
                        var formData = new FormData();
                        formData.append('ajax', 'delete_book');
                        formData.append('id', bookId);
                        
                        var deleteXhr = new XMLHttpRequest();
                        deleteXhr.open('POST', window.location.pathname, true);
                        deleteXhr.onload = function() {
                            if (deleteXhr.status === 200) {
                                try {
                                    var deleteData = JSON.parse(deleteXhr.responseText);
                                    if(deleteData.success) { 
                                        showToast('✅ Book deleted successfully!', 'success');
                                        setTimeout(function() { location.reload(); }, 1500);
                                    } else { 
                                        showToast('❌ ' + (deleteData.message || 'Could not delete'), 'error');
                                    }
                                } catch(e) {
                                    showToast('❌ Could not delete', 'error');
                                }
                            } else {
                                showToast('❌ Could not delete', 'error');
                            }
                        };
                        deleteXhr.onerror = function() {
                            showToast('❌ Could not delete', 'error');
                        };
                        deleteXhr.send(formData);
                    }
                }
            } else {
                if(confirm('⚠️ Unable to verify book status for "' + bookTitle + '".\n\nDo you want to delete it anyway?')) {
                    var formData = new FormData();
                    formData.append('ajax', 'delete_book');
                    formData.append('id', bookId);
                    
                    var deleteXhr = new XMLHttpRequest();
                    deleteXhr.open('POST', window.location.pathname, true);
                    deleteXhr.onload = function() {
                        if (deleteXhr.status === 200) {
                            try {
                                var deleteData = JSON.parse(deleteXhr.responseText);
                                if(deleteData.success) { 
                                    showToast('✅ Book deleted successfully!', 'success');
                                    setTimeout(function() { location.reload(); }, 1500);
                                } else { 
                                    showToast('❌ ' + (deleteData.message || 'Could not delete'), 'error');
                                }
                            } catch(e) {
                                showToast('❌ Could not delete', 'error');
                            }
                        } else {
                            showToast('❌ Could not delete', 'error');
                        }
                    };
                    deleteXhr.onerror = function() {
                        showToast('❌ Could not delete', 'error');
                    };
                    deleteXhr.send(formData);
                }
            }
        };
        
        xhr.onerror = function() {
            if(document.getElementById('loadingMsg')) document.getElementById('loadingMsg').remove();
            if(confirm('⚠️ Unable to verify book status for "' + bookTitle + '".\n\nDo you want to delete it anyway?')) {
                var formData = new FormData();
                formData.append('ajax', 'delete_book');
                formData.append('id', bookId);
                
                var deleteXhr = new XMLHttpRequest();
                deleteXhr.open('POST', window.location.pathname, true);
                deleteXhr.onload = function() {
                    if (deleteXhr.status === 200) {
                        try {
                            var deleteData = JSON.parse(deleteXhr.responseText);
                            if(deleteData.success) { 
                                showToast('✅ Book deleted successfully!', 'success');
                                setTimeout(function() { location.reload(); }, 1500);
                            } else { 
                                showToast('❌ ' + (deleteData.message || 'Could not delete'), 'error');
                            }
                        } catch(e) {
                            showToast('❌ Could not delete', 'error');
                        }
                    } else {
                        showToast('❌ Could not delete', 'error');
                    }
                };
                deleteXhr.onerror = function() {
                    showToast('❌ Could not delete', 'error');
                };
                deleteXhr.send(formData);
            }
        };
        
        xhr.ontimeout = function() {
            if(document.getElementById('loadingMsg')) document.getElementById('loadingMsg').remove();
            if(confirm('⚠️ Unable to verify book status for "' + bookTitle + '".\n\nDo you want to delete it anyway?')) {
                var formData = new FormData();
                formData.append('ajax', 'delete_book');
                formData.append('id', bookId);
                
                var deleteXhr = new XMLHttpRequest();
                deleteXhr.open('POST', window.location.pathname, true);
                deleteXhr.onload = function() {
                    if (deleteXhr.status === 200) {
                        try {
                            var deleteData = JSON.parse(deleteXhr.responseText);
                            if(deleteData.success) { 
                                showToast('✅ Book deleted successfully!', 'success');
                                setTimeout(function() { location.reload(); }, 1500);
                            } else { 
                                showToast('❌ ' + (deleteData.message || 'Could not delete'), 'error');
                            }
                        } catch(e) {
                            showToast('❌ Could not delete', 'error');
                        }
                    } else {
                        showToast('❌ Could not delete', 'error');
                    }
                };
                deleteXhr.onerror = function() {
                    showToast('❌ Could not delete', 'error');
                };
                deleteXhr.send(formData);
            }
        };
        
        xhr.send();
    }
    
    function closeWarningModal() { 
        warningModal.style.display = 'none'; 
    }
    
    function escapeHtml(text) { 
        if(!text) return ''; 
        var div = document.createElement('div'); 
        div.textContent = text; 
        return div.innerHTML; 
    }
</script>
</body>
</html>