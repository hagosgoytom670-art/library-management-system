<?php
include("../../includes/auth.php");
include("../../db.php");

// ONLY STUDENTS
if ($_SESSION['role'] !== 'student') {
    die("Access denied");
}

// Get student info
$student_id = $_SESSION['user_id'] ?? null;
$student_name = $_SESSION['username'] ?? 'Student';

/* =========================
   DOWNLOAD HANDLER
========================= */
if (isset($_GET['file'])) {
    $id = (int)$_GET['file'];
    
    // Track download for analytics
    $trackStmt = $conn->prepare("UPDATE course_files SET downloads = downloads + 1 WHERE id = ?");
    $trackStmt->bind_param("i", $id);
    $trackStmt->execute();
    $trackStmt->close();

    $stmt = $conn->prepare("SELECT file_path, original_filename, file_type, department, year FROM course_files WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $file = $res->fetch_assoc();

    if ($file) {
        // Clean department and year for folder names
        $cleanDept = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file['department']);
        $cleanYear = preg_replace('/[^a-zA-Z0-9_-]/', '_', $file['year']);
        
        $path = __DIR__ . "/../admin/uploads/" . $file['file_type'] . "/" . $cleanDept . "/";
        if (!empty($file['year']) && $file['year'] != 'all') {
            $path .= $cleanYear . "/";
        }
        $path .= $file['file_path'];

        if (file_exists($path)) {
            header("Content-Description: File Transfer");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"" . basename($file['original_filename']) . "\"");
            header("Content-Length: " . filesize($path));
            header("Cache-Control: no-cache");
            readfile($path);
            exit;
        } else {
            die("File not found on server. Please contact admin.");
        }
    }
    die("Invalid file.");
}

/* =========================
   SEARCH FILTERS - ALL TYPABLE
========================= */
$file_type = $_GET['file_type'] ?? '';
$course = $_GET['course'] ?? '';
$department = $_GET['department'] ?? '';
$year = $_GET['year'] ?? '';
$chapter = $_GET['chapter'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM course_files WHERE 1=1";
$params = [];
$types = "";

if (!empty($file_type)) {
    $sql .= " AND file_type = ?";
    $params[] = $file_type;
    $types .= "s";
}
if (!empty($course)) {
    $sql .= " AND course_name LIKE ?";
    $params[] = "%$course%";
    $types .= "s";
}
if (!empty($department)) {
    $sql .= " AND department LIKE ?";
    $params[] = "%$department%";
    $types .= "s";
}
if (!empty($year)) {
    $sql .= " AND year LIKE ?";
    $params[] = "%$year%";
    $types .= "s";
}
if (!empty($chapter)) {
    $sql .= " AND chapter LIKE ?";
    $params[] = "%$chapter%";
    $types .= "s";
}
if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR tags LIKE ? OR course_name LIKE ? OR description LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sssss";
}

$sql .= " ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Get unique departments for suggestions (from existing data)
$deptQuery = "SELECT DISTINCT department FROM course_files WHERE department IS NOT NULL AND department != '' ORDER BY department LIMIT 10";
$deptResult = $conn->query($deptQuery);

// Get unique years for suggestions
$yearQuery = "SELECT DISTINCT year FROM course_files WHERE year IS NOT NULL AND year != '' AND year != 'all' ORDER BY year LIMIT 10";
$yearResult = $conn->query($yearQuery);

// Get popular downloads
$popularQuery = "SELECT title, downloads, id, file_type FROM course_files ORDER BY downloads DESC LIMIT 5";
$popularResult = $conn->query($popularQuery);

// Get latest uploads
$latestQuery = "SELECT title, created_at, id, file_type FROM course_files ORDER BY created_at DESC LIMIT 5";
$latestResult = $conn->query($latestQuery);

// Get statistics
$stats = [];
$stats['total'] = $conn->query("SELECT COUNT(*) as count FROM course_files")->fetch_assoc()['count'];
$stats['ebooks'] = $conn->query("SELECT COUNT(*) as count FROM course_files WHERE file_type='ebook'")->fetch_assoc()['count'];
$stats['modules'] = $conn->query("SELECT COUNT(*) as count FROM course_files WHERE file_type='module'")->fetch_assoc()['count'];
$stats['journals'] = $conn->query("SELECT COUNT(*) as count FROM course_files WHERE file_type='journal'")->fetch_assoc()['count'];
$stats['research'] = $conn->query("SELECT COUNT(*) as count FROM course_files WHERE file_type='research'")->fetch_assoc()['count'];

