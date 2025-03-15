<?php
session_start();
include '../db/db_conn.php'; // Include your database connection

if (!isset($_SESSION['a_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $employeeId = $data['employee_id'];
    $comment = $data['comment'] ?? '';

    // Validate input
    if (empty($employeeId) || empty($comment)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit();
    }

    // Fetch the username of the commenter
    $adminId = $_SESSION['a_id'];
    $sql = "SELECT firstname, lastname FROM admin_register WHERE a_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $adminInfo = $result->fetch_assoc();
    $username = $adminInfo['firstname'] . ' ' . $adminInfo['lastname'];

    // Get the current timestamp
    $timestamp = date('Y-m-d H:i:s');

    // Save comment to the database
    $sql = "INSERT INTO employee_comments (employee_id, comment, username, timestamp) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $employeeId, $comment, $username, $timestamp);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Comment saved successfully', 'username' => $username, 'timestamp' => $timestamp]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save comment']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>