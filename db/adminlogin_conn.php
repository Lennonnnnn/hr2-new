<?php
session_start();

include '../db/db_conn.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../phpmailer/vendor/autoload.php'; // Ensure PHPMailer is autoloaded

// Check if form data is set
$inputEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$inputPassword = isset($_POST['password']) ? trim($_POST['password']) : '';

// Basic validation
if (empty($inputEmail) || empty($inputPassword)) {
    redirectWithError("Email and password fields are required.");
}

// Password validation: at least one uppercase letter, one lowercase letter, one number, and one special character
$passwordPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/";
if (!preg_match($passwordPattern, $inputPassword)) {
    // If the password does not match the pattern, record it as a failed login attempt
    recordFailedLoginAttempt($conn, $inputEmail);
    redirectWithError("Invalid email or password.");
}

// Prepare and execute SQL statement to check if the email exists
$sql = "SELECT a_id, password FROM admin_register WHERE email = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $inputEmail);
$stmt->execute();
$result = $stmt->get_result();

// Check if the email exists in the admin_register table
if ($result->num_rows > 0) {
    $adminData = $result->fetch_assoc(); // Fetch admin data

    // Check login attempts and account lock status
    $attemptsData = checkLoginAttempts($conn, $inputEmail);

    if ($attemptsData['locked'] == 1) {
        redirectWithError("Your account is locked due to multiple failed login attempts. Please reset your login attempts via the email link.");
    }

    // Verify the password
    if (password_verify($inputPassword, $adminData['password'])) {
        resetLoginAttempts($conn, $inputEmail);
        $_SESSION['a_id'] = $adminData['a_id']; // Store admin ID in session
        $stmt->close();
        $conn->close();
        header("Location: ../admin/dashboard.php"); // Redirect to main dashboard
        exit();
    } else {
        // Handle failed login attempt
        recordFailedLoginAttempt($conn, $inputEmail);
        redirectWithError("Invalid email or password.");
    }
} else {
    // Handle failed login attempt
    recordFailedLoginAttempt($conn, $inputEmail);
    redirectWithError("Invalid email or password.");
}

// Helper function: Redirect with error message
function redirectWithError($message) {
    $error = urlencode($message);
    header("Location: ../admin/login.php?error=$error");
    exit();
}

// Helper function: Check login attempts and return data
function checkLoginAttempts($conn, $email) {
    $attemptsSql = "SELECT attempts, locked, alert_sent FROM login_attempts WHERE email = ?";
    $attemptsStmt = $conn->prepare($attemptsSql);
    $attemptsStmt->bind_param("s", $email);
    $attemptsStmt->execute();
    $attemptsResult = $attemptsStmt->get_result();

    if ($attemptsResult->num_rows > 0) {
        return $attemptsResult->fetch_assoc();
    } else {
        // If no login attempt record exists, create one
        $insertSql = "INSERT INTO login_attempts (email, attempts, last_attempt, alert_sent) VALUES (?, 0, NOW(), 0)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("s", $email);
        $insertStmt->execute();
        return ['attempts' => 0, 'locked' => 0, 'alert_sent' => 0];
    }
}

// Function to record failed login attempt
function recordFailedLoginAttempt($conn, $email) {
    $check_sql = "SELECT email, attempts, last_attempt, locked, alert_sent FROM login_attempts WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $attempts = $row['attempts'];
        $alertSent = $row['alert_sent'];

        // Increment the attempt counter
        $attempts += 1;

        if ($attempts >= 3 && !$alertSent) {
            lockAccount($conn, $email);
            sendAlertEmail($email, $conn); // Send email notification after 3 failed attempts
            markAlertAsSent($conn, $email); // Mark the alert as sent
        }

        // Update login attempts and the time of the last failed attempt
        $update_sql = "UPDATE login_attempts SET attempts = ?, last_attempt = NOW() WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("is", $attempts, $email);
        $update_stmt->execute();
    } else {
        // If no record exists, insert a new one
        $attempts = 1;
        $insert_sql = "INSERT INTO login_attempts (email, attempts, last_attempt, alert_sent) VALUES (?, ?, NOW(), 0)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("si", $email, $attempts);
        $insert_stmt->execute();
    }
}

// Function to lock the account after too many failed attempts
function lockAccount($conn, $email) {
    $lock_sql = "UPDATE login_attempts SET locked = 1 WHERE email = ?";
    $lock_stmt = $conn->prepare($lock_sql);
    $lock_stmt->bind_param("s", $email);
    $lock_stmt->execute();
}

