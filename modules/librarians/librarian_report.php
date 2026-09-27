<?php
include("../../includes/auth.php");
include("../../db.php");

// Check authentication
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'librarian' && $_SESSION['role'] !== 'admin')) {
    die("Access Denied");
}

// Get filter parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'books';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$student_year = isset($_GET['student_year']) ? $_GET['student_year'] : '';
$student_department = isset($_GET['student_department']) ? $_GET['student_department'] : '';
$registration_year = isset($_GET['registration_year']) ? $_GET['registration_year'] : '';
$available_status = isset($_GET['available_status']) ? $_GET['available_status'] : '';

// Get unique departments/categories from books
$departments_query = $conn->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
$departments = [];
if ($departments_query) {
    while ($row = $departments_query->fetch_assoc()) {
        $departments[] = $row['category'];
    }
}

// Get student departments
$student_dept_query = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' AND role = 'student' ORDER BY department");
$student_departments = [];
if ($student_dept_query) {
    while ($row = $student_dept_query->fetch_assoc()) {
        $student_departments[] = $row['department'];
    }
}

// Get available years for registration
$years_query = $conn->query("SELECT DISTINCT YEAR(registered_calendar) as year FROM users WHERE role = 'student' ORDER BY year DESC");
$registration_years = [];
if ($years_query) {
    while ($row = $years_query->fetch_assoc()) {
        $registration_years[] = $row['year'];
    }
}

// Build queries based on report type
$books_result = null;
$students_result = null;
$book_summary = [];
$student_summary = [];

// BOOKS REPORT
if ($report_type == 'books') {
    $book_sql = "SELECT b.*, 
                  (SELECT COUNT(*) FROM borrow_records WHERE book_id = b.id AND (status = 'borrowed' OR status = 'issued')) as borrowed_count 
                  FROM books b WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($department)) {
        $book_sql .= " AND b.category = ?";
        $params[] = $department;
        $types .= "s";
    }
    
    if (!empty($category)) {
        $book_sql .= " AND b.category LIKE ?";
        $params[] = "%$category%";
        $types .= "s";
    }
    
    if ($available_status == 'available') {
        $book_sql .= " AND b.available > 0";
    } elseif ($available_status == 'unavailable') {
        $book_sql .= " AND b.available = 0";
    } elseif ($available_status == 'low_stock') {
        $book_sql .= " AND b.available > 0 AND b.available <= (b.total_copies / 3)";
    }
    
    $book_sql .= " ORDER BY b.title ASC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($book_sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $books_result = $stmt->get_result();
    } else {
        $books_result = $conn->query($book_sql);
    }
    
    // Get summary statistics
    $summary_sql = "SELECT 
                      COUNT(*) as total_books,
                      SUM(total_copies) as total_copies,
                      SUM(available) as available_copies,
                      SUM(total_copies - available) as borrowed_copies
                    FROM books WHERE 1=1";
    
    if (!empty($department)) {
        $summary_sql .= " AND category = '$department'";
    }
    if (!empty($category)) {
        $summary_sql .= " AND category LIKE '%$category%'";
    }
    
    $summary_result = $conn->query($summary_sql);
    if ($summary_result) {
        $book_summary = $summary_result->fetch_assoc();
    }
}

