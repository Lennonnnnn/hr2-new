<?php
include '../db/db_conn.php';

function setPhilippineRegularHolidays($year, $conn) {
    $holidays = [
        "$year-01-01" => "New Year's Day",
        "$year-04-09" => "Araw ng Kagitingan",
        "$year-04-17" => "Maundy Thursday",
        "$year-04-18" => "Good Friday",
        "$year-05-01" => "Labor Day",
        "$year-06-12" => "Independence Day",
        "$year-08-28" => "National Heroes Day",
        "$year-11-30" => "Bonifacio Day",
        "$year-12-25" => "Christmas Day",
        "$year-12-30" => "Rizal Day"
    ];

    $type = "regular";
    foreach ($holidays as $date => $description) {
        $stmt = $conn->prepare("INSERT IGNORE INTO non_working_days (date, description, type) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $date, $description, $type);
        $stmt->execute();
    }
}

$currentYear = date("Y");

// Generate array of years from (current year - 5) to (current year + 5)
$years = range($currentYear - 5, $currentYear + 5);

// Set holidays for each of these years
foreach ($years as $year) {
    setPhilippineRegularHolidays($year, $conn);
}

// Check if it's a POST request to add a new non-working day
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $date = $data['date'];
    $description = $data['description'];
    $type = "irregular"; // Set manually added holidays as irregular

    $response = [];
    $stmt = $conn->prepare("INSERT INTO non_working_days (date, description, type) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $date, $description, $type);

    if ($stmt->execute()) {
        $response['status'] = 'success';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error adding non-working day: ' . $conn->error;
    }

    echo json_encode($response);
    exit;
}

// Fetch existing non-working days
$sql = "SELECT date, description, type FROM non_working_days WHERE type='irregular'";
$result = $conn->query($sql);

$nonWorkingDays = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nonWorkingDays[] = $row;
    }
}

echo json_encode($nonWorkingDays);
$conn->close();
?>
