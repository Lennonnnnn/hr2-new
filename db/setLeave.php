<?php
session_start();

// Include your database connection
include_once('../db/db_conn.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data and explicitly cast it to integers
    $employee_id = $_POST['employee_id'];
    $bereavement_leave = (int) ($_POST['bereavement_leave'] ?? 0);
    $emergency_leave = (int) ($_POST['emergency_leave'] ?? 0);
    $maternity_leave = (int) ($_POST['maternity_leave'] ?? 0);
    $mcw_special_leave = (int) ($_POST['mcw_special_leave'] ?? 0);
    $parental_leave = (int) ($_POST['parental_leave'] ?? 0);
    $service_incentive_leave = (int) ($_POST['service_incentive_leave'] ?? 0);
    $sick_leave = (int) ($_POST['sick_leave'] ?? 0);
    $vacation_leave = (int) ($_POST['vacation_leave'] ?? 0);
    $vawc_leave = (int) ($_POST['vawc_leave'] ?? 0);
    $bereavement_leave_male = (int) ($_POST['bereavement_leave_male'] ?? 0);
    $emergency_leave_male = (int) ($_POST['emergency_leave_male'] ?? 0);
    $parental_leave_male = (int) ($_POST['parental_leave_male'] ?? 0);
    $paternity_leave_male = (int) ($_POST['paternity_leave_male'] ?? 0);
    $service_incentive_leave_male = (int) ($_POST['service_incentive_leave_male'] ?? 0);
    $sick_leave_male = (int) ($_POST['sick_leave_male'] ?? 0);
    $vacation_leave_male = (int) ($_POST['vacation_leave_male'] ?? 0);

    // Fetch employee's personal info (firstname, lastname, gender)
    $employee_info_query = "SELECT firstname, lastname, gender FROM employee_register WHERE e_id = ?";
    if ($info_stmt = $conn->prepare($employee_info_query)) {
        $info_stmt->bind_param("i", $employee_id);
        $info_stmt->execute();
        $info_result = $info_stmt->get_result();

        if ($info_result->num_rows > 0) {
            $employee_info = $info_result->fetch_assoc();
            $employee_name = $employee_info['firstname'] . ' ' . $employee_info['lastname'];
            $gender = $employee_info['gender'];
        } else {
            echo "Error fetching employee info.";
            exit;
        }

        $info_stmt->close();
    } else {
        echo "Error preparing employee info query: " . $conn->error;
        exit;
    }

    // Check if the employee already exists in the employee_leaves table
    $check_query = "SELECT * FROM employee_leaves 
                    INNER JOIN employee_register ON employee_leaves.employee_id = employee_register.e_id
                    WHERE employee_leaves.employee_id = ?";
    if ($stmt = $conn->prepare($check_query)) {
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // If the employee exists, update the leave balances by adding the new values
        if ($result->num_rows > 0) {
            $update_query = "UPDATE employee_leaves 
                             SET bereavement_leave = bereavement_leave + ?, emergency_leave = emergency_leave + ?,  maternity_leave = maternity_leave + ?, 
                                 mcw_special_leave = mcw_special_leave + ?, parental_leave = parental_leave + ?, service_incentive_leave = service_incentive_leave + ?, 
                                 sick_leave = sick_leave + ?, vacation_leave = vacation_leave + ?, vawc_leave = vawc_leave + ?, bereavement_leave_male = bereavement_leave_male + ?, 
                                 emergency_leave_male = emergency_leave_male + ?, parental_leave_male = parental_leave_male + ?, paternity_leave_male = paternity_leave_male + ?, 
                                 service_incentive_leave_male = service_incentive_leave_male + ?, sick_leave_male = sick_leave_male + ?, vacation_leave_male = vacation_leave_male + ?, 
                                 employee_name = ?, gender = ?
                             WHERE employee_id = ?";
            if ($update_stmt = $conn->prepare($update_query)) {
               // Corrected bind_param call
                $update_stmt->bind_param("iiiiiiiiiiiiiiiissi",
                $bereavement_leave, $emergency_leave, $maternity_leave, $mcw_special_leave, 
                $parental_leave, $service_incentive_leave, $sick_leave, $vacation_leave, 
                $vawc_leave, $bereavement_leave_male, $emergency_leave_male, 
                $parental_leave_male, $paternity_leave_male, 
                $service_incentive_leave_male, $sick_leave_male, $vacation_leave_male, $employee_name,   // Add employee_name here
                $gender, $employee_id      // Add employee_id here
                );

                if ($update_stmt->execute()) {
                    echo "Leave allocation updated successfully!";
                } else {
                    echo "Error updating leave allocation: " . $update_stmt->error;
                }

                $update_stmt->close();
            } else {
                echo "Error preparing update query: " . $conn->error;
            }
        } else {
            // If employee doesn't exist, insert a new record
            $insert_query = "INSERT INTO employee_leaves 
                             (employee_id, 
                              employee_name, gender, bereavement_leave, emergency_leave, maternity_leave, 
                              mcw_special_leave, parental_leave, service_incentive_leave, sick_leave, 
                              vacation_leave, vawc_leave, bereavement_leave_male, emergency_leave_male, paternity_leave_male, 
                              parental_leave_male, service_incentive_leave_male, sick_leave_male, vacation_leave_male) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($insert_stmt = $conn->prepare($insert_query)) {
                $insert_stmt->bind_param("issiiiiiiiiiiiiiiii", 
                    $employee_id,
                    $employee_name,$gender,$bereavement_leave, $emergency_leave, $maternity_leave, 
                    $mcw_special_leave, $parental_leave, $service_incentive_leave, $sick_leave, 
                    $vacation_leave, $vawc_leave, $bereavement_leave_male, $emergency_leave_male, $parental_leave_male, 
                    $paternity_leave_male, $service_incentive_leave_male, $sick_leave_male, $vacation_leave_male,
                );
                
                if ($insert_stmt->execute()) {
                    echo "Leave allocation added successfully!";
                } else {
                    echo "Error inserting leave allocation: " . $insert_stmt->error;
                }

                $insert_stmt->close();
            } else {
                echo "Error preparing insert query: " . $conn->error;
            }
        }

        $stmt->close();
    } else {
        echo "Error preparing check query: " . $conn->error;
    }
} else {
    echo "Invalid request method.";
}

// Close the database connection
$conn->close();
?>
