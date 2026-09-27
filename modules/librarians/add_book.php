<?php

include("../../includes/auth.php");
include("../../db.php");

// Check if includes worked
if (!isset($_SESSION)) {
    die("Session not started. Auth include may have failed.");
}

if ($_SESSION['role'] !== "librarian") {
    die("Access denied");
}

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";

/* ================= HANDLE MESSAGE ================= */
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == "added") {
        $message = "✅ Book registered successfully!";
    } elseif ($_GET['msg'] == "deleted") {
        $message = "🗑️ Book deleted successfully!";
    } elseif ($_GET['msg'] == "error_delete") {
        $message = "❌ Error deleting book!";
    } elseif ($_GET['msg'] == "error_borrowed") {
        $message = "❌ Cannot delete: book is borrowed!";
    } elseif ($_GET['msg'] == "success") {
        $message = "✅ Book updated successfully!";
    }
}

/* ================= ADD BOOK ================= */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_book'])) {

    $title     = trim($_POST['title']);
    $author    = trim($_POST['author']);
    $category  = trim($_POST['category']);
    $isbn      = trim($_POST['isbn']);
    $publisher = trim($_POST['publisher']);
    $year      = !empty($_POST['year']) ? intval($_POST['year']) : null;
    $total_copies = intval($_POST['total_copies']);
    $available = $total_copies; // Initially, available equals total copies
    $shelf_location = trim($_POST['shelf_location'] ?? '');

    $imagePath = null;

    /* IMAGE UPLOAD */
    if (!empty($_FILES['image']['name'])) {

        $targetDir = "../../uploads/books/";

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $fileName = time() . "_" . rand(1000,9999) . "." . $ext;
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = $fileName;
        } else {
            $message = "❌ Image upload failed";
        }
    }

    if (empty($message)) {

        $stmt = $conn->prepare("
            INSERT INTO books 
            (title, author, category, isbn, publisher, year, total_copies, available, shelf_location, added_by, image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssiissi",
            $title,
            $author,
            $category,
            $isbn,
            $publisher,
            $year,
            $total_copies,
            $available,
            $shelf_location,
            $_SESSION['user_id'],
            $imagePath
        );

        if ($stmt->execute()) {
            header("Location: add_book.php?msg=added");
            exit;
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
    }
}

/* ================= DELETE BOOK ================= */
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $check = $conn->prepare("SELECT COUNT(*) FROM borrow_records WHERE book_id=?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        header("Location: add_book.php?msg=error_borrowed");
        exit;
    } else {
        $stmt = $conn->prepare("DELETE FROM books WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: add_book.php?msg=deleted");
            exit;
        } else {
            header("Location: add_book.php?msg=error_delete");
            exit;
        }
    }
}

/* ================= SEARCH ================= */
$search = $_GET['search'] ?? "";

// Debug: Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if books table exists
$table_check = $conn->query("SHOW TABLES LIKE 'books'");
if ($table_check->num_rows == 0) {
    die("Error: 'books' table does not exist in the database!");
}

// Get total count of books for debugging
$count_query = $conn->query("SELECT COUNT(*) as total FROM books");
$count_row = $count_query->fetch_assoc();
$total_books = $count_row['total'];

