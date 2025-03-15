<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require '../db/pass_conn.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'microfinancehr2@gmail.com';
        $mail->Password = 'yjla pidq jfdr qbnz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $checkEmailSql = "SELECT * FROM admin_register WHERE email = ?";
        $checkStmt = $db->prepare($checkEmailSql);
        $checkStmt->execute([$email]);

        if ($checkStmt->rowCount() > 0) {
            date_default_timezone_set('Asia/Manila');
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$email, $token, $expiresAt]);

            $mail->setFrom('microfinancehr2@gmail.com', 'Microfinance');
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Request';
            $resetLink = "http://localhost/HR2/main/reset_password.php?token=$token";
            $mail->isHTML(true);
            $mail->Body = 'Hello,<br><br>To reset your password, click the following link: <a href="' . $resetLink . '">Reset Password</a>';
            $mail->send();
            $message = '<div class="alert alert-success text-center">Password reset link sent successfully.</div>';
        } else {
            $message = '<div class="alert alert-danger text-center">Email does not exist.</div>';
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger text-center">Message could not be sent. Mailer Error: ' . $mail->ErrorInfo . '</div>';
    }
}
?>