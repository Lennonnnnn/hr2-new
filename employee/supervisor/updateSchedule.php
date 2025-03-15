<?php
include '../../db/db_conn.php'; // Include database connection

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $employeeId = $_POST['employee_id'];
    $shiftType = $_POST['shift_type'];
    $scheduleDate = $_POST['schedule_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];

    // Validate input data (optional but recommended)
    if (empty($employeeId) || empty($shiftType) || empty($scheduleDate) || empty($startTime) || empty($endTime)) {
        echo "<script>alert('All fields are required.'); window.location.href = 'schedule.php';</script>";
        exit();
    }

    // Check if a schedule already exists for the employee on the given date
    $checkQuery = "SELECT * FROM employee_schedule WHERE employee_id = ? AND schedule_date = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('is', $employeeId, $scheduleDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing schedule
        $updateQuery = "UPDATE employee_schedule 
                        SET shift_type = ?, start_time = ?, end_time = ? 
                        WHERE employee_id = ? AND schedule_date = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('sssis', $shiftType, $startTime, $endTime, $employeeId, $scheduleDate);
    } else {
        // Insert new schedule
        $insertQuery = "INSERT INTO employee_schedule (employee_id, shift_type, schedule_date, start_time, end_time) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('issss', $employeeId, $shiftType, $scheduleDate, $startTime, $endTime);
    }

    // Execute the query
    if ($stmt->execute()) {
        echo "<script>alert('Schedule updated successfully!'); window.location.href = 'scheduling.php';</script>";
    } else {
        echo "<script>alert('Error updating schedule.'); window.location.href = 'scheduling.php';</script>";
    }

    // Close the statement
    $stmt->close();
} else {
    // If the request method is not POST, redirect to the schedule page
    echo "<script>alert('Invalid request method.'); window.location.href = 'scheduling.php';</script>";
}

// Close the database connection
$conn->close();
?>