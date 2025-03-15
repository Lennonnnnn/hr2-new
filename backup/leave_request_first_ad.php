<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, position, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Fetch all pending leave requests
$sql = "SELECT lr.leave_id, e.e_id, e.firstname, e.lastname, e.available_leaves, lr.start_date, lr.end_date, lr.leave_type, lr.status
        FROM leave_requests lr
        JOIN employee_register e ON lr.e_id = e.e_id
        WHERE lr.status = 'Pending'";  // Add this WHERE clause to only fetch pending requests


$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Fetch holidays from the database
$holidays = [];
$holiday_sql = "SELECT date FROM non_working_days";
$holiday_stmt = $conn->prepare($holiday_sql);
$holiday_stmt->execute();
$holiday_result = $holiday_stmt->get_result();
while ($holiday_row = $holiday_result->fetch_assoc()) {
    $holidays[] = $holiday_row['date']; // Store holidays in an array
}

// Handle approve/deny actions
if (isset($_GET['leave_id']) && isset($_GET['status'])) {
    $leave_id = $_GET['leave_id'];
    $status = $_GET['status'];

    // Fetch the specific leave request
    $sql = "SELECT lr.leave_id, e.e_id, e.firstname, e.lastname, e.available_leaves, lr.start_date, lr.end_date, lr.leave_type, lr.status
            FROM leave_requests lr
            JOIN employee_register e ON lr.e_id = e.e_id
            WHERE lr.leave_id = ?";
    $action_stmt = $conn->prepare($sql);
    $action_stmt->bind_param("i", $leave_id);
    $action_stmt->execute();
    $action_result = $action_stmt->get_result();

    if ($action_result->num_rows > 0) {
        $row = $action_result->fetch_assoc();
        $available_leaves = $row['available_leaves'];
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];

        // Calculate total leave days excluding Sundays and holidays
        $leave_days = 0;
        $current_date = strtotime($start_date);

        while ($current_date <= strtotime($end_date)) {
            $current_date_str = date('Y-m-d', $current_date);
            // Check if the current day is not a Sunday (0 = Sunday) and not a holiday
            if (date('N', $current_date) != 7 && !in_array($current_date_str, $holidays)) {
                $leave_days++; // Count this day as a leave day
            }
            $current_date = strtotime("+1 day", $current_date); // Move to the next day
        }

        if ($status === 'approve') {
            // Check if the employee has enough leave balance
            if ($leave_days > $available_leaves) {
                // Not enough leave balance
                header("Location: leave_status.php?status=insufficient_balance");
                exit();
            } else {
                // Update leave request status and subtract days from available leave balance
                $new_balance = $available_leaves - $leave_days;

                // Update the leave request status to 'Approved' and decrease available leaves in employee_register
                $update_sql = "UPDATE leave_requests lr 
                               JOIN employee_register e ON lr.e_id = e.e_id
                               SET lr.status = 'Approved', e.available_leaves = ?
                               WHERE lr.leave_id = ?";
                               
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $new_balance, $leave_id);

                if ($update_stmt->execute()) {
                    header("Location: leave_status.php?status=success");
                } else {
                    error_log("Error updating leave balance: " . $conn->error); // Log the error
                    header("Location: leave_status.php?status=error");
                }
            }
        } elseif ($status === 'deny') {
            // Deny the leave request
            $deny_sql = "UPDATE leave_requests SET status = 'Denied' WHERE leave_id = ?";
            $deny_stmt = $conn->prepare($deny_sql);
            $deny_stmt->bind_param("i", $leave_id);

            if ($deny_stmt->execute()) {
                header("Location: leave_status.php?status=success");
            } else {
                header("Location: leave_status.php?status=error");
            }
        }
    } else {
        // Leave request not found
        header("Location: leave_status.php?status=not_exist");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../img/logo.png">
    <title>Leave Requests</title>
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
    .btn {
        transition: transform 0.3s, background-color 0.3s; /* Smooth transition */
        border-radius: 25px;
    }

    .btn:hover {
        transform: translateY(-2px); /* Raise the button up */
    }
</style>
</head>

<body class="sb-nav-fixed bg-black">
    <nav class="sb-topnav navbar navbar-expand navbar-dark border-bottom border-1 border-warning bg-dark">
        <a class="navbar-brand ps-3 text-muted" href="../admin/dashboard.php">Microfinance</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars text-light"></i></button>
    
    <!-- Flex container to hold both time/date and search form -->
        <div class="d-flex ms-auto me-0 me-md-3 my-2 my-md-0 align-items-center">
            <div class="text-light me-3 p-2 rounded shadow-sm bg-gradient" id="currentTimeContainer" 
            style="background: linear-gradient(45deg, #333333, #444444); border-radius: 5px;">
                <span class="d-flex align-items-center">
                    <span class="pe-2">
                        <i class="fas fa-clock"></i> 
                        <span id="currentTime">00:00:00</span>
                    </span>
                    <button class="btn btn-outline-warning btn-sm ms-2" type="button" onclick="toggleCalendar()">
    <i class="fas fa-calendar-alt"></i>
    <span id="currentDate">00/00/0000</span>
</button>
                </span>
            </div>
            <form class="d-none d-md-inline-block form-inline">
            <div class="input-group">
                <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                <button class="btn btn-warning" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
            </div>
            </form>
        </div>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion bg-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu ">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading text-center text-muted">Your Profile</div>
                        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                            <li class="nav-item dropdown text">
                                <a class="nav-link dropdown-toggle text-light d-flex justify-content-center ms-4" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <img src="<?php echo (!empty($adminInfo['pfp']) && $adminInfo['pfp'] !== 'defaultpfp.png') 
                                        ? htmlspecialchars($adminInfo['pfp']) 
                                        : '../img/defaultpfp.jpg'; ?>" 
                                        class="rounded-circle border border-light" width="120" height="120" alt="Profile Picture" />
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="../admin/profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="#!">Settings</a></li>
                                    <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                                    <li><hr class="dropdown-divider" /></li>
                                    <li><a class="dropdown-item" href="../admin/logout.php" onclick="confirmLogout(event)">Logout</a></li>
                                </ul>
                            </li>
                            <li class="nav-item text-light d-flex ms-3 flex-column align-items-center text-center">
                                <span class="big text-light mb-1">
                                    <?php
                                        if ($adminInfo) {
                                        echo htmlspecialchars($adminInfo['firstname'] . ' ' . $adminInfo['middlename'] . ' ' . $adminInfo['lastname']);
                                        } else {
                                        echo "Admin information not available.";
                                        }
                                    ?>
                                </span>      
                                <span class="big text-light">
                                    <?php
                                        if ($adminInfo) {
                                        echo htmlspecialchars($adminInfo['role']);
                                        } else {
                                        echo "User information not available.";
                                        }
                                    ?>
                                </span>
                            </li>
                        </ul>
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-warning mt-3">Admin Dashboard</div>
                        <a class="nav-link text-light" href="../admin/dashboard.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTAD" aria-expanded="false" aria-controls="collapseTAD">
                            <div class="sb-nav-link-icon"><i class="fa fa-address-card"></i></div>
                            Time and Attendance
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseTAD" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="../admin/attendance.php">Attendance</a>
                                <a class="nav-link text-light" href="../admin/timesheet.php">Timesheet</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLM" aria-expanded="false" aria-controls="collapseLM">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-times"></i></div>
                            Leave Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLM" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="../admin/leave_requests.php">Leave Requests</a>
                                <a class="nav-link text-light" href="../admin/leave_history.php">Leave History</a>
                                <a class="nav-link text-light"  href="../admin/leave_allocation.php">Set Leave</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePM" aria-expanded="false" aria-controls="collapsePM">
                            <div class="sb-nav-link-icon"><i class="fas fa-line-chart"></i></div>
                            Performance Management
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapsePM" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="../admin/evaluation.php">Evaluation</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseSR" aria-expanded="false" aria-controls="collapseSR">
                            <div class="sb-nav-link-icon"><i class="fa fa-address-card"></i></div>
                            Social Recognition
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseSR" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                              <a class="nav-link text-light" href="../admin/awardee.php">Awardee</a>
                              <a class="nav-link text-light" href="../admin/recognition.php">Generate Certificate</a>
                            </nav>
                        </div>
                        <div class="sb-sidenav-menu-heading text-center text-muted border-top border-1 border-warning">Account Management</div>
                        <a class="nav-link collapsed text-light" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                            <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                            Accounts
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link text-light" href="../admin/calendar.php">Calendar</a>
                                <a class="nav-link text-light" href="../admin/admin.php">Admin Accounts</a>
                                <a class="nav-link text-light" href="../admin/employee.php">Employee Accounts</a>
                            </nav>
                        </div>
                        <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                        </div>
                    </div>
                </div>
                <div class="sb-sidenav-footer bg-black text-light border-top border-1 border-warning">
                    <div class="small">Logged in as: <?php echo htmlspecialchars($adminInfo['role']); ?></div>
                </div>
            </nav>
        </div>
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid position-relative px-4">
                <h1 class="mb-4 text-light">Leave Requests</h1>
                <div class="container" id="calendarContainer" 
                    style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                    width: 700px; display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                </div>                     
                <div class="container py-4">
                    <?php if (isset($_GET['status'])): ?>
                        <div id="status-alert" class="alert 
                            <?php if ($_GET['status'] === 'success'): ?>
                                alert-success
                            <?php elseif ($_GET['status'] === 'error'): ?>
                                alert-danger
                            <?php elseif ($_GET['status'] === 'not_exist'): ?>
                                alert-warning
                            <?php elseif ($_GET['status'] === 'insufficient_balance'): ?>
                                alert-warning
                            <?php endif; ?>" role="alert">
                            <?php if ($_GET['status'] === 'success'): ?>
                                Leave request status updated successfully.
                            <?php elseif ($_GET['status'] === 'error'): ?>
                                Error updating leave request status. Please try again.
                            <?php elseif ($_GET['status'] === 'not_exist'): ?>
                                The leave request ID does not exist or could not be found.
                            <?php elseif ($_GET['status'] === 'insufficient_balance'): ?>
                                Insufficient leave balance. The request cannot be approved.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <table class="table table-bordered mt-3 text-center text-light table-dark">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Leave ID</th>
                                <th>Leave Balance</th>
                                <th>Duration of Leave</th>
                                <th>Deduction Leave</th>
                                <th>Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php
                                        // Calculate total leave days excluding Sundays and holidays
                                        $leave_days = 0;
                                        $current_date = strtotime($row['start_date']);
                                        $end_date = strtotime($row['end_date']);
                                    
                                        while ($current_date <= $end_date) {
                                        $current_date_str = date('Y-m-d', $current_date);
                                        // Check if the current day is not a Sunday (0 = Sunday) and not a holiday
                                        if (date('N', $current_date) != 7 && !in_array($current_date_str, $holidays)) {
                                            $leave_days++; // Count this day as a leave day
                                        }
                                        $current_date = strtotime("+1 day", $current_date); // Move to the next day
                                        }
                                    ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['e_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['leave_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['available_leaves']); ?></td>
                                    <td><?php echo htmlspecialchars($row['start_date'] . ' / ' . $row['end_date']); ?></td>
                                    <td><?php echo htmlspecialchars($leave_days); ?> day/s</td>
                                    <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                    <td class="d-flex justify-content-between">
                                            <button class="btn btn-success btn-block" onclick="confirmAction('approve', <?php echo $row['leave_id']; ?>)">Approve</button>
                                            <button class="btn btn-danger btn-block" onclick="confirmAction('deny', <?php echo $row['leave_id']; ?>)">Deny</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No leave requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="container">
                        <div class="text-center">
                            <a href="#" class="text-light btn btn-primary mt-2" data-toggle="modal" data-target="#setLeaveModal">Set Leave</a>
                        </div>
                        <div class="modal fade" id="setLeaveModal" tabindex="-1" aria-labelledby="setLeaveModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content bg-dark">
                                    <div class="modal-header">
                                        <h5 class="modal-title text-light" id="setLeaveModalLabel">Set Leave Allocations</h5>
                                        <button type="button" class="close text-light bg-dark" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST" action="../db/set_leave.php">
                                            <div class="form-group">
                                                <label class="text-light mt-3 mb-1" for="employee_leaves">Leave Days for Employees:</label>
                                                <input type="number" name="employee_leaves" id="employee_leaves" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="text-light mt-3 mb-1" for="employee_id">Select Employee:</label>
                                                <select name="employee_id" id="employee_id" class="form-control">
                                                    <option value="all">All Employees</option>
                                                    <?php
                                                    // Fetch employees from the database for the dropdown
                                                    $employees_sql = "SELECT e_id, firstname, lastname FROM employee_register";
                                                    $employees_result = $conn->query($employees_sql);
                                                    while ($employee = $employees_result->fetch_assoc()) {
                                                        echo "<option value='" . $employee['e_id'] . "'>" . $employee['firstname'] . " " . $employee['lastname'] . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary mt-3">Set Allocations</button>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <footer class="py-4 bg-dark text-light mt-auto border-top border-warning">
            <div class="container-fluid px-4">
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="text-muted">Copyright &copy; Your Website 2024</div>
                    <div>
                        <a href="#">Privacy Policy</a>
                        &middot;
                        <a href="#">Terms & Conditions</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
<script>
    //CALENDAR 
    let calendar;
        function toggleCalendar() {
            const calendarContainer = document.getElementById('calendarContainer');
                if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
                    calendarContainer.style.display = 'block';
                    if (!calendar) {
                        initializeCalendar();
                        }
                    } else {
                        calendarContainer.style.display = 'none';
                    }
        }

        function initializeCalendar() {
            const calendarEl = document.getElementById('calendar');
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    height: 440,  
                    events: {
                    url: '../db/holiday.php',  
                    method: 'GET',
                    failure: function() {
                    alert('There was an error fetching events!');
                    }
                    }
                });

                calendar.render();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const currentDateElement = document.getElementById('currentDate');
            const currentDate = new Date().toLocaleDateString(); 
            currentDateElement.textContent = currentDate; 
        });

        document.addEventListener('click', function(event) {
            const calendarContainer = document.getElementById('calendarContainer');
            const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');

                if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
                    calendarContainer.style.display = 'none';
                    }
        });
        //CALENDAR END

        //TIME 
        function setCurrentTime() {
            const currentTimeElement = document.getElementById('currentTime');
            const currentDateElement = document.getElementById('currentDate');

            const currentDate = new Date();
    
            currentDate.setHours(currentDate.getHours() + 0);
                const hours = currentDate.getHours();
                const minutes = currentDate.getMinutes();
                const seconds = currentDate.getSeconds();
                const formattedHours = hours < 10 ? '0' + hours : hours;
                const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
                const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;

            currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
            currentDateElement.textContent = currentDate.toLocaleDateString();
        }
        setCurrentTime();
        setInterval(setCurrentTime, 1000);
        //TIME END

        //LEAVE STATUS 
        function confirmAction(action, requestId) {
            let confirmation = confirm(`Are you sure you want to ${action} this leave request?`);
                if (confirmation) {
                window.location.href = `leave_status.php?leave_id=${requestId}&status=${action}`;
                }
        }
        //LEAVE STATUS END

        $(document).ready(function() {
    var table = $('.table').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "search": "Search:",  // Custom search label
            "lengthMenu": "Display _MENU_ records per page",  // Custom display records label
            "zeroRecords": "No matching records found",
            "info": "Showing _START_ to _END_ of _TOTAL_ records",  // Custom info label
            "infoEmpty": "No records available",
            "infoFiltered": "(filtered from _MAX_ total records)"
        },
        "lengthMenu": [
            [5, 10, 25, 50, -1],
            ['5', '10', '25', '50', 'All']
        ]
    });

    // Apply styles to the "Display X records per page" text
    $('.dataTables_length label').css({
        'color': '#007bff',  // Change text color to blue
        'font-weight': 'bold'  // Make the text bold
    });

    // Apply styles to the "Search:" label
    $('.dataTables_filter label').css({
        'color': '#007bff',  // Change text color to blue
        'font-weight': 'bold'  // Make the text bold
    });

    // Apply styles to the "Showing X to X of X records" text
    $('.dataTables_info').css({
        'color': '#007bff',  // Change text color to blue
        'font-weight': 'bold'  // Make the text bold
    });

    // Apply styles to the "Search" input box
    $('.dataTables_filter input').css({
        'background-color': '#343a40',  // Dark background
        'color': 'white',  // White text
        'border': '1px solid #ddd'  // Light border
    });

    // Apply styles to the "Display records per page" select dropdown
    $('.dataTables_length select').css({
        'background-color': '#343a40',  // Dark background
        'color': 'white',  // White text
        'border': '1px solid #ddd'  // Light border
    });

        function applyPaginationStyles() {
        // Pagination button styles
        $('.dataTables_paginate .paginate_button').css({
            'background-color': 'white',
            'color': 'black',
            'border': '1px solid #ddd'
        });

        // Active page button styles
        $('.dataTables_paginate .paginate_button.current').css({
            'background-color': 'white',
            'border': '2px solid red'
        });
    }

    // Apply the styles immediately after the table is drawn (including pagination)
    table.on('draw', function() {
        applyPaginationStyles();  // Apply styles after each page redraw
    });

    // Apply styles on initial load
    applyPaginationStyles();
});


    // Automatically hide the alert after 10 seconds (10,000 milliseconds)
    setTimeout(function() {
        var alertElement = document.getElementById('status-alert');
        if (alertElement) {
            alertElement.style.transition = "opacity 1s ease"; // Add transition for smooth fade-out
            alertElement.style.opacity = 0; // Set the opacity to 0 (fade out)
            
            setTimeout(function() {
                alertElement.remove(); // Remove the element from the DOM after fade-out
            }, 1000); // Wait 1 second after fade-out to remove the element completely
        }
    }, 5000); // 10 seconds delay


</script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>
