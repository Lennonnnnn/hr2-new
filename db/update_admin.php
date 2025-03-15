<?php
session_start();

include '../db/db_conn.php';

// Get form data with validation
$adminId = isset($_POST['a_id']) ? trim($_POST['a_id']) : '';
$firstName = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
$lastName = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
$Email = isset($_POST['email']) ? trim($_POST['email']) : '';
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';

// Basic validation
if (empty($adminId) || empty($firstName) || empty($lastName) || empty($Email) || empty($gender) || empty($role) || empty($phone_number) || empty($address)) {
    echo json_encode(['error' => 'All fields are required.']);
    exit();
}

// Prepare and execute SQL statement for updating
$sql = "UPDATE admin_register SET firstname=?, lastname=?, email=?, gender=?, role=?, phone_number=?, address=? WHERE a_id=?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['error' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("sssssssi", $firstName, $lastName, $Email, $gender, $role, $phone_number, $address, $adminId);

if ($stmt->execute()) {
    echo json_encode(['success' => 'Admin information updated successfully!']);
} else {
    echo json_encode(['error' => 'Error: ' . $stmt->error]);
}

// Close connection
$stmt->close();
$conn->close();
?>
