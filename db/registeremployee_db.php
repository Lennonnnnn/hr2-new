<?php
session_start();  // Ensure session is started at the beginning

include '../db/db_conn.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include the Composer autoloader (make sure PHPMailer is installed via Composer)
require '../phpmailer/vendor/autoload.php';

// Ensure admin_id is available
if (!isset($_SESSION['a_id'])) {
    echo "<script>alert('You must be logged in as an admin to perform this action.'); window.history.back();</script>";
    exit();
}

// Sanitize inputs
$firstname = htmlspecialchars($_POST['firstname']);
$lastname = htmlspecialchars($_POST['lastname']);
$email = htmlspecialchars($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$gender = $_POST['gender'];
$role = "Employee"; // Static role value
$department = $_POST['department'];
$position = $_POST['position'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Invalid email format.'); window.history.back();</script>";
    exit();
}

// Check password match
if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match. Please try again.'); window.history.back();</script>";
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if email exists
$email_check_query = "SELECT * FROM employee_register WHERE email = ?";
$stmt = $conn->prepare($email_check_query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<script>alert('Email already exists. Please use a different email.'); window.history.back();</script>";
    exit();
}

// Generate unique employee ID
function generateEmployeeId($conn) {
    do {
        $randomDigits = rand(1000, 9999);
        $employeeId = '8080' . $randomDigits; // Concatenating the prefix with the random digits
        $checkIdQuery = "SELECT * FROM employee_register WHERE e_id = ?";
        $stmt = $conn->prepare($checkIdQuery);
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
    } while ($result->num_rows > 0);

    return $employeeId;
}

$employeeId = generateEmployeeId($conn);

// Handle face descriptor
$faceDescriptor = $_POST['face_descriptor'] ?? null;
if (empty($faceDescriptor)) {
    echo "<script>alert('No face descriptor received. Please try again.'); window.history.back();</script>";
    exit();
}

// Decode the face descriptor string into an array
$faceDescriptorArray = json_decode($faceDescriptor);  // Move json_decode to variable first

// Handle multiple photo uploads (maximum 3 images)
$uploadedPhotos = [];
if (isset($_FILES['photo']) && count($_FILES['photo']['name']) > 0) {
    $maxFiles = 3; // Limit to 3 files
    $targetDirectory = $_SERVER['DOCUMENT_ROOT'] . '/HR2/face/';  // Absolute path
    
    // Ensure the target directory exists, if not create it
    if (!file_exists($targetDirectory)) {
        mkdir($targetDirectory, 0755, true);  // Create folder with permissions
        echo "Folder created successfully.<br>";
    }

    // Loop through all uploaded files
    for ($i = 0; $i < min(count($_FILES['photo']['name']), $maxFiles); $i++) {
        if ($_FILES['photo']['error'][$i] === UPLOAD_ERR_OK) {
            $photoTmpName = $_FILES['photo']['tmp_name'][$i];
            $photoName = $_FILES['photo']['name'][$i];
            $targetFilePath = $targetDirectory . basename($photoName);

            // Debugging: Check the target file path
            echo "Target file path: " . $targetFilePath . "<br>";

            // Validate the file type (image/jpeg, image/png, etc.)
            $allowedFileTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $fileType = mime_content_type($photoTmpName);

            // Check extension as well
            $fileExtension = pathinfo($photoName, PATHINFO_EXTENSION);
            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (in_array($fileType, $allowedFileTypes) && in_array(strtolower($fileExtension), $allowedExtensions)) {
                // Move the uploaded file to the uploads folder
                if (move_uploaded_file($photoTmpName, $targetFilePath)) {
                    $uploadedPhotos[] = $targetFilePath;
                    echo "File uploaded successfully: " . $targetFilePath . "<br>";
                } else {
                    echo "<script>alert('Error uploading the image. Please try again.'); window.history.back();</script>";
                    exit();
                }
            } else {
                echo "<script>alert('Invalid file type. Only JPEG or PNG images are allowed.'); window.history.back();</script>";
                exit();
            }
        } else {
            echo "<script>alert('Error in file upload. Error code: " . $_FILES['photo']['error'][$i] . "'); window.history.back();</script>";
            exit();
        }
    }
} else {
    $uploadedPhotos = []; // No files uploaded
}


// Insert employee data into the database, with the photos stored as a JSON array
$insertQuery = "INSERT INTO employee_register (e_id, firstname, lastname, email, password, role, gender, department, position, face_descriptor, face_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insertQuery);

// Prepare the face descriptor array (if any)
$faceDescriptorArray = json_decode($faceDescriptor ?? '[]');  // Fix here, ensure it is assigned before

// Bind the parameters and execute
$stmt->bind_param("issssssssss", $employeeId, $firstname, $lastname, $email, $hashed_password, $role, $gender, $department, $position, json_encode($faceDescriptorArray), json_encode($uploadedPhotos));

if ($stmt->execute()) {
    // Get admin ID from session (ensure this session value is set when admin logs in)
    $admin_id = $_SESSION['a_id'];  // Make sure session contains this value

    // Get admin's name
    $admin_query = "SELECT firstname, lastname FROM admin_register WHERE a_id = ?";
    $admin_stmt = $conn->prepare($admin_query);
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    if ($admin_result->num_rows > 0) {
        $admin = $admin_result->fetch_assoc();
        $admin_name = $admin['firstname'] . ' ' . $admin['lastname'];
    } else {
        echo "<script>alert('Admin details not found.'); window.history.back();</script>";
        exit();
    }

    // Capture admin's IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Log the action
    $action_type = "Created Employee Account";
    $affected_feature = "Employee Management";
    $details = "New employee registered with ID: ($employeeId) Name: {$firstname} {$lastname} in $department.";

    $log_query = "INSERT INTO activity_logs (admin_id, admin_name, action_type, affected_feature, details, ip_address) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("isssss", $admin_id, $admin_name, $action_type, $affected_feature, $details, $ip_address);

    if ($log_stmt->execute()) {
        // Send email after successful registration
        sendWelcomeEmail($email, $firstname, $lastname, $password);

        echo "<script>alert('Employee account created successfully!'); window.location.href='../admin/employee.php';</script>";
    } else {
        echo "<script>alert('Error logging activity. Please try again.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Error creating account. Please try again.'); window.history.back();</script>";
}

$stmt->close();
$conn->close();

// Function to send welcome email using PHPMailer
function sendWelcomeEmail($email, $firstname, $lastname, $password) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to Gmail
        $mail->SMTPAuth = true;
        $mail->Username = 'microfinancehr2@gmail.com'; // Your email address
        $mail->Password = 'yjla pidq jfdr qbnz'; // Your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('microfinancehr2@gmail.com', 'HR Department');
        $mail->addAddress($email); // Add the employee's email address

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to the Company';
        $mail->Body = "
        <html>
        <head>
            <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' rel='stylesheet'>
            <style>
                body {
                    font-family: 'Arial', sans-serif;
                    background-color: rgba(16, 17, 18, 1); /* .bg-black */
                    margin: 0;
                    padding: 0;
                    color: #fff; /* Default text color */
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .card {
                    border-radius: 10px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                    overflow: hidden;
                    border: none;
                    background-color: rgba(33, 37, 41, 1); /* .text-bg-dark */
                }
                .card-header {
                    background-color: rgba(16, 17, 18, 1); /* .bg-black */
                    color: #fff;
                    padding: 20px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }
                .card-header h3 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 600;
                }
                .card-body {
                    padding: 25px;
                }
                .card-title {
                    font-size: 20px;
                    font-weight: 600;
                    color: #fff;
                    margin-bottom: 15px;
                }
                .card-text {
                    font-size: 16px;
                    color: #ddd; /* Light gray for better readability */
                    line-height: 1.6;
                    margin-bottom: 20px;
                }
                .alert-info {
                    background-color: rgba(33, 37, 41, 1); /* .text-bg-dark */
                    color: #fff;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 20px;
                }
                .alert-info strong {
                    font-size: 18px;
                    font-weight: 600;
                    color: #fff;
                }
                .alert-info ul {
                    margin: 10px 0 0 20px;
                    padding: 0;
                }
                .alert-info ul li {
                    font-size: 16px;
                    color: #ddd; /* Light gray for better readability */
                    margin-bottom: 5px;
                }
                .btn-contact {
                    display: inline-block;
                    background-color:rgb(255, 255, 255);
                    color: white;
                    text-decoration: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    font-size: 16px;
                    font-weight: 500;
                    transition: background-color 0.3s;
                }
                .card-footer {
                    background-color: rgba(16, 17, 18, 1); /* .bg-black */
                    padding: 15px;
                    text-align: center;
                    border-radius: 0 0 10px 10px;
                }
                .card-footer p {
                    margin: 0;
                    font-size: 14px;
                    color: #ddd; /* Light gray for better readability */
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='card'>
                    <div class='card-header'>
                        <h3>Welcome to the Company!</h3>
                    </div>
                    <div class='card-body'>
                        <h4 class='card-title'>Dear $firstname $lastname,</h4>
                        <p class='card-text'>We are excited to have you join us! Your account has been successfully created, and we look forward to working with you.</p>
        
                        <div class='alert alert-info'>
                            <strong>Account Information:</strong>
                            <ul>
                                <li><b>Email:</b> $email</li>
                                <li><b>Password:</b> $password</li>
                            </ul>
                        </div>
        
                        <p class='card-text'>Please keep your account details secure. If you have any questions, don't hesitate to contact HR.</p>
                        <p class='card-text'>Best regards,<br>The HR Team</p>
                    </div>
                    <div class='card-footer'>
                        <p>If you have any questions, feel free to <a href='mailto:microfinancehr2@gmail.com' class='btn-contact'>Contact HR</a>.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

        // Send the email
        $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
