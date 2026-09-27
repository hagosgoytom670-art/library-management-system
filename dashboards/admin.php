<?php include("../includes/auth.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Library Management System</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      color: #1e293b;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: 280px;
      height: 100vh;
      background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
      color: white;
      padding: 30px 0;
      transition: all 0.3s ease;
      z-index: 1000;
      box-shadow: 2px 0 12px rgba(0, 0, 0, 0.1);
    }

    .sidebar-header {
      text-align: center;
      padding: 0 20px 25px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 25px;
    }

    .sidebar-header h3 {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .sidebar-header p {
      font-size: 12px;
      opacity: 0.7;
    }

    .sidebar-menu {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar-menu li {
      margin: 5px 0;
    }

    .sidebar-menu a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 24px;
      color: #cbd5e1;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 14px;
      font-weight: 500;
      border-left: 3px solid transparent;
    }

    .sidebar-menu a:hover {
      background: rgba(255, 255, 255, 0.08);
      color: white;
      border-left-color: #3b82f6;
    }

    .sidebar-menu a.active {
      background: rgba(59, 130, 246, 0.2);
      color: white;
      border-left-color: #3b82f6;
    }

    /* Main Content */
    .main-content {
      margin-left: 280px;
      padding: 20px 30px;
    }

    /* Top Header */
    .top-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: white;
      padding: 15px 25px;
      border-radius: 12px;
      margin-bottom: 25px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .welcome-text h2 {
      font-size: 20px;
      color: #1e293b;
      margin-bottom: 4px;
    }

    .welcome-text p {
      font-size: 13px;
      color: #64748b;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .user-avatar {
      width: 45px;
      height: 45px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 18px;
    }

    .logout-btn {
      background: #ef4444;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: background 0.3s;
    }

    .logout-btn:hover {
      background: #dc2626;
    }

    /* Stats Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s, box-shadow 0.3s;
      cursor: pointer;
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
      font-size: 32px;
      margin-bottom: 12px;
    }

    .stat-value {
      font-size: 28px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 13px;
      color: #64748b;
      font-weight: 500;
    }

    /* Quick Actions */
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .actions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .action-card {
      background: white;
      border-radius: 16px;
      padding: 25px;
      text-align: center;
      text-decoration: none;
      transition: all 0.3s ease;
      border: 1px solid #e2e8f0;
      display: block;
    }

    .action-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
      border-color: transparent;
    }

    .action-icon {
      font-size: 48px;
      margin-bottom: 15px;
    }

    .action-title {
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 8px;
    }

    .action-desc {
      font-size: 13px;
      color: #64748b;
      line-height: 1.5;
    }

    /* Recent Activity */
    .recent-section {
      background: white;
      border-radius: 16px;
      padding: 25px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .recent-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid #e2e8f0;
    }

    .recent-header h3 {
      font-size: 18px;
      color: #1e293b;
    }

    .recent-header a {
      color: #3b82f6;
      text-decoration: none;
      font-size: 13px;
    }

    .activity-item {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 12px 0;
      border-bottom: 1px solid #f1f5f9;
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      background: #eef2ff;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .activity-details {
      flex: 1;
    }

    .activity-title {
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 4px;
    }

    .activity-time {
      font-size: 11px;
      color: #94a3b8;
    }

    /* Mobile Menu Toggle */
    .menu-toggle {
      display: none;
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1100;
      background: #1e293b;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 20px;
    }

    /* Loading Spinner */
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 2px solid #e2e8f0;
      border-radius: 50%;
      border-top-color: #3b82f6;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .menu-toggle {
        display: block;
      }

      .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
        padding: 60px 20px 20px;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .top-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
      }
    }

    @media (min-width: 769px) {
      .sidebar {
        transform: translateX(0) !important;
      }
    }
  </style>
</head>
<body>

