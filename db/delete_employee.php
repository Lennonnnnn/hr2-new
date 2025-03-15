<?php
session_start();
include '../db/db_conn.php';

// Ensure the admin is logged in
if (!isset($_SESSION['a_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get the admin's ID from session
$admin_id = $_SESSION['a_id'];

// Get employee ID from request
$employeeId = isset($_POST['e_id']) ? trim($_POST['e_id']) : '';

// Basic validation
if (empty($employeeId)) {
    echo json_encode(['error' => 'Employee ID is required.']);
    exit();
}

// Fetch employee details for logging purposes
$query = "SELECT * FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    echo json_encode(['error' => 'Employee not found.']);
    exit();
}

// Prepare and execute SQL statement for deleting related records from user_activity
$sql = "DELETE FROM user_activity WHERE user_id=?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['error' => 'Prepare failed for user_activity delete: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $employeeId);

if (!$stmt->execute()) {
    echo json_encode(['error' => 'Error deleting user activity: ' . $stmt->error]);
    exit();
}

// Prepare and execute SQL statement for deleting the employee record
$sql = "DELETE FROM employee_register WHERE e_id=?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['error' => 'Prepare failed for employee delete: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $employeeId);

if ($stmt->execute()) {
    // Prepare details for logging the deletion
    $action_type = "Deleted Employee Account";
    $affected_feature = "Employee Management";
    $details = "Employee account with ID: ($employeeId) Name: ({$employee['firstname']} {$employee['lastname']}) has been deleted.";

    // Get the admin's name
    $admin_query = "SELECT firstname, lastname FROM admin_register WHERE a_id = ?";
    $admin_stmt = $conn->prepare($admin_query);
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    $admin = $admin_result->fetch_assoc();
    $admin_name = $admin['firstname'] . ' ' . $admin['lastname'];

    // Capture admin's IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Insert the log entry into activity_logs table
    $log_query = "INSERT INTO activity_logs (admin_id, admin_name, action_type, affected_feature, details, ip_address) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("isssss", $admin_id, $admin_name, $action_type, $affected_feature, $details, $ip_address);
    $log_stmt->execute();

    echo json_encode(['success' => 'Employee and related data deleted successfully!']);
} else {
    echo json_encode(['error' => 'Error deleting employee: ' . $stmt->error]);
}

// Close connection
$stmt->close();
$conn->close();
?>
