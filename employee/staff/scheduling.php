<?php
session_start(); // Start the session

// Include database connection
include '../../db/db_conn.php';

// Check if the user is logged in
if (!isset($_SESSION['e_id'])) {
    header("Location: ../../login.php"); // Redirect to login if not logged in
    exit();
}

// Number of records to show per page
$recordsPerPage = 10;

// Get the current page or set a default
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Fetch logged-in employee info
$employeeId = $_SESSION['e_id'];
$sql = "SELECT e_id, firstname, middlename, lastname, birthdate, email, role, position, department, phone_number, address, pfp FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();
$stmt->close();

// Set the profile picture, default if not provided
$profilePicture = !empty($employeeInfo['pfp']) ? $employeeInfo['pfp'] : '../../img/defaultpfp.png';

// Fetch total number of employees for pagination
$totalQuery = "SELECT COUNT(*) as total FROM employee_register";
$totalResult = $conn->query($totalQuery);
$totalEmployees = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalEmployees / $recordsPerPage);

// Fetch all employees with pagination
$query = "SELECT * FROM employee_register LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $recordsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Schedule</title>
    <link href='../../css/styles.css' rel='stylesheet' />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --dark-bg: #121212;
            --darker-bg: #0a0a0a;
            --card-bg: #1e1e1e;
            --border-color: #333;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --accent-color: #8c8c8c;
            --primary-color: #6c757d;
            --primary-hover: #5a6268;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }

        body {
            background-color: var(--dark-bg);
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            min-height: 100vh;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background-color: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            font-weight: 600;
        }

        .table {
            margin-bottom: 0;
            color: #ffffff;
        }

        .table th {
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 15px;
            border-color: var(--border-color);
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            border-color: var(--border-color);
        }

        .table tbody tr {
            background-color: var(--card-bg);
        }

        .table tbody tr:nth-child(odd) {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .badge {
            font-size: 12px;
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 6px;
        }

        .badge-day {
            background-color: #6c757d;
            color: white;
        }

        .badge-night {
            background-color: #343a40;
            color: white;
        }

        .btn-edit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-edit:hover {
            background-color: var(--primary-hover);
            color: white;
        }

        /* Modal styling */
        .modal-content {
            background-color: var(--card-bg);
            color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 20px;
        }

        .modal-title {
            font-weight: 600;
            color: #ffffff;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 20px;
        }

        .form-label {
            color: #ffffff;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 10px 15px;
        }

        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.25);
        }

        .form-select option {
            background-color: var(--card-bg);
            color: #ffffff;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: #343a40;
            border-color: #343a40;
        }

        .btn-secondary:hover {
            background-color: #23272b;
            border-color: #23272b;
        }

        .btn-close {
            color: #ffffff;
            opacity: 0.8;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 18px;
            margin-bottom: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-responsive {
                border-radius: 10px;
                overflow: hidden;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--card-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #777;
        }

        /* Change the placeholder color to white */
        #searchInput::placeholder {
            color: white;
            opacity: 1; /* Ensure full visibility */
        }

        /* Optional: Ensure the input text color is also white */
        #searchInput {
            color: white;
        }

        /* Add styles for pagination */
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }

        .pagination .page-item .page-link {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .pagination .page-item.disabled .page-link {
            color: var(--text-secondary);
            pointer-events: none;
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }

        /* Sidebar and Content Layout */
        #layoutSidenav {
            display: flex;
            min-height: 100vh;
        }

        #layoutSidenav_nav {
            width: 250px; /* Fixed width for the sidebar */
            height: 100vh; /* Full height */
            position: fixed; /* Fixed position */
            top: 0;
            left: 0;
            z-index: 1000; /* Ensure it's above other content */
            overflow-y: auto; /* Enable scrolling if content is long */
            background-color: var(--darker-bg); /* Dark background */
            color: var(--text-primary); /* Light text */
            transition: left 0.3s; /* Smooth transition for sidebar toggle */
        }

        #layoutSidenav_content {
            margin-left: 250px; /* Offset content by sidebar width */
            flex-grow: 1; /* Allow content to take remaining space */
            padding: 20px; /* Add some padding */
            transition: margin-left 0.3s; /* Smooth transition for sidebar toggle */
        }

        /* Adjustments for smaller screens */
        @media (max-width: 768px) {
            #layoutSidenav_nav {
                left: -250px; /* Hide sidebar off-screen */
            }

            #layoutSidenav_content {
                margin-left: 0; /* No offset for content */
            }

            #layoutSidenav_nav.active {
                left: 0; /* Show sidebar when active */
            }

            #layoutSidenav_content.active {
                margin-left: 250px; /* Offset content when sidebar is active */
            }
        }
    </style>
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main id="main-content">
                <div class="container-fluid position-relative px-4">
                    <div class="">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="page-title">
                                    <i class="fas fa-calendar-alt me-2 text-light"></i>Employee Schedule
                                </h1>
                            </div>
                            <div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkEditModal">
                                    <i class="fas fa-users me-2"></i>Bulk Edit Schedules
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center text-white">
                        <div>
                            <i class="fas fa-table me-2 text-white"></i>Schedule Overview
                        </div>
                        <div class="d-flex gap-2">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search employee..." id="searchInput">
                                <button class="btn btn-outline-secondary" type="button" id="searchButton">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <button class="btn btn-outline-secondary" id="refreshButton">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="scheduleTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee Name</th>
                                    <th>Shift Type</th>
                                    <th>Schedule Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($employee = $result->fetch_assoc()): ?>
                                        <?php
                                        // Fetch the employee's schedule
                                        $scheduleQuery = "SELECT * FROM employee_schedule WHERE employee_id = ? ORDER BY schedule_date DESC LIMIT 1";
                                        $stmt = $conn->prepare($scheduleQuery);
                                        $stmt->bind_param('i', $employee['e_id']);
                                        $stmt->execute();
                                        $scheduleResult = $stmt->get_result();
                                        $schedule = $scheduleResult->fetch_assoc();
                                        $stmt->close();

                                        // Determine shift badge class
                                        $shiftType = $schedule['shift_type'] ?? 'day';
                                        $badgeClass = ($shiftType == 'night') ? 'badge-night' : 'badge-day';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($employee['e_id']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle me-2 bg-secondary">
                                                        <?php echo strtoupper(substr($employee['firstname'], 0, 1)); ?>
                                                    </div>
                                                    <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($schedule['shift_type'] ?? 'N/A')); ?> Shift
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($schedule['schedule_date'] ?? 'Not Scheduled'); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['start_time'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['end_time'] ?? 'N/A'); ?></td>
                                            <td>
                                                <button class="btn btn-edit"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editModal"
                                                        data-employee-id="<?php echo $employee['e_id']; ?>"
                                                        data-employee-name="<?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-users-slash"></i>
                                                <p>No employees found in the system.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?php echo $currentPage == 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $currentPage == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Edit Schedule Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Employee Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editForm" method="POST" action="../../employee/supervisor/updateSchedule.php">
                        <div class="modal-body">
                            <input type="hidden" id="editEmployeeId" name="employee_id">

                            <div class="mb-3">
                                <label class="form-label">Employee:</label>
                                <div class="employee-name fw-bold" id="employeeName"></div>
                            </div>

                            <div class="mb-3">
                                <label for="editShiftType" class="form-label">Shift Type:</label>
                                <select class="form-select" id="editShiftType" name="shift_type" required>
                                    <option value="day">Day Shift</option>
                                    <option value="night">Night Shift</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="editScheduleDate" class="form-label">Schedule Date:</label>
                                <input type="date" class="form-control" id="editScheduleDate" name="schedule_date" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="editStartTime" class="form-label">Start Time:</label>
                                    <input type="time" class="form-control" id="editStartTime" name="start_time" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="editEndTime" class="form-label">End Time:</label>
                                    <input type="time" class="form-control" id="editEndTime" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bulk Edit Modal -->
        <div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkEditModalLabel">Bulk Edit Employee Schedules</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="bulkEditForm" method="POST" action="../../employee/supervisor/bulkUpdateSchedule.php">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Select Employees:</label>
                                <div class="employee-select-container border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    <?php
                                    // Reset the result pointer
                                    $result->data_seek(0);
                                    while ($employee = $result->fetch_assoc()):
                                    ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="employee_ids[]" value="<?php echo $employee['e_id']; ?>" id="employee<?php echo $employee['e_id']; ?>">
                                        <label class="form-check-label" for="employee<?php echo $employee['e_id']; ?>">
                                            <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>
                                        </label>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="bulkShiftType" class="form-label">Shift Type:</label>
                                <select class="form-select" id="bulkShiftType" name="shift_type" required>
                                    <option value="day">Day Shift</option>
                                    <option value="night">Night Shift</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="bulkScheduleDate" class="form-label">Schedule Date:</label>
                                    <input type="date" class="form-control" id="bulkScheduleDate" name="schedule_date" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="bulkStartTime" class="form-label">Start Time:</label>
                                    <input type="time" class="form-control" id="bulkStartTime" name="start_time" required>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="bulkEndTime" class="form-label">End Time:</label>
                                    <input type="time" class="form-control" id="bulkEndTime" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Apply to Selected</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                <button type="button" class="close-btn" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to log out?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-submit" onclick="logout()">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

        <!-- Bootstrap JS and Popper.js -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <script>

