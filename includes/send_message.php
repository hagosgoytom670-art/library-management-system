<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $category = $_POST['category'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Recipient email (Library Director)
    $to = "TekleHaylekiros@rayu.edu.et";
    
    $subject = "Library Support Request - $category";
    
    $email_body = "You have received a new message from the Library Help Center.\n\n";
    $email_body .= "Name: $name\n";
    $email_body .= "Email: $email\n";
    $email_body .= "Student/Staff ID: $user_id\n";
    $email_body .= "Category: $category\n\n";
    $email_body .= "Message:\n$message\n";
    
    $headers = "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";
    
    // Send email
    if (mail($to, $subject, $email_body, $headers)) {
        header("Location: help.php?success=1");
    } else {
        header("Location: help.php?error=1");
    }
} else {
    header("Location: help.php");
}
exit;
?>