<?php
include("../../db.php");

session_start();
if ($_SESSION['role'] !== 'admin') { die("Access denied!"); }

// Handle form actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $id_number = trim($_POST['id_number']);
    $phone_number = !empty($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
    $home_address = !empty($_POST['home_address']) ? trim($_POST['home_address']) : null;
    $password = trim($_POST['password']);
    $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

    if ($action === "create") {
        $stmt = $conn->prepare("INSERT INTO users (username, email, id_number, phone_number, home_address, password, role) VALUES (?, ?, ?, ?, ?, ?, 'librarian')");
        $stmt->bind_param("ssssss", $username, $email, $id_number, $phone_number, $home_address, $hashedPassword);
        $msg = $stmt->execute() ? "✅ Librarian created successfully!" : "❌ Error: " . $stmt->error;
        $stmt->close();
    }

    if ($action === "update") {
        if ($hashedPassword) {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone_number=?, home_address=?, password=? WHERE id_number=? AND role='librarian'");
            $stmt->bind_param("ssssss", $username, $email, $phone_number, $home_address, $hashedPassword, $id_number);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone_number=?, home_address=? WHERE id_number=? AND role='librarian'");
            $stmt->bind_param("sssss", $username, $email, $phone_number, $home_address, $id_number);
        }
        $msg = $stmt->execute() ? "✅ Librarian updated successfully!" : "❌ Error: " . $stmt->error;
        $stmt->close();
    }

    if ($action === "delete") {
        $stmt = $conn->prepare("DELETE FROM users WHERE id_number=? AND role='librarian'");
        $stmt->bind_param("s", $id_number);
        $msg = $stmt->execute() ? "✅ Librarian deleted successfully!" : "❌ Error: " . $stmt->error;
        $stmt->close();
    }
}

// Search, Sorting, and Pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'username';
$sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] == 'desc' ? 'DESC' : 'ASC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Increased to 10 records per page
$offset = ($page - 1) * $limit;

// Allowed sort columns to prevent SQL injection
$allowed_sort_columns = ['username', 'email', 'id_number', 'phone_number', 'created_at'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'username';
}

// Build search conditions
$search_conditions = "";
$params = [];
$types = "";

if (!empty($search)) {
    $search_conditions = " AND (username LIKE ? OR email LIKE ? OR id_number LIKE ? OR phone_number LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
    $types .= "ssss";
}

// Main query with sorting
$sql = "SELECT username, email, id_number, phone_number, home_address, created_at 
        FROM users 
        WHERE role='librarian' 
        $search_conditions 
        ORDER BY $sort_by $sort_order 
        LIMIT ? OFFSET ?";

// Prepare the statement with dynamic parameters
$stmt = $conn->prepare($sql);
array_push($params, $limit, $offset);
$types .= "ii";

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Count total records for pagination
$countSql = "SELECT COUNT(*) as total FROM users WHERE role='librarian' $search_conditions";
$countStmt = $conn->prepare($countSql);

