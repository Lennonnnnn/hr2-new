<?php
session_start();
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
$failedAttemptsSql = "SELECT failed_attempts, last_failed_attempt FROM login_attempts WHERE email = ?";
$stmt = $conn->prepare($failedAttemptsSql);
$stmt->bind_param("s", $inputEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $loginData = $result->fetch_assoc();
    $failedAttempts = $loginData['failed_attempts'];
    $lastFailedAttempt = strtotime($loginData['last_failed_attempt']);
    $currentTime = time();

    // Reset failed attempts if it's been more than 30 minutes
    if ($currentTime - $lastFailedAttempt > 1800) {
        $failedAttempts = 0;
        $updateFailedAttemptsSql = "UPDATE login_attempts SET failed_attempts = 0 WHERE email = ?";
        $updateStmt = $conn->prepare($updateFailedAttemptsSql);
        $updateStmt->bind_param("s", $inputEmail);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // Send email alert after 3 failed attempts
    if ($failedAttempts >= 3) {
        sendAlertEmail($inputEmail); // Send alert email
    }
} else {
    // Initialize failed login attempts for new user
    $insertSql = "INSERT INTO login_attempts (email, failed_attempts, last_failed_attempt) VALUES (?, 0, NOW())";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("s", $inputEmail);
    $insertStmt->execute();
    $insertStmt->close();
}

// Employee login check
$sql = "SELECT e_id, password, position, department FROM employee_register WHERE email = ?"; // Fetch department
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
        $_SESSION['department'] = $employeeData['department']; // Store the user's department in the session

        date_default_timezone_set('Asia/Manila');

        $login_time = date("Y-m-d H:i:s");

        // Record login activity in the user_activity table
        $activitySql = "INSERT INTO user_activity (user_id, login_time) VALUES (?, ?)";
        $activityStmt = $conn->prepare($activitySql);
        $activityStmt->bind_param("is", $_SESSION['e_id'], $login_time);
        $activityStmt->execute();
        $activityStmt->close();

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
        } elseif ($employeeData['position'] === 'Field Worker') {
            $stmt->close();
            $conn->close();
            header("Location: ../HR2/employee/fieldworker/dashboard.php"); // Redirect to field worker dashboard
            exit();
        } elseif ($employeeData['position'] === 'Contractual') {
            $stmt->close();
            $conn->close();
            header("Location: ../HR2/employee/contractual/dashboard.php"); // Redirect to contractual dashboard
            exit();
        } else {
            // Handle other positions or unknown positions
            $error = urlencode("Invalid position detected.");
            $stmt->close();
            $conn->close();
            header("Location: ../HR2/login.php?error=$error");
            exit();
        }
    }
} 

// Admin login check if employee login fails
$sql = "SELECT a_id, password, role FROM admin_register WHERE email = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $inputEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $adminData = $result->fetch_assoc();

    // Verify the password
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

        // Redirect to admin dashboard
        $stmt->close();
        $conn->close();
        header("Location: ../HR2/admin/dashboard.php");
        exit();
    } else {
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

// Function to send an alert email
function sendAlertEmail($email) {
    global $conn;

    $mail = new PHPMailer(true);
    try {
        // Email setup
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';  // Set the SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@example.com';  // SMTP username
        $mail->Password = 'your_password';  // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender and recipient
        $mail->setFrom('your_email@example.com', 'HR System');
        $mail->addAddress($email);

        // Email content
        $mail->Subject = 'Multiple Failed Login Attempts';
        $mail->Body    = 'There have been multiple failed login attempts to your account. If you did not initiate these attempts, please reset your password immediately.';

        $mail->send();
    } catch (Exception $e) {
        error_log("Failed to send email. Error: " . $mail->ErrorInfo);
    }
}
?>