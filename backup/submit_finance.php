<?php
session_start();

// Include database connection
include '../db/db_conn.php';

if (!isset($_SESSION['a_id'])) {
    echo 'Admin ID not set.';
    exit();
}

// Get data from the POST request
$adminId = $_SESSION['a_id'];
$employeeId = $_POST['e_id'];
$categoryAverages = $_POST['categoryAverages'];
$department = $_POST['department']; // Get the department from POST data

// Check if the current admin has already evaluated this employee
$checkSql = "SELECT * FROM admin_evaluations WHERE a_id = ? AND e_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param('ii', $adminId, $employeeId);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    echo 'You have already evaluated this employee.';
} else {
    // Prepare the SQL to insert the evaluation into the database
    $sql = "INSERT INTO admin_evaluations (
                a_id, e_id, department, quality, communication_skills, teamwork, punctuality, initiative
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Bind the parameters to the statement (i for integer, s for string, d for decimal)
    $stmt->bind_param(
        'iisddddd',  // Changed to 'iisdddddd' - department is now string
        $adminId, 
        $employeeId, 
        $department,  // Bind the department as a string
        $categoryAverages['QualityOfWork'], 
        $categoryAverages['CommunicationSkills'], 
        $categoryAverages['Teamwork'], 
        $categoryAverages['Punctuality'], 
        $categoryAverages['Initiative']
    );

    // Execute the statement and check if successful
    if ($stmt->execute()) {
        echo 'Evaluation saved successfully.';
    } else {
        echo 'Error: ' . $conn->error;
    }
}

// Close the database connection
$conn->close();
?>
