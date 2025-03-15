<?php
include '../../db/db_conn.php';

// Get JSON data from the POST request
$data = json_decode(file_get_contents("php://input"), true);

// Extract face descriptor and user ID from the request
$faceDescriptor = $data['faceDescriptor'];
$userId = $data['userId'];

// Convert face descriptor to a format that can be saved in the database (e.g., JSON)
$faceDescriptorJson = json_encode($faceDescriptor);

// Save the face descriptor in the database (replace with your own query and table)
$query = "INSERT INTO face_descriptors (user_id, descriptor) VALUES (?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $userId, $faceDescriptorJson);
$stmt->execute();

// Return a response indicating success
echo json_encode(['message' => 'Face descriptor saved successfully.']);
?>
