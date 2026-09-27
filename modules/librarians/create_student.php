<?php
include("../../db.php");
session_start();

/* =====================
   ACCESS CONTROL
===================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'librarian') {
    die("Access denied!");
}

/* =====================
   CSRF TOKEN
===================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

/* =====================
   SEARCH & FILTERS & PAGINATION
===================== */
$search = trim($_GET['search'] ?? "");
$filter_department = trim($_GET['department'] ?? "");
$filter_year = trim($_GET['year'] ?? "");
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$message = "";
$message_type = "";

/* =====================
   Helper: handle uploaded image
===================== */
function handle_uploaded_image($fileInputName, &$message, $targetDir = "../../uploads/students/") {
    if (!isset($_FILES[$fileInputName]) || empty($_FILES[$fileInputName]['name'])) {
        return null;
    }

    $file = $_FILES[$fileInputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Error uploading profile picture.";
        return null;
    }

    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $message = "Profile picture must be less than 2MB.";
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (strpos($mime, 'image/') !== 0) {
        $message = "Uploaded file is not an image.";
        return null;
    }

    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            $message = "Failed to create upload directory.";
            return null;
        }
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed, true)) {
        $message = "Unsupported image format. Allowed: jpg, jpeg, png, gif, webp.";
        return null;
    }

    $baseName = bin2hex(random_bytes(8)) . "_" . time();
    $fileName = $baseName . "." . $ext;
    $targetFile = rtrim($targetDir, "/") . "/" . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        $message = "Error moving uploaded file.";
        return null;
    }

    return $fileName;
}

/* =====================
   CREATE / UPDATE / DELETE
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf) {
        die("Invalid CSRF token");
    }

    $action     = $_POST['action'] ?? '';
    $username   = trim($_POST['username'] ?? "");
    $email      = trim($_POST['email'] ?? "");
    $id_number  = trim($_POST['id_number'] ?? "");
    $department = trim($_POST['department'] ?? "");
    $year       = trim($_POST['year'] ?? "");
    $calendar   = $_POST['registered_calendar'] ?? null;
    $password   = trim($_POST['password'] ?? "");

    /* CREATE STUDENT */
    if ($action === "create") {

        if ($username === "" || $email === "" || $id_number === "" || $department === "") {
            $message = "Username, email, ID number, and department are required.";
            $message_type = "error";
        } else {
            $profilePic = handle_uploaded_image('profile_picture', $message);
            if ($profilePic === null && !empty($_FILES['profile_picture']['name'])) {
                $message_type = "error";
            }

            $hashed = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : password_hash("default123", PASSWORD_DEFAULT);

            try {
                $stmt = $conn->prepare(
                    "INSERT INTO users (username, email, id_number, department, year, registered_calendar, password, role, profile_picture)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'student', ?)"
                );
                $stmt->bind_param("ssssssss",
                    $username, $email, $id_number, $department, $year, $calendar, $hashed, $profilePic
                );

                if ($stmt->execute()) {
                    $message = "Student created successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $message = "Database error: Could not create student.";
                $message_type = "error";
                error_log("Database error in create_student.php (create): " . $e->getMessage());
            }
        }
    }

    /* UPDATE STUDENT */
    if ($action === "update") {
        $profilePic = handle_uploaded_image('profile_picture', $message);
        if ($profilePic === null && !empty($_FILES['profile_picture']['name'])) {
            $message_type = "error";
        }

        try {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "UPDATE users SET username=?, email=?, department=?, year=?, registered_calendar=?, password=?, profile_picture=IFNULL(?, profile_picture)
                     WHERE id_number=? AND role='student'"
                );
                $stmt->bind_param("ssssssss",
                    $username, $email, $department, $year, $calendar, $hashed, $profilePic, $id_number
                );
            } else {
                $stmt = $conn->prepare(
                    "UPDATE users SET username=?, email=?, department=?, year=?, registered_calendar=?, profile_picture=IFNULL(?, profile_picture)
                     WHERE id_number=? AND role='student'"
                );
                $stmt->bind_param("sssssss",
                    $username, $email, $department, $year, $calendar, $profilePic, $id_number
                );
            }

            if ($stmt->execute()) {
                $message = "Student updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $message = "Database error: Could not update student.";
            $message_type = "error";
            error_log("Database error in create_student.php (update): " . $e->getMessage());
        }
    }

    /* DELETE STUDENT */
    if ($action === "delete") {
        try {
            $picStmt = $conn->prepare("SELECT profile_picture FROM users WHERE id_number=? AND role='student' LIMIT 1");
            $picStmt->bind_param("s", $id_number);
            $picStmt->execute();
            $picRes = $picStmt->get_result();
            $picRow = $picRes->fetch_assoc();
            $picStmt->close();

            $stmt = $conn->prepare("DELETE FROM users WHERE id_number=? AND role='student'");
            $stmt->bind_param("s", $id_number);

            if ($stmt->execute()) {
                if (!empty($picRow['profile_picture'])) {
                    $filePath = "../../uploads/students/" . $picRow['profile_picture'];
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    }
                }
                $message = "Student deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $message = "Database error: Could not delete student.";
            $message_type = "error";
            error_log("Database error in create_student.php (delete): " . $e->getMessage());
        }
    }
}

