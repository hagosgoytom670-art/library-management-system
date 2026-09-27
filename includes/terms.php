<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - RU Digital Central Library</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #0a4b8c, #003d6b);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .logo h1 {
            font-size: 1.3rem;
        }
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 25px;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        .title {
            color: #0a4b8c;
            border-bottom: 3px solid #0a4b8c;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        .last-updated {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 2rem;
            font-style: italic;
        }
        .section {
            margin-bottom: 2rem;
        }
        .section h2 {
            color: #0a4b8c;
            font-size: 1.3rem;
            margin-bottom: 1rem;
            padding-left: 0.5rem;
            border-left: 4px solid #27ae60;
        }
        .section p {
            margin-bottom: 0.8rem;
            color: #555;
        }
        .section ul {
            margin: 0.8rem 0 0.8rem 2rem;
            color: #555;
        }
        .section li {
            margin-bottom: 0.5rem;
        }
        .footer {
            background: #1a2c3e;
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1rem;
            }
            .header {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <h1>📚 RU Digital Central Library</h1>
        </div>
        <a href="../index.php" class="back-btn">← Back to Login</a>
    </div>

    <div class="container">
        <h1 class="title">Terms of Service</h1>
        <div class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></div>

        <div class="section">
            <h2>1. Acceptance of Terms</h2>
            <p>By accessing and using the RU Digital Central Library website, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>
        </div>

        <div class="section">
            <h2>2. Library Account Registration</h2>
            <p>To access library resources, you must register for an account. You agree to provide accurate information and maintain the security of your account credentials.</p>
        </div>

        <div class="section">
            <h2>3. Borrowing Rules</h2>
            <p><strong>Loan Period:</strong> Books can be borrowed for 14 days.<br>
            <strong>Late return:</strong> Late returns will cause account suspension for 30 days.<br>
            <strong>Maximum Borrowing Limit:</strong> Students may borrow up to 3 books at a time. </p>
        </div>

        <div class="section">
            <h2>4. Code of Conduct</h2>
            <p>Users agree to respect intellectual property rights, not share login credentials, and maintain a respectful environment for all library users.</p>
        </div>

        <div class="section">
            <h2>5. Privacy Policy</h2>
            <p>Your privacy is important to us. We do not share your personal information with third parties. Borrowing history is kept confidential.</p>
        </div>

        <div class="section">
            <h2>6. Contact Information</h2>
             <h3>Library Director</h3>
            <p>📧 Email:TekleHaylekiros@rayu.edu.et<br>
            📞 Phone: +251 985 41 48 46<br>
            📍 Address: Main Campus Library Building(block-13), RU University</p>
        </div>
    </div>

    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> RU Digital Central Library. All rights reserved.</p>
    </div>
</body>
</html>