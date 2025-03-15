<?php
session_start();

include '../db/db_conn.php';

// Get admin ID from request
$adminId = isset($_POST['a_id']) ? trim($_POST['a_id']) : '';

// Basic validation
if (empty($adminId)) {
    echo json_encode(['error' => 'Admin ID is required.']);
    exit();
}

// Check if the admin is logged in
if (!isset($_SESSION['a_id'])) {
    echo json_encode(['error' => 'You must be logged in to perform this action.']);
    exit();
}

$loggedInAdminId = $_SESSION['a_id'];  // The ID of the logged-in admin

// Get logged-in admin's name (for the activity log)
$adminQuery = "SELECT firstname, lastname FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($adminQuery);
$stmt->bind_param("i", $loggedInAdminId);
$stmt->execute();
$adminResult = $stmt->get_result();

if ($adminResult->num_rows > 0) {
    $admin = $adminResult->fetch_assoc();
    $adminName = $admin['firstname'] . ' ' . $admin['lastname'];
} else {
    echo json_encode(['error' => 'Logged-in admin details not found.']);
    exit();
}

// Prepare and execute SQL statement for deleting the admin
$sql = "DELETE FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $adminId);

if ($stmt->execute()) {
    // Log the action in activity logs table
    $actionType = "Deleted Admin Account";
    $affectedFeature = "Admin Management";
    $details = "Admin account with ID: ($adminId) Name: {$admin['firstname']} {$admin['lastname']} has been deleted.";

    // Capture the IP address of the admin performing the action
    $ipAddress = $_SERVER['REMOTE_ADDR'];

    // Insert activity log
    $logQuery = "INSERT INTO activity_logs (admin_id, admin_name, action_type, affected_feature, details, ip_address) 
                 VALUES (?, ?, ?, ?, ?, ?)";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bind_param("isssss", $loggedInAdminId, $adminName, $actionType, $affectedFeature, $details, $ipAddress);

    if ($logStmt->execute()) {
        echo json_encode(['success' => 'Admin deleted successfully and activity logged!']);
    } else {
        echo json_encode(['error' => 'Failed to log activity: ' . $logStmt->error]);
    }

    // Close log statement
    $logStmt->close();
} else {
    echo json_encode(['error' => 'Error deleting admin: ' . $stmt->error]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>
