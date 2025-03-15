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
    $reaction = $data['reaction'];
    $adminId = $_SESSION['a_id'];

    // Validate input
    if (empty($employeeId) || empty($reaction)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit();
    }

    // Check if a reaction already exists for the given employee by the current user
    $checkSql = "SELECT id FROM employee_reactions WHERE employee_id = ? AND admin_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $employeeId, $adminId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Reaction exists, update it
        $updateSql = "UPDATE employee_reactions SET reaction = ? WHERE employee_id = ? AND admin_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sii", $reaction, $employeeId, $adminId);

        if ($updateStmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Reaction updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update reaction']);
        }

        $updateStmt->close();
    } else {
        // Reaction does not exist, insert a new one
        $insertSql = "INSERT INTO employee_reactions (employee_id, admin_id, reaction) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iis", $employeeId, $adminId, $reaction);

        if ($insertStmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Reaction saved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save reaction']);
        }

        $insertStmt->close();
    }

    $checkStmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>