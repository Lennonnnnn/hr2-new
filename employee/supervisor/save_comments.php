<?php
session_start();
include '../../db/db_conn.php'; // Include your database connection

if (!isset($_SESSION['e_id']) && !isset($_SESSION['a_id'])) {
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
    $userId = isset($_SESSION['e_id']) ? $_SESSION['e_id'] : $_SESSION['a_id'];
    $sql = "SELECT firstname, lastname FROM employee_register WHERE e_id = ? UNION SELECT firstname, lastname FROM admin_register WHERE a_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userInfo = $result->fetch_assoc();
    $username = $userInfo['firstname'] . ' ' . $userInfo['lastname'];

    // Get the current timestamp
    $timestamp = date('Y-m-d H:i:s');

    // Save comment to the database
    $sql = "INSERT INTO employee_comments (employee_id, comment, username, created_at) VALUES (?, ?, ?, ?)";
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