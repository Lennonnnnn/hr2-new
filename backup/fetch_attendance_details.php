<?php
header('Content-Type: application/json');

include '../../db/db_conn.php';

// Check if necessary parameters are provided
if (!isset($_GET['e_id'], $_GET['day'], $_GET['month'], $_GET['year'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$employee_id = $_GET['e_id'];
$day = $_GET['day'];
$month = $_GET['month'];
$year = $_GET['year'];

// Prepare and execute the query to get attendance details for the given day
$sql = "SELECT time_in, time_out FROM attendance_logs WHERE e_id = ? AND DAY(attendance_date) = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iiii", $employee_id, $day, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

$attendanceDetails = [];
if ($row = $result->fetch_assoc()) {
    $attendanceDetails = [
        'time_in' => $row['time_in'] ? $row['time_in'] : 'No data',
        'time_out' => $row['time_out'] ? $row['time_out'] : 'No data'
    ];
}

if (empty($attendanceDetails)) {
    echo json_encode(['message' => 'No attendance details found']);
} else {
    echo json_encode($attendanceDetails);
}

$stmt->close();
$conn->close();
?>
