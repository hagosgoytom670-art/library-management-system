<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
  <meta name="description" content="RU Digital Central Library - Secure login portal for students, librarians, and administrators. Access your digital library account.">
  <meta name="theme-color" content="#0056b3">
  <title>RU Digital Central Library | Secure Login</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary: #0a4b8c;
      --primary-dark: #003d6b;
      --primary-light: #2c6e9e;
      --accent: #e8a735;
      --gray-50: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-600: #475569;
      --gray-700: #334155;
      --gray-800: #1e293b;
      --error: #b91c1c;
      --error-bg: #fef2f2;
      --success: #0f7b3a;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -1px rgb(0 0 0 / 0.06);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -2px rgb(0 0 0 / 0.05);
      --radius-md: 0.5rem;
      --radius-lg: 0.75rem;
    }

    body {
      min-height: 100vh;
      font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3rem 2rem;
      position: relative;
      transition: background-image 1.2s ease-in-out;
    }

    /* Dynamic background container */
    .bg-slideshow {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 0;
      overflow: hidden;
    }

    .bg-slideshow img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0;
      transition: opacity 1.5s ease-in-out;
    }

    .bg-slideshow img.active {
      opacity: 1;
    }

    /* Overlay */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.45);
      backdrop-filter: blur(1px);
      pointer-events: none;
      z-index: 1;
    }

    /* Main card container */
    .login-container {
      max-width: 1000px;
      width: 90%;
      background: white;
      border-radius: 1.5rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
      overflow: hidden;
      display: grid;
      grid-template-columns: 1fr 1fr;
      position: relative;
      z-index: 2;
      margin: auto;
    }

    /* Left panel */
    .hero-section {
      background: linear-gradient(145deg, rgba(10, 75, 140, 0.92) 0%, rgba(6, 46, 82, 0.94) 100%);
      backdrop-filter: blur(0px);
      padding: 1.5rem 1.5rem 2rem;
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .hero-logo {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }

    .hero-logo img {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      background: white;
      padding: 3px;
      object-fit: contain;
    }

    .hero-logo-text h3 {
      font-size: 0.85rem;
      opacity: 0.9;
      font-weight: 500;
    }

    .hero-logo-text p {
      font-size: 0.6rem;
      opacity: 0.7;
    }

    .library-badge {
      display: inline-block;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(4px);
      padding: 0.2rem 0.6rem;
      border-radius: 2rem;
      font-size: 0.65rem;
      font-weight: 500;
      letter-spacing: 0.5px;
      width: fit-content;
      margin-bottom: 0.75rem;
    }

    .hero-title {
      font-size: 1.6rem;
      font-weight: 700;
      line-height: 1.2;
      margin-bottom: 0.75rem;
      letter-spacing: -0.01em;
    }

    .hero-title span {
      color: #ffd966;
    }

    .hero-desc {
      font-size: 0.8rem;
      line-height: 1.4;
      opacity: 0.85;
      margin-bottom: 1.25rem;
    }

    .stats-grid {
      display: flex;
      gap: 0.75rem;
      margin: 1rem 0;
      flex-wrap: wrap;
    }

    .stat-item {
      background: rgba(255, 255, 255, 0.08);
      border-radius: 0.5rem;
      padding: 0.5rem 0.75rem;
      flex: 1;
      min-width: 65px;
      text-align: center;
    }

    .stat-number {
      font-size: 1.1rem;
      font-weight: 700;
    }

    .stat-label {
      font-size: 0.6rem;
      opacity: 0.75;
    }

    .info-card {
      background: rgba(0, 0, 0, 0.2);
      border-radius: 0.5rem;
      padding: 0.75rem;
      margin-top: 0.75rem;
      font-size: 0.7rem;
      border-left: 2px solid #ffd966;
    }

    .info-card p {
      margin: 3px 0;
    }

    /* Right panel */
    .form-section {
      padding: 1.5rem 1.5rem 2rem;
      background: white;
    }

    .form-header {
      text-align: center;
      margin-bottom: 1rem;
    }

    .form-header h2 {
      font-size: 1.4rem;
      font-weight: 600;
      color: var(--gray-800);
      margin-bottom: 0.25rem;
    }

    .form-header p {
      color: var(--gray-600);
      font-size: 0.75rem;
    }

    .server-message {
      background: var(--error-bg);
      border-left: 4px solid var(--error);
      color: var(--error);
      padding: 0.5rem 0.75rem;
      border-radius: var(--radius-md);
      margin-bottom: 1rem;
      font-size: 0.75rem;
      display: none;
      align-items: center;
      gap: 0.5rem;
    }

    .server-message.visible {
      display: flex;
    }

    .input-group {
      margin-bottom: 0.9rem;
    }

    label {
      display: block;
      font-weight: 500;
      font-size: 0.75rem;
      color: var(--gray-700);
      margin-bottom: 0.3rem;
    }

    .input-wrapper {
      position: relative;
    }

    input, select {
      width: 100%;
      padding: 0.6rem 0.85rem;
      font-size: 0.85rem;
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-md);
      transition: all 0.2s ease;
      background: white;
      font-family: inherit;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(10, 75, 140, 0.1);
    }

    input.error, select.error {
      border-color: var(--error);
    }

    .error-text {
      color: var(--error);
      font-size: 0.65rem;
      margin-top: 0.25rem;
      display: none;
    }

    .error-text.visible {
      display: block;
    }

    .login-btn {
      width: 100%;
      background: var(--primary);
      color: white;
      border: none;
      padding: 0.7rem;
      font-size: 0.9rem;
      font-weight: 600;
      border-radius: var(--radius-md);
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 0.5rem;
    }

    .login-btn:hover {
      background: var(--primary-dark);
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    .login-btn:active {
      transform: translateY(0);
    }

    .form-footer {
      margin-top: 1rem;
      text-align: center;
      font-size: 0.7rem;
    }

    .form-footer a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
    }

    .form-footer a:hover {
      text-decoration: underline;
    }

    .announcement-bar {
      background: var(--gray-50);
      border-radius: var(--radius-md);
      padding: 0.6rem 0.75rem;
      margin: 0.75rem 0;
      border: 1px solid var(--gray-200);
      position: relative;
      min-height: 55px;
      display: flex;
      align-items: center;
    }

    .marquee-item {
      position: absolute;
      opacity: 0;
      transition: opacity 0.5s ease-in-out;
      font-size: 0.75rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--gray-800);
    }

    .marquee-item.active {
      opacity: 1;
      position: relative;
    }

    .top-links {
      position: fixed;
      top: 1rem;
      right: 1.5rem;
      z-index: 20;
      display: flex;
      gap: 0.75rem;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(8px);
      padding: 0.4rem 1rem;
      border-radius: 2rem;
      box-shadow: var(--shadow-md);
    }

    .top-links a {
      color: var(--primary-dark);
      text-decoration: none;
      font-size: 0.75rem;
      font-weight: 500;
      transition: color 0.2s;
    }

    .top-links a:hover {
      color: var(--accent);
    }

    /* Background indicator dots */
    .bg-indicator {
      position: fixed;
      bottom: 1.5rem;
      left: 50%;
      transform: translateX(-50%);
      z-index: 20;
      display: flex;
      gap: 0.5rem;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
      padding: 0.4rem 0.8rem;
      border-radius: 2rem;
    }

    .bg-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.5);
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .bg-dot.active {
      background: white;
      width: 20px;
      border-radius: 4px;
    }

    .bg-dot:hover {
      background: rgba(255, 255, 255, 0.9);
    }

    @media (max-width: 768px) {
      body {
        padding: 1.5rem;
      }
      
      .login-container {
        grid-template-columns: 1fr;
        width: 100%;
        border-radius: 1rem;
      }

      .hero-section {
        padding: 1rem;
      }

      .form-section {
        padding: 1.25rem;
      }

      .top-links {
        top: 0.75rem;
        right: 0.75rem;
        background: rgba(255, 255, 255, 0.98);
        padding: 0.3rem 0.75rem;
        gap: 0.5rem;
      }
      
      .top-links a {
        font-size: 0.7rem;
      }

      .stats-grid {
        gap: 0.5rem;
      }
      
      .hero-logo img {
        width: 35px;
        height: 35px;
      }
      
      .hero-title {
        font-size: 1.3rem;
      }

      .bg-indicator {
        bottom: 1rem;
      }
    }

    @media (min-width: 1400px) {
      body {
        padding: 5rem 4rem;
      }
      
      .login-container {
        max-width: 1100px;
      }
    }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border-width: 0;
    }
  </style>
