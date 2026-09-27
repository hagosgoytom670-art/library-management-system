<?php
require 'vendor/autoload.php'; // adjust path

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';      // or your provider
    $mail->SMTPAuth = true;
    $mail->Username = 'hagosjeb@gmail.com';
    $mail->Password = 'woujcppxivbczopy'; // use app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('hagosjeb@gmail.com', 'Test');
    $mail->addAddress('rebojeb19@gmail.com'); // send to yourself

    $mail->Subject = 'SMTP Test';
    $mail->Body    = 'This is a test.';

    $mail->send();
    echo 'Message sent successfully';
} catch (Exception $e) {
    echo "Mailer Error: " . $mail->ErrorInfo;
}
?>