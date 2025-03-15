<?php
include 'db/db_conn.php'; // Ensure correct file path

if (isset($_GET['employeeId'])) {
    $employeeId = $_GET['employeeId'];
    $attendanceDate = date('Y-m-d'); // Current date

    // Check if the employee has already logged "Time In" today
    $query = "SELECT * FROM attendance_logs WHERE e_id = ? AND attendance_date = ? AND status = 'Time In'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $employeeId, $attendanceDate);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a Time In record exists
    if ($result->num_rows > 0) {
        // Employee has already checked in
        echo json_encode(['checkedIn' => true]);
    } else {
        // Employee has not checked in yet
        echo json_encode(['checkedIn' => false]);
    }

    $stmt->close();
    $conn->close();
}
?>