</head>
<body>

<!-- Dynamic Background Slideshow -->
<div class="bg-slideshow" id="bgSlideshow">
  <!-- Replace these image URLs with your actual image paths -->
  <img src="assets/images/ru-library-bg.jpg" alt="Library Background 1" class="active">
  <img src="assets/images/ru-library-bg2.jpg" alt="Library Background 2">
  <img src="assets/images/ru-library-bg3.jpg" alt="Library Background 3">
  <img src="assets/images/ru-library-bg4.jpg" alt="Library Background 4">
  <img src="assets/images/ru-library-bg5.jpg" alt="Library Background 5">
</div>

<!-- Floating quick links -->
<div class="top-links" role="navigation" aria-label="Utility navigation">
  <a href="contact_us.php">📞 Contact</a>
  <a href="includes/about.php">📖 About</a>
  <a href="includes/help.php">❓ Help</a>
  <a href="includes/important_links.php">🔗 Important Links</a>
  <a href="includes/terms.php">📜 Terms</a>
</div>

<!-- Background indicator dots -->
<div class="bg-indicator" id="bgIndicator"></div>

<div class="login-container">
  <!-- Left: Hero panel -->
  <div class="hero-section">
    <div>
      <div class="hero-logo">
        <img src="assets/images/ru-logo.jpg" alt="RU Library Logo" onerror="this.src='https://placehold.co/40x40?text=RU'">
        <div class="hero-logo-text">
          <h3>Raya University</h3>
          <p>Established 2015</p>
        </div>
      </div>
      
      <div class="library-badge">📍 Maichew, Tigray, Ethiopia</div>
      <h1 class="hero-title"><span>Central Library</span></h1>
      <p class="hero-desc">Access physical & digital resources — books, journals, e-books, and research archives.</p>
      
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-number">++</div>
          <div class="stat-label"><h1>Books</h1></div>
        </div>
      
        <div class="stat-item">
          <div class="stat-number">++</div>
          <div class="stat-label"><h1>E-Resources</h1></div>
        </div>
      </div>
    </div>
    
    <div class="info-card">
      <strong>⏰ Hours:</strong> Mon–Sun: 2:30 AM – 7:00 PM<br>
      <strong>📍Location: Block-13, Near Student Cafeteria</strong>
    </div>
  </div>

  <!-- Right: Login form -->
  <div class="form-section">
    <div class="form-header">
      <h2>Welcome back</h2>
      <p>Sign in to access library resources</p>
    </div>

    <?php
      $serverMsg = '';
      if (!empty($_GET['msg'])) {
          $serverMsg = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
      }
    ?>
    <div id="serverMessage" class="server-message <?php echo $serverMsg ? 'visible' : ''; ?>">
      <span>⚠️</span> <span id="serverMsgText"><?php echo $serverMsg ?: ''; ?></span>
    </div>

    <div class="announcement-bar" id="announcementMarquee" aria-live="polite">
    </div>

    <form id="loginForm" method="POST" action="login.php" novalidate>
      <div class="input-group">
        <label for="username">Username</label>
        <div class="input-wrapper">
          <input type="text" id="username" name="username" autocomplete="username" placeholder="e.g., hagos goytom">
        </div>
        <div id="usernameError" class="error-text"></div>
      </div>

      <div class="input-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <input type="password" id="password" name="password" autocomplete="current-password" placeholder="••••••••">
        </div>
        <div id="passwordError" class="error-text"></div>
      </div>

      <div class="input-group">
        <label for="role">Account Type</label>
        <select id="role" name="role">
          <option value="">-- Select role --</option>
          <option value="student">🎓 Student</option>
          <option value="librarian">📚 Librarian</option>
          <option value="admin">⚙️ Administrator</option>
        </select>
        <div id="roleError" class="error-text"></div>
      </div>

      <button type="submit" class="login-btn">Sign in →</button>

    </form>
  </div>
