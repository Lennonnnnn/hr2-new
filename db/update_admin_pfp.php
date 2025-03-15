<?php
session_start();
require_once '../db/db_conn.php';  // Replace with your actual DB connection

// Ensure the user is logged in before proceeding
if (!isset($_SESSION['a_id'])) {
    echo json_encode(['success' => false, 'message' => 'Admin is not logged in.']);
    exit;
}

// Handle the image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Define the directory where you want to save the uploaded files
    $targetDir = "../uploads/profile_pictures/";  // Create this directory on your server if it doesn't exist
    
    // Sanitize the filename and construct the full file path
    $targetFile = $targetDir . basename(preg_replace("/[^a-zA-Z0-9.]/", "_", $file["name"]));
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if image file is a valid image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        echo json_encode(['success' => false, 'message' => 'File is not an image.']);
        exit;
    }

    // Limit file size to 2MB
    if ($file["size"] > 2000000) {
        echo json_encode(['success' => false, 'message' => 'Sorry, your file is too large.']);
        exit;
    }

    // Allow only certain file formats
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        echo json_encode(['success' => false, 'message' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.']);
        exit;
    }

    // Try to upload the file
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        // Save the file path to the database
        $adminId = $_SESSION['a_id'];  // Get the admin ID from session
        $sql = "UPDATE admin_register SET pfp = ? WHERE a_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $targetFile, $adminId);
        
        if ($stmt->execute()) {
            // Log the action in the activity log
            // Get the admin's name (for the activity log)
            $adminQuery = "SELECT firstname, lastname, gender FROM admin_register WHERE a_id = ?";
            $adminStmt = $conn->prepare($adminQuery);
            $adminStmt->bind_param("i", $adminId);
            $adminStmt->execute();
            $adminResult = $adminStmt->get_result();
            $admin = $adminResult->fetch_assoc();
            $adminName = $admin['firstname'] . ' ' . $admin['lastname'];

            // Capture the IP address of the admin
            $ipAddress = $_SERVER['REMOTE_ADDR'];

            // Insert activity log
            $actionType = "Updated Profile Picture";
            $affectedFeature = "Admin Profile";
            $gender = strtolower(trim($admin['gender'])); // Normalize the gender value to lowercase
            $pronoun = ($gender === 'female') ? 'her' : 'his'; // Assign the correct pronoun based on gender            
            $details = "{$admin['firstname']} {$admin['lastname']} ($adminId) updated $pronoun profile picture.";
            
            $logQuery = "INSERT INTO activity_logs (admin_id, admin_name, action_type, affected_feature, details, ip_address) 
                         VALUES (?, ?, ?, ?, ?, ?)";
            $logStmt = $conn->prepare($logQuery);
            $logStmt->bind_param("isssss", $adminId, $adminName, $actionType, $affectedFeature, $details, $ipAddress);

            if ($logStmt->execute()) {
                // Redirect back to the profile page after successfully uploading and logging the action
                header('Location: ../admin/profile.php');
                exit(); // Ensure to stop script execution after redirect
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to log activity.']);
            }

            // Close log statement
            $logStmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating profile picture.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sorry, there was an error uploading your file.']);
    }
}
?>
