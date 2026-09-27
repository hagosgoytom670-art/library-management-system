<?php
include("../../db.php");
session_start();

if ($_SESSION['role'] !== 'student') {
    die("Access denied");
}

$student_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

/* =====================
   AUTO EXPIRE REQUESTS (48 hours)
===================== */
$conn->query("
UPDATE reservations 
SET status='rejected', response_date=NOW()
WHERE status='pending' 
AND request_date < NOW() - INTERVAL 48 HOUR
");

/* =====================
   HANDLE REQUEST
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_book'])) {
    $book_id = (int)$_POST['book_id'];

    // Check if book exists
    $bookCheck = $conn->prepare("SELECT title, available FROM books WHERE id = ?");
    $bookCheck->bind_param("i", $book_id);
    $bookCheck->execute();
    $bookResult = $bookCheck->get_result();
    $bookData = $bookResult->fetch_assoc();
    
    if (!$bookData) {
        $message = "❌ Book not found!";
        $message_type = "error";
    } else {
        // prevent duplicate request
        $check = $conn->prepare("
            SELECT id FROM reservations 
            WHERE student_id=? AND book_id=? 
            AND status IN ('pending','approved')
        ");
        $check->bind_param("ii", $student_id, $book_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "❌ You already requested this book.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO reservations (student_id, book_id, request_date, status) VALUES (?, ?, NOW(), 'pending')");
            $stmt->bind_param("ii", $student_id, $book_id);
            if ($stmt->execute()) {
                $message = "✅ Request for '{$bookData['title']}' sent successfully!";
                $message_type = "success";
            } else {
                $message = "❌ Error sending request. Please try again.";
                $message_type = "error";
            }
        }
    }
}

/* =====================
   SEARCH FUNCTIONALITY - ALL TEXT FIELDS
===================== */
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$availability_filter = isset($_GET['availability']) ? $_GET['availability'] : '';

/* =====================
   BOOK LIST WITH SEARCH
===================== */
$sql = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_term)) {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
    $like = "%$search_term%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($category_filter)) {
    $sql .= " AND category LIKE ?";
    $params[] = "%$category_filter%";
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
    $books = $stmt->get_result();
} else {
    $books = $conn->query($sql);
}