function showLogoutModal() {
            const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
            logoutModal.show();
        }

        function logout() {
            // Perform the logout process
            window.location.href = '../../login.php';
        }
            // Initialize Bootstrap tooltips
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Handle edit modal
                const editModal = document.getElementById('editModal');
                if (editModal) {
                    editModal.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;
                        const employeeId = button.getAttribute('data-employee-id');
                        const employeeName = button.getAttribute('data-employee-name');

                        // Set employee name in the modal
                        document.getElementById('employeeName').textContent = employeeName;
                        document.getElementById('editEmployeeId').value = employeeId;

                        // Fetch the employee's schedule data
                        fetch(`/HR2/employee_db/supervisor/getSchedule.php?employee_id=${employeeId}`)
                            .then(response => response.json())
                            .then(data => {
                                document.getElementById('editShiftType').value = data.shift_type || 'day';
                                document.getElementById('editScheduleDate').value = data.schedule_date || '';
                                document.getElementById('editStartTime').value = data.start_time || '';
                                document.getElementById('editEndTime').value = data.end_time || '';
                            })
                            .catch(error => {
                                console.error('Error fetching schedule:', error);
                                // Show error toast or notification
                                alert('Failed to load employee schedule data. Please try again.');
                            });
                    });
                }

                // Search functionality
                const searchInput = document.getElementById('searchInput');
                const searchButton = document.getElementById('searchButton');
                const scheduleTable = document.getElementById('scheduleTable');

                function performSearch() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const rows = scheduleTable.querySelectorAll('tbody tr');

                    rows.forEach(row => {
                        const employeeName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                        if (employeeName.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }

                if (searchButton) {
                    searchButton.addEventListener('click', performSearch);
                }

                if (searchInput) {
                    searchInput.addEventListener('keyup', function(event) {
                        if (event.key === 'Enter') {
                            performSearch();
                        }
                    });
                }

                // Refresh button
                const refreshButton = document.getElementById('refreshButton');
                if (refreshButton) {
                    refreshButton.addEventListener('click', function() {
                        location.reload();
                    });
                }

                // Style for avatar circles
                const avatarCircles = document.querySelectorAll('.avatar-circle');
                avatarCircles.forEach(avatar => {
                    avatar.style.width = '30px';
                    avatar.style.height = '30px';
                    avatar.style.borderRadius = '50%';
                    avatar.style.display = 'flex';
                    avatar.style.alignItems = 'center';
                    avatar.style.justifyContent = 'center';
                    avatar.style.fontWeight = 'bold';
                    avatar.style.color = 'white';
                });
            });
        </script>
    </body>
</html>
