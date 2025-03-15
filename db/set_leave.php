<?php
session_start();
include '../db/db_conn.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure the admin is logged in
if (!isset($_SESSION['a_id'])) {
    die("Error: You must be logged in as admin.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get leave allocations from the form
    $employee_leaves = $_POST['employee_leaves'];
    $employeeId = $_POST['employee_id']; // Get specific employee ID

    // Validate leave input
    if (!is_numeric($employee_leaves) || $employee_leaves <= 0 || $employee_leaves > 20) {
        die("Error: Leave days must be a number between 1 and 20.");
    }

    // Validate employee selection
    if (empty($employeeId) || $employeeId == '') {
        die("Error: You must select a valid employee.");
    }


    // Get the admin's ID from session
    $admin_id = $_SESSION['a_id'];

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

    // Prepare activity log details
    $action_type = "Leave Allocation Updated";
    $affected_feature = "Leave Information";
    $details = '';

    // Insert the log entry into activity_logs table
    $log_query = "INSERT INTO activity_logs (admin_id, admin_name, action_type, affected_feature, details, ip_address) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("isssss", $admin_id, $admin_name, $action_type, $affected_feature, $details, $ip_address);
    $log_stmt->execute();

    // Respond to the user with success message
    echo "<div class='alert alert-success'>$details</div>";
}
?>
