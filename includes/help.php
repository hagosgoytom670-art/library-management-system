<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Help & Support - RU Digital Central Library</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      line-height: 1.6;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 20px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      background: white;
      border-radius: 15px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      overflow: hidden;
    }
    
    /* Header */
    .header {
      background: linear-gradient(135deg, #2c3e50, #3498db);
      color: white;
      padding: 30px 40px;
      text-align: center;
    }
    
    .header h1 {
      font-size: 32px;
      margin-bottom: 10px;
    }
    
    .header p {
      font-size: 16px;
      opacity: 0.9;
    }
    
    .back-link {
      display: inline-block;
      margin-top: 15px;
      color: white;
      text-decoration: none;
      background: rgba(255,255,255,0.2);
      padding: 8px 20px;
      border-radius: 25px;
      transition: all 0.3s;
    }
    
    .back-link:hover {
      background: rgba(255,255,255,0.3);
      transform: translateY(-2px);
    }
    
    /* Content */
    .content {
      padding: 40px;
    }
    
    /* Welcome Section */
    .welcome-section {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 25px;
      border-radius: 10px;
      margin-bottom: 30px;
      text-align: center;
    }
    
    .welcome-section h2 {
      color: #2c3e50;
      margin-bottom: 10px;
    }
    
    /* Quick Links */
    .quick-links {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    
    .quick-card {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      cursor: pointer;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .quick-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    
    .quick-card .icon {
      font-size: 40px;
      margin-bottom: 10px;
    }
    
    .quick-card h3 {
      font-size: 16px;
    }
    
    /* FAQ Section */
    .section-title {
      font-size: 24px;
      color: #2c3e50;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 3px solid #3498db;
      display: inline-block;
    }
    
    .faq-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    
    .faq-card {
      background: #f8f9fa;
      border-radius: 10px;
      overflow: hidden;
      transition: all 0.3s;
    }
    
    .faq-card:hover {
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .faq-question {
      background: #e9ecef;
      padding: 15px 20px;
      cursor: pointer;
      font-weight: 600;
      color: #2c3e50;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background 0.3s;
    }
    
    .faq-question:hover {
      background: #dee2e6;
    }
    
    .faq-question .icon {
      font-size: 20px;
      transition: transform 0.3s;
    }
    
    .faq-question.active .icon {
      transform: rotate(180deg);
    }
    
    .faq-answer {
      padding: 0 20px;
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease, padding 0.3s ease;
      color: #555;
      line-height: 1.6;
    }
    
    .faq-answer.show {
      padding: 20px;
      max-height: 500px;
    }
    
    .faq-answer ul {
      margin-left: 20px;
      margin-top: 8px;
    }
    
    /* Contact Section - with Admin Image */
    .contact-section {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      border-radius: 10px;
      padding: 30px;
      margin-bottom: 30px;
    }
    
    .contact-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 25px;
      margin-top: 20px;
    }
    
    .contact-card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }
    
    .contact-card:hover {
      transform: translateY(-5px);
    }
    
    .contact-card .icon {
      font-size: 40px;
      margin-bottom: 15px;
    }
    
    /* Director Avatar Styling - replacing icon */
    .director-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      margin-bottom: 15px;
      border: 3px solid #3498db;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      transition: transform 0.3s;
    }
    
    .director-avatar:hover {
      transform: scale(1.05);
    }
    
    .contact-card h3 {
      color: #2c3e50;
      margin-bottom: 10px;
    }
    
    .contact-card p {
      color: #666;
      margin: 5px 0;
    }
    
    .contact-card a {
      color: #3498db;
      text-decoration: none;
    }
    
    .contact-card a:hover {
      text-decoration: underline;
    }
    
    /* Support Form */
    .support-form {
      background: white;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    
    .support-form h3 {
      color: #2c3e50;
      margin-bottom: 20px;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      font-weight: 600;
      margin-bottom: 5px;
      color: #2c3e50;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-family: inherit;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #3498db;
    }
    
    .recipient-info {
      background: #e8f4fd;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #3498db;
    }
    
    .recipient-info p {
      margin: 5px 0;
      font-size: 14px;
    }
    
    .recipient-info strong {
      color: #2c3e50;
    }
    
    .btn-submit {
      background: linear-gradient(135deg, #27ae60, #229954);
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: transform 0.2s;
    }
    
    .btn-submit:hover {
      transform: translateY(-2px);
    }
    
    /* Library Hours */
    .hours-section {
      background: linear-gradient(135deg, #2c3e50, #3498db);
      color: white;
      padding: 25px;
      border-radius: 10px;
      text-align: center;
      margin-bottom: 30px;
    }
    
    .hours-section h3 {
      margin-bottom: 15px;
      font-size: 22px;
    }
    
    .hours-grid {
      display: flex;
      justify-content: center;
      gap: 40px;
      flex-wrap: wrap;
    }
    
    .hours-item {
      text-align: center;
      padding: 10px 20px;
      background: rgba(255,255,255,0.1);
      border-radius: 10px;
      min-width: 180px;
    }
    
    .hours-day {
      font-weight: bold;
      font-size: 18px;
      margin-bottom: 8px;
    }
    
    .hours-time {
      font-size: 14px;
      opacity: 0.9;
    }
    
    .ethiopian-note {
      margin-top: 15px;
      font-size: 12px;
      opacity: 0.8;
      font-style: italic;
    }
    
    /* Footer */
    .footer {
      background: #2c3e50;
      color: white;
      text-align: center;
      padding: 20px;
      font-size: 14px;
    }
    
    /* Alert Messages */
    .alert {
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: none;
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
    
    @media (max-width: 768px) {
      .content {
        padding: 20px;
      }
      
      .faq-grid {
        grid-template-columns: 1fr;
      }
      
      .contact-grid {
        grid-template-columns: 1fr;
      }
      
      .quick-links {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .hours-grid {
        gap: 15px;
      }
      
      .hours-item {
        min-width: 140px;
      }
      
      .director-avatar {
        width: 70px;
        height: 70px;
      }
    }
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>📚 Help & Support Center</h1>
    <p>Your guide to using the RU Digital Central Library System</p>
    <a href="/lmsPro/index.php" class="back-link">← Back to Home</a>
  </div>
  
  <div class="content">
    <!-- Welcome Section -->
    <div class="welcome-section">
      <h2>Welcome to the Library Help Center</h2>
      <p>Find answers to common questions and contact our library staff for assistance.</p>
    </div>
    
    <!-- Quick Links -->
    <div class="quick-links">
      <div class="quick-card" onclick="scrollToSection('faq')">
        <div class="icon">❓</div>
        <h3>Frequently Asked Questions</h3>
      </div>
      <div class="quick-card" onclick="scrollToSection('contact')">
        <div class="icon">📞</div>
        <h3>Contact Support</h3>
      </div>
      <div class="quick-card" onclick="scrollToSection('form')">
        <div class="icon">✉️</div>
        <h3>Send a Message</h3>
      </div>
    </div>
    
    <!-- FAQ Section -->
    <h2 class="section-title" id="faq">❓ Frequently Asked Questions</h2>
    <div class="faq-grid">
      <div class="faq-card">
        <div class="faq-question">
          <span>🔐 How do I log in to the system?</span>
          <span class="icon">▼</span>
        </div>
        <div class="faq-answer">
          To log in, enter your username and password, then select your role (Student, Librarian, or Admin). 
          Use the credentials provided by the library administration. If you're a new student, your ID number 
          is your default username.
        </div>
      </div>
      
      <div class="faq-card">
        <div class="faq-question">
          <span>🔑 What if I forgot my password?</span>
          <span class="icon">▼</span>
        </div>
        <div class="faq-answer">
          If you've forgotten your password, you must meet with a librarian in person at the Main Campus Library. 
          They will verify your identity and help you reset your password. This security measure ensures your 
          account remains protected.
        </div>
      </div>
      
      <div class="faq-card">
        <div class="faq-question">
          <span>📖 What can I do after logging in?</span>
          <span class="icon">▼</span>
        </div>
        <div class="faq-answer">
          After logging in, you can:
          <ul>
            <li>📚 Search and browse the book catalog</li>
            
            <li>📋 View your borrow history,currently borrowed and over due books</li>
            <li>📥 Download digital course materials</li>
            <li>📝 Make reservation requests</li>
          
          </ul>
        </div>
      </div>
      
      <div class="faq-card">
        <div class="faq-question">
          <span>⏰ How long can I borrow a book?</span>
          <span class="icon">▼</span>
        </div>
        <div class="faq-answer">
          Books are issued for <strong>14 days</strong>. You can borrow up to <strong>3 books</strong> at a time. 
          Late returns result in a <strong>30-day account suspension</strong>. Always check the due date and return 
          books on time to avoid penalties.
        </div>
      </div>
      
      <div class="faq-card">
        <div class="faq-question">
          <span>📝 How do I request a book reservation?</span>
          <span class="icon">▼</span>
        </div>
        <div class="faq-answer">
          To request a book reservation:
          <ul>
            <li>Go to "Request Reservations" from your dashboard</li>
            <li>Search for the book you want</li>
            <li>Click the "Request" or "Notify Me" button</li>
            <li>Wait for librarian approval (48-hour expiry)</li>
            <li>Once approved, pick up the book within 24 hours</li>
          </ul>
        </div>
      </div>
      
      <div class="faq-card">
        <div class="faq-question">
          <span>⚠️ What happens if I return a book late?</span>
          <span class="icon">▼</span>
        </div>
        <div class="faq-answer">
          If you return a book after the due date:
          <ul>
            <li>Your account will be <strong>suspended for 30 days</strong></li>
            <li>You cannot borrow any books during suspension</li>
            <li>The librarian will apply the suspension automatically</li>
            <li>Your account will be automatically reactivated after 30 days</li>
          </ul>
        </div>
      </div>
    </div>
    
    <!-- Library Hours Section - Ethiopian Time -->
    <div class="hours-section">
      <h3>🕒 Library Operating Hours (Ethiopian Time)</h3>
      <div class="hours-grid">
        <div class="hours-item">
          <div class="hours-day">Monday - Sunday</div>
          <div class="hours-time">🇪🇹 2:30 AM - 7:00 PM</div>
      
        </div>
        
      </div>
      <div class="ethiopian-note">
        📍 All times shown in Ethiopian local time (UTC+3)
      </div>
    </div>
    
    <!-- Contact Section with Library Admin Image instead of icon -->
    <h2 class="section-title" id="contact">📞 Contact Information</h2>
    <div class="contact-section">
      <div class="contact-grid">
        <div class="contact-card">
          <!-- REPLACED icon with library admin image -->
          <img 
            class="director-avatar" 
            src="/lmspro/assets/images/library-admin.jpg" 
            alt="Library Director - Tekle Haylekiros Assefa"
            onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?background=2c3e50&color=fff&rounded=true&size=80&name=Tekle+H';"
            loading="lazy"
          >
          <h3>Library Director</h3>
          <p><strong>Tekle Haylekiros Assefa</strong></p>
          <p>Director of Library and Documentation</p>
          <p>📞 <a href="tel:+251985414846">+251 985 41 48 46</a></p>
          <p>✉️ <a href="mailto:TekleHaylekiros@rayu.edu.et">TekleHaylekiros@rayu.edu.et</a></p>
        </div>
        
        <div class="contact-card">
          <div class="icon">🏛️</div>
          <h3>Visit Us</h3>
          <p>Main Campus Library</p>
          <p>Help Desk - 1st Floor</p>
          <p>Raya University</p>
          <p>Maychew, Ethiopia</p>
        </div>
      </div>
    </div>
    
    <!-- Support Form -->
    <h2 class="section-title" id="form">✉️ Send Us a Message</h2>
    <div class="support-form">
      <!-- Recipient Information Box -->
      <div class="recipient-info">
        <p><strong>📨 Your message will be sent to:</strong></p>
        <p>👨‍💼 <strong>Tekle Haylekiros Assefa</strong> - Director of Library and Documentation</p>
        <p>📧 Email: <a href="mailto:TekleHaylekiros@rayu.edu.et">TekleHaylekiros@rayu.edu.et</a></p>
        <p>📞 Phone: <a href="tel:+251985414846">+251 985 41 48 46</a></p>
        <p><small>📌 Response time: Within 24-48 hours during library working days</small></p>
      </div>
      
      <div id="alertMessage" class="alert"></div>
      <form id="supportForm" action="send_message.php" method="POST">
        <div class="form-group">
          <label>Your Full Name *</label>
          <input type="text" id="name" name="name" required placeholder="Enter your full name">
        </div>
        <div class="form-group">
          <label>Your Email Address *</label>
          <input type="email" id="email" name="email" required placeholder="your.email@example.com">
        </div>
        <div class="form-group">
          <label>Student / Staff ID *</label>
          <input type="text" id="user_id" name="user_id" required placeholder="Enter your ID number">
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select id="category" name="category" required>
            <option value="">Select category</option>
            <option value="login">Login Issues</option>
            <option value="borrowing">Borrowing Problems</option>
            <option value="reservation">Reservation Questions</option>
            <option value="account">Account Suspension</option>
            <option value="digital">Digital Resources</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Your Message *</label>
          <textarea id="message" name="message" rows="5" required placeholder="Describe your issue or question in detail..."></textarea>
        </div>
        <button type="submit" class="btn-submit">📤 Send Message to Library Director</button>
      </form>
    </div>
  </div>
  
  <div class="footer">
    <p>© <?php echo date('Y'); ?> Raya University Digital Central Library. All rights reserved.</p>
    <p>Empowering knowledge through digital innovation</p>
  </div>
</div>

<script>
  // FAQ Toggle Functionality
  document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
      const answer = question.nextElementSibling;
      const icon = question.querySelector('.icon');
      
      answer.classList.toggle('show');
      question.classList.toggle('active');
      
      document.querySelectorAll('.faq-question').forEach(q => {
        if (q !== question) {
          q.nextElementSibling.classList.remove('show');
          q.classList.remove('active');
        }
      });
    });
  });
  
  // Scroll to section function
  function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
  
  // Optional: Form validation before submit
  document.getElementById('supportForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const userId = document.getElementById('user_id').value;
    const category = document.getElementById('category').value;
    const message = document.getElementById('message').value;
    
    if (!name || !email || !userId || !category || !message) {
      e.preventDefault();
      const alertDiv = document.getElementById('alertMessage');
      alertDiv.className = 'alert alert-error';
      alertDiv.style.display = 'block';
      alertDiv.innerHTML = '⚠️ Please fill in all required fields before submitting.';
      setTimeout(() => {
        alertDiv.style.display = 'none';
      }, 5000);
    }
  });
</script>
</body>
</html>