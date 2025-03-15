<?php
session_start();
include '../../db/db_conn.php';

if (!isset($_SESSION['e_id']) || !isset($_SESSION['position']) || $_SESSION['position'] !== 'Supervisor') {
    header("Location: ../../login.php");
    exit();
}

// Fetch user info from the employee_register table
$employeeId = $_SESSION['e_id'];
$sql = "SELECT e_id, firstname, middlename, lastname, birthdate, gender, email, role, position, department, phone_number, address, pfp FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();

if (!$employeeInfo) {
    die("Error: Employee information not found.");
}

$gender = $employeeInfo['gender']; // Fetch gender

// Fetch the available leaves from the employee_leaves table (including both male and female leave types)
$leavesQuery = "SELECT 
                    bereavement_leave, emergency_leave, maternity_leave, mcw_special_leave, 
                    parental_leave, service_incentive_leave, sick_leave, vacation_leave, vawc_leave,
                    bereavement_leave_male,emergency_leave_male, parental_leave_male, 
                    paternity_leave_male, service_incentive_leave_male, sick_leave_male, vacation_leave_male 
                FROM employee_leaves 
                WHERE employee_id = ?";
$leavesStmt = $conn->prepare($leavesQuery);
$leavesStmt->bind_param("i", $employeeId);
$leavesStmt->execute();
$leavesResult = $leavesStmt->get_result();
$leavesInfo = $leavesResult->fetch_assoc();

// If no leave information is found, set default values for leave types
if (!$leavesInfo) {
    $leaveTypes = [
        'bereavement_leave', 'emergency_leave', 'maternity_leave', 'mcw_special_leave', 
        'parental_leave', 'service_incentive_leave', 'sick_leave', 'vacation_leave', 'vawc_leave',
        'bereavement_leave_male', 'emergency_leave_male', 'parental_leave_male', 'paternity_leave_male', 
        'service_incentive_leave_male', 'sick_leave_male', 'vacation_leave_male'
    ];
    $leavesInfo = array_fill_keys($leaveTypes, 0);
}

// Fetch the used leave by summing up approved leave days
$usedLeaveQuery = "SELECT start_date, end_date, SUM(DATEDIFF(end_date, start_date) + 1) AS used_leaves 
                   FROM leave_requests 
                   WHERE e_id = ? AND status = 'approved'
                   GROUP BY e_id";
$usedLeaveStmt = $conn->prepare($usedLeaveQuery);
$usedLeaveStmt->bind_param("i", $employeeId);
$usedLeaveStmt->execute();
$usedLeaveResult = $usedLeaveStmt->get_result();
$usedLeaveRow = $usedLeaveResult->fetch_assoc();
$usedLeave = $usedLeaveRow['used_leaves'] ?? 0; // Default to 0 if no leave has been used

// Calculate total available leaves based on gender
$totalAvailableLeaves = 0;
if ($employeeInfo['gender'] === 'Male') {
    // For male employees
    $totalAvailableLeaves = 
        $leavesInfo['bereavement_leave_male'] +
        $leavesInfo['emergency_leave_male'] +
        $leavesInfo['parental_leave_male'] +
        $leavesInfo['paternity_leave_male'] +
        $leavesInfo['service_incentive_leave_male'] +
        $leavesInfo['sick_leave_male'] +
        $leavesInfo['vacation_leave_male'];
} else {
    // For female employees
    $totalAvailableLeaves = 
        $leavesInfo['bereavement_leave'] +
        $leavesInfo['emergency_leave'] +
        $leavesInfo['maternity_leave'] +
        $leavesInfo['mcw_special_leave'] +
        $leavesInfo['parental_leave'] +
        $leavesInfo['service_incentive_leave'] +
        $leavesInfo['sick_leave'] +
        $leavesInfo['vacation_leave'] +
        $leavesInfo['vawc_leave'];
}

// Calculate remaining total leaves by subtracting used leaves
$remainingLeaves = $totalAvailableLeaves;

