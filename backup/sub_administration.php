<?php

include '../db/db_conn.php'; // Ensure this file properly initializes the connection

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$adminId = $_SESSION['a_id']; // Assuming the admin ID is stored in the session

// Corrected SQL query
$sql = "SELECT id, firstname, lastname, department, role, position, 
        (SELECT COUNT(*) FROM admin_evaluations 
         WHERE admin_evaluations.e_id = employee_register.e_id 
         AND admin_evaluations.admin_id = ?) AS alreadyEvaluated 
        FROM employee_register 
        WHERE role = 'employee' AND department = 'Administration Department'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId); // Bind adminId (who is evaluating)
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        print_r($row); // Debug output for each employee row
    }
}

$conn->close(); // Close the connection after you're done

?>
