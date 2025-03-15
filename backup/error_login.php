<?php
session_start();
session_regenerate_id(true);

include 'db/db_conn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../HR2/phpmailer/vendor/autoload.php'; // Ensure PHPMailer is autoloaded

// Check if form data is set
$inputEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$inputPassword = isset($_POST['password']) ? trim($_POST['password']) : '';

// Basic validation
if (empty($inputEmail) || empty($inputPassword)) {
    $error = urlencode("Email and password fields are required.");
    header("Location: ../HR2/login.php?error=$error");
    exit();
}

// Password validation: at least one uppercase letter, one lowercase letter, one number, and one special character
$passwordPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).+$/";
if (!preg_match($passwordPattern, $inputPassword)) {
    $error = urlencode("Password must be at least 8 characters long and contain at least one number, special character, uppercase and lowercase letter.");
    header("Location: ../HR2/login.php?error=$error");
    exit();
}

// Track failed login attempts
$failedAttemptsSql = "SELECT failed_attempts, last_failed_attempt, banned_until FROM login_attempts WHERE email = ?";
$stmt = $conn->prepare($failedAttemptsSql);
$stmt->bind_param("s", $inputEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $loginData = $result->fetch_assoc();
    $failedAttempts = $loginData['failed_attempts'];
    $lastFailedAttempt = strtotime($loginData['last_failed_attempt']);
    $bannedUntil = strtotime($loginData['banned_until']);
    $currentTime = time();

    // If the account is banned, check if the ban time has passed
    if ($bannedUntil > $currentTime) {
        // The account is still banned
        $banEndTime = $bannedUntil; // Store the banned until time
        $error = urlencode("Your account is temporarily banned. Please wait until...");
        $stmt->close();
        $conn->close();
        header("Location: ../HR2/login.php?error=$error&banEndTime=$banEndTime");
        exit();
    } elseif ($bannedUntil <= $currentTime && $loginData['banned_until'] !== null) {
        // Ban has passed, reset the ban status and failed attempts
        $failedAttempts = 0;
        $updateBanStatusSql = "UPDATE login_attempts SET failed_attempts = 0, banned_until = NULL WHERE email = ?";
        $updateBanStmt = $conn->prepare($updateBanStatusSql);
        $updateBanStmt->bind_param("s", $inputEmail);
        $updateBanStmt->execute();
        $updateBanStmt->close();
    }

    // Reset failed attempts if it's been more than 30 minutes
    if ($currentTime - $lastFailedAttempt > 20) {
        $failedAttempts = 0;
        $updateFailedAttemptsSql = "UPDATE login_attempts SET failed_attempts = 0 WHERE email = ?";
        $updateStmt = $conn->prepare($updateFailedAttemptsSql);
        $updateStmt->bind_param("s", $inputEmail);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // Ban account after 3 failed attempts
    if ($failedAttempts >= 3) {
        $bannedUntil = date("Y-m-d H:i:s", strtotime('+20 seconds')); // Ban for 30 minutes
        $updateBanStatusSql = "UPDATE login_attempts SET banned_until = ?, failed_attempts = 0 WHERE email = ?";
        $updateBanStmt = $conn->prepare($updateBanStatusSql);
        $updateBanStmt->bind_param("ss", $bannedUntil, $inputEmail);
        $updateBanStmt->execute();
        sendBanAlertEmail($inputEmail, $conn); // Send alert email
        $updateBanStmt->close();
        
        $banEndTime = strtotime($bannedUntil);
        $error = urlencode("Your account has been temporarily banned due to multiple failed login attempts.");
        $stmt->close();
        $conn->close();
        header("Location: ../HR2/login.php?error=$error&banEndTime=$banEndTime");
        exit();
    }
} else {
    // Initialize failed login attempts for new user
    $insertSql = "INSERT INTO login_attempts (email, failed_attempts, last_failed_attempt, banned_until) VALUES (?, 0, NOW(), NULL)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("s", $inputEmail);
    $insertStmt->execute();
    $insertStmt->close();
}



// Employee login check
$sql = "SELECT e_id, password, position FROM employee_register WHERE email = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $inputEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $employeeData = $result->fetch_assoc();

    // Verify the password
    if (password_verify($inputPassword, $employeeData['password'])) {
        $_SESSION['e_id'] = $employeeData['e_id']; 
        $_SESSION['position'] = $employeeData['position']; // Store the user's position in the session

        date_default_timezone_set('Asia/Manila');

        $login_time = date("Y-m-d H:i:s");

        // Record login activity in the user_activity table
        $activitySql = "INSERT INTO user_activity (user_id, login_time) VALUES (?, ?)";
        $activityStmt = $conn->prepare($activitySql);
        $activityStmt->bind_param("is", $_SESSION['e_id'], $login_time);
        $activityStmt->execute();
        $activityStmt->close();

        // Reset failed attempts on successful login
        $resetAttemptsSql = "UPDATE login_attempts SET failed_attempts = 0 WHERE email = ?";
        $resetStmt = $conn->prepare($resetAttemptsSql);
        $resetStmt->bind_param("s", $inputEmail);
        $resetStmt->execute();
        $resetStmt->close();

        // Redirect based on position
        if ($employeeData['position'] === 'Staff') {
            $stmt->close();
            $conn->close();
            header("Location: ../HR2/employee/staff/dashboard.php"); // Redirect to staff dashboard
            exit();
        } elseif ($employeeData['position'] === 'Supervisor') {
            $stmt->close();
            $conn->close();
            header("Location: ../HR2/employee/supervisor/dashboard.php"); // Redirect to supervisor dashboard
            exit();
        } else {
            // Handle other positions or unknown positions
            $error = urlencode("Invalid position detected.");
            $stmt->close();
            $conn->close();
            header("Location: ../HR2/login.php?error=$error");
            exit();
        }
    } else {
        // Increment failed attempts if password verification fails
        $failedAttempts++;
        $updateFailedAttemptsSql = "UPDATE login_attempts SET failed_attempts = ?, last_failed_attempt = NOW() WHERE email = ?";
        $updateStmt = $conn->prepare($updateFailedAttemptsSql);
        $updateStmt->bind_param("is", $failedAttempts, $inputEmail);
        $updateStmt->execute();
        $updateStmt->close();

        $error = urlencode("Invalid email or password.");
        $stmt->close();
        $conn->close();
        header("Location: ../HR2/login.php?error=$error");
        exit();
    }
} else {
    $error = urlencode("Invalid email or password.");
    $stmt->close();
    $conn->close();
    header("Location: ../HR2/login.php?error=$error");
    exit();
}

