<?php
header('Content-Type: application/json'); // Set response header to JSON

// Include the database connection file
include '../db/db_conn.php';  // Ensure the correct path to the db_connection.php file

try {
    // Query to fetch employee count per department
    $sql = "SELECT department, COUNT(id) as count 
            FROM employee_register 
            GROUP BY department";
    $result = $conn->query($sql);

    if ($result === false) {
        // Handle query execution errors
        echo json_encode(['error' => 'Query execution failed: ' . $conn->error]);
        exit;
    }

    // Fetch data as associative array
    $formattedData = [];
    while ($row = $result->fetch_assoc()) {
        $formattedData[] = [
            'name' => $row['department'],
            'count' => (int)$row['count']
        ];
    }

    // Return JSON response
    echo json_encode($formattedData);
} catch (Exception $e) {
    // Handle unexpected errors
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
?>
