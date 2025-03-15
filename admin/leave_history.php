<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Prepare SQL query without search
$sql = "
    SELECT lr.*, e.firstname, e.lastname 
    FROM leave_requests lr
    JOIN employee_register e ON lr.e_id = e.e_id
    ORDER BY lr.created_at ASC";

// Prepare statement
$stmt = $conn->prepare($sql);

// Execute the query
$stmt->execute();

// Fetch the result
$result = $stmt->get_result();

// Store all leave requests in an array for later use
$allLeaveRequests = [];
while ($row = $result->fetch_assoc()) {
    $allLeaveRequests[] = $row;
}

// Reset the result pointer
$result->data_seek(0);

// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Count leave requests by status for the current month
$pendingCount = 0;
$approvedCount = 0;
$deniedCount = 0;
$supervisorApprovedCount = 0;

foreach ($allLeaveRequests as $request) {
    $requestMonth = date('m', strtotime($request['created_at']));
    $requestYear = date('Y', strtotime($request['created_at']));
    
    if ($requestMonth == $currentMonth && $requestYear == $currentYear) {
        if ($request['status'] === 'Pending') {
            $pendingCount++;
        } elseif ($request['status'] === 'Approved') {
            $approvedCount++;
        } elseif ($request['status'] === 'Denied') {
            $deniedCount++;
        } elseif ($request['status'] === 'Supervisor Approved') {
            $supervisorApprovedCount++;
        }
    }
}

