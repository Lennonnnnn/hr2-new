<?php
session_start();
include '../db/db_conn.php';

// Set the content type to JSON for proper response formatting
header('Content-Type: application/json');

// Enable error reporting to help debug issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the admin is logged in
if (!isset($_SESSION['a_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get the admin's ID from session
$admin_id = $_SESSION['a_id'];

// Get form data with validation
$employeeId = isset($_POST['e_id']) ? trim($_POST['e_id']) : '';
$firstName = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
$lastName = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
$Email = isset($_POST['email']) ? trim($_POST['email']) : '';
$department = isset($_POST['department']) ? trim($_POST['department']) : '';
$position = isset($_POST['position']) ? trim($_POST['position']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';

// Basic validation
if (empty($employeeId) || empty($firstName) || empty($lastName) || empty($Email) || empty($department) || empty($position) || empty($phone_number) || empty($address)) {
    echo json_encode(['error' => 'All fields are required.']);
    exit();
}

// Fetch current employee details to compare with the new ones
$query = "SELECT * FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

// If the employee is not found
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Employee not found.']);
    exit();
}

$employee = $result->fetch_assoc();

// Capture old values for comparison
$old_firstname = $employee['firstname'];
$old_lastname = $employee['lastname'];
$old_email = $employee['email'];
$old_department = $employee['department'];
$old_position = $employee['position'];
$old_phone_number = $employee['phone_number'];
$old_address = $employee['address'];

// Prepare and execute SQL statement for updating
$sql = "UPDATE employee_register SET firstname=?, lastname=?, email=?, department=?, position=?, phone_number=?, address=? WHERE e_id=?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("sssssssi", $firstName, $lastName, $Email, $department, $position, $phone_number, $address, $employeeId);

if ($stmt->execute()) {
    // Prepare details for logging
    $action_type = "Updated Employee Information";
    $affected_feature = "Employee Details";
    $details = "Employee ID: $employeeId updated. ";

    // Log changes in names
    if ($old_firstname !== $firstName || $old_lastname !== $lastName) {
        $details .= "Name changed from '$old_firstname $old_lastname' to '$firstName $lastName'. ";
    }

    // Log changes in email
    if ($old_email !== $Email) {
        $details .= "Email changed from '$old_email' to '$Email'. ";
    }

    // Log changes in position
    if ($old_position !== $position) {
        $details .= "Position changed from '$old_position' to '$position'. ";
    }

    // Log changes in department
    if ($old_department !== $department) {
        $details .= "Department changed from '$old_department' to '$department'. ";
    }

    // Log changes in phone number
    if ($old_phone_number !== $phone_number) {
        $details .= "Phone number changed from '$old_phone_number' to '$phone_number'. ";
    }

    // Log changes in address
    if ($old_address !== $address) {
        $details .= "Address changed from '$old_address' to '$address'. ";
    }

    // Get the admin's name
    $admin_query = "SELECT firstname, lastname FROM admin_register WHERE a_id = ?";
    $admin_stmt = $conn->prepare($admin_query);
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    $admin = $admin_result->fetch_assoc();
    $admin_name = $admin['firstname'] . ' ' . $admin['lastname'];

    // Fetch the IP address of the admin (considering IPv4 fallback)
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        // Check if shared internet
        $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Check if the IP is passed from a proxy
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        // Use REMOTE_ADDR
        $ip_address = $_SERVER['REMOTE_ADDR'];
    }

    // If IPv6 loopback is detected, force it to IPv4
    if ($ip_address === '::1') {
        $ip_address = '127.0.0.1';
    }

    // Insert the log entry into activity_logs table
    $log_query = "INSERT INTO activity_logs (admin_id, admin_name, action_type, affected_feature, details, ip_address) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("isssss", $admin_id, $admin_name, $action_type, $affected_feature, $details, $ip_address);
    $log_stmt->execute();

    echo json_encode(['success' => 'Employee information updated successfully!']);
} else {
    echo json_encode(['error' => 'Error: ' . $stmt->error]);
}

// Close connection
$stmt->close();
$conn->close();
?>
