<?php
session_start();
include '../../db/db_conn.php';

// Check if the employee is logged in
if (!isset($_SESSION['e_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please log in.']);
    exit();
}

// Get the notification ID from the request
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['notification_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request data.']);
    exit();
}

$notificationId = $data['notification_id'];

try {
    // Mark the notification as read
    $updateQuery = "UPDATE notifications SET status = 'read' WHERE notification_id = ?";
    $updateStmt = $conn->prepare($updateQuery);

    if (!$updateStmt) {
        throw new Exception("Failed to prepare the SQL statement: " . $conn->error);
    }

    $updateStmt->bind_param("i", $notificationId);

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