<?php
session_start();
include '../../db/db_conn.php';

if (!isset($_SESSION['e_id']) || !isset($_SESSION['position']) || $_SESSION['position'] !== 'Supervisor') {
    header("Location: ../../login.php");
    exit();
}

// Fetch employee's leave data
$employee_id = $_SESSION['e_id']; // Assuming the employee ID is stored in session

// Query to fetch employee's info from the employee_register table
$query_employee = "SELECT * FROM employee_register WHERE e_id = ?";
$stmt_employee = $conn->prepare($query_employee);
$stmt_employee->bind_param("i", $employee_id);
$stmt_employee->execute();
$result_employee = $stmt_employee->get_result();

$employeeInfo = $result_employee->fetch_assoc();

// Fetch the used leave by summing up approved leave days
$usedLeaveQuery = "SELECT start_date, end_date, SUM(DATEDIFF(end_date, start_date) + 1) AS used_leaves 
                   FROM leave_requests 
                   WHERE e_id = ? AND status = 'approved'
                   GROUP BY e_id";
$usedLeaveStmt = $conn->prepare($usedLeaveQuery);
$usedLeaveStmt->bind_param("i", $employee_id); // Corrected variable name
$usedLeaveStmt->execute();
$usedLeaveResult = $usedLeaveStmt->get_result();
$usedLeaveRow = $usedLeaveResult->fetch_assoc();
$usedLeave = $usedLeaveRow['used_leaves'] ?? 0; // Default to 0 if no leave has been used

// Fetch the employee's gender from the employee_register table
$gender = $employeeInfo['gender']; // Assuming gender is stored in employee_register

// Fetch employee's leave data
$query_leave = "SELECT * FROM employee_leaves WHERE employee_id = ?";
$stmt_leave = $conn->prepare($query_leave);
$stmt_leave->bind_param("i", $employee_id);
$stmt_leave->execute();
$result_leave = $stmt_leave->get_result();

$leavesInfo = $result_leave->fetch_assoc();

// Calculate total available leaves based on gender
$totalAvailableLeaves = 0;
if ($gender == 'Male') {
    // For male employees
    $totalAvailableLeaves = 
        ($leavesInfo['bereavement_leave_male'] ?? 0) +
        ($leavesInfo['emergency_leave_male'] ?? 0) +
        ($leavesInfo['parental_leave_male'] ?? 0) +
        ($leavesInfo['paternity_leave_male'] ?? 0) +
        ($leavesInfo['service_incentive_leave_male'] ?? 0) +
        ($leavesInfo['sick_leave_male'] ?? 0) +
        ($leavesInfo['vacation_leave_male'] ?? 0);
} else {
    // For female employees
    $totalAvailableLeaves = 
        ($leavesInfo['bereavement_leave'] ?? 0) +
        ($leavesInfo['emergency_leave'] ?? 0) +
        ($leavesInfo['maternity_leave'] ?? 0) +
        ($leavesInfo['mcw_special_leave'] ?? 0) +
        ($leavesInfo['parental_leave'] ?? 0) +
        ($leavesInfo['service_incentive_leave'] ?? 0) +
        ($leavesInfo['sick_leave'] ?? 0) +
        ($leavesInfo['vacation_leave'] ?? 0) +
        ($leavesInfo['vawc_leave'] ?? 0);
}

// Calculate remaining total leaves by subtracting used leaves
$remainingLeaves = $totalAvailableLeaves - $usedLeave;

// Fetch the leave requests that are approved for the employee
$query_approved_leave = "SELECT * FROM leave_requests WHERE e_id = ? AND status = 'approved' ORDER BY start_date DESC LIMIT 1"; // Only get the most recent approved leave
$stmt_approved_leave = $conn->prepare($query_approved_leave);
$stmt_approved_leave->bind_param("i", $employee_id);
$stmt_approved_leave->execute();
$result_approved_leave = $stmt_approved_leave->get_result();

