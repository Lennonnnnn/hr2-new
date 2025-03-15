<?php
header('Content-Type: application/json');

date_default_timezone_set('Asia/Manila'); // Set the time zone to Philippine time

include '../../db/db_conn.php';

// Check if necessary parameters are provided
if (!isset($_GET['month'], $_GET['year'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$month = $_GET['month'];
$year = $_GET['year'];

// Fetch non-working days (holidays) for the given month and year
$sql = "SELECT DAY(non_working_date) AS day FROM non_working_days 
        WHERE MONTH(non_working_date) = ? AND YEAR(non_working_date) = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$result = $stmt->get_result();

$nonWorkingDays = [];
while ($row = $result->fetch_assoc()) {
    $nonWorkingDays[] = (int)$row['day']; // Store the day as an integer
}

echo json_encode($nonWorkingDays);

$stmt->close();
$conn->close();
?>