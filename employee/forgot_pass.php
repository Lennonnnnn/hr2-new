<?php
session_start(); // Start the session

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/vendor/autoload.php';
require '../db/db_conn.php'; // Ensure this includes your database connection

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $mail = new PHPMailer(true);

    try {
        // Check if the email exists in the admin_register table
        $sql = "SELECT firstname, lastname FROM admin_register WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch the first and last name
            $row = $result->fetch_assoc();
            $userName = $row['firstname'] . ' ' . $row['lastname'];

            // Generate the reset token and set expiration time
            date_default_timezone_set('Asia/Manila');
            $token = bin2hex(random_bytes(32));  // Generate a secure token
            $expiresAt = date('Y-m-d H:i:s', strtotime('+3 minutes'));  // Token expires in 3 minutes

            // Store token and expiration in the database
            $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $email, $token, $expiresAt);  // Correct parameter binding
            $stmt->execute();

            // PHPMailer settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'microfinancehr2@gmail.com';  // Use your Gmail account
            $mail->Password = 'yjla pidq jfdr qbnz';  // Use the App Password if 2FA is enabled
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('microfinancehr2@gmail.com', 'Microfinance');
            $mail->addAddress($email);
            $mail->Subject = 'Password Reset Request';
            $resetLink = "http://localhost/HR2/employee/reset_password.php?token=$token";
            $mail->isHTML(true);

            // Email body with inline styles
            $mail->Body = '
                <div style="font-family: Arial, sans-serif; padding: 20px; background-color: rgba(24, 25, 26); color: #f8f9fa;">
                    <div style="max-width: 600px; margin: 0 auto; background-color: rgba(33, 37, 41); padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h2 style="text-align: center; color: #007bff;">Password Reset Request</h2>
                        <p style="font-size: 16px; color: #f8f9fa;">Hello, ' . $userName . '</p>
                        <p style="font-size: 16px; color: #f8f9fa;">We received a request to reset your password. Click the button below to reset your password:</p>
                        <p style="text-align: center; margin-top: 30px;">
                            <a href="' . $resetLink . '" style="padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px; display: inline-block;">
                                Reset Password
                            </a>
                        </p>
                        <p style="font-size: 16px; color: #999; text-align: center;">This link will expire in 3 minutes.</p>
                        <hr style="border-top: 1px solid #ddd; margin-top: 20px;">
                        <p style="font-size: 14px; color: #f8f9fa;">If you didn\'t request a password reset, you can safely ignore this email.</p>
                        <p style="font-size: 14px; color: #999; text-align: center;">Microfinance HR 2 System</p>
                    </div>
                </div>
            ';

            // Send the email
            $mail->send();

            // Store success message in session
            $_SESSION['message'] = '<div class="alert alert-success text-center">Password reset link sent successfully.</div>';

            // Redirect to prevent form resubmission on refresh
            header('Location: ' . $_SERVER['REQUEST_URI']); // Refresh the current page
            exit;
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger text-center">Email does not exist.</div>';
            header('Location: ' . $_SERVER['REQUEST_URI']); // Refresh the current page
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['message'] = '<div class="alert alert-danger text-center">Message could not be sent. Mailer Error: ' . $mail->ErrorInfo . '</div>';
        header('Location: ' . $_SERVER['REQUEST_URI']); // Refresh the current page
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Password Recovery</title>
    <link href="../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="bg-black">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container">
                    <div class="row justify-content-center mt-5">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-3 bg-dark">
                                <div class="card-header border-bottom border-1 border-warning">
                                    <h3 class="text-center text-light font-weight-light my-4">Password Recovery</h3>
                                    <?php
                                    // Display message if set in session
                                    if (isset($_SESSION['message'])) {
                                        echo $_SESSION['message'];
                                        unset($_SESSION['message']); // Clear the message after displaying it
                                    }
                                    ?>
                                </div>
                                <div class="card-body">
                                    <div class="small mb-3 text-light">Enter your email address and we will send you a
                                        link to your Gmail to reset your password.</div>
                                    <form method="POST" action="">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputEmail" name="email" type="email" placeholder="name@example.com" required />
                                            <label for="inputEmail">Email address</label>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-end mt-4 mb-2">
                                            <button type="submit" class="btn btn-primary align-items-center w-100">Send Link</button>
                                        </div>
                                        <div class="text-center mt-3 mb-2"> <a class="btn border-secondary w-100 text-light border border-1" href="../employee/login.php">Back</a></div>
                                    </form>
                                </div>
                                <div class="card-footer text-center border-top border-1 border-warning">
                                    <div class="text-center text-muted">Human Resource 2</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div id="layoutAuthentication_footer">
            <footer class="py-4 bg-dark mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2023</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="/js/scripts.js"></script>
</body>
</html>
