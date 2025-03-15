<?php
include '../db/db_conn.php';

// Query the non_working_days table
$sql = "SELECT date, description FROM non_working_days";
$result = $conn->query($sql);

// Prepare data for JSON
$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = [
        'title' => $row['description'],
        'start' => $row['date'],
        'color' => 'red'  // or any color you prefer
    ];
}

// Return JSON response
echo json_encode($events);
$conn->close();
?>
