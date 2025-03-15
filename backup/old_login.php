<?php
session_start();
include '../db/db_conn.php';

// Check if form data is set
$inputEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$inputPassword = isset($_POST['password']) ? trim($_POST['password']) : '';

// Basic validation
if (empty($inputEmail) || empty($inputPassword)) {
    $error = urlencode("Email and password fields are required.");
    header("Location: ../employee/login.php?error=$error");
    exit();
}

// Password validation: at least one uppercase letter, one lowercase letter, one number, and one special character
$passwordPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).+$/";
if (!preg_match($passwordPattern, $inputPassword)) {
    $error = urlencode("Password must be at least 8 characters long and contain at least one number, special character, uppercase and lowercase letter.");
    header("Location: ../employee/login.php?error=$error");
    exit();
}

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

        // Redirect based on position
        if ($employeeData['position'] === 'Staff') {
            $stmt->close();
            $conn->close();
            header("Location: ../employee/staff/dashboard.php"); // Redirect to staff dashboard
            exit();
        } elseif ($employeeData['position'] === 'Supervisor') {
            $stmt->close();
            $conn->close();
            header("Location: ../employee/supervisor/dashboard.php"); // Redirect to supervisor dashboard
            exit();
        } else {
            // Handle other positions or unknown positions
            $error = urlencode("Invalid position detected.");
            $stmt->close();
            $conn->close();
            header("Location: ../employee/login.php?error=$error");
            exit();
        }
    } else {
        $error = urlencode("Invalid email or password.");
        $stmt->close();
        $conn->close();
        header("Location: ../employee/login.php?error=$error");
        exit();
    }
} else {
    $error = urlencode("Invalid email or password.");
    $stmt->close();
    $conn->close();
    header("Location: ../employee/login.php?error=$error");
    exit();
}
?>