<?php 
include("../includes/auth.php");
include("../db.php");

// Initialize variables
$total_books = 0;
$total_students = 0;
$books_issued = 0;
$pending_reservations = 0;

// Get total books
$book_query = $conn->query("SELECT COUNT(*) as count FROM books");
if($book_query) {
    $row = $book_query->fetch_assoc();
    $total_books = $row['count'];
}

// Get total students (users with role 'student')
$student_query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
if($student_query) {
    $row = $student_query->fetch_assoc();
    $total_students = $row['count'];
}

// Get books currently issued from borrow_records table
$issued_query = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE status = 'borrowed' OR status = 'issued'");
if($issued_query) {
    $row = $issued_query->fetch_assoc();
    $books_issued = $row['count'];
} else {
    $issued_query2 = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE return_date IS NULL");
    if($issued_query2) {
        $row = $issued_query2->fetch_assoc();
        $books_issued = $row['count'];
    }
}

// Get pending reservations
$res_check = $conn->query("SHOW TABLES LIKE 'reservations'");
if($res_check && $res_check->num_rows > 0) {
    $reservation_query = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'");
    if($reservation_query) {
        $row = $reservation_query->fetch_assoc();
        $pending_reservations = $row['count'];
    }
} else {
    $res_alt_check = $conn->query("SHOW TABLES LIKE 'reserved_books'");
    if($res_alt_check && $res_alt_check->num_rows > 0) {
        $reservation_query = $conn->query("SELECT COUNT(*) as count FROM reserved_books WHERE status = 'pending'");
        if($reservation_query) {
            $row = $reservation_query->fetch_assoc();
            $pending_reservations = $row['count'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Librarian Dashboard | Library Management System</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-image: url('../assets/images/librarian.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
      color: #fff;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.5) 100%);
      z-index: 0;
    }

    /* Header Bar */
    .header-bar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: rgba(0, 0, 0, 0.85);
      backdrop-filter: blur(10px);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 100;
      box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.3rem;
      font-weight: bold;
    }

    .logo span {
      background: #0073e6;
      width: 35px;
      height: 35px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .user-badge {
      background: rgba(255, 255, 255, 0.15);
      padding: 8px 16px;
      border-radius: 30px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .user-badge .role {
      background: #0073e6;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 0.7rem;
      font-weight: bold;
    }

    .logout-btn {
      background: #e60000;
      color: white;
      text-decoration: none;
      padding: 8px 20px;
      border-radius: 30px;
      transition: all 0.3s ease;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .logout-btn:hover {
      background: #b30000;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(230, 0, 0, 0.3);
    }

    /* Dashboard Container */
    .dashboard-container {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 100px 20px 60px;
    }

    /* Welcome Card */
    .welcome-card {
      text-align: center;
      margin-bottom: 40px;
      animation: fadeInDown 0.6s ease;
    }

    .welcome-card h1 {
      font-size: 2.5rem;
      margin-bottom: 10px;
      background: linear-gradient(135deg, #fff, #a8c8ff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .welcome-card p {
      font-size: 1rem;
      opacity: 0.9;
    }

    .date-time {
      margin-top: 15px;
      font-size: 0.85rem;
      background: rgba(255, 255, 255, 0.15);
      display: inline-block;
      padding: 5px 15px;
      border-radius: 30px;
      backdrop-filter: blur(5px);
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      max-width: 1000px;
      width: 100%;
      margin-bottom: 40px;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .stat-card:hover {
      transform: translateY(-5px);
      background: rgba(255, 255, 255, 0.15);
      border-color: rgba(255, 255, 255, 0.4);
    }

    .stat-icon {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    .stat-number {
      font-size: 1.8rem;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 0.8rem;
      opacity: 0.8;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    /* Menu Grid */
    .menu-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 25px;
      max-width: 900px;
      width: 100%;
    }

    .menu-card {
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 25px 20px;
      text-align: center;
      text-decoration: none;
      color: white;
      transition: all 0.3s ease;
      border: 1px solid rgba(255, 255, 255, 0.15);
      animation: fadeInUp 0.6s ease;
      animation-fill-mode: both;
    }

    .menu-card:nth-child(1) { animation-delay: 0.1s; }
    .menu-card:nth-child(2) { animation-delay: 0.15s; }
    .menu-card:nth-child(3) { animation-delay: 0.2s; }
    .menu-card:nth-child(4) { animation-delay: 0.25s; }
    .menu-card:nth-child(5) { animation-delay: 0.3s; }
    .menu-card:nth-child(6) { animation-delay: 0.35s; }
    .menu-card:nth-child(7) { animation-delay: 0.4s; }

    .menu-card:hover {
      transform: translateY(-8px);
      background: rgba(0, 115, 230, 0.7);
      border-color: rgba(255, 255, 255, 0.4);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    }

    .menu-icon {
      font-size: 2.5rem;
      margin-bottom: 15px;
    }

    .menu-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .menu-desc {
      font-size: 0.7rem;
      opacity: 0.8;
    }

    /* Refresh Button */
    .refresh-stats {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: rgba(0, 115, 230, 0.8);
      color: white;
      border: none;
      border-radius: 50px;
      padding: 10px 20px;
      cursor: pointer;
      font-size: 0.8rem;
      z-index: 100;
      backdrop-filter: blur(5px);
      transition: all 0.3s ease;
    }

    .refresh-stats:hover {
      background: #0073e6;
      transform: scale(1.05);
    }

    /* Toast message */
    .toast-message {
      position: fixed;
      bottom: 80px;
      right: 20px;
      padding: 10px 20px;
      border-radius: 8px;
      z-index: 1000;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateX(50px); }
      to { opacity: 1; transform: translateX(0); }
    }

    /* Loading animation */
    @keyframes pulse {
      0%, 100% { opacity: 0.6; }
      50% { opacity: 1; }
    }
    
    .loading {
      animation: pulse 1s ease-in-out infinite;
    }

    /* Animations */
    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .header-bar {
        padding: 12px 20px;
        flex-direction: column;
        gap: 10px;
      }

      .logo {
        font-size: 1.1rem;
      }

      .user-info {
        width: 100%;
        justify-content: space-between;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }

      .menu-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }

      .welcome-card h1 {
        font-size: 1.8rem;
      }

      .dashboard-container {
        padding: 140px 15px 40px;
      }
    }

    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }

      .menu-grid {
        grid-template-columns: 1fr;
      }

      .stat-number {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="header-bar">
    <div class="logo">
      <span>📚</span>
      Library Management System
    </div>
    <div class="user-info">
      <div class="user-badge">
        👤 <?php echo htmlspecialchars($_SESSION['username']); ?>
        <span class="role">Librarian</span>
      </div>
      <a class="logout-btn" href="../logout.php">
        🚪 Logout
      </a>
    </div>
  </div>

  <div class="dashboard-container">
    <div class="welcome-card">
      <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
      <p>Manage your library operations efficiently from this central dashboard</p>
      <div class="date-time" id="dateTime"></div>
    </div>

    <!-- Stats Overview (Data fetched from database) -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📖</div>
        <div class="stat-number" id="totalBooks"><?php echo number_format($total_books); ?></div>
        <div class="stat-label">Total Books</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number" id="totalStudents"><?php echo number_format($total_students); ?></div>
        <div class="stat-label">Total Students</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📤</div>
        <div class="stat-number" id="booksIssued"><?php echo number_format($books_issued); ?></div>
        <div class="stat-label">Books Issued</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-number" id="pendingReservations"><?php echo number_format($pending_reservations); ?></div>
        <div class="stat-label">Pending Reservations</div>
      </div>
    </div>

    <!-- Main Menu Grid -->
    <div class="menu-grid">
      <a href="../modules/librarians/add_book.php" class="menu-card">
        <div class="menu-icon">➕</div>
        <div class="menu-title">Add Book</div>
        <div class="menu-desc">Add new books to the library collection</div>
      </a>
      <a href="../modules/librarians/catalog_book.php" class="menu-card">
        <div class="menu-icon">📚</div>
        <div class="menu-title">Catalog Books</div>
        <div class="menu-desc">View, search, and manage book catalog</div>
      </a>
      <a href="../modules/librarians/issue_book.php" class="menu-card">
        <div class="menu-icon">📤</div>
        <div class="menu-title">Issue Book</div>
        <div class="menu-desc">Issue books to students</div>
      </a>
      <a href="../modules/librarians/librarian_reservations.php" class="menu-card">
        <div class="menu-icon">📅</div>
        <div class="menu-title">View Reservations</div>
        <div class="menu-desc">Review and manage book reservation requests</div>
      </a>
      <a href="../modules/librarians/librarian_report.php" class="menu-card">
        <div class="menu-icon">📊</div>
        <div class="menu-title">Generate Reports</div>
        <div class="menu-desc">View books and students reports with filters</div>
      </a>
      <a href="../modules/librarians/create_student.php" class="menu-card">
        <div class="menu-icon">👨‍🎓</div>
        <div class="menu-title">Add Student</div>
        <div class="menu-desc">Register new students to the system</div>
      </a>
      <a href="../modules/librarians/librarian_schedule.php" class="menu-card">
        <div class="menu-icon">📅</div>
        <div class="menu-title">View Schedules</div>
        <div class="menu-desc">Check your work schedule and shifts</div>
      </a>
    </div>
  </div>

  <button class="refresh-stats" onclick="refreshStats()">
    🔄 Refresh Statistics
  </button>

  <script>
    // Display current date and time
    function updateDateTime() {
      const now = new Date();
      const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      };
      const dateTimeElement = document.getElementById('dateTime');
      if(dateTimeElement) {
        dateTimeElement.textContent = now.toLocaleDateString('en-US', options);
      }
    }
    updateDateTime();
    setInterval(updateDateTime, 60000);

    // Function to refresh statistics via AJAX
    function refreshStats() {
      // Add loading animation
      const statNumbers = document.querySelectorAll('.stat-number');
      statNumbers.forEach(el => {
        el.classList.add('loading');
      });
      
      fetch(window.location.pathname + '?ajax=stats')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('totalBooks').textContent = formatNumber(data.total_books);
            document.getElementById('totalStudents').textContent = formatNumber(data.total_students);
            document.getElementById('booksIssued').textContent = formatNumber(data.books_issued);
            document.getElementById('pendingReservations').textContent = formatNumber(data.pending_reservations);
            
            // Show success indicator
            showToast('Statistics updated successfully', 'success');
          }
          // Remove loading animation
          statNumbers.forEach(el => {
            el.classList.remove('loading');
          });
        })
        .catch(error => {
          console.error('Error refreshing stats:', error);
          statNumbers.forEach(el => {
            el.classList.remove('loading');
          });
          showToast('Could not refresh statistics', 'error');
        });
    }
    
    function formatNumber(num) {
      return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    function showToast(message, type) {
      // Remove existing toast
      const existingToast = document.querySelector('.toast-message');
      if(existingToast) existingToast.remove();
      
      const toast = document.createElement('div');
      toast.className = 'toast-message';
      toast.style.cssText = 'position:fixed; bottom:80px; right:20px; background:' + (type === 'success' ? '#27ae60' : '#e74c3c') + '; color:white; padding:10px 20px; border-radius:8px; z-index:1000; animation:fadeIn 0.3s ease;';
      toast.innerHTML = (type === 'success' ? '✅ ' : '❌ ') + message;
      document.body.appendChild(toast);
      setTimeout(() => {
        if(toast) toast.remove();
      }, 3000);
    }
    
    // Auto refresh stats every 60 seconds
    setInterval(refreshStats, 60000);
  </script>

  <?php
  // Handle AJAX request for stats
  if(isset($_GET['ajax']) && $_GET['ajax'] === 'stats') {
    header('Content-Type: application/json');
    
    // Re-fetch data from database
    $total_books_ajax = 0;
    $total_students_ajax = 0;
    $books_issued_ajax = 0;
    $pending_reservations_ajax = 0;
    
    $book_query = $conn->query("SELECT COUNT(*) as count FROM books");
    if($book_query) {
      $row = $book_query->fetch_assoc();
      $total_books_ajax = $row['count'];
    }
    
    $student_query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    if($student_query) {
      $row = $student_query->fetch_assoc();
      $total_students_ajax = $row['count'];
    }
    
    // Use borrow_records table
    $issued_query = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE status = 'borrowed' OR status = 'issued'");
    if($issued_query && $issued_query->num_rows > 0) {
      $row = $issued_query->fetch_assoc();
      $books_issued_ajax = $row['count'];
    } else {
      // Fallback: count records with no return date
      $issued_query2 = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE return_date IS NULL");
      if($issued_query2) {
        $row = $issued_query2->fetch_assoc();
        $books_issued_ajax = $row['count'];
      }
    }
    
    // Check for reservations
    $res_check = $conn->query("SHOW TABLES LIKE 'reservations'");
    if($res_check && $res_check->num_rows > 0) {
      $reservation_query = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'");
      if($reservation_query) {
        $row = $reservation_query->fetch_assoc();
        $pending_reservations_ajax = $row['count'];
      }
    }
    
    echo json_encode([
      'success' => true,
      'total_books' => $total_books_ajax,
      'total_students' => $total_students_ajax,
      'books_issued' => $books_issued_ajax,
      'pending_reservations' => $pending_reservations_ajax
    ]);
    $conn->close();
    exit;
  }
  ?>
</body>
</html>