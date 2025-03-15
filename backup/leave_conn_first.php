<?php
session_start();
include '../db/db_conn.php';  // Ensure the database connection is correct

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the form is submitted via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Ensure the employee is logged in
    if (!isset($_SESSION['e_id'])) {
        $_SESSION['status_message'] = "User not logged in.";
        header("Location: ../employee/leave_request.php");
        exit();
    }

    // Get the employee ID from session
    $employeeId = $_SESSION['e_id'];

    // Collect form data
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $leaveType = $_POST['leave_type'];
    $reason = $_POST['reason'];

    // Validate the input (check for empty fields)
    if (empty($startDate) || empty($endDate) || empty($leaveType) || empty($reason)) {
        $_SESSION['status_message'] = "Please fill in all required fields.";
        header("Location: ../employee/leave_request.php");
        exit();
    }

    // Check if the end date is after the start date
    if (strtotime($endDate) < strtotime($startDate)) {
        $_SESSION['status_message'] = "End date cannot be earlier than start date.";
        header("Location: ../employee/leave_request.php");
        exit();
    }

    // Query to get the available leaves for the logged-in employee
    $sql = "SELECT available_leaves FROM employee_register WHERE e_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        $availableLeaves = $employee['available_leaves'];

        // Calculate the number of days requested for the leave
        $startDateObj = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);
        $leaveDaysRequested = $endDateObj->diff($startDateObj)->days + 1; // Include the start date in the count

        // Check if the requested leave days exceed available leaves
        if ($leaveDaysRequested > $availableLeaves) {
            $_SESSION['status_message'] = "You do not have enough leave days. Available leaves: $availableLeaves";
            header("Location: ../employee/leave_request.php");
            exit();
        }
    } else {
        $_SESSION['status_message'] = "Employee not found.";
        header("Location: ../employee/leave_request.php");
        exit();
    }

    // Insert the leave request into the database
    $sql = "INSERT INTO leave_requests (e_id, start_date, end_date, leave_type, status) 
            VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Bind parameters and execute
        $stmt->bind_param('isss', $employeeId, $startDate, $endDate, $leaveType);
        $stmt->execute();

        // Check if the insertion was successful
        if ($stmt->affected_rows > 0) {
            $_SESSION['status_message'] = "Leave request submitted successfully.";
        } else {
            $_SESSION['status_message'] = "Error: Failed to submit leave request.";
        }

        // Close the statement
        $stmt->close();
    } else {
        $_SESSION['status_message'] = "Error: Failed to prepare SQL query.";
    }

    // Close the database connection
    $conn->close();

    // Redirect back to the leave request form with the status message
    header("Location: ../employee/leave_request.php");
    exit();
} else {
    $_SESSION['status_message'] = "Invalid request method.";
    header("Location: ../employee/leave_request.php");
    exit();
}
?>
