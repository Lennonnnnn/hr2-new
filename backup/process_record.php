<?php
header('Content-Type: application/json');

include '../db/db_conn.php';

// Get POST data
$employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validate data
if (empty($employee_id) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    $conn->close();
    exit();
}

// Prepare and execute SQL query
$stmt = $conn->prepare("INSERT INTO timesheet_db (employee_id, action) VALUES (?, ?)");
$stmt->bind_param("ss", $employee_id, $action);

if ($stmt->execute()) {
    $record = [
        'employee_id' => $employee_id,
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    echo json_encode(['success' => true, 'record' => $record]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
}

// Close connection
$stmt->close();
$conn->close();
?>
