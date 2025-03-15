<?php
session_start();
include '../db/db_conn.php'; // Include your database connection

if (!isset($_SESSION['a_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $employeeId = $data['employee_id'];

    // Validate input
    if (empty($employeeId)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit();
    }

    // Fetch reaction count from the database, ensuring one reaction per account
    $sql = "SELECT COUNT(DISTINCT admin_id) AS count FROM employee_reactions WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();


    if ($row) {
        echo json_encode(['status' => 'success', 'count' => $row['count']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch reaction count']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>