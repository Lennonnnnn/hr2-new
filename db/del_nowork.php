<?php
include '../db/db_conn.php';

$data = json_decode(file_get_contents("php://input"), true);
$date = $data['date'];

$response = [];
$sql = "DELETE FROM non_working_days WHERE date = '$date'";
if ($conn->query($sql) === TRUE) {
    $response['status'] = 'success';
} else {
    $response['status'] = 'error';
    $response['message'] = 'Error deleting non-working day: ' . $conn->error;
}

echo json_encode($response);
$conn->close();
?>
