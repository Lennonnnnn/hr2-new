<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers to ensure the response is JSON
header('Content-Type: application/json');

// Set the time zone to Philippine time
date_default_timezone_set('Asia/Manila');

// Include the database connection file
include '../../db/db_conn.php';

// Check if the database connection is successful
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Check if necessary parameters are provided
if (!isset($_GET['e_id'], $_GET['day'], $_GET['month'], $_GET['year'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Validate and sanitize input parameters
$employee_id = (int)$_GET['e_id'];
$day = (int)$_GET['day'];
$month = (int)$_GET['month'];
$year = (int)$_GET['year'];

if ($employee_id <= 0 || $day < 1 || $day > 31 || $month < 1 || $month > 12 || $year < 1900) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

try {
    // Fetch leave data for the employee on the given day
    $leaveSql = "SELECT start_date, end_date, leave_type FROM leave_requests 
                 WHERE e_id = ? AND status = 'Approved' 
                 AND ? BETWEEN DAY(start_date) AND DAY(end_date) 
                 AND MONTH(start_date) = ? AND YEAR(start_date) = ?";
    $leaveStmt = $conn->prepare($leaveSql);

    if (!$leaveStmt) {
        echo json_encode(['error' => 'Failed to prepare leave SQL statement']);
        exit;
    }

    $leaveStmt->bind_param("iiii", $employee_id, $day, $month, $year);

    if (!$leaveStmt->execute()) {
        echo json_encode(['error' => 'Failed to execute leave SQL statement']);
        exit;
    }

    $leaveResult = $leaveStmt->get_result();

    if ($leaveResult->num_rows > 0) {
        // Fetch the leave type
        $leaveRow = $leaveResult->fetch_assoc();
        $leaveType = $leaveRow['leave_type'];

        // Employee is on leave
        echo json_encode([
            'onLeave' => true,
            'status' => 'On Leave',
            'leaveType' => $leaveType // Include the actual leave type
        ]);
    } else {
        // Employee is not on leave
        echo json_encode([
            'onLeave' => false,
            'status' => 'Not on Leave'
        ]);
    }

    $leaveStmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>