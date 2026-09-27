<?php
include("includes/header.php");
include("db.php");

$success = '';
$error = '';
$form_data = ['name' => '', 'email' => '', 'message' => ''];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $name = trim(htmlspecialchars($_POST['name']));
    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $message = trim(htmlspecialchars($_POST['message']));
    
    $form_data = ['name' => $name, 'email' => $email, 'message' => $message];
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Please enter your name.";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters long.";
    }
    
    if (empty($email)) {
        $errors[] = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($message)) {
        $errors[] = "Please enter your message.";
    } elseif (strlen($message) < 10) {
        $errors[] = "Message must be at least 10 characters long.";
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message, submitted_at, ip_address) VALUES (?, ?, ?, NOW(), ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt->bind_param("ssss", $name, $email, $message, $ip);
        
        if ($stmt->execute()) {
            $success = "Thank you for contacting us! We'll respond within 24-48 hours.";
            $form_data = ['name' => '', 'email' => '', 'message' => '']; // Reset form
        } else {
            $error = "We're experiencing technical difficulties. Please try again later or call us directly.";
            error_log("Contact form error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Library Management System</title>
    <link rel="stylesheet" href="../assets/css/contact_us.css">
    <style>
        .contact-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .contact-header h2 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .contact-header p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
            font-size: 14px;
        }
        
        .form-group label .required {
            color: #e74c3c;
            margin-left: 3px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
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
        
        .btn-submit {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }
        
        .btn-submit:hover {
            background: #2980b9;
        }
        
        .contact-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .info-item {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-item h4 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .info-item p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-align: center;
            width: 100%;
            color: #7f8c8d;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: #3498db;
        }
        
        @media (max-width: 768px) {
            .contact-container {
                margin: 20px;
                padding: 20px;
            }
            
            .contact-info {
                flex-direction: column;
            }
        }
    </style>
    <a href="index.php" 
   style="display:inline-block; padding:8px 14px; background-color:#007BFF; color:#fff; 
          text-decoration:none; border-radius:4px; font-family:Arial, sans-serif; 
          font-size:14px; transition:background-color 0.3s ease;"
   class="back-link">← Back to Home</a>

</head>
<body>
    <div class="contact-container">
        <div class="contact-header">
            <h2>Contact Us</h2>
            <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-error">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="contactForm">
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" 
                       value="<?= htmlspecialchars($form_data['name']) ?>" 
                       placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($form_data['email']) ?>" 
                       placeholder="you@example.com" required>
            </div>
            
            <div class="form-group">
                <label for="message">Message <span class="required">*</span></label>
                <textarea id="message" name="message" 
                          placeholder="How can we help you? Please provide as much detail as possible..." 
                          required><?= htmlspecialchars($form_data['message']) ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Send Message</button>
        </form>
        
        <div class="contact-info">
            <div class="info-item">
                <h4>📍 Visit Us</h4>
                <p>manin compus Block-13, Near Student Cafeteria</p>
            </div>
            <div class="info-item">
                <p>Desta Hagos Berhanu</br>
                	Library Users Service Team Leader</p>
                <h4>📞 Call Us</h4>
                <p><a href="tel: +251 920 84 53 43"> +251 920 84 53 43</a>
</p>
            </div>
            <div class="info-item">
                <h4>✉️ Email</h4>
                <p>
               <a href="mailto:destahagos@rayu.edu.et"> destahagos@rayu.edu.e </a>
              
            </p>
            </div>
     
    
    <script>
    // Client-side validation for better UX
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const message = document.getElementById('message').value.trim();
        
        if (name.length < 2) {
            e.preventDefault();
            alert('Please enter your full name (minimum 2 characters).');
            return false;
        }
        
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            return false;
        }
        
        if (message.length < 10) {
            e.preventDefault();
            alert('Please enter a message with at least 10 characters.');
            return false;
        }
        
        return true;
    });
    </script>
</body>
</html>
