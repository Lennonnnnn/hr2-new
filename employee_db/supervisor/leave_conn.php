<?php
session_start(); // Start the session at the beginning
include '../../db/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure user is logged in
    if (!isset($_SESSION['e_id'])) {
        $_SESSION['status_message'] = "User not logged in.";
        header("Location: ../../login.php");
        exit();
    }

    $employeeId = $_SESSION['e_id'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $leaveType = $_POST['leave_type'];

    // Validate required fields
    if (empty($startDate) || empty($endDate) || empty($leaveType)) {
        $_SESSION['status_message'] = "<strong>Error:</strong> Please fill in all required fields.";
        header("Location: ../../employee/supervisor/leave_file.php");
        exit();
    }

    // Ensure the end date is not earlier than the start date
    if (strtotime($endDate) < strtotime($startDate)) {
        $_SESSION['status_message'] = "<strong>Error:</strong> End date cannot be earlier than start date.";
        header("Location: ../../employee/supervisor/leave_file.php");
        exit();
    }

    // Ensure the start date is not in the past
    if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
        $_SESSION['status_message'] = "<strong>Error:</strong> Start date cannot be in the past.";
        header("Location: ../../employee/supervisor/leave_file.php");
        exit();
    }

    // Check available leaves for the employee
    $sql = "SELECT 
            employee_register.gender, 
            bereavement_leave, 
            emergency_leave, 
            maternity_leave, 
            mcw_special_leave, 
            parental_leave, 
            service_incentive_leave, 
            sick_leave, 
            vacation_leave, 
            vawc_leave,
            bereavement_leave_male,
            emergency_leave_male,
            parental_leave_male,
            paternity_leave_male,
            service_incentive_leave_male,
            sick_leave_male,
            vacation_leave_male 
            FROM employee_leaves 
            INNER JOIN employee_register ON employee_leaves.employee_id = employee_register.e_id 
            WHERE employee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $leavesInfo = $result->fetch_assoc();
        $employeeGender = $leavesInfo['gender'];
        $availableLeaveBalance = 0;

        if ($employeeGender === 'Male') {
            switch ($leaveType) {
                case 'Bereavement Leave':
                    $availableLeaveBalance = $leavesInfo['bereavement_leave_male'];
                    break;
                case 'Emergency Leave':
                    $availableLeaveBalance = $leavesInfo['emergency_leave_male'];
                    break;
                case 'Parental Leave':
                    $availableLeaveBalance = $leavesInfo['parental_leave_male'];
                    break;
                case 'Paternity Leave':
                    $availableLeaveBalance = $leavesInfo['paternity_leave_male'];
                    break;
                case 'Service Incentive Leave':
                    $availableLeaveBalance = $leavesInfo['service_incentive_leave_male'];
                    break;
                case 'Sick Leave':
                    $availableLeaveBalance = $leavesInfo['sick_leave_male'];
                    break;
                case 'Vacation Leave':
                    $availableLeaveBalance = $leavesInfo['vacation_leave_male'];
                    break;
                default:
                    error_log("Unknown leave type for male employee: " . $leaveType);
                    $availableLeaveBalance = 0;
                    break;
            }
        } else {
            switch ($leaveType) {
                case 'Bereavement Leave':
                    $availableLeaveBalance = $leavesInfo['bereavement_leave'];
                    break;
                case 'Emergency Leave':
                    $availableLeaveBalance = $leavesInfo['emergency_leave'];
                    break;
                case 'Maternity Leave':
                    $availableLeaveBalance = $leavesInfo['maternity_leave'];
                    break;
                case 'MCW Special Leave':
                    $availableLeaveBalance = $leavesInfo['mcw_special_leave'];
                    break;
                case 'Parental Leave':
                    $availableLeaveBalance = $leavesInfo['parental_leave'];
                    break;
                case 'Service Incentive Leave':
                    $availableLeaveBalance = $leavesInfo['service_incentive_leave'];
                    break;
                case 'Sick Leave':
                    $availableLeaveBalance = $leavesInfo['sick_leave'];
                    break;
                case 'Vacation Leave':
                    $availableLeaveBalance = $leavesInfo['vacation_leave'];
                    break;
                case 'VAWC Leave':
                    $availableLeaveBalance = $leavesInfo['vawc_leave'];
                    break;
            }
        }

        // Calculate the number of requested leave days
        try {
            $startDateObj = new DateTime($startDate);
            $endDateObj = new DateTime($endDate);
            $leaveDaysRequested = $endDateObj->diff($startDateObj)->days + 1;

            if ($leaveDaysRequested > $availableLeaveBalance) {
                $_SESSION['status_message'] = "<strong>Error:</strong> You don't have enough leave days for this type.";
                header("Location: ../../employee/supervisor/leave_file.php");
                exit();
            }
        } catch (Exception $e) {
            error_log("Date Error: " . $e->getMessage());
            $_SESSION['status_message'] = "<strong>Error:</strong> Invalid leave dates.";
            header("Location: ../../employee/supervisor/leave_file.php");
            exit();
        }
    } else {
        error_log("No leave information found for employee_id: " . $employeeId);
        $_SESSION['status_message'] = "<strong>Error:</strong> Employee leave information not found.";
        header("Location: ../../employee/supervisor/leave_file.php");
        exit();
    }

    // Handle multiple file uploads
    $proofFiles = [];
    $uploadDir = '../../proof/';

    if (isset($_FILES['proof']) && !empty($_FILES['proof']['name'][0])) {
        $files = $_FILES['proof'];

        foreach ($files['tmp_name'] as $key => $tmpName) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $fileTmpPath = $tmpName;
                $fileName = $files['name'][$key];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = uniqid('proof_', true) . '.' . $fileExtension;

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
                $maxFileSize = 5 * 1024 * 1024; // 5 MB

                if (!in_array($fileExtension, $allowedExtensions)) {
                    $_SESSION['status_message'] = "<strong>Error:</strong> Invalid file type. Only images, PDFs, and documents are allowed.";
                    header("Location: ../../employee/supervisor/leave_file.php");
                    exit();
                }

                if ($files['size'][$key] > $maxFileSize) {
                    $_SESSION['status_message'] = "<strong>Error:</strong> File size exceeds the maximum limit of 5 MB.";
                    header("Location: ../../employee/supervisor/leave_file.php");
                    exit();
                }

                $destFilePath = $uploadDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $destFilePath)) {
                    $proofFiles[] = $newFileName;
                } else {
                    $_SESSION['status_message'] = "<strong>Error:</strong> Could not move the uploaded file.";
                    header("Location: ../../employee/supervisor/leave_file.php");
                    exit();
                }
            } else {
                $_SESSION['status_message'] = "<strong>Error:</strong> File upload failed.";
                header("Location: ../../employee/supervisor/leave_file.php");
                exit();
            }
        }
    }

    // Insert the leave request into the database
    $sql = "INSERT INTO leave_requests (e_id, start_date, end_date, leave_type, proof, status) 
            VALUES (?, ?, ?, ?, ?, 'Supervisor Approved')";
    $stmt = $conn->prepare($sql);
    $proofFilePaths = implode(',', $proofFiles);
    $stmt->bind_param('issss', $employeeId, $startDate, $endDate, $leaveType, $proofFilePaths);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Fetch all admin IDs
            $adminQuery = "SELECT a_id FROM admin_register";
            $adminResult = $conn->query($adminQuery);
    
            if ($adminResult->num_rows > 0) {
                // Loop through each admin and insert a notification
                while ($adminRow = $adminResult->fetch_assoc()) {
                    $adminId = $adminRow['a_id']; // Get the admin ID
    
                    // Notify the admin
                    $message = "New leave request submitted by Employee ID: $employeeId.";
                    $notificationSql = "INSERT INTO notifications (admin_id, message) VALUES (?, ?)";
                    $notificationStmt = $conn->prepare($notificationSql);
                    $notificationStmt->bind_param("is", $adminId, $message);
    
                    if (!$notificationStmt->execute()) {
                        // Log the error if notification insertion fails
                        error_log("Failed to notify admin ID: $adminId. Error: " . $notificationStmt->error);
                    }
                }
    
                $_SESSION['status_message'] = "<strong>Success:</strong> Leave request submitted successfully.";
            } else {
                $_SESSION['status_message'] = "<strong>Error:</strong> No admins found.";
                header("Location: ../../employee/supervisor/leave_file.php");
                exit();
            }
        } else {
            $_SESSION['status_message'] = "<strong>Error:</strong> Leave request was not inserted.";
        }
    } else {
        $_SESSION['status_message'] = "<strong>Error:</strong> executing query: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: ../../employee/supervisor/leave_file.php");
    exit();
}
?>