// STUDENTS REPORT
if ($report_type == 'students') {
    $student_sql = "SELECT u.*, 
                    (SELECT COUNT(*) FROM borrow_records WHERE user_id = u.id AND (status = 'borrowed' OR status = 'issued')) as books_borrowed,
                    (SELECT COUNT(*) FROM borrow_records WHERE user_id = u.id) as total_borrowed
                    FROM users u WHERE u.role = 'student'";
    $params = [];
    $types = "";
    
    if (!empty($student_year)) {
        $student_sql .= " AND u.year = ?";
        $params[] = $student_year;
        $types .= "s";
    }
    
    if (!empty($student_department)) {
        $student_sql .= " AND u.department = ?";
        $params[] = $student_department;
        $types .= "s";
    }
    
    if (!empty($registration_year)) {
        $student_sql .= " AND YEAR(u.registered_calendar) = ?";
        $params[] = $registration_year;
        $types .= "i";
    }
    
    $student_sql .= " ORDER BY u.username ASC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($student_sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $students_result = $stmt->get_result();
    } else {
        $students_result = $conn->query($student_sql);
    }
    
    // Get student summary statistics
    $summary_sql = "SELECT 
                      COUNT(*) as total_students,
                      SUM(CASE WHEN year = '1' OR year = '1st Year' OR year = '1st' THEN 1 ELSE 0 END) as first_year,
                      SUM(CASE WHEN year = '2' OR year = '2nd Year' OR year = '2nd' THEN 1 ELSE 0 END) as second_year,
                      SUM(CASE WHEN year = '3' OR year = '3rd Year' OR year = '3rd' THEN 1 ELSE 0 END) as third_year,
                      SUM(CASE WHEN year = '4' OR year = '4th Year' OR year = '4th' THEN 1 ELSE 0 END) as fourth_year,
                      SUM(CASE WHEN year = '5' OR year = '5th Year' OR year = '5th' THEN 1 ELSE 0 END) as fifth_year,
                      SUM(CASE WHEN year = 'Graduate' OR year = 'graduated' OR year = 'Grad' THEN 1 ELSE 0 END) as graduate
                    FROM users WHERE role = 'student'";
    
    if (!empty($student_department)) {
        $summary_sql .= " AND department = '$student_department'";
    }
    if (!empty($registration_year)) {
        $summary_sql .= " AND YEAR(registered_calendar) = $registration_year";
    }
    
    $summary_result = $conn->query($summary_sql);
    if ($summary_result) {
        $student_summary = $summary_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Reports | Analytics Dashboard</title>
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
        }

        .header {
            background: linear-gradient(135deg, #0a4b8c, #003d6b);
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 25px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h2 {
            font-size: 1.5rem;
        }

        .back-link {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            background: white;
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            color: #6c757d;
        }

        .tab-btn.active {
            background: #0a4b8c;
            color: white;
        }

        .tab-btn:hover:not(.active) {
            background: #e9ecef;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 0.75rem;
            margin-bottom: 5px;
            color: #495057;
            text-transform: uppercase;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 1.5px solid #e0e4e8;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #0a4b8c;
            box-shadow: 0 0 0 3px rgba(10, 75, 140, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .btn-primary {
            background: #0a4b8c;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: #003d6b;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-print {
            background: #27ae60;
        }

        .btn-print:hover {
            background: #229954;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
        }

        .summary-card:hover {
            transform: translateY(-3px);
        }

        .summary-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .summary-number {
            font-size: 2rem;
            font-weight: bold;
            color: #0a4b8c;
        }

        .summary-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            position: sticky;
            top: 0;
        }

        tr:hover td {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .badge-available {
            background: #d4edda;
            color: #155724;
        }

        .badge-low {
            background: #fff3cd;
            color: #856404;
        }

        .badge-out {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .no-results {
            text-align: center;
            padding: 60px;
            color: #6c757d;
        }

        .no-results p:first-child {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .header, .filter-section, .report-tabs, .back-link, .btn-print {
                display: none;
            }
            .table-container {
                box-shadow: none;
            }
            th {
                background: #f0f0f0;
            }
            .summary-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            th, td {
                padding: 8px;
                font-size: 0.8rem;
            }
            .summary-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>📊 Library Reports & Analytics</h2>
        <a href="../../dashboards/librarian.php" class="back-link">← Back to Dashboard</a>
    </div>

    <div class="report-tabs">
        <button class="tab-btn <?php echo $report_type == 'books' ? 'active' : ''; ?>" onclick="setReportType('books')">
            📚 Books Report
        </button>
        <button class="tab-btn <?php echo $report_type == 'students' ? 'active' : ''; ?>" onclick="setReportType('students')">
            👨‍🎓 Students Report
        </button>
    </div>

    <!-- BOOKS FILTER SECTION -->
    <div id="booksFilter" class="filter-section" <?php echo $report_type != 'books' ? 'style="display:none;"' : ''; ?>>
        <div class="filter-title">📖 Filter Books</div>
        <form method="GET" action="">
            <input type="hidden" name="report_type" value="books">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Department / Category</label>
                    <select name="department">
                        <option value="">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department == $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Category Search</label>
                    <input type="text" name="category" placeholder="Search category..." value="<?php echo htmlspecialchars($category); ?>">
                </div>
                <div class="filter-group">
                    <label>Availability Status</label>
                    <select name="available_status">
                        <option value="">All Books</option>
                        <option value="available" <?php echo $available_status == 'available' ? 'selected' : ''; ?>>Available Now</option>
                        <option value="low_stock" <?php echo $available_status == 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="unavailable" <?php echo $available_status == 'unavailable' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">🔍 Generate Report</button>
                <a href="?report_type=books" class="btn-secondary">Reset Filters</a>
                <button type="button" class="btn-secondary btn-print" onclick="window.print()">🖨️ Print</button>
            </div>
        </form>
    </div>

    <!-- STUDENTS FILTER SECTION -->
    <div id="studentsFilter" class="filter-section" <?php echo $report_type != 'students' ? 'style="display:none;"' : ''; ?>>
        <div class="filter-title">👨‍🎓 Filter Students</div>
        <form method="GET" action="">
            <input type="hidden" name="report_type" value="students">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Year Level</label>
                    <select name="student_year">
                        <option value="">All Years</option>
                        <option value="1" <?php echo $student_year == '1' ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo $student_year == '2' ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo $student_year == '3' ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo $student_year == '4' ? 'selected' : ''; ?>>4th Year</option>
                        <option value="5" <?php echo $student_year == '5' ? 'selected' : ''; ?>>5th Year</option>
                        <option value="Graduate" <?php echo $student_year == 'Graduate' ? 'selected' : ''; ?>>Graduate</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Department</label>
                    <select name="student_department">
                        <option value="">All Departments</option>
                        <?php foreach($student_departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $student_department == $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Registration Year</label>
                    <select name="registration_year">
                        <option value="">All Years</option>
                        <?php foreach($registration_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $registration_year == $year ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">🔍 Generate Report</button>
                <a href="?report_type=students" class="btn-secondary">Reset Filters</a>
                <button type="button" class="btn-secondary btn-print" onclick="window.print()">🖨️ Print</button>
            </div>
        </form>
    </div>

    <!-- BOOKS REPORT SUMMARY -->
    <?php if ($report_type == 'books' && $books_result && $books_result->num_rows > 0): ?>
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon">📚</div>
            <div class="summary-number"><?php echo number_format($book_summary['total_books'] ?? 0); ?></div>
            <div class="summary-label">Total Titles</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📖</div>
            <div class="summary-number"><?php echo number_format($book_summary['total_copies'] ?? 0); ?></div>
            <div class="summary-label">Total Copies</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">✅</div>
            <div class="summary-number"><?php echo number_format($book_summary['available_copies'] ?? 0); ?></div>
            <div class="summary-label">Available Copies</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📤</div>
            <div class="summary-number"><?php echo number_format($book_summary['borrowed_copies'] ?? 0); ?></div>
            <div class="summary-label">Borrowed Copies</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- STUDENTS REPORT SUMMARY -->
    <?php if ($report_type == 'students'): ?>
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon">👨‍🎓</div>
            <div class="summary-number"><?php echo number_format($student_summary['total_students'] ?? 0); ?></div>
            <div class="summary-label">Total Students</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📘</div>
            <div class="summary-number"><?php echo number_format($student_summary['first_year'] ?? 0); ?></div>
            <div class="summary-label">1st Year</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📗</div>
            <div class="summary-number"><?php echo number_format($student_summary['second_year'] ?? 0); ?></div>
            <div class="summary-label">2nd Year</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📕</div>
            <div class="summary-number"><?php echo number_format($student_summary['third_year'] ?? 0); ?></div>
            <div class="summary-label">3rd Year</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">📙</div>
            <div class="summary-number"><?php echo number_format($student_summary['fourth_year'] ?? 0); ?></div>
            <div class="summary-label">4th Year</div>
        </div>
        <div class="summary-card">
            <div class="summary-icon">🎓</div>
            <div class="summary-number"><?php echo number_format(($student_summary['fifth_year'] ?? 0) + ($student_summary['graduate'] ?? 0)); ?></div>
            <div class="summary-label">5th+ Year</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- BOOKS REPORT TABLE -->
    <div id="booksTable" class="table-container" <?php echo $report_type != 'books' ? 'style="display:none;"' : ''; ?>>
        <?php if ($books_result && $books_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>ISBN</th>
                    <th>Total</th>
                    <th>Available</th>
                    <th>Borrowed</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($book = $books_result->fetch_assoc()): 
                    $status_class = $book['available'] <= 0 ? 'badge-out' : ($book['available'] <= ($book['total_copies'] / 3) ? 'badge-low' : 'badge-available');
                    $status_text = $book['available'] <= 0 ? 'Out of Stock' : ($book['available'] <= ($book['total_copies'] / 3) ? 'Low Stock' : 'Available');
                ?>
                <tr>
                    <td><?php echo $book['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($book['author'] ?? 'N/A'); ?></td>
                    <td><span style="background:#e3f2fd; padding:4px 8px; border-radius:12px; font-size:11px;"><?php echo htmlspecialchars($book['category'] ?? 'General'); ?></span></td>
                    <td><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></td>
                    <td><?php echo $book['total_copies']; ?></td>
                    <td><?php echo $book['available']; ?></td>
                    <td><?php echo $book['borrowed_count'] ?? 0; ?></td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="no-results">
                <p>📭 No books found matching your criteria</p>
                <p>Try adjusting your filters or add books to the library catalog</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- STUDENTS REPORT TABLE -->
    <div id="studentsTable" class="table-container" <?php echo $report_type != 'students' ? 'style="display:none;"' : ''; ?>>
        <?php if ($students_result && $students_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Email</th>
                    <th>ID Number</th>
                    <th>Department</th>
                    <th>Year Level</th>
                    <th>Registered Date</th>
                    <th>Books Borrowed</th>
                    <th>Total Borrows</th>
                </tr>
            </thead>
            <tbody>
                <?php while($student = $students_result->fetch_assoc()): 
                    $year_display = $student['year'];
                    if ($student['year'] == '1') $year_display = '1st Year';
                    elseif ($student['year'] == '2') $year_display = '2nd Year';
                    elseif ($student['year'] == '3') $year_display = '3rd Year';
                    elseif ($student['year'] == '4') $year_display = '4th Year';
                    elseif ($student['year'] == '5') $year_display = '5th Year';
                ?>
                <tr>
                    <td><?php echo $student['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($student['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                    <td><span style="background:#e3f2fd; padding:4px 8px; border-radius:12px; font-size:11px;"><?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?></span></td>
                    <td><?php echo $year_display; ?></td>
                    <td><?php echo date('M d, Y', strtotime($student['registered_calendar'])); ?></td>
                    <td><span class="badge badge-active"><?php echo $student['books_borrowed'] ?? 0; ?> currently</span></td>
                    <td><?php echo $student['total_borrowed'] ?? 0; ?> total</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="no-results">
                <p>📭 No students found matching your criteria</p>
                <p>Try adjusting your filters or add students to the system</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function setReportType(type) {
        const url = new URL(window.location.href);
        url.searchParams.set('report_type', type);
        if (type === 'books') {
            url.searchParams.delete('student_year');
            url.searchParams.delete('student_department');
            url.searchParams.delete('registration_year');
        } else {
            url.searchParams.delete('department');
            url.searchParams.delete('category');
            url.searchParams.delete('available_status');
        }
        window.location.href = url.toString();
    }
</script>
</body>
</html>