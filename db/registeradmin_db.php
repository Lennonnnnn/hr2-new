<?php
session_start();
include '../db/db_conn.php';

// Get form data with validation
$firstName = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
$lastName = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
$Email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';

// Basic validation
if (empty($firstName) || empty($lastName) || empty($Email) || empty($password) || empty($confirm_password) || empty($gender) || empty($role) || empty($phone_number) || empty($address)) {
    echo json_encode(['error' => 'All fields are required.']);
    exit();
}

// Check if passwords match
if ($password !== $confirm_password) {
    echo json_encode(['error' => 'Passwords do not match.']);
    exit();
}

// Password strength validation
$pattern = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/';
if (!preg_match($pattern, $password)) {
    echo json_encode(['error' => 'Password must be at least 8 characters long and contain at least one number, special character, uppercase and lowercase letter.']);
    exit();
}

// Check if the email already exists
$sql = "SELECT COUNT(*) FROM admin_register WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("s", $Email);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    echo json_encode(['error' => 'This email is already registered.']);
    exit();
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Prepare and execute SQL statement
$sql = "INSERT INTO admin_register (firstname, lastname, email, password, gender, role, phone_number, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ssssssss", $firstName, $lastName, $Email, $hashedPassword, $gender, $role, $phone_number, $address);

if ($stmt->execute()) {
    // Fetch the new admin ID for logging purposes
    $new_admin_id = $stmt->insert_id;

    // Get the logged-in admin's ID and name for logging purposes
    $logged_in_admin_id = isset($_SESSION['a_id']) ? $_SESSION['a_id'] : null;

    if ($logged_in_admin_id) {
        // Fetch the logged-in admin's details
        $admin_query = "SELECT firstname, lastname FROM admin_register WHERE a_id = ?";
        $admin_stmt = $conn->prepare($admin_query);
        $admin_stmt->bind_param("i", $logged_in_admin_id);
        $admin_stmt->execute();
        $admin_result = $admin_stmt->get_result();
        $admin = $admin_result->fetch_assoc();
        $admin_name = $admin['firstname'] . ' ' . $admin['lastname'];
    } else {
        $admin_name = "System"; // Fallback if no admin is logged in
    }

    // Capture the IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Prepare details for logging the new admin registration
    $action_type = "Registered Admin Account";
    $affected_feature = "Admin Management";
    $details = "New admin registered with ID: $new_admin_id Name: $firstName $lastName.";

    // Insert the log entry into activity_logs table
    $log_query = "INSERT INTO activity_logs (admin_id, admin_name, action_type, affected_feature, details, ip_address) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);

    if (!$log_stmt) {
        echo json_encode(['error' => 'Error preparing log query: ' . $conn->error]);
        exit();
    }

    $log_stmt->bind_param("isssss", $logged_in_admin_id, $admin_name, $action_type, $affected_feature, $details, $ip_address);
    $log_stmt->execute();

    echo json_encode(['success' => 'Registration successful and activity logged!']);
} else {
    echo json_encode(['error' => 'Error: ' . $stmt->error]);
}

// Close connection
$stmt->close();
$conn->close();
?>
