<?php
include '../../db.php';
session_start();

// Check authentication
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'librarian' && $_SESSION['role'] !== 'admin')) {
    die("Access denied");
}

// Fetch book data for editing
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    
    if (!$book) {
        header("Location: add_book.php?msg=error");
        exit();
    }
} else {
    header("Location: add_book.php?msg=error");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $category = $_POST['category'] ?? '';
    $isbn = $_POST['isbn'] ?? '';
    $publisher = $_POST['publisher'] ?? '';
    $year = $_POST['year'] ?? '';
    $new_total_copies = isset($_POST['total_copies']) ? (int)$_POST['total_copies'] : 0;
    $shelf_location = $_POST['shelf_location'] ?? '';
    
    // IMPORTANT: Get the old values to calculate correctly
    $old_total_copies = (int)($book['total_copies'] ?? 0);
    $old_available = (int)($book['available'] ?? 0);
    
    // Calculate how many copies are currently borrowed
    // Borrowed = Total - Available
    $borrowed_count = $old_total_copies - $old_available;
    
    // Calculate new available copies based on new total copies
    // Keep the same number of borrowed copies
    $new_available = $new_total_copies - $borrowed_count;
    
    // Ensure available doesn't go negative
    if ($new_available < 0) {
        $new_available = 0;
    }
    
    // Handle image upload
    $image_name = $book['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            // Delete old image if exists
            if (!empty($book['image']) && file_exists('../../uploads/books/' . $book['image'])) {
                unlink('../../uploads/books/' . $book['image']);
            }
            
            $image_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
            $upload_path = '../../uploads/books/' . $image_name;
            
            if (!file_exists('../../uploads/books/')) {
                mkdir('../../uploads/books/', 0777, true);
            }
            
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
        }
    }
    
    // Validate inputs
    if (empty($title) || empty($author)) {
        $error = "Title and author are required.";
    } else {
        // Update ALL fields including available
        $updateStmt = $conn->prepare("UPDATE books SET title = ?, author = ?, category = ?, isbn = ?, publisher = ?, year = ?, total_copies = ?, available = ?, shelf_location = ?, image = ? WHERE id = ?");
        $updateStmt->bind_param("ssssssiissi", $title, $author, $category, $isbn, $publisher, $year, $new_total_copies, $new_available, $shelf_location, $image_name, $id);
        
        if ($updateStmt->execute()) {
            header("Location: add_book.php?msg=success&book_title=" . urlencode($title));
            exit();
        } else {
            $error = "Error updating book: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - RU Library</title>
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
            max-width: 900px;
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

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 0.85rem;
        }

        label .required {
            color: #e74c3c;
        }

        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #e0e4e8;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
            font-family: inherit;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #0a4b8c;
            box-shadow: 0 0 0 3px rgba(10, 75, 140, 0.1);
        }

        .help-text {
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .error-message {
            background: #fef2f2;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn-submit {
            background: #0a4b8c;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
        }

        .btn-submit:hover {
            background: #003d6b;
            transform: translateY(-1px);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .current-image {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .current-image img {
            border-radius: 6px;
            margin-top: 8px;
        }

        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e0e4e8;
        }

        .info-note {
            background: #e7f3ff;
            border-left: 4px solid #0a4b8c;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #004080;
        }

        .stats-badge {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: space-around;
            text-align: center;
        }

        .stat-item {
            flex: 1;
        }

        .stat-label {
            font-size: 0.7rem;
            color: #6c757d;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 1.3rem;
            font-weight: bold;
            color: #0a4b8c;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .full-width {
                grid-column: span 1;
            }
            
            .container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>✏️ Edit Book Details</h2>
        <a href="add_book.php" class="back-link">← Back to Book Management</a>
    </div>

    <div class="form-card">
        <?php
        // Calculate current stats
        $old_total = (int)($book['total_copies'] ?? 0);
        $old_avail = (int)($book['available'] ?? 0);
        $current_borrowed = $old_total - $old_avail;
        ?>
        
        <div class="stats-badge">
            <div class="stat-item">
                <div class="stat-label">Current Total Copies</div>
                <div class="stat-value"><?php echo $old_total; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Currently Available</div>
                <div class="stat-value"><?php echo $old_avail; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Currently Borrowed</div>
                <div class="stat-value"><?php echo $current_borrowed; ?></div>
            </div>
        </div>

        <div class="info-note">
            ℹ️ <strong>Important:</strong> When you change the total number of copies, the available copies will be automatically adjusted to maintain the same number of borrowed books (<?php echo $current_borrowed; ?> currently borrowed).
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Author <span class="required">*</span></label>
                    <input type="text" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Category / Department</label>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($book['category'] ?? ''); ?>">
                    <div class="help-text">e.g., IT, CS, Medical, Nursing, Management, etc.</div>
                </div>
                
                <div class="form-group">
                    <label>ISBN</label>
                    <input type="text" name="isbn" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Publisher</label>
                    <input type="text" name="publisher" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Publication Year</label>
                    <input type="number" name="year" min="1800" max="2026" value="<?php echo htmlspecialchars($book['year'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Total Copies</label>
                    <input type="number" name="total_copies" id="total_copies" min="0" value="<?php echo $old_total; ?>" 
                           onchange="updateAvailableHint(this.value, <?php echo $current_borrowed; ?>)">
                    <div class="help-text" id="availableHint">
                        Currently borrowed: <?php echo $current_borrowed; ?> copies
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label>Shelf Location</label>
                    <input type="text" name="shelf_location" placeholder="e.g., A-12, Section B" value="<?php echo htmlspecialchars($book['shelf_location'] ?? ''); ?>">
                    <div class="help-text">Physical location of the book in the library</div>
                </div>
            </div>

            <?php if (!empty($book['image']) && file_exists('../../uploads/books/' . $book['image'])): ?>
                <hr>
                <div class="form-group">
                    <label>Current Book Cover</label>
                    <div class="current-image">
                        <img src="../../uploads/books/<?php echo htmlspecialchars($book['image']); ?>" width="100" style="border-radius: 6px;">
                        <div class="help-text">Upload a new image below to replace it</div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Book Cover Image (Optional)</label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/jpg">
                <div class="help-text">Upload JPG or PNG image. Leave empty to keep current image.</div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-submit">💾 Update Book</button>
                <a href="add_book.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    const currentBorrowed = <?php echo $current_borrowed; ?>;
    
    function updateAvailableHint(newTotal, borrowed) {
        const hintSpan = document.getElementById('availableHint');
        const newAvailable = newTotal - borrowed;
        if (newAvailable >= 0) {
            hintSpan.innerHTML = `📊 Current borrowed: ${borrowed} copies → New available will be: ${newAvailable} copies`;
            hintSpan.style.color = '#28a745';
            hintSpan.style.fontWeight = 'bold';
        } else {
            hintSpan.innerHTML = `⚠️ WARNING: New total (${newTotal}) is less than borrowed copies (${borrowed}). Available will be set to 0.`;
            hintSpan.style.color = '#e74c3c';
            hintSpan.style.fontWeight = 'bold';
        }
    }
    
    // Initialize hint on page load
    document.addEventListener('DOMContentLoaded', function() {
        const totalInput = document.getElementById('total_copies');
        if (totalInput) {
            updateAvailableHint(totalInput.value, currentBorrowed);
        }
    });
</script>

</body>
</html>