/* =====================
   MY RESERVATIONS
===================== */
$stmt = $conn->prepare("
    SELECT r.id, b.title, b.author, r.status, r.request_date, r.response_date
    FROM reservations r
    JOIN books b ON r.book_id = b.id
    WHERE r.student_id = ?
    ORDER BY r.request_date DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$reservations = $stmt->get_result();

// Get statistics
$pending_count = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE student_id = $student_id AND status = 'pending'")->fetch_assoc()['count'];
$approved_count = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE student_id = $student_id AND status = 'approved'")->fetch_assoc()['count'];
$rejected_count = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE student_id = $student_id AND status = 'rejected'")->fetch_assoc()['count'];
$total_requests = $pending_count + $approved_count + $rejected_count;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Reservation - Library System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        /* Message Styles */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
        
        .stat-card.pending { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.approved { background: linear-gradient(135deg, #27ae60, #229954); }
        .stat-card.rejected { background: linear-gradient(135deg, #e74c3c, #c0392b); }
        .stat-card.total { background: linear-gradient(135deg, #3498db, #2980b9); }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 13px;
            opacity: 0.9;
        }
        
        /* Search Filter Section */
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
            align-items: flex-end;
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
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .filter-group input::placeholder {
            color: #adb5bd;
            font-size: 12px;
        }
        
        .help-text {
            font-size: 11px;
            color: #6c757d;
            margin-top: 4px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
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
        
        /* Tables */
        .table-container {
            overflow-x: auto;
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #2c3e50;
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-request {
            background: #28a745;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }
        
        .btn-request:hover {
            background: #218838;
        }
        
        .btn-request:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        .book-unavailable {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .book-available {
            color: #27ae60;
            font-weight: bold;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            th, td {
                padding: 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="../../dashboards/student.php" class="back-link">← Back to Dashboard</a>
        <h2>📚 Book Reservation System</h2>
        <p class="subtitle">Request books and get notified when they become available</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card total">
            <div class="stat-number"><?php echo $total_requests; ?></div>
            <div class="stat-label">Total Requests</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-number"><?php echo $pending_count; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card approved">
            <div class="stat-number"><?php echo $approved_count; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card rejected">
            <div class="stat-number"><?php echo $rejected_count; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
    
    <!-- Search Filter Section - Category is now TEXT FIELD -->
    <div class="filter-section">
        <h3 style="margin-bottom: 15px; color: #2c3e50;">🔍 Find Books</h3>
        <form method="GET" id="filterForm">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Search by Title, Author or ISBN</label>
                    <input type="text" name="search" placeholder="Enter book title, author or ISBN..." 
                           value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="filter-group">
                    <label>Category (Type to search)</label>
                    <input type="text" name="category" placeholder="e.g., Programming, Science, Fiction..." 
                           value="<?php echo htmlspecialchars($category_filter); ?>">
                    <div class="help-text">💡 Type any category name to filter books</div>
                </div>
                <div class="filter-group">
                    <label>Availability</label>
                    <select name="availability">
                        <option value="">All Books</option>
                        <option value="available" <?php echo $availability_filter == 'available' ? 'selected' : ''; ?>>Available Now</option>
                        <option value="unavailable" <?php echo $availability_filter == 'unavailable' ? 'selected' : ''; ?>>Currently Unavailable</option>
                    </select>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn-primary">🔍 Search</button>
                    <a href="student_reservations.php" class="btn-secondary">🔄 Reset</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Books Table -->
    <div class="table-container">
        <h3 style="margin-bottom: 15px; color: #2c3e50;">📖 Books Available for Reservation</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>ISBN</th>
                    <th>Available Copies</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($books && $books->num_rows > 0): ?>
                    <?php while($b = $books->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($b['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($b['author'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($b['category'] ?? 'General'); ?></td>
                            <td><?php echo htmlspecialchars($b['isbn'] ?? 'N/A'); ?></td>
                            <td class="<?php echo $b['available'] > 0 ? 'book-available' : 'book-unavailable'; ?>">
                                <?php echo $b['available'] > 0 ? "✅ {$b['available']} copy(s)" : "❌ Not Available"; ?>
                             </td>
                            <td>
                                <?php if ($b['available'] > 0): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" name="request_book" class="btn-request">
                                            📝 Request
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" name="request_book" class="btn-request">
                                            🔔 Notify Me
                                        </button>
                                    </form>
                                <?php endif; ?>
                             </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-results">
                            📭 No books found matching your criteria
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- My Reservations Table -->
    <div class="table-container">
        <h3 style="margin-bottom: 15px; color: #2c3e50;">📋 My Reservation Requests</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Request Date</th>
                    <th>Response Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reservations->num_rows > 0): ?>
                    <?php while($r = $reservations->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['author'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $r['status']; ?>">
                                    <?php 
                                        if ($r['status'] === 'pending') echo "⏳ Pending";
                                        elseif ($r['status'] === 'approved') echo "✅ Approved";
                                        else echo "❌ Rejected";
                                    ?>
                                </span>
                             </td>
                            <td><?php echo date('M d, Y - h:i A', strtotime($r['request_date'])); ?></td>
                            <td>
                                <?php echo $r['response_date'] ? date('M d, Y - h:i A', strtotime($r['response_date'])) : '-'; ?>
                             </td>
                         </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <td>
                        <td colspan="5" class="no-results">
                            📭 No reservation requests yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Information Box -->
    <div style="background: #ecf0f1; padding: 15px; border-radius: 8px; margin-top: 20px;">
        <strong>ℹ️ How Reservations Work:</strong>
        <ul style="margin-top: 10px; margin-left: 20px; color: #555;">
            <li>Reservations are valid for <strong>48 hours</strong> - you'll be notified when approved</li>
            <li>You can request books that are currently unavailable</li>
            <li>Once approved, you'll have <strong>24 hours</strong> to borrow the book</li>
            <li>Maximum <strong>3 active reservation requests</strong> at a time</li>
            <li>Reservations expire automatically after 48 hours if not processed</li>
            <li>You can search books by typing any category name in the category field</li>
        </ul>
    </div>
</div>
</body>
</html>