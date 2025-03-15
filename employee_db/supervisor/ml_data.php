<?php
include '../../db/db_conn.php';

// Fetch aggregated data for all months
$sql = "
    SELECT 
        a.e_id, 
        IFNULL(l.total_leave_days, 0) AS total_leave_days, 
        IFNULL(at.attendance_rate, 0) AS attendance_rate, 
        IFNULL(e.performance_score, 0) AS performance_score
    FROM 
        (SELECT DISTINCT e_id FROM attendance_log) a
    LEFT JOIN 
        (SELECT e_id, SUM(DATEDIFF(end_date, start_date) + 1) AS total_leave_days 
         FROM leave_requests 
         WHERE status = 'approved' 
         GROUP BY e_id) l 
        ON a.e_id = l.e_id
    LEFT JOIN 
        (SELECT e_id, COUNT(CASE WHEN status = 'Present' THEN 1 END) * 1.0 / COUNT(*) AS attendance_rate 
         FROM attendance_log 
         GROUP BY e_id) at 
        ON a.e_id = at.e_id
    LEFT JOIN 
        (SELECT e_id, (quality + communication_skills + punctuality + initiative + teamwork) / 5.0 AS performance_score 
         FROM admin_evaluations 
         WHERE (e_id, evaluated_at) IN 
            (SELECT e_id, MAX(evaluated_at) FROM admin_evaluations 
             GROUP BY e_id)
        ) e 
        ON a.e_id = e.e_id;
";

// Execute the query
$result = $conn->query($sql);

if (!$result) {
    error_log("Database query failed: " . $conn->error);
    echo json_encode(['error' => 'Database query failed', 'message' => $conn->error]);
    $conn->close();
    exit();
}

// Fetch and process the result
$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'e_id' => $row['e_id'],
            'total_leave_days' => (int)$row['total_leave_days'],  // Ensure numbers are properly cast
            'performance_score' => (float)$row['performance_score'],
            'attendance_rate' => (float)$row['attendance_rate']
        ];
    }
} else {
    error_log("No data found in the database.");
    echo json_encode(['error' => 'No data found', 'message' => 'No data available for employees']);
    $conn->close();
    exit();
}

// Debugging: Output the data to check its structure (can be removed in production)
error_log(print_r($data, true));

// Set the response header to JSON
header('Content-Type: application/json');
echo json_encode($data);

// Close the connection
$conn->close();
?>
