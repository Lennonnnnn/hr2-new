<?php
session_start();
include '../db/db_conn.php';

// Check if the admin is logged in
if (!isset($_SESSION['a_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please log in.']);
    exit();
}

// Get the notification ID from the request body
$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['notification_id'];

try {
    // Delete the notification
    $deleteQuery = "DELETE FROM notifications WHERE notification_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);

    if (!$deleteStmt) {
        throw new Exception("Failed to prepare the SQL statement: " . $conn->error);
    }

    $deleteStmt->bind_param("i", $notificationId);

    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to execute the SQL statement: " . $deleteStmt->error);
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