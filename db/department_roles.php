<?php
include '../db/db_conn.php';

// Get the department from the GET request, if provided
$department = isset($_GET['department']) ? $_GET['department'] : null;

// Initialize the response array
$response = [];

// Query to get all distinct departments
$departmentQuery = "SELECT DISTINCT department FROM employee_register";
$departmentResult = $conn->query($departmentQuery);

$department = [];
while ($row = $departmentResult->fetch_assoc()) {
    $department[] = $row;
}

// Add the departments to the response
$response['department'] = $department;

// If a department is specified, get the corresponding roles
if ($department) {
    $roleQuery = "SELECT DISTINCT position FROM employee_register WHERE department = '$department'";
    $roleResult = $conn->query($roleQuery);

    $roles = [];
    while ($row = $roleResult->fetch_assoc()) {
        $roles[] = $row;
    }

    // Add the roles to the response
    $response['roles'] = $roles;
}

// Return the response as JSON
echo json_encode($response);
?>
