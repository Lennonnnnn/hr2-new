<?php
session_start();
include '../db/db_conn.php';

// Check if the admin is logged in
if (!isset($_SESSION['a_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access. Please log in.']);
    exit();
}

// Get the logged-in admin's ID
$adminId = $_SESSION['a_id'];

try {
    // Handle reaction notifications if data is sent
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $employeeId = $data['employee_id'];
        $reaction = $data['reaction'];

        // Insert the reaction notification into the database
        $message = "New reaction: $reaction from employee ID $employeeId";
        $insertQuery = "
            INSERT INTO notifications (admin_id, message, status) 
            VALUES (?, ?, 'unread')";
        $insertStmt = $conn->prepare($insertQuery);

        if (!$insertStmt) {
            throw new Exception("Failed to prepare the SQL statement: " . $conn->error);
        }

        $insertStmt->bind_param("is", $adminId, $message);

        if (!$insertStmt->execute()) {
            throw new Exception("Failed to execute the SQL statement: " . $insertStmt->error);
        }

        $insertStmt->close();
    }

    // Fetch all notifications (both read and unread)
    $notificationQuery = "
        SELECT notification_id, message, created_at, status 
        FROM notifications 
        WHERE admin_id = ?
        ORDER BY created_at DESC";
    $notificationStmt = $conn->prepare($notificationQuery);

    if (!$notificationStmt) {
        throw new Exception("Failed to prepare the SQL statement: " . $conn->error);
    }

    $notificationStmt->bind_param("i", $adminId);

    if (!$notificationStmt->execute()) {
        throw new Exception("Failed to execute the SQL statement: " . $notificationStmt->error);
    }

    $notifications = $notificationStmt->get_result();

    // Fetch all notifications as an associative array
    $notificationData = [];
    while ($row = $notifications->fetch_assoc()) {
        $notificationData[] = $row;
    }

    // Close the statement
    $notificationStmt->close();

    // Set the response header to JSON
    header('Content-Type: application/json');

    // Return the notifications as JSON
    echo json_encode([
        'success' => true,
        'notifications' => $notificationData
    ]);
} catch (Exception $e) {
    // Handle errors and return an error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Close the database connection
    $conn->close();
}
?>
