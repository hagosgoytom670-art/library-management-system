<?php
include '../../db.php'; // your DB connection file

$search = "";
$category = "";
$whereClauses = [];
$params = [];
$types = "";

// Handle search input
if (isset($_POST['search']) && !empty(trim($_POST['search']))) {
    $search = trim($_POST['search']);
    $whereClauses[] = "title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Handle category filter
if (isset($_POST['category']) && !empty($_POST['category'])) {
    $category = $_POST['category'];
    $whereClauses[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

// Build the query
$sql = "SELECT * FROM books";
if (count($whereClauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY title ASC";

// Execute query
$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get unique categories for filter dropdown
$categoryResult = $conn->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = [];
while ($cat = $categoryResult->fetch_assoc()) {
    $categories[] = $cat['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Catalog - RU Digital Library</title>
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
            min-height: 100vh;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

        /* Header & Navigation */
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

        .header h1 {
            font-size: 1.6rem;
            font-weight: 600;
        }

        .header h1 small {
            font-size: 0.8rem;
            opacity: 0.8;
            font-weight: normal;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateX(-3px);
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 180px;
        }

        .filter-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #e0e4e8;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #0a4b8c;
            box-shadow: 0 0 0 3px rgba(10, 75, 140, 0.1);
        }

        .search-btn {
            background: #0a4b8c;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .search-btn:hover {
            background: #003d6b;
            transform: translateY(-1px);
        }

        .reset-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .reset-btn:hover {
            background: #5a6268;
        }

        /* Results Info */
        .results-info {
            background: #e8f0fe;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .results-count {
            font-size: 0.9rem;
            color: #0a4b8c;
            font-weight: 500;
        }

        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter-badge {
            background: #0a4b8c;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .filter-badge a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            margin-left: 5px;
        }

        .filter-badge a:hover {
            text-decoration: underline;
        }

        /* Book Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .book-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .book-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .book-image {
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 1px solid #eee;
        }

        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
        }

        .book-image .no-image {
            text-align: center;
            color: #adb5bd;
            font-size: 0.8rem;
        }

        .book-info {
            padding: 15px;
        }

        .book-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a2c3e;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .book-author {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .book-category {
            display: inline-block;
            background: #e9ecef;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
        }

        .availability {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-low {
            background: #fff3cd;
            color: #856404;
        }

        .status-unavailable {
            background: #f8d7da;
            color: #721c24;
        }

        .copy-count {
            font-size: 0.7rem;
            color: #6c757d;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-state h3 {
            color: #6c757d;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #adb5bd;
        }

        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .books-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header Section -->
    <div class="header">
        <h1>
            📚 Book Catalog 
            <small>Discover your next read</small>
        </h1>
        <a href="../../dashboards/student.php" class="back-btn">← Back to Dashboard</a>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="POST" action="" class="filter-form">
            <div class="filter-group">
                <label>🔍 Search by Title</label>
                <input type="text" name="search" placeholder="Enter book title..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>📂 Filter by Category</label>
                <select name="category">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category == $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="search-btn">🔍 Apply Filters</button>
            <a href="?" class="reset-btn">Reset All</a>
        </form>
    </div>

    <!-- Results Info -->
    <div class="results-info">
        <div class="results-count">
            📖 Found <strong><?php echo $result->num_rows; ?></strong> book(s)
        </div>
        <?php if (!empty($search) || !empty($category)): ?>
        <div class="active-filters">
            <?php if (!empty($search)): ?>
                <span class="filter-badge">
                    Search: "<?php echo htmlspecialchars($search); ?>"
                    <a href="?">&times;</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($category)): ?>
                <span class="filter-badge">
                    Category: <?php echo htmlspecialchars($category); ?>
                    <a href="?">&times;</a>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Book Grid Display -->
    <?php if ($result->num_rows > 0): ?>
        <div class="books-grid">
            <?php while ($row = $result->fetch_assoc()): 
                $available = intval($row['available']);
                $statusClass = '';
                $statusText = '';
                if ($available > 5) {
                    $statusClass = 'status-available';
                    $statusText = '✓ Available';
                } elseif ($available > 0 && $available <= 5) {
                    $statusClass = 'status-low';
                    $statusText = '⚠ Low Stock';
                } else {
                    $statusClass = 'status-unavailable';
                    $statusText = '✗ Unavailable';
                }
            ?>
                <div class="book-card">
                    <div class="book-image">
                        <?php if (!empty($row['image']) && file_exists('../../uploads/books/' . $row['image'])): ?>
                            <img src="../../uploads/books/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                        <?php else: ?>
                            <div class="no-image">
                                📖<br>
                                <span style="font-size: 11px;">No Cover</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="book-info">
                        <div class="book-title"><?php echo htmlspecialchars($row['title']); ?></div>
                        <div class="book-author">✍️ <?php echo htmlspecialchars($row['author']); ?></div>
                        <span class="book-category">📂 <?php echo htmlspecialchars($row['category']); ?></span>
                        <div class="availability">
                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            <span class="copy-count"><?php echo $available; ?> copy(s)</span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>📭 No books found</h3>
            <p>Try adjusting your search or category filter, or browse all books.</p>
            <a href="?" style="display: inline-block; margin-top: 15px; background: #0a4b8c; color: white; padding: 8px 20px; border-radius: 8px; text-decoration: none;">View All Books</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>