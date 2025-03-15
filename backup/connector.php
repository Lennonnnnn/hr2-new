<?php
// Database connection
$servername = "localhost";
$username = "root"; // Change if necessary
$password = "";     // Change if necessary
$dbname = "hr2";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_name = $_POST['employee_name'];
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    // Insert leave request into the database
    $sql = "INSERT INTO leave_db (employee_name, leave_type, start_date, end_date, reason)
            VALUES ('$employee_name', '$leave_type', '$start_date', '$end_date', '$reason')";

    if ($conn->query($sql) === TRUE) {
        echo "Leave request submitted successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>