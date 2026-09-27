<?php
// layout.php — reusable wrapper for pages
function renderPage($title, $contentFile) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo htmlspecialchars($title); ?></title>
        <link rel="stylesheet" href="/lmspro/assets/css/style.css">
    </head>
    <body>
        <?php include("nav.php"); ?>   <!-- Navigation bar -->

        <main>
            <?php include($contentFile); ?> <!-- Page-specific content -->
        </main>

        <?php include("footer.php"); ?> <!-- Footer -->
    </body>
    </html>
    <?php
}
?>