// Admin login check if employee login fails

$sql = "SELECT a_id, password, role FROM admin_register WHERE email = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error); // Add error output for debugging
}

$stmt->bind_param("s", $inputEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $adminData = $result->fetch_assoc();

    // Verify the password using password_verify
    if (password_verify($inputPassword, $adminData['password'])) {
        $_SESSION['a_id'] = $adminData['a_id']; 
        $_SESSION['role'] = $adminData['role']; // Store the admin's role in the session

        date_default_timezone_set('Asia/Manila');

        $login_time = date("Y-m-d H:i:s");

        // Record login activity in the user_activity table
        $activitySql = "INSERT INTO user_activity (user_id, login_time) VALUES (?, ?)";
        $activityStmt = $conn->prepare($activitySql);
        $activityStmt->bind_param("is", $_SESSION['a_id'], $login_time);
        $activityStmt->execute();
        $activityStmt->close();

        // Reset failed attempts on successful login
        $resetAttemptsSql = "UPDATE login_attempts SET failed_attempts = 0 WHERE email = ?";
        $resetStmt = $conn->prepare($resetAttemptsSql);
        $resetStmt->bind_param("s", $inputEmail);
        $resetStmt->execute();
        $resetStmt->close();

        // Redirect to admin dashboard
        $stmt->close();
        $conn->close();
        header("Location: ../HR2/admin/dashboard.php");
        exit();
    } else {
        // Increment failed attempts if password verification fails
        $failedAttempts++;
        $updateFailedAttemptsSql = "UPDATE login_attempts SET failed_attempts = ?, last_failed_attempt = NOW() WHERE email = ?";
        $updateStmt = $conn->prepare($updateFailedAttemptsSql);
        $updateStmt->bind_param("is", $failedAttempts, $inputEmail);
        $updateStmt->execute();
        $updateStmt->close();

        $error = urlencode("Invalid email or password.");
        $stmt->close();
        $conn->close();
        header("Location: ../HR2/login.php?error=$error");
        exit();
    }
} else {
    $error = urlencode("Invalid email or password.");
    $stmt->close();
    $conn->close();
    header("Location: ../HR2/login.php?error=$error");
    exit();
}


// Function to send a ban alert email
function sendBanAlertEmail($email, $conn) {
    $mail = new PHPMailer(true);

    try {
        // Fetch user's name
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
            error_log("No user found with the email: " . $email);
            return;
        }

        // Send email
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'microfinancehr2@gmail.com'; // Your Gmail address
        $mail->Password = 'yjla pidq jfdr qbnz'; // Your Gmail app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('microfinancehr2@gmail.com', 'Microfinance');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Security Alert: Account Temporarily Banned';
        $mail->Body = "
            <p>Hello $userName,</p>
            <p>Your account has been temporarily banned due to multiple failed login attempts. Please try again later.</p>
            <p>Regards,<br>Microfinance HR Team</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