// Function to reset login attempts after successful login
function resetLoginAttempts($conn, $email) {
    $reset_attempts_sql = "DELETE FROM login_attempts WHERE email = ?";
    $reset_stmt = $conn->prepare($reset_attempts_sql);
    $reset_stmt->bind_param("s", $email);
    $reset_stmt->execute();
}

// Function to mark the alert as sent
function markAlertAsSent($conn, $email) {
    $update_sql = "UPDATE login_attempts SET alert_sent = 1 WHERE email = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $email);
    $update_stmt->execute();
}

// Function to send an email alert using PHPMailer
function sendAlertEmail($email, $conn) {
    $mail = new PHPMailer(true);

    try {
        // Check if the email exists in the admin_register table to fetch the user's name
        $sql = "SELECT firstname, lastname FROM admin_register WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch the user's name if the email exists
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userName = $row['firstname'] . ' ' . $row['lastname'];
        } else {
            // If no user is found with the email, return an error or handle accordingly
            error_log("No user found with the email: " . $email);
            return;
        }

        // Generate a unique token for resetting password
        date_default_timezone_set('Asia/Manila');
        $token = bin2hex(random_bytes(32)); 

        // Create the reset links
        $resetLogin = "http://localhost/HR2/admin/reset_login.php?email=" . urlencode($email);
        $resetLink = "http://localhost/HR2/admin/reset_password.php?token=$token";
        
        // Insert the token into the database (ensure you have a password_resets table)
        $expires_at = date("Y-m-d H:i:s", strtotime('+3 minutes')); // Token expires in 3 minutes
        $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $email, $token, $expires_at);
        $stmt->execute();

        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Use Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'microfinancehr2@gmail.com'; // Your Gmail address
        $mail->Password = 'yjla pidq jfdr qbnz'; // Your Gmail password or app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('microfinancehr2@gmail.com', 'Microfinance');
        $mail->addAddress($email); // Send the email to the user

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Security Alert: Multiple Failed Login Attempts';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; padding: 20px; background-color: rgba(24, 25, 26); color: #f8f9fa;">
                <div style="max-width: 600px; margin: 0 auto; background-color: rgba(33, 37, 41); padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <h2 style="text-align: center; color: #e74c3c;">Security Alert: Multiple Failed Login Attempts</h2>
                    <p style="font-size: 16px; color: #f8f9fa;">Hello, ' . $userName . '</p>
                    <p style="font-size: 16px; color: #f8f9fa;">There have been 10 consecutive failed login attempts to your account. If this wasn’t you, please reset your password immediately to secure your account.</p>
                    <p style="font-size: 16px; color: #f8f9fa;">To reset your login attempts and prevent further issues, click the button below:</p>
                    <p style="text-align: center;">
                    <form method="POST" action="http://localhost/HR2/db/reset_login_attempt.php" style="max-width: 400px; margin: 0 auto; padding: 20px; text-align: center;">
                        <div class="mb-3" style="display: none;"> <!-- Hides the email input -->
                            <input type="email" class="form-control" id="email" name="email" value="' . $email . '" readonly style="color:rgb(0, 0, 0); border-color: #7f8c8d; width: 100%; padding: 10px 15px; font-size: 14px; border-radius: 5px; text-align: center;">
                        </div>
                        <button type="submit" class="btn btn-lg btn-success w-100" name="reset_login_attempt" style="padding: 10px 20px; background-color: #3498db; color: #fff; text-decoration: none; border-radius: 5px; display: block; margin: 10px auto 0; cursor: pointer !important;">
                            Reset Login Attempts
                        </button>
                    </form>
                    </p>   
                    <p style="font-size: 16px; color: #f8f9fa;">If you believe your password was compromised, please click the link below to reset it:</p>
                    <p style="text-align: center;">
                        <a href="' . $resetLink . '" style="padding: 10px 20px; background-color: #3498db; color: #fff; text-decoration: none; border-radius: 5px; display: inline-block;">
                            Reset Your Password
                        </a>
                    </p>
                    <p style="font-size: 14px; color: #f8f9fa; text-align: center; margin-top: 20px;">This link will expire in 3 minutes.</p>
                    <p style="font-size: 14px; color: #f8f9fa;">Best regards,<br>The HR Team</p>
                </div>
            </div>';

        // Send the email
        $mail->send();

    } catch (Exception $e) {
        // Handle email sending error
        error_log("Email could not be sent. PHPMailer Error: {$mail->ErrorInfo}");
    }
}

?>
