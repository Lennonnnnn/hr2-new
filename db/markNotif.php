<?php
session_start();
include '../db/db_conn.php';

// Check if the admin is logged in
if (!isset($_SESSION['a_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please log in.']);
    exit();
}

// Get the logged-in admin's ID
$adminId = $_SESSION['a_id'];

try {
    // Update the status of all unread notifications to "read"
    $updateQuery = "
        UPDATE notifications 
        SET status = 'read' 
        WHERE admin_id = ? AND status = 'unread'";
    $updateStmt = $conn->prepare($updateQuery);

    if (!$updateStmt) {
        throw new Exception("Failed to prepare the SQL statement: " . $conn->error);
    }

    $updateStmt->bind_param("i", $adminId);

    if (!$updateStmt->execute()) {
        throw new Exception("Failed to execute the SQL statement: " . $updateStmt->error);
    }

    // Return a success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Handle errors and return an error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    // Close the database connection
    $conn->close();
}
?>