<?php
date_default_timezone_set('Asia/Manila');
include 'db/db_conn.php';

// Get the current date
$currentDate = date('Y-m-d');

// Check the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to insert 'Absent' status for employees who don't have an attendance record for today
$query = "INSERT INTO attendance_log (id, e_id, attendance_date, status)
          SELECT e.e_id, ?, 'Absent'
          FROM employees e
          WHERE e.e_id NOT IN (
              SELECT a.e_id FROM attendance_log a WHERE a.attendance_date = ?
          )";

// Prepare and execute the statement
if ($stmt = $conn->prepare($query)) {
    // Bind the current date twice (once for the insert, once for the subquery)
    $stmt->bind_param("ss", $currentDate, $currentDate);
    
    // Execute the query
    if ($stmt->execute()) {
        echo "Absent records inserted successfully for employees with no attendance.";
    } else {
        echo "Error inserting absent records: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
} else {
    echo "Error preparing the statement: " . $conn->error;
}

// Close the connection
$conn->close();
?>