$holidays = [];
$holiday_sql = "SELECT date FROM non_working_days";
$holiday_stmt = $conn->prepare($holiday_sql);
$holiday_stmt->execute();
$holiday_result = $holiday_stmt->get_result();
while ($holiday_row = $holiday_result->fetch_assoc()) {
    $holidays[] = $holiday_row['date']; // Store holidays in an array
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave History</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-bg: rgba(33, 37, 41) !important;
            --darker-bg: rgba(16, 17, 18) !important;
            --card-bg: rgba(33, 37, 41) !important;
            --border-color: #333;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            background-color: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header i {
            margin-right: 0.75rem;
            color: var(--accent-color);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .status-card {
            height: 100%;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
        }
        
        .status-card-pending {
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.1), rgba(243, 156, 18, 0.2));
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .status-card-approved {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.2));
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .status-card-denied {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1), rgba(231, 76, 60, 0.2));
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .status-card-supervisor {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(52, 152, 219, 0.2));
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .status-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .status-pending .status-icon {
            color: var(--warning-color);
        }
        
        .status-approved .status-icon {
            color: var(--success-color);
        }
        
        .status-denied .status-icon {
            color: var(--danger-color);
        }
        
        .status-supervisor .status-icon {
            color: #3498db;
        }
        
        .status-count {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .status-pending .status-count {
            color: var(--warning-color);
        }
        
        .status-approved .status-count {
            color: var(--success-color);
        }
        
        .status-denied .status-count {
            color: var(--danger-color);
        }
        
        .status-supervisor .status-count {
            color: #3498db;
        }
        
        .status-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .month-label {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            text-align: center;
        }
        
        .table {
            color: var(--text-primary);
            border-color: var(--border-color);
        }
        
        .table th {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-color: var(--border-color);
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: var(--border-color);
        }
        
        .table tbody tr {
            background-color: var(--card-bg);
            transition: background-color 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .badgeTimesheet {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }
        
        .badge-pending {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .badge-approved {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .badge-denied {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .badge-supervisor {
            background-color: rgba(52, 152, 219, 0.15);
            color: #3498db;
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .calendar-toggle-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .calendar-toggle-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .calendar-toggle-btn i {
            margin-right: 0.5rem;
        }
        
        #calendarContainer {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            max-width: 800px;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .datatable-wrapper .datatable-top,
        .datatable-wrapper .datatable-bottom {
            padding: 0.75rem 1.5rem;
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }
        
        .datatable-wrapper .datatable-search input {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.5rem 1rem;
        }
        
        .datatable-wrapper .datatable-selector {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.5rem;
        }
        
        .datatable-wrapper .datatable-info {
            color: var(--text-secondary);
        }
        
        .datatable-wrapper .datatable-pagination ul li a {
            color: var(--text-primary);
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin: 0 2px;
        }
        
        .datatable-wrapper .datatable-pagination ul li.active a {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .datatable-wrapper .datatable-pagination ul li:not(.active) a:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }
        
        /* Status-based row styles */
        .status-approved-row {
            background-color: rgba(46, 204, 113, 0.1); /* Light green for approved */
            border-left: 4px solid var(--success-color); /* Green border */
        }

        .status-denied-row {
            background-color: rgba(231, 76, 60, 0.1); /* Light red for denied */
            border-left: 4px solid var(--danger-color); /* Red border */
        }

        .status-pending-row {
            background-color: rgba(243, 156, 18, 0.1); /* Light yellow for pending */
            border-left: 4px solid var(--warning-color); /* Yellow border */
        }

        .status-supervisor-row {
            background-color: rgba(52, 152, 219, 0.1); /* Light blue for supervisor approved */
            border-left: 4px solid #3498db; /* Blue border */
        }

        /* Hover effect for rows */
        .status-approved-row:hover,
        .status-denied-row:hover,
        .status-pending-row:hover,
        .status-supervisor-row:hover {
            background-color: rgba(255, 255, 255, 0.05); /* Slightly lighter on hover */
        }
        
        /* Animation */
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .status-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.5rem;
            }
            
            .status-cards {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-header .btn {
                margin-top: 1rem;
                align-self: flex-end;
            }
        }
        .badge-approved {
    background-color: #28a745; /* Green */
    color: white;
    padding: 5px 10px;
    border-radius: 12px;
}

.badge-denied {
    background-color: #dc3545; /* Red */
    color: white;
    padding: 5px 10px;
    border-radius: 12px;
}

.badge-pending {
    background-color: #ffc107; /* Yellow */
    color: black;
    padding: 5px 10px;
    border-radius: 12px;
}

.badge-supervisor {
    background-color: #17a2b8; /* Teal */
    color: white;
    padding: 5px 10px;
    border-radius: 12px;
}
    </style>
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php' ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php' ?>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative px-4 py-4">
                    <!-- Calendar Container -->
                    <div class="container" id="calendarContainer" 
                        style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                        max-width: 800px; display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Page Header -->
                    <div class="page-header d-flex justify-content-between align-items-center fade-in">
                        <div>
                            <h1 class="page-title">Leave History</h1>
                            <p class="text-secondary mb-0">Track and manage employee leave requests</p>
                        </div>
                        <button class="calendar-toggle-btn" onclick="toggleCalendar()">
                            <i class="fas fa-calendar-alt"></i>
                            <span>View Calendar</span>
                        </button>
                    </div>
                    
                    <!-- Status Cards -->
                    <div class="row fade-in" style="animation-delay: 0.1s;">
                        <div class="col-12 mb-3">
                            <div class="month-label">
                                <i class="fas fa-calendar-day me-2"></i>
                                Leave Requests for <?php echo date('F Y'); ?>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card status-card status-card-pending h-100">
                                <div class="status-pending">
                                    <div class="status-icon">
                                        <i class="fas fa-hourglass-half"></i>
                                    </div>
                                    <div class="status-count"><?php echo $pendingCount; ?></div>
                                    <div class="status-label">Pending</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card status-card status-card-supervisor h-100">
                                <div class="status-supervisor">
                                    <div class="status-icon">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="status-count"><?php echo $supervisorApprovedCount; ?></div>
                                    <div class="status-label">Supervisor Approved</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card status-card status-card-approved h-100">
                                <div class="status-approved">
                                    <div class="status-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="status-count"><?php echo $approvedCount; ?></div>
                                    <div class="status-label">Approved</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card status-card status-card-denied h-100">
                                <div class="status-denied">
                                    <div class="status-icon">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <div class="status-count"><?php echo $deniedCount; ?></div>
                                    <div class="status-label">Denied</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leave History Table -->
                    <div class="card mb-4 fade-in" style="animation-delay: 0.2s;">
                        <div class="card-header">
                            <div>
                                <i class="fas fa-history"></i>
                                Leave History Record
                            </div>
                        </div>                             
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="datatablesSimple" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date of Request</th>
                                            <th>Employee ID</th>
                                            <th>Employee Name</th>
                                            <th>Duration of Leave</th>
                                            <th>Reason</th>
                                            <th>Leave Deduction</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                        <?php if (count($allLeaveRequests) > 0): ?>
                                            <?php foreach ($allLeaveRequests as $row): ?>
                                                <?php
                                                    // Calculate total leave days excluding Sundays and holidays
                                                    $leave_days = 0;
                                                    $current_date = strtotime($row['start_date']);
                                                    $end_date = strtotime($row['end_date']);
                                                    
                                                    while ($current_date <= $end_date) {
                                                        $current_date_str = date('Y-m-d', $current_date);
                                                        
                                                        if (date('N', $current_date) != 7 && !in_array($current_date_str, $holidays)) {
                                                            $leave_days++;
                                                        }
                                                        $current_date = strtotime("+1 day", $current_date); 
                                                    }

                                                    // Determine the status class
                                                    $status_class = '';
                                                    if ($row['status'] === 'Approved') {
                                                        $status_class = 'status-approved-row';
                                                    } elseif ($row['status'] === 'Denied') {
                                                        $status_class = 'status-denied-row';
                                                    } elseif ($row['status'] === 'Pending') {
                                                        $status_class = 'status-pending-row';
                                                    } elseif ($row['status'] === 'Supervisor Approved') {
                                                        $status_class = 'status-supervisor-row';
                                                    }
                                                ?>
                                                <tr class="<?php echo $status_class; ?>">
                                                    <td>
                                                        <?php 
                                                            if (isset($row['created_at'])) {
                                                                echo '<div>' . htmlspecialchars(date("F j, Y", strtotime($row['created_at']))) . '</div>';
                                                                echo '<div class="text-secondary">' . htmlspecialchars(date("g:i A", strtotime($row['created_at']))) . '</div>';
                                                            } else {
                                                                echo "Not Available";
                                                            }
                                                        ?>
                                                    </td> 
                                                    <td><?php echo htmlspecialchars($row['e_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                                    <td>
                                                        <div><?php echo htmlspecialchars(date("M j, Y", strtotime($row['start_date']))); ?></div>
                                                        <div class="text-secondary">to</div>
                                                        <div><?php echo htmlspecialchars(date("M j, Y", strtotime($row['end_date']))); ?></div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                                    <td><span class="badgeTimesheet bg-secondary"><?php echo htmlspecialchars($leave_days); ?> day<?php echo $leave_days !== 1 ? 's' : ''; ?></span></td>
                                                    <td>


                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2">
                                                            </div>
                                                            <div>
                                                        <?php 
                                                            
                                                  
                                                    
                                                    $status = $row['status'];
                                                    if ($status === 'Approved') {
                                                        echo '<span class="badgeTimeshhet badge-approved">Approved</span>';
                                                    } elseif ($status === 'Denied') {
                                                        echo '<span class="badgeTimesheet badge-denied">Denied</span>';
                                                    } elseif ($status === 'Pending') {
                                                        echo '<span class="badgeTimesheet badge-pending">Pending</span>';
                                                    } elseif ($status === 'Supervisor Approved') {
                                                        echo '<span class="badgeTimesheet badge-supervisor">Supervisor Approved</span>'; 
                                                    }
                                                    ?>
                                                </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Logout Modal -->
            <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light">
                        <div class="modal-header border-bottom border-secondary">
                            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to log out?
                        </div>
                        <div class="modal-footer border-top border-secondary">
                            <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                            <form action="../admin/logout.php" method="POST">
                                <button type="submit" class="btn btn-danger">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>  
            
            <?php include 'footer.php' ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/admin.js"></script>
    
</body>
</html>