if (!empty($search)) {
    // Remove the LIMIT and OFFSET params for count query
    $countParams = array_slice($params, 0, -2);
    $countTypes = substr($types, 0, -2);
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$total = $countResult->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Get unique search suggestions
$suggestions = [];
if (!empty($search)) {
    $suggestSql = "SELECT DISTINCT username, email, id_number FROM users WHERE role='librarian' AND (username LIKE ? OR email LIKE ? OR id_number LIKE ?) LIMIT 5";
    $suggestStmt = $conn->prepare($suggestSql);
    $suggestLike = "%$search%";
    $suggestStmt->bind_param("sss", $suggestLike, $suggestLike, $suggestLike);
    $suggestStmt->execute();
    $suggestResult = $suggestStmt->get_result();
    while($row = $suggestResult->fetch_assoc()) {
        $suggestions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Librarians</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .sidebar {
            background: #2c3e50;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            gap: 15px;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .sidebar a:hover {
            background: #34495e;
        }
        
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        
        th { 
            background: #34495e;
            color: white;
            font-weight: 600;
            position: relative;
            user-select: none;
        }
        
        th a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        th a:hover {
            text-decoration: underline;
        }
        
        .sort-icon {
            font-size: 12px;
            opacity: 0.7;
        }
        
        tr:nth-child(even) { 
            background: #f9f9f9; 
        }
        
        tr:hover { 
            background: #f0f0f0; 
            transition: background 0.2s;
        }
        
        input[type=text], 
        input[type=email], 
        input[type=password], 
        input[type=tel], 
        textarea, 
        select { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 4px;
            font-size: 14px;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52,152,219,0.3);
        }
        
        button { 
            margin: 2px; 
            padding: 6px 12px; 
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .msg { 
            margin: 10px 0; 
            padding: 12px;
            border-radius: 4px;
            font-weight: bold;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .msg-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .msg-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-container { 
            max-width: 500px; 
            margin: 0 auto 30px auto;
            padding: 25px; 
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        
        .form-container h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .optional-field { 
            color: #6c757d; 
            font-size: 0.85em; 
            display: block;
            margin-top: -8px;
            margin-bottom: 10px;
        }
        
        .search-section {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            position: relative;
            min-width: 250px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 35px 10px 10px;
            border: 2px solid #ddd;
            font-size: 14px;
        }
        
        .search-box input:focus {
            border-color: #3498db;
        }
        
        .search-box .clear-search {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-weight: bold;
        }
        
        .search-box .clear-search:hover {
            color: #333;
        }
        
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .search-suggestions div {
            padding: 8px 10px;
            cursor: pointer;
        }
        
        .search-suggestions div:hover {
            background: #f0f0f0;
        }
        
        .search-stats {
            color: #6c757d;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .pagination { 
            margin-top: 30px; 
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span { 
            margin: 0 3px; 
            text-decoration: none; 
            padding: 8px 12px; 
            background: #fff;
            border: 1px solid #ddd;
            color: #333;
            border-radius: 4px;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .pagination a:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination .active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        
        .btn-create {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            font-size: 14px;
        }
        
        .btn-update {
            background: #007bff;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .export-buttons {
            margin-left: auto;
            display: flex;
            gap: 10px;
        }
        
        .export-btn {
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            font-size: 13px;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-new {
            background: #28a745;
            color: white;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px;
            }
            
            .search-section {
                flex-direction: column;
            }
            
            .export-buttons {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <a href="../../dashboards/admin.php">📊 Dashboard</a>
        <a href="../../logout.php">🚪 Logout</a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="msg <?php echo strpos($msg, 'successfully') !== false ? 'msg-success' : 'msg-error'; ?>">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <h2>➕ Create New Librarian</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="id_number" placeholder="ID Number" required>
            <input type="tel" name="phone_number" placeholder="Phone Number (Optional)">
            <small class="optional-field">Optional contact number</small>
            <textarea name="home_address" placeholder="Home Address (Optional)" rows="3"></textarea>
            <small class="optional-field">Optional home address</small>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="action" value="create" class="btn-create">Create Librarian</button>
        </form>
    </div>

    <hr>

    <h2>📚 Manage Librarians</h2>
    
    <!-- Enhanced Search Section -->
    <div class="search-section">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Search by username, email, ID, or phone..." 
                   value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
            <span class="clear-search" onclick="clearSearch()">✕</span>
            <div id="suggestions" class="search-suggestions"></div>
        </div>
        <button onclick="performSearch()" style="background: #3498db; color: white; padding: 10px 20px;">Search</button>
        <button onclick="resetSearch()" style="background: #6c757d; color: white;">Reset</button>
        <div class="export-buttons">
            <button onclick="exportToCSV()" class="export-btn">📄 Export CSV</button>
            <button onclick="window.print()" class="export-btn">🖨️ Print</button>
        </div>
        <?php if (!empty($search)): ?>
            <div class="search-stats">
                Found <?php echo $total; ?> result(s) for "<strong><?php echo htmlspecialchars($search); ?></strong>"
            </div>
        <?php endif; ?>
    </div>

    <!-- Librarians Table with Sorting -->
    <table id="librariansTable">
        <thead>
            <tr>
                <th>
                    <a href="?sort_by=username&sort_order=<?php echo $sort_by == 'username' && $sort_order == 'ASC' ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>">
                        Username
                        <?php if ($sort_by == 'username'): ?>
                            <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort_by=email&sort_order=<?php echo $sort_by == 'email' && $sort_order == 'ASC' ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>">
                        Email
                        <?php if ($sort_by == 'email'): ?>
                            <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort_by=id_number&sort_order=<?php echo $sort_by == 'id_number' && $sort_order == 'ASC' ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>">
                        ID Number
                        <?php if ($sort_by == 'id_number'): ?>
                            <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>
                    <a href="?sort_by=phone_number&sort_order=<?php echo $sort_by == 'phone_number' && $sort_order == 'ASC' ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>">
                        Phone Number
                        <?php if ($sort_by == 'phone_number'): ?>
                            <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>Home Address</th>
                <th>
                    <a href="?sort_by=created_at&sort_order=<?php echo $sort_by == 'created_at' && $sort_order == 'ASC' ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>">
                        Created Date
                        <?php if ($sort_by == 'created_at'): ?>
                            <span class="sort-icon"><?php echo $sort_order == 'ASC' ? '↑' : '↓'; ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php 
                $rowIndex = 0;
                while($row = $result->fetch_assoc()): 
                    $isNew = (strtotime($row['created_at']) > strtotime('-7 days'));
                ?>
                <tr>
                    <form method="POST" class="update-form">
                        <td>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($row['username']); ?>" required>
                        </td>
                        <td>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                         </td>
                        <td>
                            <input type="text" name="id_number" value="<?php echo htmlspecialchars($row['id_number']); ?>" readonly 
                                   style="background:#f9f9f9; font-weight:bold;">
                         </td>
                        <td>
                            <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($row['phone_number'] ?? ''); ?>">
                         </td>
                        <td>
                            <textarea name="home_address" rows="2"><?php echo htmlspecialchars($row['home_address'] ?? ''); ?></textarea>
                         </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            <?php if ($isNew): ?>
                                <span class="badge badge-new">NEW</span>
                            <?php endif; ?>
                         </td>
                        <td style="white-space: nowrap;">
                            <input type="password" name="password" placeholder="New password" style="width:120px; margin-bottom:5px;">
                            <button type="submit" name="action" value="update" class="btn-update">Update</button>
                            <button type="submit" name="action" value="delete" onclick="return confirm('Delete <?php echo htmlspecialchars($row['username']); ?> permanently?');" class="btn-delete">Delete</button>
                         </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:40px;">
                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486756.png" alt="No data" style="width:80px; opacity:0.5;">
                        <p style="margin-top:10px; color:#999;">No librarians found</p>
                        <p style="font-size:12px; color:#ccc;">Try adjusting your search or create a new librarian</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Enhanced Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=1&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>">« First</a>
            <a href="?page=<?php echo $page-1; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>">‹ Previous</a>
        <?php else: ?>
            <span class="disabled">« First</span>
            <span class="disabled">‹ Previous</span>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        if ($startPage > 1) {
            echo "<span>...</span>";
        }
        
        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <a href="?page=<?php echo $i; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>" 
               class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor;
        
        if ($endPage < $totalPages) {
            echo "<span>...</span>";
        }
        ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>">Next ›</a>
            <a href="?page=<?php echo $totalPages; ?>&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>">Last »</a>
        <?php else: ?>
            <span class="disabled">Next ›</span>
            <span class="disabled">Last »</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 20px; text-align: center; color: #6c757d; font-size: 12px;">
        Showing <?php echo $result->num_rows; ?> of <?php echo $total; ?> librarians
    </div>
</div>

<script>
// Live search functionality
const searchInput = document.getElementById('searchInput');
const suggestionsDiv = document.getElementById('suggestions');
let searchTimeout;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length >= 2) {
        searchTimeout = setTimeout(() => {
            fetchSuggestions(query);
        }, 300);
    } else {
        suggestionsDiv.style.display = 'none';
    }
});

function fetchSuggestions(query) {
    fetch(`get_suggestions.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                suggestionsDiv.innerHTML = data.map(item => 
                    `<div onclick="selectSuggestion('${item.username}')">
                        <strong>${item.username}</strong> - ${item.email} (${item.id_number})
                    </div>`
                ).join('');
                suggestionsDiv.style.display = 'block';
            } else {
                suggestionsDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            suggestionsDiv.style.display = 'none';
        });
}

function selectSuggestion(value) {
    searchInput.value = value;
    suggestionsDiv.style.display = 'none';
    performSearch();
}

function performSearch() {
    const searchValue = searchInput.value.trim();
    window.location.href = `?search=${encodeURIComponent(searchValue)}&sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>`;
}

function clearSearch() {
    searchInput.value = '';
    performSearch();
}

function resetSearch() {
    window.location.href = window.location.pathname;
}

function exportToCSV() {
    const table = document.getElementById('librariansTable');
    let csv = [];
    
    // Get headers
    const headers = [];
    const headerCells = table.querySelectorAll('thead th');
    headerCells.forEach(cell => {
        // Skip Actions column
        if (cell.innerText !== 'Actions') {
            headers.push(cell.innerText.replace(/[↑↓]/g, '').trim());
        }
    });
    csv.push(headers.join(','));
    
    // Get data rows
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td');
        // Skip the last column (Actions)
        for(let i = 0; i < cells.length - 1; i++) {
            let cellText = cells[i].innerText.trim();
            // Remove NEW badge
            cellText = cellText.replace('NEW', '').trim();
            // Wrap in quotes if contains comma
            if (cellText.includes(',')) {
                cellText = `"${cellText}"`;
            }
            rowData.push(cellText);
        }
        csv.push(rowData.join(','));
    });
    
    // Download CSV
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `librarians_export_<?php echo date('Y-m-d'); ?>.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
        suggestionsDiv.style.display = 'none';
    }
});

// Keyboard navigation
searchInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        performSearch();
    }
});
</script>

</body>
</html>
<?php $conn->close(); ?>

