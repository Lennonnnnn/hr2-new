<?php
session_start();
include '../db/db_conn.php'; // Include your database connection file

// Set the timezone to Manila time (or your desired timezone)
date_default_timezone_set('Asia/Manila');

// Get employee ID from session before destroying the session
$user_id = $_SESSION['e_id']; // Assuming 'e_id' stores the employee ID

// Check if the user is logged in
if (isset($user_id)) {
    // Get current time for logout (in UTC)
    $logout_time = date("Y-m-d H:i:s");  // This will use Manila time now

    // Update only the logout_time for the current session
    $update_sql = "UPDATE user_activity SET logout_time = ? WHERE user_id = ? ORDER BY login_time DESC LIMIT 1";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $logout_time, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Unset session variables
unset($_SESSION['e_id']);
unset($_SESSION['role']);

// Redirect to the login page
header("Location: ../login.php");
exit();
?>
