<?php
include '../../db/db_conn.php'; // Include database connection

// Check if the employee_id is provided in the request
if (isset($_GET['employee_id'])) {
    $employeeId = $_GET['employee_id'];

    // Fetch the latest schedule for the employee
    $query = "SELECT * FROM employee_schedule WHERE employee_id = ? ORDER BY schedule_date DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the schedule details
        $row = $result->fetch_assoc();
        echo json_encode($row); // Return schedule details as JSON
    } else {
        // No schedule found for the employee
        echo json_encode([]); // Return an empty object
    }

    $stmt->close();
} else {
    // If employee_id is not provided, return an error
    echo json_encode(['error' => 'Invalid request: employee_id is missing']);
}

// Close the database connection
$conn->close();
?>