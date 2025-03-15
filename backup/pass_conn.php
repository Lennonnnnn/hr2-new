<?php
// Define your database connection parameters
$host = 'localhost'; // Your database host
$dbName = 'hr2'; // Replace with your actual database name
$username = 'root'; // Replace with your actual database username
$password = ''; // Replace with your actual database password

// Create a new mysqli instance
$conn = new mysqli($host, $username, $password, $dbName);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Uncomment for debugging
// echo "Database connected successfully";
?>
