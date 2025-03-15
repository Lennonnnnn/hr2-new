<?php
// Start the session
session_start();

// Include the database connection file
include '../../db/db_conn.php';

// Check if the user is logged in
if (!isset($_SESSION['e_id'])) {
    // Redirect to the login page if the user is not logged in
    header("Location: ../../login.php");
    exit();
}

// Get the logged-in user's ID from the session
$loggedInUserId = $_SESSION['e_id'];

// Initialize an array to store employee information
$employeeInfo = [];

// Fetch employee details (name, ID, department, and position)
$employeeQuery = "SELECT e_id, CONCAT(firstname, ' ', lastname) AS name, department, position 
                  FROM employee_register 
                  WHERE e_id = ?";
if ($employeeStmt = $conn->prepare($employeeQuery)) {
    $employeeStmt->bind_param("i", $loggedInUserId);
    $employeeStmt->execute();
    $employeeResult = $employeeStmt->get_result();
    if ($employeeRow = $employeeResult->fetch_assoc()) {
        $employeeInfo = $employeeRow; // Populate the $employeeInfo array
    }
    $employeeStmt->close();
} else {
    die("Error preparing employee statement: " . $conn->error);
}

// Initialize an array to store attendance logs
$attendanceLogs = [];

// Get the selected month and year from the request (default to current month and year)
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Fetch data from the attendance_log table for the logged-in user and selected month/year
$sql = "SELECT e_id, name, attendance_date, time_in, time_out, status 
        FROM attendance_log 
        WHERE e_id = ? 
        AND MONTH(attendance_date) = ? 
        AND YEAR(attendance_date) = ?";

// Prepare and execute the query
if ($stmt = $conn->prepare($sql)) {
    // Bind the logged-in user's ID, selected month, and selected year to the query
    $stmt->bind_param("iii", $loggedInUserId, $selectedMonth, $selectedYear);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all rows as an associative array
    while ($row = $result->fetch_assoc()) {
        // Calculate total_hours based on time_in and time_out
        if (!empty($row['time_in']) && !empty($row['time_out'])) {
            $timeIn = new DateTime($row['time_in']);
            $timeOut = new DateTime($row['time_out']);
            $interval = $timeIn->diff($timeOut);

            // Calculate total hours and minutes
            $totalHours = ($interval->days * 24) + $interval->h; // Include days if any
            $minutes = $interval->i;

            // Format the duration display
            $durationParts = [];
            if ($totalHours > 0) {
                $durationParts[] = $totalHours . ' hour' . ($totalHours != 1 ? 's' : '');
            }
            if ($minutes > 0) {
                $durationParts[] = $minutes . ' minute' . ($minutes != 1 ? 's' : '');
            }

            // Handle cases where both are zero
            if (empty($durationParts)) {
                $row['total_hours'] = '0 hours';
            } else {
                $row['total_hours'] = implode(' and ', $durationParts);
            }
        } else {
            // If time_in or time_out is null, set total_hours to 'N/A'
            $row['total_hours'] = 'N/A';
        }

        // Append the row to the attendanceLogs array
        $attendanceLogs[] = $row;
    }

    // Close the statement
    $stmt->close();
} else {
    // Handle query preparation error
    die("Error preparing statement: " . $conn->error);
}

// Fetch holidays from non_working_days table
$holidays = [];
$holidayQuery = "SELECT date, description FROM non_working_days 
                 WHERE MONTH(date) = ? AND YEAR(date) = ?";
if ($holidayStmt = $conn->prepare($holidayQuery)) {
    $holidayStmt->bind_param("ii", $selectedMonth, $selectedYear);
    $holidayStmt->execute();
    $holidayResult = $holidayStmt->get_result();
    while ($holidayRow = $holidayResult->fetch_assoc()) {
        $holidays[$holidayRow['date']] = $holidayRow['description'];
    }
    $holidayStmt->close();
} else {
    die("Error preparing holiday statement: " . $conn->error);
}

// Fetch leave requests from leave_requests table
$leaveRequests = [];
$leaveQuery = "SELECT start_date, end_date, leave_type FROM leave_requests 
               WHERE e_id = ? 
               AND status = 'Approved'  -- Filter by approved status
               AND ((MONTH(start_date) = ? AND YEAR(start_date) = ?) 
               OR (MONTH(end_date) = ? AND YEAR(end_date) = ?))";

