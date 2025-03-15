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
    $reaction = $data['reaction'];
    $accountId = isset($_SESSION['e_id']) ? $_SESSION['e_id'] : $_SESSION['a_id'];

    // Validate input
    if (empty($employeeId) || empty($reaction)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit();
    }

    // Check if a reaction already exists for this account
    $checkSql = "SELECT * FROM employee_reactions WHERE employee_id = ? AND account_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $employeeId, $accountId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Update existing reaction
        $updateSql = "UPDATE employee_reactions SET reaction = ? WHERE employee_id = ? AND account_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sii", $reaction, $employeeId, $accountId);
        $updateSuccess = $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insert new reaction
        $insertSql = "INSERT INTO employee_reactions (employee_id, account_id, reaction) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iis", $employeeId, $accountId, $reaction);
        $insertSuccess = $insertStmt->execute();
        $insertStmt->close();
    }

    if (isset($updateSuccess) ? $updateSuccess : $insertSuccess) {
        echo json_encode(['status' => 'success', 'message' => 'Reaction saved successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save reaction']);
    }

    $checkStmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>