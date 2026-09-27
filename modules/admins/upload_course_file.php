<?php
include("../../includes/auth.php");
include("../../db.php");

// ONLY ADMIN
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$msg = "";
$msgType = "";
$edit_id = null;
$edit_data = null;

/* =========================
   GET EDIT DATA
========================= */
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM course_files WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* =========================
   UPDATE FILE
========================= */
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $file_type = $_POST['file_type'];
    $course = $_POST['course'];
    $department = $_POST['department'];
    $year = $_POST['year'];
    $chapter = isset($_POST['chapter']) ? $_POST['chapter'] : null;
    $title = $_POST['title'];
    $author = isset($_POST['author']) ? $_POST['author'] : null;
    $publication_date = isset($_POST['publication_date']) ? $_POST['publication_date'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $tags = isset($_POST['tags']) ? $_POST['tags'] : null;

    // Check if new file is uploaded
    if ($_FILES['file']['name']) {
        $file = $_FILES['file'];
        $originalName = $file['name'];
        $fileSize = $file['size'];
        $fileMimeType = mime_content_type($file['tmp_name']);
        $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = time() . "_" . uniqid() . "." . $fileExt;

        // Get old file to delete
        $stmt = $conn->prepare("SELECT file_path, file_type, department, year FROM course_files WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $oldFile = $stmt->get_result()->fetch_assoc();
        
        if ($oldFile) {
            $cleanDept = preg_replace('/[^a-zA-Z0-9_-]/', '_', $oldFile['department']);
            $cleanYear = preg_replace('/[^a-zA-Z0-9_-]/', '_', $oldFile['year']);
            $oldPath = __DIR__ . "/../admin/uploads/" . $oldFile['file_type'] . "/" . $cleanDept . "/";
            if (!empty($oldFile['year']) && $oldFile['year'] != 'all') {
                $oldPath .= $cleanYear . "/";
            }
            $oldPath .= $oldFile['file_path'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        $stmt->close();

        // Create new directory structure
        $cleanDept = preg_replace('/[^a-zA-Z0-9_-]/', '_', $department);
        $cleanYear = preg_replace('/[^a-zA-Z0-9_-]/', '_', $year);
        $uploadDir = __DIR__ . "/../admin/uploads/" . $file_type . "/" . $cleanDept . "/";
        if (!empty($year) && $year != 'all') {
            $uploadDir .= $cleanYear . "/";
        }
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
            $stmt = $conn->prepare("
                UPDATE course_files SET 
                file_type=?, course_name=?, department=?, year=?, chapter=?, 
                title=?, author=?, publication_date=?, description=?, tags=?, 
                original_filename=?, file_path=?, file_size=?, mime_type=?
                WHERE id=?
            ");
            $stmt->bind_param(
                "sssssssssssssi",
                $file_type, $course, $department, $year, $chapter,
                $title, $author, $publication_date, $description, $tags,
                $originalName, $fileName, $fileSize, $fileMimeType, $id
            );
        } else {
            $msg = "❌ Failed to upload new file!";
            $msgType = "error";
        }
    } else {
        // Update without changing file
        $stmt = $conn->prepare("
            UPDATE course_files SET 
            file_type=?, course_name=?, department=?, year=?, chapter=?, 
            title=?, author=?, publication_date=?, description=?, tags=?
            WHERE id=?
        ");
        $stmt->bind_param(
            "ssssssssssi",
            $file_type, $course, $department, $year, $chapter,
            $title, $author, $publication_date, $description, $tags, $id
        );
    }

    if (isset($stmt) && $stmt->execute()) {
        $msg = "✅ File updated successfully!";
        $msgType = "success";
        $edit_id = null;
        $edit_data = null;
    } else if (!isset($stmt)) {
        // Do nothing, already handled
    } else {
        $msg = "❌ Update failed: " . $stmt->error;
        $msgType = "error";
    }
    if (isset($stmt)) $stmt->close();
}

/* =========================
   UPLOAD FILE
========================= */
if (isset($_POST['upload'])) {

    $file_type = $_POST['file_type'];
    $course = $_POST['course'];
    $department = $_POST['department'];
    $year = $_POST['year'];
    $chapter = isset($_POST['chapter']) ? $_POST['chapter'] : null;
    $title = $_POST['title'];
    $author = isset($_POST['author']) ? $_POST['author'] : null;
    $publication_date = isset($_POST['publication_date']) ? $_POST['publication_date'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $tags = isset($_POST['tags']) ? $_POST['tags'] : null;

    $file = $_FILES['file'];
    $originalName = $file['name'];
    $fileSize = $file['size'];
    $fileMimeType = mime_content_type($file['tmp_name']);
    
    // Generate unique filename
    $fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
    $fileName = time() . "_" . uniqid() . "." . $fileExt;

    // Clean department and year for folder names
    $cleanDept = preg_replace('/[^a-zA-Z0-9_-]/', '_', $department);
    $cleanYear = preg_replace('/[^a-zA-Z0-9_-]/', '_', $year);
    
    // Create directory structure
    $uploadDir = __DIR__ . "/../admin/uploads/" . $file_type . "/" . $cleanDept . "/";
    if (!empty($year) && $year != 'all') {
        $uploadDir .= $cleanYear . "/";
    }
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Validate file size (max 50MB)
    if ($fileSize > 50 * 1024 * 1024) {
        $msg = "❌ File size too large! Maximum 50MB allowed.";
        $msgType = "error";
    } 
    // Validate file extension
    else if (!in_array(strtolower($fileExt), ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'epub', 'txt'])) {
        $msg = "❌ Invalid file type! Allowed: PDF, DOC, DOCX, PPT, PPTX, EPUB, TXT";
        $msgType = "error";
    }
    else if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {

        $stmt = $conn->prepare("
            INSERT INTO course_files 
            (file_type, course_name, department, year, chapter, title, author, 
             publication_date, description, tags, original_filename, file_path, 
             file_size, mime_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "ssssssssssssis",
            $file_type,
            $course,
            $department,
            $year,
            $chapter,
            $title,
            $author,
            $publication_date,
            $description,
            $tags,
            $originalName,
            $fileName,
            $fileSize,
            $fileMimeType
        );

        if ($stmt->execute()) {
            $msg = "✅ File uploaded successfully!";
            $msgType = "success";
        } else {
            $msg = "❌ Database error: " . $stmt->error;
            $msgType = "error";
        }
        $stmt->close();
    } else {
        $msg = "❌ Upload failed! Please check folder permissions.";
        $msgType = "error";
    }
}

/* =========================
   DELETE FILE
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("SELECT file_path, file_type, department, year FROM course_files WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $file = $res->fetch_assoc();

    if ($file) {
        $cleanDept = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file['department']);
        $cleanYear = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file['year']);
        
        $path = __DIR__ . "/../admin/uploads/" . $file['file_type'] . "/" . $cleanDept . "/";
        if (!empty($file['year']) && $file['year'] != 'all') {
            $path .= $cleanYear . "/";
        }
        $path .= $file['file_path'];
        
        if (file_exists($path)) {
            unlink($path);
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM course_files WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: upload_course_file.php?deleted=1");
    exit;
}

/* =========================
   GET STATISTICS
========================= */
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM course_files")->fetch_assoc()['count'];
$stats['ebooks'] = $conn->query("SELECT COUNT(*) as count FROM course_files WHERE file_type='ebook'")->fetch_assoc()['count'];
$stats['modules'] = $conn->query("SELECT COUNT(*) as count FROM course_files WHERE file_type='module'")->fetch_assoc()['count'];
$stats['journals'] = $conn->query("SELECT COUNT(*) as count FROM course_files WHERE file_type='journal'")->fetch_assoc()['count'];
$stats['research'] = $conn->query("SELECT COUNT(*) as count FROM course_files WHERE file_type='research'")->fetch_assoc()['count'];

/* =========================
   FILTER AND LIST FILES
========================= */
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$filter_dept = isset($_GET['filter_dept']) ? $_GET['filter_dept'] : '';
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM course_files WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_type)) {
    $sql .= " AND file_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}
if (!empty($filter_dept)) {
    $sql .= " AND department LIKE ?";
    $params[] = "%$filter_dept%";
    $types .= "s";
}
if (!empty($filter_year)) {
    $sql .= " AND year LIKE ?";
    $params[] = "%$filter_year%";
    $types .= "s";
}
if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR course_name LIKE ? OR author LIKE ? OR tags LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

$sql .= " ORDER BY id DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Get unique departments for filter
$depts = $conn->query("SELECT DISTINCT department FROM course_files ORDER BY department");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - File Upload System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            margin: auto; 
            background: white;
            border-radius: 10px;
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
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card.ebook { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.module { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.journal { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.research { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .form-container {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-container h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .help-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .btn-submit, .btn-update {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        .btn-update {
            background: #ffc107;
            color: #333;
        }
        
        .btn-update:hover {
            background: #e0a800;
        }
        
        .btn-submit:hover {
            background: #218838;
        }
        
        .filter-section {
            background: #e9ecef;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .filter-grid {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 5px;
        }
        
        .btn-filter {
            background: #3498db;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: left;
            vertical-align: middle;
        }
        
        th {
            background: #2c3e50;
            color: white;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .badge-ebook { background: #667eea; color: white; }
        .badge-module { background: #f5576c; color: white; }
        .badge-journal { background: #4facfe; color: white; }
        .badge-research { background: #43e97b; color: white; }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
        }
        
        .btn-edit:hover {
            background: #e0a800;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .msg {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
        
        .edit-mode {
            border-left: 4px solid #ffc107;
            padding-left: 20px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .filter-grid {
                flex-direction: column;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="../../dashboards/admin.php" class="back-link">⬅ Back to Dashboard</a>
        <h2>📚 Library File Management System</h2>
        <p>Upload, edit, and manage E-books, Modules, Journals, and Research Papers</p>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="msg msg-<?php echo $msgType; ?>">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="msg msg-success">✅ File deleted successfully!</div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Files</div>
        </div>
        <div class="stat-card ebook">
            <div class="stat-number"><?php echo $stats['ebooks']; ?></div>
            <div class="stat-label">E-Books</div>
        </div>
        <div class="stat-card module">
            <div class="stat-number"><?php echo $stats['modules']; ?></div>
            <div class="stat-label">Modules</div>
        </div>
        <div class="stat-card journal">
            <div class="stat-number"><?php echo $stats['journals']; ?></div>
            <div class="stat-label">Journals</div>
        </div>
        <div class="stat-card research">
            <div class="stat-number"><?php echo $stats['research']; ?></div>
            <div class="stat-label">Research</div>
        </div>
    </div>

    <!-- UPLOAD/EDIT FORM -->
    <div class="form-container <?php echo $edit_data ? 'edit-mode' : ''; ?>">
        <h3><?php echo $edit_data ? '✏️ Edit File' : '📤 Upload New File'; ?></h3>
        
        <form method="POST" enctype="multipart/form-data">
            <?php if ($edit_data): ?>
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>File Type *</label>
                    <select name="file_type" required>
                        <option value="">Select File Type</option>
                        <option value="ebook" <?php echo ($edit_data && $edit_data['file_type'] == 'ebook') ? 'selected' : ''; ?>>📖 E-Book</option>
                        <option value="module" <?php echo ($edit_data && $edit_data['file_type'] == 'module') ? 'selected' : ''; ?>>📚 University Module</option>
                        <option value="journal" <?php echo ($edit_data && $edit_data['file_type'] == 'journal') ? 'selected' : ''; ?>>📰 Journal</option>
                        <option value="research" <?php echo ($edit_data && $edit_data['file_type'] == 'research') ? 'selected' : ''; ?>>🔬 Research Paper</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="Enter title" required 
                           value="<?php echo $edit_data ? htmlspecialchars($edit_data['title']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="course" placeholder="e.g., Computer Science 101"
                           value="<?php echo $edit_data ? htmlspecialchars($edit_data['course_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Department * (Type manually)</label>
                    <input type="text" name="department" placeholder="e.g., Computer Science, Engineering, Business" required
                           value="<?php echo $edit_data ? htmlspecialchars($edit_data['department']) : ''; ?>">
                    <div class="help-text">💡 Type any department name</div>
                </div>
                
                <div class="form-group">
                    <label>Year Level * (Type manually)</label>
                    <input type="text" name="year" placeholder="e.g., 1st Year, 2nd Year, Freshman, Sophomore" required
                           value="<?php echo $edit_data ? htmlspecialchars($edit_data['year']) : ''; ?>">
                    <div class="help-text">💡 Type any year/level</div>
                </div>
                
                <div class="form-group">
                    <label>Chapter</label>
                    <input type="text" name="chapter" placeholder="e.g., Chapter 1"
                           value="<?php echo $edit_data ? htmlspecialchars($edit_data['chapter']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" placeholder="Author name"
                           value="<?php echo $edit_data ? htmlspecialchars($edit_data['author']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Publication Date</label>
                    <input type="date" name="publication_date"
                           value="<?php echo $edit_data ? $edit_data['publication_date'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Tags</label>
                    <input type="text" name="tags" placeholder="e.g., programming, database, AI"
                           value="<?php echo $edit_data ? htmlspecialchars($edit_data['tags']) : ''; ?>">
                    <div class="help-text">Separate with commas</div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description"><?php echo $edit_data ? htmlspecialchars($edit_data['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>File <?php echo $edit_data ? '(Leave empty to keep current file)' : '*'; ?></label>
                    <input type="file" name="file" <?php echo !$edit_data ? 'required' : ''; ?> accept=".pdf,.doc,.docx,.ppt,.pptx,.epub,.txt">
                    <div class="help-text">Max size: 50MB | Allowed: PDF, DOC, PPT, EPUB, TXT</div>
                    <?php if ($edit_data): ?>
                        <div class="help-text" style="color: #28a745;">Current file: <?php echo htmlspecialchars($edit_data['original_filename']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($edit_data): ?>
                <button type="submit" name="update" class="btn-update">✏️ Update File</button>
                <a href="upload_course_file.php" class="btn-reset" style="margin-left: 10px;">Cancel Edit</a>
            <?php else: ?>
                <button type="submit" name="upload" class="btn-submit">🚀 Upload File</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- FILTER SECTION -->
    <div class="filter-section">
        <h3>🔍 Filter Files</h3>
        <form method="GET" class="filter-grid">
            <div class="filter-group">
                <label>File Type</label>
                <select name="filter_type">
                    <option value="">All Types</option>
                    <option value="ebook" <?php echo $filter_type == 'ebook' ? 'selected' : ''; ?>>E-Books</option>
                    <option value="module" <?php echo $filter_type == 'module' ? 'selected' : ''; ?>>Modules</option>
                    <option value="journal" <?php echo $filter_type == 'journal' ? 'selected' : ''; ?>>Journals</option>
                    <option value="research" <?php echo $filter_type == 'research' ? 'selected' : ''; ?>>Research</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Department (Type to search)</label>
                <input type="text" name="filter_dept" placeholder="Search department" value="<?php echo htmlspecialchars($filter_dept); ?>">
            </div>
            
            <div class="filter-group">
                <label>Year (Type to search)</label>
                <input type="text" name="filter_year" placeholder="Search year" value="<?php echo htmlspecialchars($filter_year); ?>">
            </div>
            
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Title, author, tags..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-group">
                <button type="submit" class="btn-filter">🔍 Apply Filter</button>
                <a href="upload_course_file.php" class="btn-reset">🔄 Reset</a>
            </div>
        </form>
    </div>

    <!-- FILE LIST -->
    <h3>📁 Uploaded Files (<?php echo $result->num_rows; ?> files)</h3>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Author</th>
                    <th>File</th>
                    <th>Downloads</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['file_type']; ?>">
                                    <?php 
                                        switch($row['file_type']) {
                                            case 'ebook': echo '📖 E-Book'; break;
                                            case 'module': echo '📚 Module'; break;
                                            case 'journal': echo '📰 Journal'; break;
                                            case 'research': echo '🔬 Research'; break;
                                            default: echo $row['file_type'];
                                        }
                                    ?>
                                </span>
                             </td>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['course_name'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['year'] ?: 'All'); ?></td>
                            <td><?php echo htmlspecialchars($row['author'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['original_filename']); ?></td>
                            <td><?php echo $row['downloads']; ?></td>
                            <td>
                                <a href="?edit=<?php echo $row['id']; ?>" class="btn-edit">✏️ Edit</a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Delete this file permanently?')">🗑️ Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px;">📭 No files found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
<?php $conn->close(); ?>