if ($result_approved_leave->num_rows > 0) {
    $approved_leave_data = $result_approved_leave->fetch_assoc(); // Fetch the most recent approved leave

    // Get the start and end date of the leave
    $leave_start_date = new DateTime($approved_leave_data['start_date']);
    $leave_end_date = new DateTime($approved_leave_data['end_date']);
    $current_date = new DateTime(); // Current date and time

    // Calculate the total duration of the leave (in days)
    $leave_duration = $leave_start_date->diff($leave_end_date)->days + 1;

    // Calculate how many days have passed since the start date
    $days_passed = $leave_start_date->diff($current_date)->days;

    if ($current_date < $leave_start_date) {
        // If current date is before the leave starts, no progress has been made
        $days_passed = 0;
    } elseif ($current_date > $leave_end_date) {
        // If current date is after the leave ends, progress is complete
        $days_passed = $leave_duration;
    }

    // Calculate the percentage of progress
    $progress_percentage = ($days_passed / $leave_duration) * 100;
} else {
    // If no approved leave, set leave data to null
    $approved_leave_data = null;
}

// Close the database connections
$stmt_employee->close();
$stmt_leave->close();
$stmt_approved_leave->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../css/styles.css"> <!-- If you have custom styles -->
    <style>
        .progress-bar {
            width: 100%;
            background-color: #e0e0e0;
        }
        .progress-bar div {
            height: 20px;
            background-color: #4caf50;
            width: 0%;
        }
        .table-dark {
            --bs-table-bg: #2d3238;
            --bs-table-border-color: #495057;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
    </style>
</head>
<body class="bg-black text-light">
    <div class="container mt-5 bg-dark text-light p-4 rounded">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-light mb-0">Leave Details</h2>
            <div>
                <a href="../../employee/supervisor/leave_file.php" class="btn btn-primary">Back</a>
                <?php if ($approved_leave_data): ?>
                    <button type="button" class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#leaveScheduleModal">
                        View Ongoing Leave Schedule
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card bg-dark text-light border border-secondary mb-4">
            <div class="card-header border-bottom border-secondary">
                <h5 class="card-title mb-0">Employee Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo $employeeInfo['firstname'] . ' ' . $employeeInfo['lastname']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>ID No.:</strong> <?php echo $employeeInfo['e_id']; ?></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Position:</strong> <?php echo $employeeInfo['position']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Department:</strong> <?php echo $employeeInfo['department']; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card bg-dark text-light border border-secondary">
            <div class="card-header border-bottom border-secondary">
                <h5 class="card-title mb-0">Leave Balance</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-dark">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th class="text-center" style="width: 100px;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($gender == 'Female') { ?>
                            <tr>
                                <td>Bereavement Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['bereavement_leave']) && $leavesInfo['bereavement_leave'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['bereavement_leave']) ? $leavesInfo['bereavement_leave'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Emergency Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['emergency_leave']) && $leavesInfo['emergency_leave'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['emergency_leave']) ? $leavesInfo['emergency_leave'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Maternity Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['maternity_leave']) && $leavesInfo['maternity_leave'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['maternity_leave']) ? $leavesInfo['maternity_leave'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>MCW Special Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['mcw_special_leave']) && $leavesInfo['mcw_special_leave'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['mcw_special_leave']) ? $leavesInfo['mcw_special_leave'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Parental Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['parental_leave']) && $leavesInfo['parental_leave'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['parental_leave']) ? $leavesInfo['parental_leave'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Service Incentive Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['service_incentive_leave']) && $leavesInfo['service_incentive_leave'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['service_incentive_leave']) ? $leavesInfo['service_incentive_leave'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Sick Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['sick_leave']) && $leavesInfo['sick_leave'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['sick_leave']) ? $leavesInfo['sick_leave'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Vacation Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['vacation_leave']) && $leavesInfo['vacation_leave'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['vacation_leave']) ? $leavesInfo['vacation_leave'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>VAWC Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['vawc_leave']) && $leavesInfo['vawc_leave'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['vawc_leave']) ? $leavesInfo['vawc_leave'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } elseif ($gender == 'Male') { ?>
                            <tr>
                                <td>Bereavement Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['bereavement_leave_male']) && $leavesInfo['bereavement_leave_male'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['bereavement_leave_male']) ? $leavesInfo['bereavement_leave_male'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Emergency Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['emergency_leave_male']) && $leavesInfo['emergency_leave_male'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['emergency_leave_male']) ? $leavesInfo['emergency_leave_male'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Parental Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['parental_leave_male']) && $leavesInfo['parental_leave_male'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['parental_leave_male']) ? $leavesInfo['parental_leave_male'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Paternity Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['paternity_leave_male']) && $leavesInfo['paternity_leave_male'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['paternity_leave_male']) ? $leavesInfo['paternity_leave_male'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Service Incentive Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['service_incentive_leave_male']) && $leavesInfo['service_incentive_leave_male'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['service_incentive_leave_male']) ? $leavesInfo['service_incentive_leave_male'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Sick Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['sick_leave_male']) && $leavesInfo['sick_leave_male'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['sick_leave_male']) ? $leavesInfo['sick_leave_male'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Vacation Leave</td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-<?php echo isset($leavesInfo['vacation_leave_male']) && $leavesInfo['vacation_leave_male'] > 0 ? 'primary' : 'secondary'; ?>" style="font-size: 14px; padding: 6px 12px; width: 40px;">
                                        <?php echo isset($leavesInfo['vacation_leave_male']) ? $leavesInfo['vacation_leave_male'] : '0'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <td><strong>Total Available Leave</strong></td>
                            <td class="text-center align-middle">
                                <span class="badge bg-success" style="font-size: 14px; padding: 6px 12px;">
                                    <?php echo $remainingLeaves; ?>
                                </span>
                            </td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Used Leave</strong></td>
                            <td class="text-center align-middle">
                                <span class="badge bg-danger" style="font-size: 14px; padding: 6px 12px;">
                                    <?php echo $usedLeave; ?>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                
                <!-- Leave Balance Summary Section -->
                <div class="mt-4 p-3 bg-dark border border-secondary rounded">
                    <h5 class="mb-3 text-center">Leave Balance Summary</h5>
                    <div class="row">
                        <?php if ($gender == 'Female') { ?>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-dark border border-secondary h-100">
                                    <div class="card-body text-center">
                                        <h6>Standard Leaves</h6>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Sick Leave:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['sick_leave']) && $leavesInfo['sick_leave'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['sick_leave']) ? $leavesInfo['sick_leave'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Vacation Leave:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['vacation_leave']) && $leavesInfo['vacation_leave'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['vacation_leave']) ? $leavesInfo['vacation_leave'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Emergency Leave:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['emergency_leave']) && $leavesInfo['emergency_leave'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['emergency_leave']) ? $leavesInfo['emergency_leave'] : '0'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-dark border border-secondary h-100">
                                    <div class="card-body text-center">
                                        <h6>Special Leaves</h6>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Maternity:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['maternity_leave']) && $leavesInfo['maternity_leave'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['maternity_leave']) ? $leavesInfo['maternity_leave'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>MCW Special:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['mcw_special_leave']) && $leavesInfo['mcw_special_leave'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['mcw_special_leave']) ? $leavesInfo['mcw_special_leave'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>VAWC Leave:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['vawc_leave']) && $leavesInfo['vawc_leave'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['vawc_leave']) ? $leavesInfo['vawc_leave'] : '0'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-dark border border-secondary h-100">
                                    <div class="card-body text-center">
                                        <h6>Other Leaves</h6>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Bereavement:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['bereavement_leave']) && $leavesInfo['bereavement_leave'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['bereavement_leave']) ? $leavesInfo['bereavement_leave'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Parental:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['parental_leave']) && $leavesInfo['parental_leave'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['parental_leave']) ? $leavesInfo['parental_leave'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Service Incentive:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['service_incentive_leave']) && $leavesInfo['service_incentive_leave'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['service_incentive_leave']) ? $leavesInfo['service_incentive_leave'] : '0'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } elseif ($gender == 'Male') { ?>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-dark border border-secondary h-100">
                                    <div class="card-body text-center">
                                        <h6>Standard Leaves</h6>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Sick Leave:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['sick_leave_male']) && $leavesInfo['sick_leave_male'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['sick_leave_male']) ? $leavesInfo['sick_leave_male'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Vacation Leave:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['vacation_leave_male']) && $leavesInfo['vacation_leave_male'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['vacation_leave_male']) ? $leavesInfo['vacation_leave_male'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Emergency Leave:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['emergency_leave_male']) && $leavesInfo['emergency_leave_male'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['emergency_leave_male']) ? $leavesInfo['emergency_leave_male'] : '0'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-dark border border-secondary h-100">
                                    <div class="card-body text-center">
                                        <h6>Special Leaves</h6>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Paternity:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['paternity_leave_male']) && $leavesInfo['paternity_leave_male'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['paternity_leave_male']) ? $leavesInfo['paternity_leave_male'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Parental:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['parental_leave_male']) && $leavesInfo['parental_leave_male'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['parental_leave_male']) ? $leavesInfo['parental_leave_male'] : '0'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-dark border border-secondary h-100">
                                    <div class="card-body text-center">
                                        <h6>Other Leaves</h6>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Bereavement:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['bereavement_leave_male']) && $leavesInfo['bereavement_leave_male'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['bereavement_leave_male']) ? $leavesInfo['bereavement_leave_male'] : '0'; ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Service Incentive:</span>
                                            <span class="badge bg-<?php echo isset($leavesInfo['service_incentive_leave_male']) && $leavesInfo['service_incentive_leave_male'] > 0 ? 'primary' : 'secondary'; ?>">
                                                <?php echo isset($leavesInfo['service_incentive_leave_male']) ? $leavesInfo['service_incentive_leave_male'] : '0'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card bg-dark border border-secondary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">Total Available Leave</h6>
                                            <small class="text-muted">Combined from all leave types</small>
                                        </div>
                                        <span class="badge bg-success" style="font-size: 18px; padding: 8px 15px;">
                                            <?php echo $remainingLeaves; ?> days
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (isset($approved_leave_data)): ?>
        <div class="card bg-dark text-light border border-secondary mt-4">
            <div class="card-header border-bottom border-secondary">
                <h5 class="card-title mb-0">Current Leave Status</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Start Date:</strong> <?php echo $approved_leave_data['start_date']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>End Date:</strong> <?php echo $approved_leave_data['end_date']; ?></p>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Leave Progress</span>
                        <span><?php echo $days_passed; ?> of <?php echo $leave_duration; ?> days (<?php echo round($progress_percentage); ?>%)</span>
                    </div>
                    <div class="progress bg-secondary">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percentage; ?>%" 
                            aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    !-- Ongoing Leave Schedule Modal -->
            <div class="modal fade" id="leaveScheduleModal" tabindex="-1" aria-labelledby="leaveScheduleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content bg-dark text-light">
                        <div class="modal-header border-bottom border-secondary">
                            <h5 class="modal-title" id="leaveScheduleModalLabel">Ongoing Leave Schedule</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-bordered border-secondary text-light">
                                <tbody>
                                    <tr>
                                        <td><strong>Leave Start Date</strong></td>
                                        <td><?php echo $approved_leave_data['start_date']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Leave End Date</strong></td>
                                        <td><?php echo $approved_leave_data['end_date']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Leave Duration</strong></td>
                                        <td><?php echo $leave_duration; ?> days</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Days Passed</strong></td>
                                        <td><?php echo $days_passed; ?> days</td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <div class="mt-3">
                                <p class="d-flex justify-content-between">
                                    <span>Progress:</span>
                                    <span><?php echo round($progress_percentage); ?>%</span>
                                </p>
                                <div class="progress bg-secondary">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress_percentage; ?>%" 
                                        aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>