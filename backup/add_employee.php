<?php
session_start();
include '../db/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $name = $_POST['name'];
    $recognition_type = $_POST['recognition_type'];

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO employee_recognition (name, recognition_type) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $recognition_type);

    // Execute the query
    if ($stmt->execute()) {
        // If successful, redirect back to the form with a success message
        header('Location: social_recognition.php?success=1');
        exit();
    } else {
        // On error, redirect with an error message
        header('Location: social_recognition.php?error=1');
        exit();
    }

    // Close the statement and connection
    $stmt->close();
}

$conn->close();
?>
