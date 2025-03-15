<?php
// Start the session
session_start();

// Include the database connection file
include '../db/db_conn.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['a_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Get the selected month and year from the request (default to current month and year)
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$searchName = isset($_GET['search_name']) ? $_GET['search_name'] : '';

// Pagination settings
$entriesPerPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

// Fetch all employees from the employee_register table
$employeeQuery = "SELECT e_id, CONCAT(firstname, ' ', lastname) AS name
                  FROM employee_register";
if (!empty($searchName)) {
    $employeeQuery .= " WHERE CONCAT(firstname, ' ', lastname) LIKE '%" . $searchName . "%'";
}
$employeeResult = $conn->query($employeeQuery);
$employees = [];
while ($row = $employeeResult->fetch_assoc()) {
    $employees[$row['e_id']] = $row['name'];
}

// Fetch attendance logs for the selected month and year
$attendanceQuery = "SELECT e_id, attendance_date, time_in, time_out, status
                    FROM attendance_log
                    WHERE MONTH(attendance_date) = ?
                    AND YEAR(attendance_date) = ?";
if ($attendanceStmt = $conn->prepare($attendanceQuery)) {
    $attendanceStmt->bind_param("ii", $selectedMonth, $selectedYear);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();

    // Fetch all rows as an associative array
    $attendanceLogs = [];
    while ($row = $attendanceResult->fetch_assoc()) {
        $attendanceLogs[$row['attendance_date']][$row['e_id']] = $row;
    }

    // Close the statement
    $attendanceStmt->close();
} else {
    die("Error preparing attendance statement: " . $conn->error);
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
$leaveQuery = "SELECT start_date, end_date, leave_type, e_id FROM leave_requests
               WHERE status = 'Approved'
               AND ((MONTH(start_date) = ? AND YEAR(start_date) = ?)
               OR (MONTH(end_date) = ? AND YEAR(end_date) = ?))";

if ($leaveStmt = $conn->prepare($leaveQuery)) {
    $leaveStmt->bind_param("iiii", $selectedMonth, $selectedYear, $selectedMonth, $selectedYear);
    $leaveStmt->execute();
    $leaveResult = $leaveStmt->get_result();

    while ($leaveRow = $leaveResult->fetch_assoc()) {
        $startDate = new DateTime($leaveRow['start_date']);
        $endDate = new DateTime($leaveRow['end_date']);
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

        foreach ($dateRange as $date) {
            $leaveRequests[$date->format('Y-m-d')][$leaveRow['e_id']] = $leaveRow['leave_type'];
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
$currentDate = new DateTime(); // Get current date/time

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
    } else {
        $status = ($dateObj <= $currentDate) ? 'Absent' : 'No Record';
    }

    // Add the date to the array
    $allDatesInMonth[$dateStr] = [
        'date' => $dateStr,
        'status' => $status,
    ];
}

// Merge attendance logs with all dates for each employee
$employeeAttendance = [];
foreach ($allDatesInMonth as $dateStr => $dateInfo) {
    foreach ($employees as $e_id => $name) {
        if (isset($attendanceLogs[$dateStr][$e_id])) {
            // Use the attendance log if it exists
            $employeeAttendance[] = array_merge($attendanceLogs[$dateStr][$e_id], ['name' => $name]);
        } elseif (isset($leaveRequests[$dateStr][$e_id])) {
            // Use leave request if it exists
            $employeeAttendance[] = [
                'e_id' => $e_id,
                'name' => $name,
                'attendance_date' => $dateStr,
                'time_in' => 'N/A',
                'time_out' => 'N/A',
                'total_hours' => 'N/A',
                'status' => 'Leave (' . $leaveRequests[$dateStr][$e_id] . ')',
            ];
        } else {
            // Mark as absent if no attendance or leave record exists
            $employeeAttendance[] = [
                'e_id' => $e_id,
                'name' => $name,
                'attendance_date' => $dateStr,
                'time_in' => 'N/A',
                'time_out' => 'N/A',
                'total_hours' => 'N/A',
                'status' => $dateInfo['status'],
            ];
        }
    }
}

// Filter by employee name if search is provided
if (!empty($searchName)) {
    $employeeAttendance = array_filter($employeeAttendance, function($record) use ($searchName) {
        return stripos($record['name'], $searchName) !== false;
    });
    // Re-index array after filtering
    $employeeAttendance = array_values($employeeAttendance);
}

// Sort by date (newest first) and then by employee name
usort($employeeAttendance, function($a, $b) {
    $dateCompare = strcmp($b['attendance_date'], $a['attendance_date']);
    if ($dateCompare === 0) {
        return strcmp($a['name'], $b['name']);
    }
    return $dateCompare;
});

// Calculate pagination
$totalRecords = count($employeeAttendance);
$totalPages = ceil($totalRecords / $entriesPerPage);
if ($currentPage > $totalPages && $totalPages > 0) $currentPage = $totalPages;

// Get the records for the current page
$startIndex = ($currentPage - 1) * $entriesPerPage;
$paginatedRecords = array_slice($employeeAttendance, $startIndex, $entriesPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Timesheet</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="../css/styles.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #121212;
            --darker-bg: #0a0a0a;
            --card-bg: #1e1e1e;
            --border-color: #333;
            --text-primary: #ffffff;
            --text-secondary: #e0e0e0;
            --accent-color: #8c8c8c;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --primary-color: #0d6efd;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .card-header {
            background-color: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .card-body {
            padding: 20px;
        }

        .form-control, .form-select {
            background-color: #2d2d2d;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px 15px;
        }

        .form-control:focus, .form-select:focus {
            background-color: #3d3d3d;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            color: var(--text-primary);
        }

        /* Make sure select option text is visible */
        .form-select option {
            background-color: #2d2d2d;
            color: var(--text-primary);
        }

        .btn {
            border-radius: 6px;
            padding: 10px 15px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .btn-outline-light {
            color: var(--text-primary);
            border-color: var(--text-secondary);
        }

        .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            margin-bottom: 0;
        }

        .table th {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .table tbody tr {
            background-color: var(--card-bg);
        }

        /* Alternating row colors for better readability */
        .table tbody tr:nth-child(odd) {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .badgeT {
            font-size: 12px;
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 6px;
            color: white; /* Ensure all badge text is white */
        }

        .badge-present {
            background-color: var(--success-color);
        }

        .badge-late {
            background-color: var(--warning-color);
            color: #212529; /* Dark text for better visibility on yellow */
        }

        .badge-overtime {
            background-color: var(--primary-color);
        }

        .badge-holiday, .badge-leave, .badge-absent {
            background-color: var(--danger-color);
        }

        .badge-dayoff {
            background-color: var(--accent-color);
        }

        .badge-norecord {
            background-color: #6c757d;
        }

        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Ensure all text is white */
        .text-secondary {
            color: var(--text-secondary) !important;
        }

        /* Pagination styling */
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }

        .pagination .page-item .page-link {
            background-color: var(--card-bg);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .pagination .page-item.disabled .page-link {
            background-color: var(--card-bg);
            color: var(--accent-color);
            border-color: var(--border-color);
        }

        .pagination-info {
            color: var(--text-secondary);
            text-align: center;
            margin-top: 10px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        /* Custom scrollbar - simplified */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #1e1e1e;
        }

        ::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
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
                            <h1 class="page-title">Admin Timesheet</h1>
                            <p class="text-secondary mb-0">Track and manage employee attendance</p>
                        </div>
                        <button class="calendar-toggle-btn" onclick="toggleCalendar()">
                            <i class="fas fa-calendar-alt"></i>
                            <span>View Calendar</span>
                        </button>
                    </div>

                    <div class="dashboard-container">
                        <div class="page-header">
                            <h1 class="page-title">
                                <i class="fas fa-calendar-check me-2"></i>Admin Timesheet
                            </h1>
                            <div class="d-flex align-items-center">
                                <span class="text-secondary me-3">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>
                                </span>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-filter me-2"></i>Filter Options
                            </div>
                            <div class="card-body">
                                <form method="GET" class="month-selector">
                                    <div class="row align-items-end">
                                        <div class="col-md-3">
                                            <div class="mb-3 mb-md-0">
                                                <label for="month" class="filter-label">Select Month</label>
                                                <select name="month" id="month" class="form-select">
                                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo ($i == $selectedMonth) ? 'selected' : ''; ?>>
                                                            <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3 mb-md-0">
                                                <label for="year" class="filter-label">Select Year</label>
                                                <input type="number" name="year" id="year" class="form-control" value="<?php echo $selectedYear; ?>" min="2000" max="<?php echo date('Y'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3 mb-md-0">
                                                <label for="search_name" class="filter-label">Search Employee</label>
                                                <input type="text" name="search_name" id="search_name" class="form-control" value="<?php echo htmlspecialchars($searchName); ?>" placeholder="Enter name...">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-search me-2"></i>Apply Filter
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Preserve pagination when filtering -->
                                    <input type="hidden" name="page" value="1">
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-table me-2"></i>Attendance Records
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-light" onclick="exportToCSV()">
                                            <i class="fas fa-download me-1"></i>Export
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table id="timesheet" class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Employee</th>
                                                <th>Time-In</th>
                                                <th>Time-Out</th>
                                                <th>Total Hours</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($paginatedRecords)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No data available for the selected criteria.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($paginatedRecords as $record): ?>
                                                    <tr>
                                                        <td><?php echo date('F j, Y', strtotime($record['attendance_date'])); ?></td>
                                                        <td><?php echo $record['name']; ?></td>
                                                        <td><?php echo $record['time_in']; ?></td>
                                                        <td><?php echo $record['time_out']; ?></td>
                                                        <td><?php echo isset($record['total_hours']) ? $record['total_hours'] : 'N/A'; ?></td>
                                                        <td>
                                                            <?php
                                                            // Set badge class based on status
                                                            $statusClass = '';
                                                            $status = $record['status'];

                                                            if (strpos($status, 'Present') !== false) {
                                                                $statusClass = 'badge-present';
                                                            } elseif (strpos($status, 'Late') !== false) {
                                                                $statusClass = 'badge-late';
                                                            } elseif (strpos($status, 'Overtime') !== false) {
                                                                $statusClass = 'badge-overtime';
                                                            } elseif (strpos($status, 'Holiday') !== false) {
                                                                $statusClass = 'badge-holiday';
                                                            } elseif (strpos($status, 'Leave') !== false) {
                                                                $statusClass = 'badge-leave';
                                                            } elseif (strpos($status, 'Day Off') !== false) {
                                                                $statusClass = 'badge-dayoff';
                                                            } elseif (strpos($status, 'Absent') !== false) {
                                                                $statusClass = 'badge-absent';
                                                            } elseif (strpos($status, 'No Record') !== false) {
                                                                $statusClass = 'badge-norecord';
                                                            }
                                                            ?>
                                                            <span class="badgeT <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="pagination-info">
                                    Showing <?php echo min($startIndex + 1, $totalRecords); ?> to <?php echo min($startIndex + $entriesPerPage, $totalRecords); ?> of <?php echo $totalRecords; ?> entries
                                </div>

                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <!-- Previous button -->
                                        <li class="page-item <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?month=<?php echo $selectedMonth; ?>&year=<?php echo $selectedYear; ?>&search_name=<?php echo urlencode($searchName); ?>&page=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>

                                        <!-- Page numbers -->
                                        <?php
                                        $startPage = max(1, $currentPage - 2);
                                        $endPage = min($totalPages, $startPage + 4);
                                        if ($endPage - $startPage < 4 && $totalPages > 5) {
                                            $startPage = max(1, $endPage - 4);
                                        }

                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?month=<?php echo $selectedMonth; ?>&year=<?php echo $selectedYear; ?>&search_name=<?php echo urlencode($searchName); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Next button -->
                                        <li class="page-item <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?month=<?php echo $selectedMonth; ?>&year=<?php echo $selectedYear; ?>&search_name=<?php echo urlencode($searchName); ?>&page=<?php echo $currentPage + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                <?php endif; ?>
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
    <script>
        // Function to export table data to CSV
        function exportToCSV() {
            const table = document.getElementById('timesheet');
            let csv = [];
            const rows = table.querySelectorAll('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');

                for (let j = 0; j < cols.length; j++) {
                    // Get the text content and replace any commas with spaces to avoid CSV issues
                    let data = cols[j].textContent.replace(/,/g, ' ');
                    // Remove any double quotes to avoid CSV issues
                    data = data.replace(/"/g, '');
                    // Wrap the data in double quotes
                    row.push('"' + data + '"');
                }

                csv.push(row.join(','));
            }

            // Create a CSV file and download it
            const csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            const downloadLink = document.createElement('a');

            // Create a download link
            downloadLink.download = 'timesheet_' + new Date().toISOString().slice(0, 10) + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';

            // Add the link to the DOM and trigger the download
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        function toggleCalendar() {
            const calendarContainer = document.getElementById('calendarContainer');
            if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
                calendarContainer.style.display = 'block';
            } else {
                calendarContainer.style.display = 'none';
            }
        }

        // Function to filter table rows based on search input
        function filterTable() {
            const input = document.getElementById('search_name');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('timesheet');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let matchFound = false;

                for (let j = 0; j < cells.length; j++) {
                    if (cells[j]) {
                        const cellText = cells[j].textContent || cells[j].innerText;
                        if (cellText.toLowerCase().indexOf(filter) > -1) {
                            matchFound = true;
                            break;
                        }
                    }
                }

                if (matchFound) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }

        // Add event listener to search input for real-time filtering
        document.getElementById('search_name').addEventListener('input', filterTable);
    </script>
</body>
</html>