<!-- Mobile Menu Toggle -->
<button class="menu-toggle" onclick="toggleSidebar()">☰</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h3>📚 LMS Admin</h3>
    <p>Library Management System</p>
  </div>
  <ul class="sidebar-menu">
    <li><a href="#" class="active">🏠 Dashboard</a></li>
    <li><a href="../modules/admins/purchase.php">🛒 Purchases</a></li>
    <li><a href="../modules/admins/create_librarian.php">👥 Create Librarian</a></li>
    <li><a href="../modules/admins/view_messages.php">💬 Messages</a></li>
    <li><a href="../modules/admins/scudule.php">📅 Schedules</a></li>
    <li><a href="../modules/admins/upload_course_file.php">📚 Upload Courses</a></li>
    <li><a href="../modules/admins/view_replies.php">✉️ Reply Log</a></li>
  </ul>
</div>

<!-- Main Content -->
<div class="main-content">
  
  <!-- Top Header -->
  <div class="top-header">
    <div class="welcome-text">
      <h2>እንኳን ደህና መጡ, <?php echo htmlspecialchars($_SESSION['username']); ?>! (Welcome back!)</h2>
      <p>Here's what's happening with your library today.</p>
    </div>
    <div class="user-info">
      <div class="user-avatar">
        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
      </div>
      <a href="../logout.php" class="logout-btn">🚪 Logout</a>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="stats-grid">
    <div class="stat-card" onclick="window.location.href='../modules/admins/purchase.php'">
      <div class="stat-icon">💰</div>
      <div class="stat-value" id="totalPurchases">-</div>
      <div class="stat-label">Total Purchases</div>
    </div>
    <div class="stat-card" onclick="window.location.href='../modules/admins/create_librarian.php'">
      <div class="stat-icon">👥</div>
      <div class="stat-value" id="totalLibrarians">-</div>
      <div class="stat-label">Librarians</div>
    </div>
    <div class="stat-card" onclick="window.location.href='../modules/admins/view_messages.php'">
      <div class="stat-icon">💬</div>
      <div class="stat-value" id="unreadMessages">-</div>
      <div class="stat-label">Unread Messages</div>
    </div>
    <div class="stat-card" onclick="window.location.href='../modules/admins/scudule.php'">
      <div class="stat-icon">📅</div>
      <div class="stat-value" id="totalSchedules">-</div>
      <div class="stat-label">Active Schedules</div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="section-title">
    <span>⚡</span> Quick Actions
  </div>
  <div class="actions-grid">
    <a href="../modules/admins/purchase.php" class="action-card">
      <div class="action-icon">🛒</div>
      <div class="action-title">Record Purchase</div>
      <div class="action-desc">Add new purchase items to inventory in ብር</div>
    </a>
    <a href="../modules/admins/create_librarian.php" class="action-card">
      <div class="action-icon">👤</div>
      <div class="action-title">Create Librarian</div>
      <div class="action-desc">Add new librarian accounts</div>
    </a>
    <a href="../modules/admins/scudule.php" class="action-card">
      <div class="action-icon">📆</div>
      <div class="action-title">Manage Schedules</div>
      <div class="action-desc">Create and manage library schedules</div>
    </a>
    <a href="../modules/admins/upload_course_file.php" class="action-card">
      <div class="action-icon">📚</div>
      <div class="action-title">Upload Courses</div>
      <div class="action-desc">Add new course materials</div>
    </a>
    <a href="../modules/admins/view_messages.php" class="action-card">
      <div class="action-icon">💬</div>
      <div class="action-title">View Messages</div>
      <div class="action-desc">Read and reply to contact messages</div>
    </a>
    <a href="../modules/admins/view_replies.php" class="action-card">
      <div class="action-icon">✉️</div>
      <div class="action-title">Reply Log</div>
      <div class="action-desc">View all admin replies history</div>
    </a>
  </div>

  <!-- Recent Activity -->
  <div class="recent-section">
    <div class="recent-header">
      <h3>📋 Recent Activity</h3>
      <a href="#" onclick="refreshActivity()">⟳ Refresh</a>
    </div>
    <div id="activityList">
      <div class="activity-item">
        <div class="activity-icon">🔄</div>
        <div class="activity-details">
          <div class="activity-title">Loading activity...</div>
          <div class="activity-time">Please wait</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Toggle sidebar on mobile
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.menu-toggle');
    if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
      if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
        sidebar.classList.remove('open');
      }
    }
  });

  // Format currency in Birr
  function formatBirr(amount) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ብር';
  }

  // Fetch dashboard statistics
  async function fetchStats() {
    try {
      // Show loading state
      document.getElementById('totalPurchases').innerHTML = '<span class="loading-spinner"></span>';
      document.getElementById('totalLibrarians').innerHTML = '<span class="loading-spinner"></span>';
      document.getElementById('unreadMessages').innerHTML = '<span class="loading-spinner"></span>';
      document.getElementById('totalSchedules').innerHTML = '<span class="loading-spinner"></span>';
      
      const response = await fetch('../api/dashboard_stats.php');
      const data = await response.json();
      
      if (data.success) {
        document.getElementById('totalPurchases').innerText = data.totalPurchases || 0;
        document.getElementById('totalLibrarians').innerText = data.totalLibrarians || 0;
        document.getElementById('unreadMessages').innerText = data.unreadMessages || 0;
        document.getElementById('totalSchedules').innerText = data.totalSchedules || 0;
      } else {
        console.error('API Error:', data.error);
        document.getElementById('totalPurchases').innerText = 'Error';
        document.getElementById('totalLibrarians').innerText = 'Error';
        document.getElementById('unreadMessages').innerText = 'Error';
        document.getElementById('totalSchedules').innerText = 'Error';
      }
    } catch (error) {
      console.error('Error fetching stats:', error);
      document.getElementById('totalPurchases').innerText = '0';
      document.getElementById('totalLibrarians').innerText = '0';
      document.getElementById('unreadMessages').innerText = '0';
      document.getElementById('totalSchedules').innerText = '0';
    }
  }

  // Fetch recent activity
  async function fetchRecentActivity() {
    try {
      const response = await fetch('../api/recent_activity.php');
      const data = await response.json();
      
      const activityList = document.getElementById('activityList');
      
      if (data.success && data.activities && data.activities.length > 0) {
        activityList.innerHTML = data.activities.map(activity => `
          <div class="activity-item">
            <div class="activity-icon">${activity.icon || '📌'}</div>
            <div class="activity-details">
              <div class="activity-title">${escapeHtml(activity.title)}</div>
              <div class="activity-time">${escapeHtml(activity.time)}</div>
            </div>
          </div>
        `).join('');
      } else {
        activityList.innerHTML = `
          <div class="activity-item">
            <div class="activity-icon">📭</div>
            <div class="activity-details">
              <div class="activity-title">No recent activity found</div>
              <div class="activity-time">Start by adding your first purchase or librarian</div>
            </div>
          </div>
        `;
      }
    } catch (error) {
      console.error('Error fetching activity:', error);
      document.getElementById('activityList').innerHTML = `
        <div class="activity-item">
          <div class="activity-icon">⚠️</div>
          <div class="activity-details">
            <div class="activity-title">Error loading activities</div>
            <div class="activity-time">Please refresh the page</div>
          </div>
        </div>
      `;
    }
  }

  // Refresh activity manually
  function refreshActivity() {
    fetchRecentActivity();
  }

  // Escape HTML to prevent XSS
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Load data when page loads
  document.addEventListener('DOMContentLoaded', function() {
    fetchStats();
    fetchRecentActivity();
    
    // Refresh stats every 30 seconds
    setInterval(fetchStats, 30000);
    // Refresh activity every 60 seconds
    setInterval(fetchRecentActivity, 60000);
  });
</script>

</body>
</html>



  

  