// Fetch the leave requests that are approved for the employee
$query_approved_leave = "SELECT * FROM leave_requests WHERE e_id = ? AND status = 'approved' ORDER BY start_date DESC LIMIT 1"; // Only get the most recent approved leave
$stmt_approved_leave = $conn->prepare($query_approved_leave);
$stmt_approved_leave->bind_param("i", $employeeId);
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

// Close the database connection
$stmt->close();
$leavesStmt->close();
$usedLeaveStmt->close();
$stmt_approved_leave->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Form</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .progress-bar {
            width: 100%;
            background-color: #444;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        .progress-bar div {
            height: 20px;
            background-color: #4caf50;
            width: 0%;
        }
        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            min-width: 40px;
            text-align: center;
        }
        .badge-primary, .bg-primary {
            background-color: #007bff !important;
            color: white;
        }
        .badge-secondary, .bg-secondary {
            background-color: #6c757d !important;
            color: white;
        }
        .table-dark {
            --bs-table-bg: #2d3238;
            --bs-table-border-color: #495057;
            color: #fff;
        }
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        
    </style>
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php' ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'sidebar.php' ?>
        </div>
        <div id="layoutSidenav_content">
            <main class="container-fluid position-relative bg-black px-4">
                <div class="container" id="calendarContainer" 
                    style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                    width: 700px; display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="calendar" class="p-2"></div>
                        </div>
                    </div>
                </div> 
                <h1 class="mb-2 text-light">File Leave</h1>                   
                <div class="card bg-black py-4">
                    <?php if (isset($_SESSION['status_message'])): ?>
                        <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content bg-dark text-light">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="statusModalLabel">
                                            <i class="fa fa-info-circle text-light me-2 fs-4"></i> Message
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body align-items-center">
                                        <?php echo $_SESSION['status_message']; ?>
                                        <div class="d-flex justify-content-center mt-3">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ok</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var myModal = new bootstrap.Modal(document.getElementById('statusModal'));
                                myModal.show();
                            });
                        </script>
                        <?php unset($_SESSION['status_message']); // Clear the message after displaying ?>
                    <?php endif; ?>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card leave-balance-card bg-dark text-light">
                                <div class="card-body text-center">
                                    <h3 class="card-title">Leave Information</h3>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="p-3">
                                                <h5>Overall Available Leave</h5>
                                                <p class="fs-4 text-success"><?php echo htmlspecialchars($remainingLeaves); ?> days</p>
                                                <a class="btn btn-success" href="../../employee/supervisor/leaveDetails.php"> View leave Details</a>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3">
                                                <h5>Used Leave</h5>
                                                <p class="fs-4 text-danger"><?php echo htmlspecialchars($usedLeave); ?> days</p>
                                                <a class="btn btn-danger" href="../../employee/supervisor/leaveHistory.php"> View leave History</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <form id="leave-request-form" action="../../employee_db/supervisor/leave_conn.php"class="needs-validation" method="POST" enctype="multipart/form-data" novalidate>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card leave-form text bg-dark text-light">
                                    <div class="card-body">
                                        <h3 class="card-title text-center mb-4">Request Leave</h3>
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <div class="position-relative mb-3 mb-md-0">
                                                    <label for="name" class="fw-bold position-absolute text-light" 
                                                        style="top: -10px; left: 15px; background-color: #212529; padding: 0 5px;">Name:</label>
                                                    <input type="text" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                        style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="name" name="name" value="<?php echo htmlspecialchars($employeeInfo['firstname'] . ' ' . $employeeInfo['lastname']); ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="position-relative mb-3 mb-md-0">
                                                    <label for="department" class="fw-bold position-absolute text-light" 
                                                        style="top: -10px; left: 15px; background-color: #212529; padding: 0 5px;">Department:</label>
                                                    <input type="text" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                        style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="department" name="department" value="<?php echo htmlspecialchars($employeeInfo['department']); ?>" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <div class="position-relative mb-3 mb-md-0">
                                                    <label class="fw-bold position-absolute text-light" 
                                                        style="top: -10px; left: 15px; background-color: #212529; padding: 0 5px;" for="leave_type">Leave Type</label>
                                                    <select id="leave_type" name="leave_type" class="form-control form-select fw-bold bg-dark border border-2 border-secondary text-light" 
                                                        style="height: 60px; padding-top: 15px; padding-bottom: 15px;" required>
                                                        <option value="" disabled selected>Select leave type</option>
                                                        <option value="Bereavement Leave">Bereavement Leave</option>
                                                        <option value="Emergency Leave">Emergency Leave</option>
                                                        <option value="Maternity Leave" class="female-leave">Maternity Leave</option>
                                                        <option value="MCW Special Leave" class="female-leave">MCW Special Leave Benefit</option>
                                                        <option value="Parental Leave">Parental Leave</option>
                                                        <option value="Paternity Leave" class="male-leave">Paternity Leave</option>
                                                        <option value="Service Incentive Leave">Service Incentive Leave</option>
                                                        <option value="Sick Leave">Sick Leave</option>
                                                        <option value="Vacation Leave">Vacation Leave</option>
                                                        <option value="VAWC Leave" class="female-leave">VAWC Leave</option>
                                                    </select>
                                                    <div class="invalid-feedback">Please select leave type.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="position-relative mb-3 mb-md-0">
                                                    <label for="leave_days" class="fw-bold position-absolute text-light" 
                                                        style="top: -10px; left: 15px; background-color: #212529; padding: 0 5px;">Number of Days</label>
                                                    <input type="number" name="leave_days" id="leave_days" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                        style="height: 60px; padding-top: 15px; padding-bottom: 15px;" min="1" max="30" placeholder="" required readonly>
                                                    <div class="invalid-feedback">Please set a value.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <div class="position-relative mb-3 mb-md-0">
                                                    <label for="start_date" class="fw-bold position-absolute text-light" 
                                                        style="top: -10px; left: 15px; background-color: #212529; padding: 0 5px;">Start Date</label>
                                                    <input type="date" id="start_date" name="start_date" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                        style="height: 60px; padding-top: 15px; padding-bottom: 15px;" required>
                                                    <div class="invalid-feedback">Please set a date.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="position-relative mb-3 mb-md-0">
                                                    <label for="end_date" class="fw-bold position-absolute text-light" 
                                                        style="top: -10px; left: 15px; background-color: #212529; padding: 0 5px;">End Date</label>
                                                    <input type="date" id="end_date" name="end_date" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                        style="height: 60px; padding-top: 15px; padding-bottom: 15px;" required>
                                                    <div class="invalid-feedback">Please set a date.</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="position-relative mb-3 mb-md-0">
                                                <label for="proof" class="fw-bold position-absolute text-light" 
                                                    style="top: -10px; left: 15px; background-color: #212529; padding: 0 5px;">Attach Proof</label>
                                                <input type="file" id="proof" name="proof[]" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                    style="height: 60px; padding-top: 15px; padding-bottom: 15px;" accept="*/*" multiple>
                                                <small class="form-text text-warning">Note: Please upload the necessary proof (image or PDF) to support your leave request. You may upload multiple files,
                                                but a single file is sufficient for your request to be considered valid.</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-danger me-2" onclick="resetForm()">Clear</button>
                                            <button type="submit" class="btn btn-primary">Submit Leave Request</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
            
            <!-- Leave Details Modal -->
            <div class="modal fade" id="leaveDetailsModal" tabindex="-1" aria-labelledby="leaveDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content bg-dark text-light">
                        <div class="modal-header border-bottom border-secondary">
                            <h5 class="modal-title" id="leaveDetailsModalLabel">Leave Details</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
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
                        <div class="modal-footer border-top border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <?php if ($approved_leave_data): ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#leaveScheduleModal">
                                    View Ongoing Leave Schedule
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ongoing Leave Schedule Modal -->
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
            
            <!-- Logout Modal -->
            <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light">
                        <div class="modal-header">
                            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to log out?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                            <form action="../../employee/logout.php" method="POST">
                                <button type="submit" class="btn btn-danger">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>  
            <?php include 'footer.php' ?>
        </div>
    </div>
    <script>

        //LEAVE DAYS
        document.getElementById('start_date').addEventListener('change', calculateLeaveDays);
        document.getElementById('end_date').addEventListener('change', calculateLeaveDays);

        function calculateLeaveDays() {
            const start_date = document.getElementById('start_date').value;
            const end_date = document.getElementById('end_date').value;
            
            if (start_date && end_date) {
                const start = new Date(start_date);
                const end = new Date(end_date);
                let totalDays = 0;

                // Loop through the dates between start and end dates
                for (let date = new Date(start); date <= end; date.setDate(date.getDate() + 1)) {
                    // Exclude Sundays (0 is Sunday)
                    if (date.getDay() !== 0) {
                        totalDays++;
                    }
                }

                // Update the number of days in the input field
                document.getElementById('leave_days').value = totalDays;
            }
        }
        //LEAVE DAYS END


        //LEAVE REQUEST
        document.addEventListener('DOMContentLoaded', function () {
            const leaveType = document.getElementById('leave_type');
            const leaveDays = document.getElementById('leave_days');
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            let holidays = [];

            // Fetch holidays from the server
            fetch('../../employee_db/getHolidays.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Holidays fetched:', data);
                    if (Array.isArray(data)) {
                        holidays = data;
                    } else {
                        console.error('Expected an array of holidays, but received:', data);
                    }
                })
                .catch(error => {
                    console.error('Error fetching holidays:', error);
                });

            function calculateEndDate(startDate, days) {
                let count = 0;
                let currentDate = new Date(startDate);
                
                while (count < days) {
                    currentDate.setDate(currentDate.getDate() + 1);

                    const currentDateString = currentDate.toISOString().split('T')[0];
                    if (currentDate.getDay() !== 0 && !holidays.includes(currentDateString)) {
                        count++;
                    }
                }
                return currentDate.toISOString().split('T')[0];
            }

            function isInvalidStartDate(date) {
                const dateString = date.toISOString().split('T')[0];
                const todayString = new Date().toISOString().split('T')[0]; // Today's date in YYYY-MM-DD format

                // Check if the date is today or a holiday or a Sunday
                return date.getDay() === 0 || holidays.includes(dateString) || dateString === todayString;
            }

            // Event listener for start date change
            startDate.addEventListener('change', function () {
                const selectedStartDate = new Date(startDate.value);
                
                // Check if the selected start date is invalid
                if (isInvalidStartDate(selectedStartDate)) {
                    alert("You cannot file leave on Sundays, holidays, or the current day.");
                    startDate.value = ''; // Clear the selected start date
                    endDate.value = ''; // Clear the end date as well
                    return;
                }

                if (leaveType.value === 'Paternity Leave' && startDate.value) {
                    const endDateValue = calculateEndDate(startDate.value, 7);
                    endDate.value = endDateValue;
                } else if (leaveType.value === 'Maternity Leave' && startDate.value) {
                    const endDateValue = calculateEndDate(startDate.value, 105);
                    endDate.value = endDateValue;
                } else {
                    endDate.value = '';
                }
            });
        });

        //LEAVE REQUEST END

        //GENDER BASED
        // Get the gender from PHP
        const gender = "<?php echo addslashes($gender); ?>";

        const femaleLeaveOptions = document.querySelectorAll('.female-leave');
        const maleLeaveOptions = document.querySelectorAll('.male-leave');

        // Hide all gender-specific options by default
        femaleLeaveOptions.forEach(option => option.style.display = 'none');
        maleLeaveOptions.forEach(option => option.style.display = 'none');

        // Show gender-specific leave options based on the user's gender
        if (gender === 'Female') {
            femaleLeaveOptions.forEach(option => option.style.display = 'block');  // Show Female Leave Options
        } else if (gender === 'Male') {
            maleLeaveOptions.forEach(option => option.style.display = 'block');  // Show Male Leave Options
        }

        function resetForm() {
            document.getElementById('leave-request-form').reset();  // Reset the form
        }
        //GENDER BASED

        //VALIDATION
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
        //VALIDATION

    </script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../js/employee.js"></script>
</body>
</html>