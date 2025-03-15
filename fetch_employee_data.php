<?php
include 'db/db_conn.php';

header('Content-Type: application/json');

// Prepare SQL query with a prepared statement
$sql = "SELECT e_id, face_descriptor FROM employee_register";
$stmt = $conn->prepare($sql);

// Execute the prepared statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

$employees = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get the face_descriptor data
        $face_descriptor = $row['face_descriptor'];

        // Log the size of the face descriptor data for debugging
        error_log("Face descriptor size: " . strlen($face_descriptor));

        if ($face_descriptor) {
            // Directly use the face_descriptor
            $employees[] = [
                'e_id' => $row['e_id'],
                'face_descriptor' => $face_descriptor // Send the face_descriptor field
            ];
        } else {
            // Log an error if no face_descriptor data is found
            error_log("No face descriptor found for employee: " . $row['e_id']);
        }
    }
} else {
    // Log an error if no employees are found or query failed
    error_log("No employees found or query failed.");
}

// Output the JSON data
echo json_encode($employees);

// Close the prepared statement and database connection
$stmt->close();
$conn->close();
?>