// Get current date for greeting
$hour = date('H');
$greeting = ($hour < 12) ? 'Good Morning' : (($hour < 18) ? 'Good Afternoon' : 'Good Evening');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student - Digital Library</title>
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
        
        .subtitle {
            color: #6c757d;
            font-size: 14px;
        }
        
        /* Student Info Card */
        .student-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .student-info h3 {
            margin-bottom: 5px;
            font-size: 18px;
        }
        
        .student-info p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .greeting {
            font-size: 14px;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: #3498db;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card.active {
            border-color: #3498db;
            background: #e3f2fd;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .stat-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        /* Content Layout */
        .content-wrapper {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .main-content {
            flex: 3;
            min-width: 300px;
        }
        
        .sidebar-content {
            flex: 1;
            min-width: 250px;
        }
        
        /* Sidebar Boxes */
        .sidebar-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .sidebar-box h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 16px;
            border-left: 3px solid #3498db;
            padding-left: 10px;
        }
        
        .popular-item, .latest-item {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .popular-item:hover, .latest-item:hover {
            background: #e9ecef;
        }
        
        .popular-title, .latest-title {
            font-weight: 500;
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        .popular-downloads {
            color: #28a745;
            font-size: 11px;
            font-weight: bold;
        }
        
        .latest-date {
            color: #6c757d;
            font-size: 10px;
        }
        
        .file-type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            margin-left: 5px;
        }
        
        /* Filter Section */
        .filter-section {
            background: #e9ecef;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .filter-section h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
            transition: border-color 0.2s;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .filter-group input::placeholder {
            color: #adb5bd;
            font-size: 12px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .btn-filter {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-filter:hover {
            background: #2980b9;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background 0.2s;
        }
        
        .btn-reset:hover {
            background: #5a6268;
        }
        
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: left;
            vertical-align: top;
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
        
        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .badge-ebook { background: #667eea; color: white; }
        .badge-module { background: #f5576c; color: white; }
        .badge-journal { background: #4facfe; color: white; }
        .badge-research { background: #43e97b; color: white; }
        
        .btn-download {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.2s;
        }
        
        .btn-download:hover {
            background: #218838;
        }
        
        .file-info {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-results img {
            width: 80px;
            opacity: 0.5;
            margin-bottom: 15px;
        }
        
        .search-active {
            background: #e3f2fd;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .student-info {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            th, td {
                padding: 8px;
                font-size: 12px;
            }
            
            .btn-download {
                padding: 5px 10px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="../../dashboards/student.php" class="back-link">⬅ Back to Dashboard</a>
        <h2>📚 Digital Library & Learning Resources</h2>
        <p class="subtitle">Access e-books, university modules, journals, and research papers</p>
    </div>
    
    <!-- Student Info -->
    <div class="student-info">
        <div>
            <h3><?php echo $greeting . ", " . htmlspecialchars($student_name); ?>!</h3>
            <p>Welcome to your digital library. Access all your course materials here.</p>
        </div>
        <div class="greeting">
            📖 <?php echo date('l, F j, Y'); ?>
        </div>
    </div>
    
    <!-- Statistics Quick Filters -->
    <div class="stats-grid">
        <div class="stat-card <?php echo empty($file_type) ? 'active' : ''; ?>" onclick="filterByType('')">
            <div class="stat-icon">📚</div>
            <div class="stat-number"><?php echo $stats['total']; ?></div>
            <div class="stat-label">All Resources</div>
        </div>
        <div class="stat-card <?php echo $file_type == 'ebook' ? 'active' : ''; ?>" onclick="filterByType('ebook')">
            <div class="stat-icon">📖</div>
            <div class="stat-number"><?php echo $stats['ebooks']; ?></div>
            <div class="stat-label">E-Books</div>
        </div>
        <div class="stat-card <?php echo $file_type == 'module' ? 'active' : ''; ?>" onclick="filterByType('module')">
            <div class="stat-icon">📚</div>
            <div class="stat-number"><?php echo $stats['modules']; ?></div>
            <div class="stat-label">Modules</div>
        </div>
        <div class="stat-card <?php echo $file_type == 'journal' ? 'active' : ''; ?>" onclick="filterByType('journal')">
            <div class="stat-icon">📰</div>
            <div class="stat-number"><?php echo $stats['journals']; ?></div>
            <div class="stat-label">Journals</div>
        </div>
        <div class="stat-card <?php echo $file_type == 'research' ? 'active' : ''; ?>" onclick="filterByType('research')">
            <div class="stat-icon">🔬</div>
            <div class="stat-number"><?php echo $stats['research']; ?></div>
            <div class="stat-label">Research</div>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div class="main-content">
            <!-- Search Filter Section - TYPABLE FIELDS -->
            <div class="filter-section">
                <h3>🔍 Find Your Materials</h3>
                <form method="GET" id="filterForm">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label>🔎 Search Anything</label>
                            <input type="text" name="search" placeholder="Title, author, tags, keywords..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <label>📂 File Type</label>
                            <select name="file_type">
                                <option value="">All Types</option>
                                <option value="ebook" <?php echo $file_type == 'ebook' ? 'selected' : ''; ?>>📖 E-Books</option>
                                <option value="module" <?php echo $file_type == 'module' ? 'selected' : ''; ?>>📚 Modules</option>
                                <option value="journal" <?php echo $file_type == 'journal' ? 'selected' : ''; ?>>📰 Journals</option>
                                <option value="research" <?php echo $file_type == 'research' ? 'selected' : ''; ?>>🔬 Research</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>📘 Course Name</label>
                            <input type="text" name="course" placeholder="e.g., Computer Science 101" 
                                   value="<?php echo htmlspecialchars($course); ?>">
                        </div>
                        <div class="filter-group">
                            <label>🏫 Department (Type manually)</label>
                            <input type="text" name="department" placeholder="e.g., Computer Science, Engineering, Business" 
                                   value="<?php echo htmlspecialchars($department); ?>" list="dept-list">
                            <datalist id="dept-list">
                                <?php while($dept = $deptResult->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                <?php endwhile; ?>
                            </datalist>
                        </div>
                        <div class="filter-group">
                            <label>📅 Year (Type manually)</label>
                            <input type="text" name="year" placeholder="e.g., 1st Year, Freshman, 2024" 
                                   value="<?php echo htmlspecialchars($year); ?>" list="year-list">
                            <datalist id="year-list">
                                <?php while($yr = $yearResult->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($yr['year']); ?>">
                                <?php endwhile; ?>
                            </datalist>
                        </div>
                        <div class="filter-group">
                            <label>📑 Chapter</label>
                            <input type="text" name="chapter" placeholder="Chapter number or name" 
                                   value="<?php echo htmlspecialchars($chapter); ?>">
                        </div>
                        <div class="filter-group button-group">
                            <button type="submit" class="btn-filter">🔍 Apply Filters</button>
                            <a href="download_course_file.php" class="btn-reset">🔄 Reset All</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Active Filters Display -->
            <?php if (!empty($search) || !empty($department) || !empty($year) || !empty($course) || !empty($file_type) || !empty($chapter)): ?>
            <div class="search-active">
                🔍 Active filters: 
                <?php 
                $filters = [];
                if(!empty($search)) $filters[] = "Search: '$search'";
                if(!empty($file_type)) $filters[] = "Type: $file_type";
                if(!empty($course)) $filters[] = "Course: $course";
                if(!empty($department)) $filters[] = "Dept: $department";
                if(!empty($year)) $filters[] = "Year: $year";
                if(!empty($chapter)) $filters[] = "Chapter: $chapter";
                echo implode(" • ", $filters);
                ?>
                <span style="float: right;">Found <?php echo $result->num_rows; ?> results</span>
            </div>
            <?php endif; ?>
            
            <!-- Results Table -->
            <div class="table-responsive">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title & Details</th>
                                <th>Course</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
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
                                <td>
                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                    <?php if(!empty($row['author'])): ?>
                                        <div class="file-info">👤 <strong>Author:</strong> <?php echo htmlspecialchars($row['author']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($row['chapter'])): ?>
                                        <div class="file-info">📑 <strong>Chapter:</strong> <?php echo htmlspecialchars($row['chapter']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($row['tags'])): ?>
                                        <div class="file-info">🏷️ <strong>Tags:</strong> <?php echo htmlspecialchars($row['tags']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($row['description'])): ?>
                                        <div class="file-info">💬 <?php echo substr(htmlspecialchars($row['description']), 0, 100); ?>...</div>
                                    <?php endif; ?>
                                    <div class="file-info">📥 Downloads: <?php echo $row['downloads']; ?> | 📅 Added: <?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($row['course_name'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td><?php echo htmlspecialchars($row['year'] ?: 'All'); ?></td>
                                <td>
                                    <a href="?file=<?php echo $row['id']; ?>" class="btn-download">
                                        ⬇️ Download
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-results">
                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486756.png" alt="No results">
                        <h4>No materials found</h4>
                        <p>Try adjusting your search filters or check back later for new content</p>
                        <a href="download_course_file.php" class="btn-reset" style="display: inline-block; margin-top: 15px;">Clear All Filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="sidebar-content">
            <!-- Popular Downloads -->
            <div class="sidebar-box">
                <h3>⭐ Most Popular</h3>
                <?php if ($popularResult && $popularResult->num_rows > 0): ?>
                    <?php while($pop = $popularResult->fetch_assoc()): ?>
                        <div class="popular-item" onclick="searchByTitle('<?php echo htmlspecialchars($pop['title']); ?>')">
                            <div class="popular-title">
                                <?php echo htmlspecialchars(substr($pop['title'], 0, 50)); ?>
                                <span class="file-type-badge badge-<?php echo $pop['file_type']; ?>">
                                    <?php echo substr($pop['file_type'], 0, 1); ?>
                                </span>
                            </div>
                            <div class="popular-downloads">
                                ⬇️ <?php echo $pop['downloads']; ?> downloads
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #6c757d; font-size: 13px;">No downloads yet</p>
                <?php endif; ?>
            </div>
            
            <!-- Latest Uploads -->
            <div class="sidebar-box">
                <h3>🆕 Latest Uploads</h3>
                <?php if ($latestResult && $latestResult->num_rows > 0): ?>
                    <?php while($latest = $latestResult->fetch_assoc()): ?>
                        <div class="latest-item" onclick="searchByTitle('<?php echo htmlspecialchars($latest['title']); ?>')">
                            <div class="latest-title">
                                <?php echo htmlspecialchars(substr($latest['title'], 0, 50)); ?>
                                <span class="file-type-badge badge-<?php echo $latest['file_type']; ?>">
                                    <?php echo substr($latest['file_type'], 0, 1); ?>
                                </span>
                            </div>
                            <div class="latest-date">
                                📅 <?php echo date('M d, Y', strtotime($latest['created_at'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #6c757d; font-size: 13px;">No uploads yet</p>
                <?php endif; ?>
            </div>
            
            <!-- Quick Tips -->
            <div class="sidebar-box">
                <h3>💡 Quick Tips</h3>
                <ul style="margin-left: 20px; color: #6c757d; font-size: 13px; line-height: 1.6;">
                    <li>✓ Type any department name to search</li>
                    <li>✓ Type any year/level to filter</li>
                    <li>✓ Use keywords in search box</li>
                    <li>✓ Click on popular items to search</li>
                    <li>✓ Downloaded files save to your device</li>
                </ul>
            </div>
            
            <!-- Need Help -->
            <div class="sidebar-box">
                <h3>🆘 Need Assistance?</h3>
                <p style="font-size: 13px; color: #6c757d; line-height: 1.5;">
                    Having trouble accessing materials?<br>
                    Contact library support:<br>
                    <strong>✉️ Email: TekleHaylekiros@rayu.edu.et</strong><br>
                    <strong>📞 Phone: +251 985 41 48 46

</strong>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function filterByType(type) {
    const urlParams = new URLSearchParams(window.location.search);
    if (type) {
        urlParams.set('file_type', type);
    } else {
        urlParams.delete('file_type');
    }
    window.location.href = '?' + urlParams.toString();
}

function searchByTitle(title) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('search', title);
    window.location.href = '?' + urlParams.toString();
}

// Auto-submit on select change
document.querySelectorAll('#filterForm select').forEach(select => {
    select.addEventListener('change', () => {
        document.getElementById('filterForm').submit();
    });
});

// Add placeholder hints for datalist
const deptInput = document.querySelector('input[name="department"]');
if (deptInput) {
    deptInput.placeholder = "Type department name (e.g., Computer Science)";
}

const yearInput = document.querySelector('input[name="year"]');
if (yearInput) {
    yearInput.placeholder = "Type year (e.g., 1st Year, 2024)";
}
</script>

</body>
</html>
<?php $conn->close(); ?>