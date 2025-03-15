<?php
// Include your database connection
include '../db/db_conn.php';

// Directly retrieve the admin ID from the session
session_start(); // Make sure the session is started
$adminId = $_SESSION['a_id']; // Assuming admin_id is stored in session

// Delete the profile picture by setting it to NULL
$query = "UPDATE admin_register SET pfp = NULL WHERE a_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $adminId); // Use 'i' for integer

if ($stmt->execute()) {
    // Redirect back to the profile page after deletion
    header('Location: ../admin/profile.php');
    exit(); // Ensure to stop script execution after redirect
} else {
    echo "Error deleting profile picture: " . $stmt->error;
}

$stmt->close();
?>
