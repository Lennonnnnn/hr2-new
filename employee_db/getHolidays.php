<?php
error_reporting(E_ALL); // Report all PHP errors
ini_set('display_errors', 1); // Display errors to the browser
ini_set('display_startup_errors', 1); // Display startup errors

session_start();
include '../db/db_conn.php';

// Fetch non-working days (holidays)
$query = "SELECT date FROM non_working_days";
$holidayResult = $conn->query($query);

$holidays = [];
if ($holidayResult->num_rows > 0) {
    while ($row = $holidayResult->fetch_assoc()) {
        $holidays[] = $row['date']; // Assuming 'date' is the field storing the holiday dates
    }
}

// Close the database connection
$conn->close();

// Return holidays as a plain array
header('Content-Type: application/json');
echo json_encode($holidays); // Return a plain array, not an object
?>