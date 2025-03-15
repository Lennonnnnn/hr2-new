<?php

include '../db/db_conn.php';

// Get the posted data
$employeeId = $_POST['employeeId'];
$action = $_POST['action'];

// Prepare and bind
$stmt = $conn->prepare("INSERT INTO attendance_records (employee_id, action) VALUES (?, ?)");
$stmt->bind_param("ss", $employeeId, $action);

// Execute the statement
if ($stmt->execute()) {
    echo "Record inserted successfully";
} else {
    echo "Error: " . $stmt->error;
}

// Close the connection
$stmt->close();
$conn->close();
?>
