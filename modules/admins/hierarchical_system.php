<?php
// hierarchical_system.php
// Course → Chapter → Files system

include("../../includes/auth.php");
include("../../db.php");

// Fetch filters
$course = $_GET['course'] ?? '';
$chapter = $_GET['chapter'] ?? '';

// Build query
$query = "SELECT id, course_name, chapter, original_filename FROM course_files WHERE 1";
$params = [];
$types = "";

if (!empty($course)) {
    $query .= " AND course_name LIKE ?";
    $params[] = "%$course%";
    $types .= "s";
}

if (!empty($chapter)) {
    $query .= " AND chapter LIKE ?";
    $params[] = "%$chapter%";
    $types .= "s";
}

$query .= " ORDER BY course_name, chapter";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Organize into hierarchy
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['course_name']][$row['chapter']][] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Course → Chapters → Files</title>
    <style>
        body { font-family: Arial; margin: 30px; background:#f5f7fb; }
        h2 { color:#333; }
        .course { margin-top:20px; padding:15px; background:#fff; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        .chapter { margin-left:20px; margin-top:10px; }
        .file { margin-left:40px; }
        .btn { background:#007bff; color:#fff; padding:5px 10px; text-decoration:none; border-radius:5px; }
        input { padding:8px; margin-right:10px; }
    </style>
</head>
<body>

<h2>📂 Course → Chapters → Files</h2>

<form method="GET">
    <input type="text" name="course" placeholder="Course" value="<?= htmlspecialchars($course) ?>">
    <input type="text" name="chapter" placeholder="Chapter" value="<?= htmlspecialchars($chapter) ?>">
    <button type="submit">Search</button>
</form>

<?php if (empty($data)): ?>
    <p>No files found.</p>
<?php else: ?>

<?php foreach ($data as $courseName => $chapters): ?>
    <div class="course">
        <h3>📘 <?= htmlspecialchars($courseName) ?></h3>

        <?php foreach ($chapters as $chapterName => $files): ?>
            <div class="chapter">
                <strong>📖 <?= htmlspecialchars($chapterName) ?></strong>

                <?php foreach ($files as $file): ?>
                    <div class="file">
                        📄 <?= htmlspecialchars($file['original_filename']) ?>
                        <a class="btn" href="download_course_file.php?id=<?= $file['id'] ?>">Download</a>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endforeach; ?>

    </div>
<?php endforeach; ?>

<?php endif; ?>

</body>
</html>