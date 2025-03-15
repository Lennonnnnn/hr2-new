<?php
session_start();
include '../db/db_conn.php';

// Check if the request ID and status are set
if (isset($_GET['leave_id']) && isset($_GET['status'])) {
    $requestId = (int) $_GET['leave_id'];
    // Set the status based on the value of 'status' parameter
   $status = (strtolower($_GET['status']) === 'approve') ? 'Approved' : 
              (strtolower($_GET['status']) === 'deny' ? 'Denied' : 'Unknown');

    // Prepare and execute the update query
    $sql = "UPDATE leave_requests SET status = ? WHERE leave_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param('si', $status, $requestId);

    // Execute and check affected rows
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Redirect to the main page with a success message
            header("Location: ../admin/leave_status.php?status=success");
        } else {
    // No rows affected (request_id may not exist)
    header("Location: ../adminleave_status.php?status=not_exist");
    exit;  // Stop further execution after header redirection
}

    } else {
        // Log SQL error for debugging
        error_log("SQL Error: " . $stmt->error);
        header("Location: ../admin/leave_status.php?status=error");
    }

    // Close the statement
    $stmt->close();
} else {
    // Redirect if parameters are missing
    header("Location: ../admin/leave_status.php?status=error");
}

// Close the database connection
$conn->close();
?>