if ($leaveStmt = $conn->prepare($leaveQuery)) {
    $leaveStmt->bind_param("iiiii", $loggedInUserId, $selectedMonth, $selectedYear, $selectedMonth, $selectedYear);
    $leaveStmt->execute();
    $leaveResult = $leaveStmt->get_result();
    
    while ($leaveRow = $leaveResult->fetch_assoc()) {
        $startDate = new DateTime($leaveRow['start_date']);
        $endDate = new DateTime($leaveRow['end_date']);
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

        foreach ($dateRange as $date) {
            $leaveRequests[$date->format('Y-m-d')] = $leaveRow['leave_type'];
        }
    }
    $leaveStmt->close();
} else {
    die("Error preparing leave statement: " . $conn->error);
}

// Close the database connection
$conn->close();

// Generate all dates for the selected month and year
$allDatesInMonth = [];
$numberOfDays = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
$currentDate = new DateTime(); // Get current date/time in Manila timezone

// Calculate attendance statistics
$totalWorkDays = 0;
$presentDays = 0;
$lateDays = 0;
$absentDays = 0;
$leaveDays = 0;
$holidayDays = 0;

for ($day = 1; $day <= $numberOfDays; $day++) {
    $dateStr = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
    $dateObj = new DateTime($dateStr);
    $dayOfWeek = $dateObj->format('N'); // 1=Monday, 7=Sunday
    
    // Reset time components for accurate comparison
    $dateObj->setTime(0, 0, 0);
    $currentDate->setTime(0, 0, 0);

    // Determine status
    if ($dayOfWeek == 7) {
        $status = 'Day Off';
    } elseif (isset($holidays[$dateStr])) {
        $status = 'Holiday (' . $holidays[$dateStr] . ')';
        $holidayDays++;
    } elseif (isset($leaveRequests[$dateStr])) {
        $status = 'Leave (' . $leaveRequests[$dateStr] . ')';
        $leaveDays++;
    } else {
        if ($dayOfWeek != 7) { // Not a Sunday
            $totalWorkDays++;
        }
        $status = ($dateObj <= $currentDate) ? 'Absent' : 'No Record';
        if ($status == 'Absent' && $dateObj <= $currentDate && $dayOfWeek != 7) {
            $absentDays++;
        }
    }

    $allDatesInMonth[$dateStr] = [
        'e_id' => $loggedInUserId,
        'name' => $employeeInfo['name'], // Use the fetched employee name
        'attendance_date' => $dateStr,
        'time_in' => null,
        'time_out' => null,
        'status' => $status,
        'total_hours' => 'N/A'
    ];
}

// Merge attendance logs with all dates
foreach ($attendanceLogs as $log) {
    $date = $log['attendance_date'];
    if (isset($allDatesInMonth[$date])) {
        // Count present and late days
        if ($log['status'] == 'Present') {
            $presentDays++;
            $absentDays--; // Adjust absent count
        } elseif ($log['status'] == 'Late') {
            $lateDays++;
            $absentDays--; // Adjust absent count
        }
        
        $allDatesInMonth[$date] = $log; // Replace with actual attendance data
    }
}

// Calculate attendance rate
$attendanceRate = ($totalWorkDays > 0) ? round(($presentDays + $lateDays) / $totalWorkDays * 100) : 0;