/* =====================
   BUILD FILTER CONDITIONS
===================== */
$where_conditions = ["role='student'"];
$params = [];
$types = "";

if ($search !== "") {
    $where_conditions[] = "(username LIKE ? OR id_number LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

if ($filter_department !== "") {
    $where_conditions[] = "department LIKE ?";
    $deptParam = "%$filter_department%";
    $params[] = $deptParam;
    $types .= "s";
}

if ($filter_year !== "") {
    $where_conditions[] = "year = ?";
    $params[] = $filter_year;
    $types .= "s";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

/* =====================
   GET DISTINCT DEPARTMENTS FOR FILTER DROPDOWN
===================== */
$dept_sql = "SELECT DISTINCT department FROM users WHERE role='student' AND department IS NOT NULL AND department != '' ORDER BY department";
$dept_result = $conn->query($dept_sql);
$departments = [];
while ($dept_row = $dept_result->fetch_assoc()) {
    $departments[] = $dept_row['department'];
}

/* =====================
   COUNT FOR PAGINATION
===================== */
$countSql = "SELECT COUNT(*) total FROM users $where_clause";
$stmtCount = $conn->prepare($countSql);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

$totalPages = max(1, ceil($total / $limit));

/* =====================
   FETCH STUDENTS WITH FILTERS
===================== */
$sql = "SELECT username, email, id_number, department, year, registered_calendar, profile_picture
        FROM users $where_clause ORDER BY username ASC LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Manage Students | Library Management System</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: #f5f7fa;
        padding: 2rem;
        color: #1e293b;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .header {
        background: white;
        border-radius: 12px;
        padding: 1.5rem 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .header h1 {
        font-size: 1.5rem;
        color: #0f172a;
    }

    .back-link {
        background: #64748b;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.875rem;
        transition: background 0.2s;
    }

    .back-link:hover {
        background: #475569;
    }

    .message {
        padding: 1rem 1.25rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }

    .message.success {
        background: #dcfce7;
        color: #166534;
        border-left: 4px solid #22c55e;
    }

    .message.error {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }

    .card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .card h2 {
        font-size: 1.25rem;
        margin-bottom: 1.25rem;
        color: #0f172a;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 0.75rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #334155;
    }

    .form-group label .required {
        color: #ef4444;
    }

    .form-group input,
    .form-group select {
        padding: 0.625rem 0.875rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }

    .form-group input[type="file"] {
        padding: 0.5rem 0;
        border: none;
    }

    .small-note {
        font-size: 0.7rem;
        color: #64748b;
        margin-top: 0.25rem;
    }

    .filter-bar {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: flex-end;
    }

    .filter-group {
        flex: 1;
        min-width: 180px;
    }

    .filter-group label {
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #475569;
        display: block;
    }

    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.875rem;
    }

    .filter-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-filter {
        background: #3b82f6;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
    }

    .btn-reset {
        background: #64748b;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-weight: 500;
    }

    .search-bar {
        display: flex;
        gap: 0.75rem;
        align-items: flex-end;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }

    .search-bar .form-group {
        flex: 1;
        min-width: 200px;
    }

    .search-bar button,
    .search-bar .clear-btn {
        padding: 0.625rem 1.25rem;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .search-bar button {
        background: #3b82f6;
        color: white;
    }

    .search-bar button:hover {
        background: #2563eb;
    }

    .search-bar .clear-btn {
        background: #e2e8f0;
        color: #475569;
        text-decoration: none;
    }

    .search-bar .clear-btn:hover {
        background: #cbd5e1;
    }

    .active-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.5rem 0;
    }

    .filter-tag {
        background: #e0f2fe;
        color: #0369a1;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-tag .remove {
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
        color: #0369a1;
    }

    .filter-tag .remove:hover {
        color: #ef4444;
    }

    .stats {
        background: #f1f5f9;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        color: #475569;
    }

    .table-wrapper {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    th, td {
        padding: 0.875rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    th {
        background: #f8fafc;
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
    }

    tr:hover {
        background: #f8fafc;
    }

    .profile-img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        background: #f1f5f9;
    }

    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .btn {
        padding: 0.5rem 0.875rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background: #2563eb;
    }

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.7rem;
    }

    .pagination {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .pagination a {
        padding: 0.5rem 0.875rem;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        text-decoration: none;
        color: #475569;
        transition: all 0.2s;
    }

    .pagination a:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .pagination a.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .password-strength {
        height: 4px;
        background: #e2e8f0;
        margin-top: 0.5rem;
        border-radius: 2px;
        overflow: hidden;
    }

    .strength-bar {
        height: 100%;
        width: 0%;
        transition: width 0.3s;
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }
        
        .header {
            flex-direction: column;
            text-align: center;
        }
        
        .action-buttons {
            flex-direction: row;
        }
        
        .filter-bar {
            flex-direction: column;
        }
        
        .filter-actions {
            justify-content: flex-end;
        }
    }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📚 Manage Students</h1>
        <a href="../../dashboards/librarian.php" class="back-link">← Back to Dashboard</a>
    </div>

    <?php if ($message): ?>
    <div class="message <?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <!-- CREATE STUDENT CARD -->
    <div class="card">
        <h2>➕ Add New Student</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-grid">
                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" placeholder="e.g., john_doe" required>
                </div>

                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" placeholder="student@rayauniversity.edu.et" required>
                </div>

                <div class="form-group">
                    <label>ID Number <span class="required">*</span></label>
                    <input type="text" name="id_number" placeholder="e.g., RU1234/25" required>
                </div>

                <div class="form-group">
                    <label>Department <span class="required">*</span></label>
                    <input type="text" name="department" placeholder="e.g., Computer Science, Business, Law" required>
                </div>

                <div class="form-group">
                    <label>Year</label>
                    <select name="year">
                        <option value="">Select year</option>
                        <option>1</option><option>2</option><option>3</option>
                        <option>4</option><option>5</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Registration Date</label>
                    <input type="date" name="registered_calendar" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Leave blank for default (default123)">
                    <div class="small-note">Minimum 6 characters. Leave blank for default password.</div>
                </div>

                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_picture" accept="image/*">
                    <div class="small-note">Max 2MB. JPG, PNG, GIF, WebP allowed.</div>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Create Student</button>
            </div>
        </form>
    </div>

    <!-- FILTERS & STUDENTS TABLE CARD -->
    <div class="card">
        <h2>🔍 Student Directory</h2>
        
        <!-- Filter by Department and Year -->
        <div class="filter-bar">
            <div class="filter-group">
                <label>📚 Filter by Department</label>
                <select name="filter_department" id="filter_department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>" <?= $filter_department == $dept ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>🎓 Filter by Year</label>
                <select name="filter_year" id="filter_year">
                    <option value="">All Years</option>
                    <option value="1" <?= $filter_year == '1' ? 'selected' : '' ?>>Year 1</option>
                    <option value="2" <?= $filter_year == '2' ? 'selected' : '' ?>>Year 2</option>
                    <option value="3" <?= $filter_year == '3' ? 'selected' : '' ?>>Year 3</option>
                    <option value="4" <?= $filter_year == '4' ? 'selected' : '' ?>>Year 4</option>
                    <option value="5" <?= $filter_year == '5' ? 'selected' : '' ?>>Year 5</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="button" class="btn-filter" onclick="applyFilters()">Apply Filters</button>
                <a href="create_student.php" class="btn-reset">Reset All</a>
            </div>
        </div>
        
        <!-- Search by Username or ID -->
        <form method="GET" class="search-bar" id="searchForm" onsubmit="return false;">
            <div class="form-group">
                <label>🔎 Search by Username or ID</label>
                <input type="text" name="search" id="search_input" placeholder="Type username or ID..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div>
                <button type="button" onclick="applySearch()">Search</button>
                <?php if ($search || $filter_department || $filter_year): ?>
                <a href="create_student.php" class="clear-btn">Clear All</a>
                <?php endif; ?>
            </div>
            <input type="hidden" name="department" id="hidden_department" value="<?= htmlspecialchars($filter_department) ?>">
            <input type="hidden" name="year" id="hidden_year" value="<?= htmlspecialchars($filter_year) ?>">
        </form>
        
        <!-- Active Filters Display -->
        <?php if ($filter_department || $filter_year || $search): ?>
        <div class="active-filters">
            <span style="font-size: 0.75rem; color: #64748b;">Active filters:</span>
            <?php if ($search): ?>
            <span class="filter-tag">
                🔍 Search: "<?= htmlspecialchars($search) ?>"
                <a href="?<?= http_build_query(array_filter(['department' => $filter_department, 'year' => $filter_year])) ?>" class="remove">✕</a>
            </span>
            <?php endif; ?>
            <?php if ($filter_department): ?>
            <span class="filter-tag">
                📚 Department: <?= htmlspecialchars($filter_department) ?>
                <a href="?<?= http_build_query(array_filter(['search' => $search, 'year' => $filter_year])) ?>" class="remove">✕</a>
            </span>
            <?php endif; ?>
            <?php if ($filter_year): ?>
            <span class="filter-tag">
                🎓 Year: <?= htmlspecialchars($filter_year) ?>
                <a href="?<?= http_build_query(array_filter(['search' => $search, 'department' => $filter_department])) ?>" class="remove">✕</a>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats">
            📊 Showing <?= $result->num_rows ?> of <?= $total ?> total students
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>ID Number</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Reg. Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem;">
                            😓 No students found matching your criteria.
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <form method="POST" enctype="multipart/form-data" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="id_number" value="<?= htmlspecialchars($row['id_number']) ?>">
                        
                        <td style="width: 100px;">
                            <?php if (!empty($row['profile_picture']) && is_file("../../uploads/students/" . $row['profile_picture'])): ?>
                                <img class="profile-img" src="../../uploads/students/<?= htmlspecialchars($row['profile_picture']) ?>" alt="profile">
                            <?php else: ?>
                                <img class="profile-img" src="../../assets/img/default_avatar.png" alt="default avatar">
                            <?php endif; ?>
                            <div class="small-note" style="margin-top: 0.5rem;">
                                <input type="file" name="profile_picture" accept="image/*" style="font-size: 0.7rem;">
                            </div>
                        </td>
                        <td><input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" style="width: 100%;"></td>
                        <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" style="width: 100%;"></td>
                        <td><strong><?= htmlspecialchars($row['id_number']) ?></strong></td>
                        <td><input type="text" name="department" value="<?= htmlspecialchars($row['department']) ?>" style="width: 100%;" placeholder="Department"></td>
                        <td>
                            <select name="year" style="width: 100%;">
                                <option value="">Select</option>
                                <option <?= $row['year'] == '1' ? 'selected' : '' ?>>1</option>
                                <option <?= $row['year'] == '2' ? 'selected' : '' ?>>2</option>
                                <option <?= $row['year'] == '3' ? 'selected' : '' ?>>3</option>
                                <option <?= $row['year'] == '4' ? 'selected' : '' ?>>4</option>
                                <option <?= $row['year'] == '5' ? 'selected' : '' ?>>5</option>
                            </select>
                        </td>
                        <td><input type="date" name="registered_calendar" value="<?= htmlspecialchars($row['registered_calendar']) ?>" style="width: 100%;"></td>
                        <td class="action-buttons">
                            <input type="password" name="password" placeholder="New password" style="width: 100%; margin-bottom: 0.5rem;">
                            <div class="password-strength" style="margin-bottom: 0.5rem;">
                                <div class="strength-bar"></div>
                            </div>
                            <button type="submit" name="action" value="update" class="btn btn-primary btn-sm">Update</button>
                            <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Delete this student permanently?')">Delete</button>
                        </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $base = "create_student.php?";
            $query_params = array_filter(['search' => $search, 'department' => $filter_department, 'year' => $filter_year]);
            if (!empty($query_params)) {
                $base .= http_build_query($query_params) . "&";
            }
            for ($i = 1; $i <= $totalPages; $i++) {
                $active = $i == $page ? "active" : "";
                echo "<a class='$active' href='{$base}page=$i'>$i</a>";
            }
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function applyFilters() {
    const department = document.getElementById('filter_department').value;
    const year = document.getElementById('filter_year').value;
    const search = document.getElementById('search_input')?.value || '';
    
    let url = 'create_student.php?';
    const params = [];
    
    if (search) params.push('search=' + encodeURIComponent(search));
    if (department) params.push('department=' + encodeURIComponent(department));
    if (year) params.push('year=' + encodeURIComponent(year));
    
    window.location.href = url + params.join('&');
}

function applySearch() {
    const search = document.getElementById('search_input')?.value || '';
    const department = document.getElementById('filter_department').value;
    const year = document.getElementById('filter_year').value;
    
    let url = 'create_student.php?';
    const params = [];
    
    if (search) params.push('search=' + encodeURIComponent(search));
    if (department) params.push('department=' + encodeURIComponent(department));
    if (year) params.push('year=' + encodeURIComponent(year));
    
    window.location.href = url + params.join('&');
}

// Enter key support for search
document.getElementById('search_input')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applySearch();
    }
});

// Password strength indicator
document.querySelectorAll('input[type="password"][name="password"]').forEach(function(passwordInput) {
    passwordInput.addEventListener('input', function() {
        const val = this.value;
        const strengthBar = this.closest('td')?.querySelector('.strength-bar');
        if (!strengthBar) return;
        
        let strength = 0;
        if (val.length >= 6) strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;
        
        const percent = (strength / 5) * 100;
        strengthBar.style.width = percent + '%';
        
        if (percent < 20) {
            strengthBar.style.backgroundColor = '#ef4444';
        } else if (percent < 40) {
            strengthBar.style.backgroundColor = '#f59e0b';
        } else if (percent < 70) {
            strengthBar.style.backgroundColor = '#3b82f6';
        } else {
            strengthBar.style.backgroundColor = '#22c55e';
        }
    });
});
</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