</div>

<script>
  (function() {
    // ----- Dynamic Background Slideshow -----
    const bgImages = document.querySelectorAll('#bgSlideshow img');
    let currentBgIndex = 0;
    let bgInterval = null;
    
    // Create indicator dots
    const indicatorContainer = document.getElementById('bgIndicator');
    if (indicatorContainer && bgImages.length > 0) {
      for (let i = 0; i < bgImages.length; i++) {
        const dot = document.createElement('div');
        dot.className = 'bg-dot';
        if (i === 0) dot.classList.add('active');
        dot.setAttribute('data-index', i);
        dot.setAttribute('aria-label', `Background image ${i + 1}`);
        dot.addEventListener('click', function() {
          stopBgRotation();
          setActiveBackground(parseInt(this.getAttribute('data-index')));
          startBgRotation();
        });
        indicatorContainer.appendChild(dot);
      }
    }
    
    const indicatorDots = document.querySelectorAll('.bg-dot');
    
    function setActiveBackground(index) {
      if (index === currentBgIndex) return;
      
      // Remove active class from all images
      bgImages.forEach(img => img.classList.remove('active'));
      // Add active class to selected image
      bgImages[index].classList.add('active');
      
      // Update indicator dots
      indicatorDots.forEach((dot, i) => {
        if (i === index) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });
      
      currentBgIndex = index;
    }
    
    function nextBackground() {
      let nextIndex = (currentBgIndex + 1) % bgImages.length;
      setActiveBackground(nextIndex);
    }
    
    function startBgRotation() {
      if (bgInterval) clearInterval(bgInterval);
      bgInterval = setInterval(nextBackground, 10000); // 10 seconds
    }
    
    function stopBgRotation() {
      if (bgInterval) {
        clearInterval(bgInterval);
        bgInterval = null;
      }
    }
    
    // Start rotation if there are multiple images
    if (bgImages.length > 1) {
      startBgRotation();
      
      // Pause rotation on hover (optional, for better UX)
      const slideshowContainer = document.getElementById('bgSlideshow');
      if (slideshowContainer) {
        slideshowContainer.addEventListener('mouseenter', stopBgRotation);
        slideshowContainer.addEventListener('mouseleave', startBgRotation);
      }
    }
    
    // ----- Marquee (Fade transition, accessible) -----
    const announcements = [
      { text: "📢 500+ new books arrived!", icon: "📚" },
      { text: "📖 New modules in Engineering & Medicine", icon: "📖" },
      { text: "🔔 3000+ peer-reviewed journals available", icon: "📰" },
      { text: "💡 Use 'Advanced Search' for better results", icon: "✨" },
      { text: "🌍 Remote access available for e-resources", icon: "🔐" },
      { text: "📚 Reserve books online & pick up later", icon: "📦" }
    ];
    
    const marqueeContainer = document.getElementById('announcementMarquee');
    if (marqueeContainer && announcements.length) {
      marqueeContainer.innerHTML = '';
      announcements.forEach((ann, idx) => {
        const div = document.createElement('div');
        div.className = 'marquee-item';
        if (idx === 0) div.classList.add('active');
        div.setAttribute('role', 'status');
        div.innerHTML = `<span style="font-size:1rem;">${ann.icon}</span> <span>${ann.text}</span>`;
        marqueeContainer.appendChild(div);
      });
      
      const items = Array.from(marqueeContainer.querySelectorAll('.marquee-item'));
      let activeIdx = 0;
      let intervalId = null;
      const rotationTime = 5000;
      
      function showItem(index) {
        items.forEach((item, i) => {
          if (i === index) {
            item.classList.add('active');
            item.setAttribute('aria-hidden', 'false');
          } else {
            item.classList.remove('active');
            item.setAttribute('aria-hidden', 'true');
          }
        });
      }
      
      function startRotation() {
        if (intervalId) clearInterval(intervalId);
        intervalId = setInterval(() => {
          activeIdx = (activeIdx + 1) % items.length;
          showItem(activeIdx);
        }, rotationTime);
      }
      
      function stopRotation() {
        if (intervalId) {
          clearInterval(intervalId);
          intervalId = null;
        }
      }
      
      const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)');
      if (!prefersReduced.matches) {
        startRotation();
        marqueeContainer.addEventListener('mouseenter', stopRotation);
        marqueeContainer.addEventListener('mouseleave', startRotation);
        marqueeContainer.addEventListener('focusin', stopRotation);
        marqueeContainer.addEventListener('focusout', startRotation);
      } else {
        showItem(0);
      }
    }
    
    // ----- Professional client-side validation -----
    const form = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const roleSelect = document.getElementById('role');
    
    const usernameErrorDiv = document.getElementById('usernameError');
    const passwordErrorDiv = document.getElementById('passwordError');
    const roleErrorDiv = document.getElementById('roleError');
    
    function setError(element, message) {
      if (!element) return;
      if (message) {
        element.textContent = message;
        element.classList.add('visible');
      } else {
        element.textContent = '';
        element.classList.remove('visible');
      }
      const parentInput = element.previousElementSibling?.querySelector('input, select');
      if (parentInput) {
        if (message) parentInput.classList.add('error');
        else parentInput.classList.remove('error');
      }
    }
    
    function validateUsername() {
      const val = usernameInput.value.trim();
      if (!val) {
        setError(usernameErrorDiv, 'Username is required.');
        return false;
      }
      if (val.length < 3) {
        setError(usernameErrorDiv, 'Username must be at least 3 characters.');
        return false;
      }
      if (!/^[A-Za-z0-9\s._-]+$/.test(val)) {
        setError(usernameErrorDiv, 'Only letters, numbers, spaces, dots, underscores, or hyphens allowed.');
        return false;
      }
      setError(usernameErrorDiv, null);
      return true;
    }
    
    function validatePassword() {
      const val = passwordInput.value;
      if (!val) {
        setError(passwordErrorDiv, 'Password is required.');
        return false;
      }
      if (val.length < 6) {
        setError(passwordErrorDiv, 'Password must be at least 6 characters.');
        return false;
      }
      setError(passwordErrorDiv, null);
      return true;
    }
    
    function validateRole() {
      const val = roleSelect.value;
      if (!val) {
        setError(roleErrorDiv, 'Please select a valid role.');
        return false;
      }
      setError(roleErrorDiv, null);
      return true;
    }
    
    if (usernameInput) usernameInput.addEventListener('input', validateUsername);
    if (passwordInput) passwordInput.addEventListener('input', validatePassword);
    if (roleSelect) roleSelect.addEventListener('change', validateRole);
    
    if (form) {
      form.addEventListener('submit', function(e) {
        const serverMsgDiv = document.getElementById('serverMessage');
        if (serverMsgDiv) serverMsgDiv.classList.remove('visible');
        
        const isUserValid = validateUsername();
        const isPassValid = validatePassword();
        const isRoleValid = validateRole();
        
        if (!isUserValid || !isPassValid || !isRoleValid) {
          e.preventDefault();
          if (!isUserValid) usernameInput.focus();
          else if (!isPassValid) passwordInput.focus();
          else if (!isRoleValid) roleSelect.focus();
        }
      });
    }
    
    const serverMsgDiv = document.getElementById('serverMessage');
    if (serverMsgDiv && serverMsgDiv.classList.contains('visible')) {
      setTimeout(() => {
        serverMsgDiv.classList.remove('visible');
      }, 7000);
    }
    
    const marqueeItems = document.querySelectorAll('.marquee-item');
    marqueeItems.forEach((item, idx) => {
      if (idx !== 0) item.setAttribute('aria-hidden', 'true');
      else item.setAttribute('aria-hidden', 'false');
    });
    
  })();
</script>

</body>
</html>