// Calculate total work hours
$totalWorkHours = 0;
$totalWorkMinutes = 0;
foreach ($allDatesInMonth as $log) {
    if (!empty($log['time_in']) && !empty($log['time_out'])) {
        $timeIn = new DateTime($log['time_in']);
        $timeOut = new DateTime($log['time_out']);
        $interval = $timeIn->diff($timeOut);
        
        $totalWorkHours += ($interval->days * 24) + $interval->h;
        $totalWorkMinutes += $interval->i;
    }
}
// Convert excess minutes to hours
$totalWorkHours += floor($totalWorkMinutes / 60);
$totalWorkMinutes = $totalWorkMinutes % 60;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Timesheet</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --dark-bg: #121212;
            --darker-bg: #0a0a0a;
            --card-bg: #1e1e1e;
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
        
        .container {
            max-width: 1200px;
            padding: 2rem 1rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
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
        
        .employee-info-card {
            background: linear-gradient(145deg, #1e1e1e, #252525);
            border: none;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .employee-info-header {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-bottom: none;
            border-radius: 12px 12px 0 0;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .info-item i {
            width: 40px;
            height: 40px;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
        }
        
        .info-label {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-secondary);
        }
        
        .info-value {
            font-size: 1.1rem;
            color: var(--text-primary);
        }
        
        .stats-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .stats-present .stats-icon {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
        }
        
        .stats-late .stats-icon {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
        }
        
        .stats-absent .stats-icon {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
        }
        
        .stats-hours .stats-icon {
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--info-color);
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .month-selector {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .form-control, .form-select {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--darker-bg);
            border-color: var(--primary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .form-select option {
            background-color: var(--darker-bg);
            color: var(--text-primary);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
            border-color: #3a56d4;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            transform: translateY(-2px);
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
            font-size: 0.8rem;
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
        
        .badge {
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
        }
        
        .badge-present {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .badge-late {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .badge-absent {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .badge-holiday {
            background-color: rgba(142, 68, 173, 0.15);
            color: #9b59b6;
            border: 1px solid rgba(142, 68, 173, 0.3);
        }
        
        .badge-leave {
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--info-color);
            border: 1px solid rgba(52, 152, 219, 0.3);
        }
        
        .badge-dayoff {
            background-color: rgba(149, 165, 166, 0.15);
            color: #95a5a6;
            border: 1px solid rgba(149, 165, 166, 0.3);
        }
        
        .badge-norecord {
            background-color: rgba(189, 195, 199, 0.15);
            color: #bdc3c7;
            border: 1px solid rgba(189, 195, 199, 0.3);
        }
        
        .progress {
            height: 8px;
            background-color: var(--darker-bg);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .attendance-chart {
            height: 200px;
            margin-top: 1rem;
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
        
        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.75rem;
            }
            
            .stats-grid {
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
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Employee Timesheet</h1>
            <p class="text-secondary">Track your attendance and working hours</p>
        </div>
        
        <!-- Employee Information Card -->
        <div class="card employee-info-card fade-in">
            <div class="card-header employee-info-header">
                <h5 class="mb-0"><i class="fas fa-id-card"></i> Employee Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <i class="fas fa-user"></i>
                            <div>
                                <div class="info-label">Name</div>
                                <div class="info-value"><?php echo $employeeInfo['name']; ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-id-badge"></i>
                            <div>
                                <div class="info-label">Employee ID</div>
                                <div class="info-value"><?php echo $employeeInfo['e_id']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <i class="fas fa-building"></i>
                            <div>
                                <div class="info-label">Department</div>
                                <div class="info-value"><?php echo $employeeInfo['department']; ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-briefcase"></i>
                            <div>
                                <div class="info-label">Position</div>
                                <div class="info-value"><?php echo $employeeInfo['position']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Month and Year Selector -->
        <div class="month-selector fade-in" style="animation-delay: 0.1s;">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label for="month" class="form-label">Month</label>
                    <select name="month" id="month" class="form-select">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($i == $selectedMonth) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="year" class="form-label">Year</label>
                    <input type="number" name="year" id="year" class="form-control" value="<?php echo $selectedYear; ?>" min="2000" max="<?php echo date('Y'); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Attendance Statistics -->
        <div class="row fade-in" style="animation-delay: 0.2s;">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card stats-present">
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-value"><?php echo $presentDays; ?></div>
                    <div class="stats-label">Present Days</div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?php echo ($totalWorkDays > 0) ? ($presentDays / $totalWorkDays * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card stats-late">
                    <div class="stats-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-value"><?php echo $lateDays; ?></div>
                    <div class="stats-label">Late Days</div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?php echo ($totalWorkDays > 0) ? ($lateDays / $totalWorkDays * 100) : 0; ?>%"></div>
                    </div>
                    
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card stats-absent">
                    <div class="stats-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stats-value"><?php echo $absentDays; ?></div>
                    <div class="stats-label">Absent Days</div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?php echo ($totalWorkDays > 0) ? ($absentDays / $totalWorkDays * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="stats-card stats-hours">
                    <div class="stats-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stats-value"><?php echo $totalWorkHours; ?><span class="fs-6"><?php echo ($totalWorkMinutes > 0) ? ':'.$totalWorkMinutes : ''; ?></span></div>
                    <div class="stats-label">Total Work Hours</div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?php echo min(100, ($totalWorkHours / 160) * 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Attendance Rate Card -->
        <div class="card fade-in" style="animation-delay: 0.3s;">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Attendance Overview</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-3">Attendance Rate</h4>
                        <div class="d-flex align-items-center mb-3">
                            <div class="display-4 fw-bold me-3"><?php echo $attendanceRate; ?>%</div>
                            <div>
                                <div class="text-secondary mb-1">Present + Late Days</div>
                                <div class="fs-5"><?php echo ($presentDays + $lateDays); ?> of <?php echo $totalWorkDays; ?> work days</div>
                            </div>
                        </div>
                        <div class="progress" style="height: 15px;">
                            <div class="progress-bar" style="width: <?php echo $attendanceRate; ?>%"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-2" style="width: 30px; height: 30px; font-size: 0.9rem;">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <div class="text-secondary fs-6">Work Days</div>
                                        <div class="fs-5 fw-bold"><?php echo $totalWorkDays; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-2" style="width: 30px; height: 30px; font-size: 0.9rem; background-color: rgba(142, 68, 173, 0.15); color: #9b59b6;">
                                        <i class="fas fa-glass-cheers"></i>
                                    </div>
                                    <div>
                                        <div class="text-secondary fs-6">Holidays</div>
                                        <div class="fs-5 fw-bold"><?php echo $holidayDays; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-2" style="width: 30px; height: 30px; font-size: 0.9rem; background-color: rgba(52, 152, 219, 0.15); color: var(--info-color);">
                                        <i class="fas fa-umbrella-beach"></i>
                                    </div>
                                    <div>
                                        <div class="text-secondary fs-6">Leave Days</div>
                                        <div class="fs-5 fw-bold"><?php echo $leaveDays; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-2" style="width: 30px; height: 30px; font-size: 0.9rem; background-color: rgba(149, 165, 166, 0.15); color: #95a5a6;">
                                        <i class="fas fa-couch"></i>
                                    </div>
                                    <div>
                                        <div class="text-secondary fs-6">Day Offs</div>
                                        <div class="fs-5 fw-bold"><?php echo $numberOfDays - $totalWorkDays - $holidayDays - $leaveDays; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Attendance Log Table -->
        <div class="card fade-in" style="animation-delay: 0.4s;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table"></i> Detailed Timesheet</h5>
                <form method="POST" action="../../employee_db/supervisor/reportTimesheet.php">
                    <input type="hidden" name="month" value="<?php echo $selectedMonth; ?>">
                    <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
                    <button type="submit" name="download_excel" class="btn btn-success">
                        <i class="fas fa-download me-2"></i> Export to Excel
                    </button>
                </form>
            </div>
            <div class="card-body">
                <table id="timesheetTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Time-In</th>
                            <th>Time-Out</th>
                            <th>Total Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allDatesInMonth)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No data available for the selected month and year.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allDatesInMonth as $date => $log): ?>
                                <?php 
                                    $dateObj = new DateTime($date);
                                    $dayName = $dateObj->format('l');
                                    $isWeekend = ($dayName == 'Saturday' || $dayName == 'Sunday');
                                    
                                    // Determine badge class based on status
                                    $badgeClass = '';
                                    if (strpos($log['status'], 'Present') !== false) {
                                        $badgeClass = 'badge-present';
                                    } elseif (strpos($log['status'], 'Late') !== false) {
                                        $badgeClass = 'badge-late';
                                    } elseif (strpos($log['status'], 'Absent') !== false) {
                                        $badgeClass = 'badge-absent';
                                    } elseif (strpos($log['status'], 'Holiday') !== false) {
                                        $badgeClass = 'badge-holiday';
                                    } elseif (strpos($log['status'], 'Leave') !== false) {
                                        $badgeClass = 'badge-leave';
                                    } elseif (strpos($log['status'], 'Day Off') !== false) {
                                        $badgeClass = 'badge-dayoff';
                                    } else {
                                        $badgeClass = 'badge-norecord';
                                    }
                                ?>
                                <tr class="<?php echo $isWeekend ? 'table-secondary bg-opacity-10' : ''; ?>">
                                    <td><?php echo date('F j, Y', strtotime($date)); ?></td>
                                    <td><?php echo $dayName; ?></td>
                                    <td><?php echo $log['time_in'] ? date('g:i a', strtotime($log['time_in'])) : 'N/A'; ?></td>
                                    <td><?php echo $log['time_out'] ? date('g:i a', strtotime($log['time_out'])) : 'N/A'; ?></td>
                                    <td><?php echo $log['total_hours']; ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $log['status']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Initialize DataTable
        document.addEventListener('DOMContentLoaded', function () {
            const table = new simpleDatatables.DataTable("#timesheetTable", {
                searchable: true,
                fixedHeight: true,
                perPage: 10,
                perPageSelect: [5, 10, 15, 20, 25],
                labels: {
                    placeholder: "Search timesheet...",
                    perPage: "{select} entries per page",
                    noRows: "No entries found",
                    info: "Showing {start} to {end} of {rows} entries",
                }
            });
        });
    </script>
</body>
</html>