// Fetch books based on search
if (!empty($search)) {
    $stmt = $conn->prepare("
        SELECT * FROM books
        WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? OR shelf_location LIKE ? OR category LIKE ?
        ORDER BY id DESC
    ");

    $like = "%$search%";
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM books ORDER BY id DESC");
}

// Check if query was successful
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add/Manage Books - Library System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background:#f4f6f8; margin:0; padding:20px; }

.container { width:95%; margin:auto; background:white; padding:20px; border-radius:10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }

h2 { text-align:center; color:#2c3e50; margin-top:0; }

form {
    background:#fff;
    padding:20px;
    border-radius:10px;
    max-width:600px;
    margin:auto;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

input, select {
    width:100%;
    padding:10px;
    margin:8px 0;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 14px;
}

input:focus, select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 5px rgba(52,152,219,0.3);
}

input[type="submit"] {
    background: #27ae60;
    color: white;
    border: none;
    padding: 12px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    margin-top: 10px;
    transition: background 0.3s;
}

input[type="submit"]:hover {
    background: #229954;
}

table {
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
    background:#fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

th, td {
    padding:12px;
    border:1px solid #ddd;
    text-align:center;
    vertical-align: middle;
}

th {
    background:#2c3e50;
    color:#fff;
    position: sticky;
    top: 0;
    font-weight: 600;
}

tr:hover {
    background: #f8f9fa;
}

.message {
    text-align:center;
    font-weight:bold;
    margin:10px;
    padding:10px;
    border-radius:5px;
}

.message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.message.warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

a {
    text-decoration:none;
}

.btn-back {
    display: inline-block;
    margin: 10px 0;
    padding: 8px 15px;
    background: #3498db;
    color: white;
    border-radius: 5px;
    transition: background 0.3s;
}

.btn-back:hover {
    background: #2980b9;
}

.search-box {
    margin: 20px 0;
    text-align: right;
}

.search-box input {
    width: 280px;
    display: inline-block;
    margin-right: 5px;
}

.search-box button {
    padding: 10px 20px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
}

.search-box button:hover {
    background: #2980b9;
}

.debug-info {
    background: #f8f9fa;
    padding: 10px;
    margin: 10px 0;
    border-left: 4px solid #3498db;
    font-size: 12px;
    color: #666;
}

.form-hint {
    font-size: 11px;
    color: #7f8c8d;
    margin-top: -5px;
    margin-bottom: 10px;
}

.available-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.available-high {
    background: #d4edda;
    color: #155724;
}

.available-low {
    background: #fff3cd;
    color: #856404;
}

.available-none {
    background: #f8d7da;
    color: #721c24;
}

.shelf-location {
    font-family: monospace;
    font-size: 12px;
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
}

.category-tag {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    background: #e3f2fd;
    color: #1976d2;
}

.edit-btn {
    color: #3498db;
    margin-right: 8px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background 0.2s;
}

.delete-btn {
    color: #e74c3c;
    margin-left: 8px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background 0.2s;
}

.edit-btn:hover {
    background: #e3f2fd;
    text-decoration: none;
}

.delete-btn:hover {
    background: #fdeaea;
    text-decoration: none;
}

/* Responsive table */
@media (max-width: 768px) {
    .container {
        width: 100%;
        padding: 10px;
    }
    
    th, td {
        padding: 8px;
        font-size: 11px;
    }
    
    .search-box input {
        width: 160px;
    }
    
    form {
        padding: 15px;
    }
}
</style>

</head>

<body>

<div class="container">

<a href="../../dashboards/librarian.php" class="btn-back">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
</a>

<!-- Debug Information (remove after fixing) -->
<div class="debug-info">
    <i class="fas fa-info-circle"></i> <strong>Debug Info:</strong> Total books in database: <?php echo $total_books; ?> | 
    Current query: <?php echo !empty($search) ? "Searching for: '" . htmlspecialchars($search) . "'" : "Showing all books"; ?> |
    Results found: <?php echo $result->num_rows; ?>
</div>

<h2><i class="fas fa-plus-circle"></i> Add New Book</h2>

<?php 
// Display message with appropriate styling
if (!empty($message)): 
    $msg_class = "success";
    if (strpos($message, "❌") !== false) {
        $msg_class = "error";
    } elseif (strpos($message, "🗑️") !== false) {
        $msg_class = "warning";
    }
?>
    <div class="message <?php echo $msg_class; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<input type="text" name="title" placeholder="📖 Book Title" required>
<input type="text" name="author" placeholder="✍️ Author Name" required>

<!-- CATEGORY FIELD - Changed from select to text input -->
<input type="text" name="category" placeholder="📂 Category / Department (e.g., IT, Medical, Engineering, Business)" required>
<div class="form-hint">💡 Enter the department or category for this book (e.g., IT, CS, Medical, Nursing, Management, Geography, ECE, ME, AE, Accounting, Economics, Midwifery, Agro Economics)</div>

<input type="text" name="isbn" placeholder="📚 ISBN (Optional)">
<input type="text" name="publisher" placeholder="🏢 Publisher (Optional)">
<input type="number" name="year" placeholder="📅 Publication Year">

<!-- TOTAL COPIES FIELD -->
<input type="number" name="total_copies" id="total_copies" placeholder="Total Number of Copies" min="1" required value="1">
<div class="form-hint">📚 Enter how many copies of this book the library has</div>

<!-- SHELF LOCATION FIELD -->
<input type="text" name="shelf_location" placeholder="📍 Shelf Location (e.g., A-12, Section B, Row 3)">
<div class="form-hint">📍 Physical location of the book in the library</div>

<!-- AVAILABLE FIELD - Hidden, auto-calculated from total_copies -->
<input type="hidden" name="available" id="available" value="1">

<input type="file" name="image" accept="image/*">
<div class="form-hint">🖼️ Upload book cover image (optional)</div>

<input type="submit" name="add_book" value="➕ Add Book">

</form>

<!-- JavaScript to auto-set available = total_copies -->
<script>
document.getElementById('total_copies').addEventListener('change', function() {
    document.getElementById('available').value = this.value;
});
document.getElementById('total_copies').addEventListener('keyup', function() {
    document.getElementById('available').value = this.value;
});
</script>

<!-- Search Box -->
<div class="search-box">
    <form method="GET" action="add_book.php">
        <input type="text" name="search" placeholder="🔍 Search by title, author, ISBN, category, or shelf location..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($search)): ?>
            <a href="add_book.php" style="margin-left: 10px; color: #e74c3c;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<h2><i class="fas fa-book"></i> 📚 Existing Books</h2>

<div style="overflow-x: auto;">
<table>
<thead>
<tr>
    <th>Image</th>
    <th>Title</th>
    <th>Author</th>
    <th>Category</th>
    <th>ISBN</th>
    <th>Shelf Location</th>
    <th>Total Copies</th>
    <th>Available</th>
    <th>Borrowed</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>

<?php 
if ($result->num_rows > 0):
    while($row = $result->fetch_assoc()):
        $total = isset($row['total_copies']) ? $row['total_copies'] : 1;
        $available = isset($row['available']) ? $row['available'] : 0;
        $borrowed = $total - $available;
        $shelf_location = !empty($row['shelf_location']) ? $row['shelf_location'] : 'Not assigned';
        $category = !empty($row['category']) ? $row['category'] : 'General';
        
        // Determine availability class
        if ($available == 0) {
            $avail_class = "available-none";
            $avail_text = "Out of Stock";
        } elseif ($available <= 3) {
            $avail_class = "available-low";
            $avail_text = $available . " left";
        } else {
            $avail_class = "available-high";
            $avail_text = $available . " available";
        }
?>
<tr>
    <td>
    <?php if ($row['image'] && !empty($row['image'])): ?>
        <img src="../../uploads/books/<?= htmlspecialchars($row['image']) ?>" width="50" height="50" style="object-fit:cover; border-radius: 5px;">
    <?php else: ?>
        <span style="font-size: 24px;">📖</span>
    <?php endif; ?>
    </td>
    <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
    <td><?= htmlspecialchars($row['author']) ?></td>
    <td><span class="category-tag"><?= htmlspecialchars($category) ?></span></td>
    <td><?= htmlspecialchars($row['isbn']) ?: 'N/A' ?></td>
    <td>
        <span class="shelf-location">
            <i class="fas fa-location-dot"></i> <?= htmlspecialchars($shelf_location) ?>
        </span>
    </td>
    <td><strong><?= $total ?></strong> <?= $total > 1 ? 'copies' : 'copy' ?></td>
    <td>
        <span class="available-badge <?= $avail_class ?>">
            <?= $avail_text ?>
        </span>
    </td>
    <td>
        <?php if ($borrowed > 0): ?>
            <span style="color:#e67e22;"><i class="fas fa-book-reader"></i> <?= $borrowed ?> borrowed</span>
        <?php else: ?>
            <span style="color:#27ae60;"><i class="fas fa-check-circle"></i> 0 borrowed</span>
        <?php endif; ?>
    </td>
    <td>
        <a href="edit_book.php?id=<?= $row['id'] ?>" class="edit-btn">
            <i class="fas fa-edit"></i> Edit
        </a>
        <a href="?delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('⚠️ Are you sure you want to delete this book?\n\nThis action cannot be undone!')">
            <i class="fas fa-trash"></i> Delete
        </a>
    </td>
</tr>
<?php 
    endwhile;
else:
?>
<tr>
    <td colspan="10" style="text-align:center; padding: 40px;">
        <?php if (!empty($search)): ?>
            <i class="fas fa-search"></i> No books found matching "<?= htmlspecialchars($search) ?>"
        <?php else: ?>
            <i class="fas fa-book-open"></i> No books found in the database. Please add some books using the form above.
        <?php endif; ?>
    </td>
</tr>
<?php endif; ?>

</tbody>
</table>
</div>

</div>

</body>
<script src="../../assets/js/book_validation.js"></script>
</html>