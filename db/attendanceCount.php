<?php
header('Content-Type: application/json'); // Set response header to JSON

include '../db/db_conn.php';

// Get department from query parameter
$department = $_GET['department'] ?? '';

if (empty($department)) {
    echo json_encode(['error' => 'Department parameter is required']);
    exit;
}

// Query to fetch employee attendance for the specified department
$sql = "SELECT er.name, al.status 
        FROM employee_register er 
        LEFT JOIN attendance_log al ON er.id = al.employee_id 
        WHERE er.department = ? 
        AND al.date = CURDATE()"; // Fetch today's attendance

// Prepare and bind the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $department); // 's' specifies the type (string) for department

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Fetch data as associative array
$labels = [];
$attendance = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['name'];
    $attendance[] = $row['status'];
}

// Return JSON response
echo json_encode([
    'labels' => $labels,
    'attendance' => $attendance
]);

// Close the statement and connection
$stmt->close();
$conn->close();
?>
