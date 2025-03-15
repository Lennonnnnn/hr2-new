<?php
// Start the session
session_start();

// Include database connection
include '../../db/db_conn.php';

// Ensure session variable is set
if (!isset($_SESSION['e_id'])) {
    die("Error: Employee ID is not set in the session.");
}

// Fetch user info
$employeeId = $_SESSION['e_id'];

// Correct SQL query
$sql = "SELECT e_id, firstname, middlename, lastname, role, position, department, phone_number
        FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();

// Query for leave requests specific to the logged-in employee
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
$fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : '';
$toDate = isset($_GET['toDate']) ? $_GET['toDate'] : '';
$statusFilter = isset($_GET['statusFilter']) ? $_GET['statusFilter'] : '';
$timeFrame = isset($_GET['timeFrame']) ? $_GET['timeFrame'] : '';

// Adjust SQL query to always show the latest history at the top
$sql = "
    SELECT lr.*, e.firstname, e.lastname, s.firstname AS supervisor_firstname, s.lastname AS supervisor_lastname
    FROM leave_requests lr
    JOIN employee_register e ON lr.e_id = e.e_id
    LEFT JOIN employee_register s ON lr.supervisor_id = s.e_id
    WHERE lr.e_id = ?";

if ($searchTerm) {
    $sql .= " AND (e.firstname LIKE ? OR e.lastname LIKE ? OR lr.e_id LIKE ?)";
}
if ($fromDate) {
    $sql .= " AND lr.start_date >= ?";
}
if ($toDate) {
    $sql .= " AND lr.end_date <= ?";
}
if ($statusFilter) {
    $sql .= " AND lr.status = ?";
}
if ($timeFrame) {
    if ($timeFrame == 'day') {
        $sql .= " AND lr.created_at >= CURDATE()";
    } elseif ($timeFrame == 'week') {
        $sql .= " AND lr.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
    } elseif ($timeFrame == 'month') {
        $sql .= " AND lr.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
    }
}

$sql .= " ORDER BY lr.created_at DESC"; // Ensure latest history is at the top

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing the query: " . $conn->error);
}

$bindParams = [$employeeId];
$bindTypes = "i";

if ($searchTerm) {
    $searchTerm = "%$searchTerm%";
    $bindParams[] = $searchTerm;
    $bindParams[] = $searchTerm;
    $bindParams[] = $searchTerm;
    $bindTypes .= "sss";
}
if ($fromDate) {
    $bindParams[] = $fromDate;
    $bindTypes .= "s";
}
if ($toDate) {
    $bindParams[] = $toDate;
    $bindTypes .= "s";
}
if ($statusFilter) {
    $bindParams[] = $statusFilter;
    $bindTypes .= "s";
}

$stmt->bind_param($bindTypes, ...$bindParams);
if (!$stmt->execute()) {
    die("Error executing the query: " . $stmt->error);
}

$result = $stmt->get_result();

// Calculate total leave days excluding Sundays and holidays
function calculateLeaveDays($start_date, $end_date) {
    $leave_days = 0;
    $current_date = strtotime($start_date);
    $end_date = strtotime($end_date);

    while ($current_date <= $end_date) {
        $day_of_week = date('w', $current_date);
        if ($day_of_week != 0) { // Exclude Sundays
            $leave_days++;
        }
        $current_date = strtotime('+1 day', $current_date);
    }
    return $leave_days;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave History</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #000000;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1200px;
        }

        .card {
            background-color: #000000;
            border: 2px solid #555555; /* Wider border */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .card-body {
            background-color: #000000;
            padding: 20px;
            color: #ffffff;
        }

        .form-control, .form-select {
            background-color: #000000;
            border: 2px solid #555555; /* Wider border */
            color: #ffffff;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            background-color: #000000;
            border-color: #777777;
            color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.1);
        }

        .form-control::placeholder {
            color: #aaaaaa;
        }

        .btn-dark {
            background-color: #000000;
            border: 2px solid #555555; /* Wider border */
            color: #ffffff;
            transition: all 0.3s;
        }

        .btn-dark:hover {
            background-color: #222222;
            border-color: #777777;
        }

        .btn-primary {
            background-color: #000000;
            border: 2px solid #555555; /* Wider border */
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #222222;
            border-color: #777777;
        }

        .btn-warning {
            background-color: #333333;
            border: 2px solid #666666; /* Wider border */
            color: #ffffff;
        }

        .btn-warning:hover {
            background-color: #444444;
            border-color: #888888;
            color: #ffffff;
        }

        .btn-danger {
            background-color: #333333;
            border: 2px solid #666666; /* Wider border */
            color: #ffffff;
        }

        .btn-danger:hover {
            background-color: #444444;
            border-color: #888888;
            color: #ffffff;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            color: #ffffff;
        }

        .table th, .table td {
            border: 2px solid #555555; /* Wider border */
            padding: 12px 15px;
            vertical-align: middle;
            color: #ffffff;
        }

        .table th {
            background-color: #111111;
            font-weight: 600;
            border-bottom: 3px solid #666666; /* Even wider bottom border for headers */
            color: #ffffff;
        }

        .table-dark {
            background-color: #000000;
            color: #ffffff;
        }

        .table-dark th {
            background-color: #111111;
            color: #ffffff;
            border-color: #555555;
        }

        .table-dark td {
            background-color: #000000;
            border-color: #555555;
            color: #ffffff;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            background-color: #111111;
            color: #ffffff;
        }

        .table-striped>tbody>tr:nth-of-type(even)>* {
            background-color: #000000;
            color: #ffffff;
        }

        .table-hover tbody tr:hover td {
            background-color: #222222 !important;
            color: #ffffff;
        }

        .text-success {
            color: #4caf50 !important;
        }

        .text-danger {
            color: #f44336 !important;
        }

        .text-warning {
            color: #ffc107 !important;
        }

        .text-primary {
            color: #2196f3 !important;
        }

        .text-muted {
            color: #aaaaaa !important;
        }

        .modal-content {
            background-color: #000000;
            color: #ffffff;
            border: 2px solid #555555; /* Wider border */
        }

        .modal-header, .modal-footer {
            border-color: #555555;
            border-width: 2px; /* Wider border */
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .page-header {
            border-bottom: 2px solid #555555; /* Wider border */
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 4px;
            font-weight: 500;
            background-color: #333333;
            color: #ffffff;
            border: 1px solid #555555;
        }

        .btn-group {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .action-buttons .btn {
            margin: 0 2px;
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        /* Status badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-block;
            min-width: 100px;
            text-align: center;
            background-color: #000000;
            border: 2px solid #555555; /* Wider border */
        }

        .status-approved {
            border-color: #4caf50;
            color: #4caf50;
        }

        .status-denied {
            border-color: #f44336;
            color: #f44336;
        }

        .status-pending {
            border-color: #ffc107;
            color: #ffc107;
        }

        .status-supervisor {
            border-color: #2196f3;
            color: #2196f3;
        }

        /* Filter section styling */
        .filters-section {
            background-color: #000000;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: 2px solid #555555; /* Wider border */
        }

        /* Header styling */
        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0;
            color: #ffffff;
        }

        /* Table container with border */
        .table-container {
            border: 3px solid #555555; /* Even wider border for table container */
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        /* Input group styling */
        .input-group-text {
            background-color: #111111;
            border: 2px solid #555555; /* Wider border */
            color: #ffffff;
        }

        /* Separator line */
        .separator {
            height: 2px; /* Thicker separator */
            background-color: #555555;
            margin: 15px 0;
        }

        /* Employee info styling */
        .employee-info {
            padding: 15px;
            border: 3px solid #555555; /* Wider border */
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: #000000;
        }

        .employee-info h5 {
            color: #ffffff;
            border-bottom: 2px solid #555555; /* Wider border */
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .employee-info p {
            color: #ffffff;
        }

        .employee-info strong {
            color: #cccccc;
        }

        /* Cell padding for better readability */
        .table td, .table th {
            padding: 15px; /* More padding in cells */
        }

        /* Highlight current row */
        .table tbody tr:hover {
            outline: 2px solid #777777; /* Outline for hovered row */
        }

        /* Section headers */
        .section-header {
            border-bottom: 2px solid #555555; /* Wider border */
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .table-responsive {
                border: 2px solid #555555; /* Wider border */
                border-radius: 8px;
                overflow: hidden;
            }

            .action-buttons {
                display: flex;
                flex-direction: column;
            }

            .action-buttons .btn {
                margin: 2px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 page-header">
            <h1 class="page-title">
                <i class="fas fa-history me-2"></i> Leave History
            </h1>
            <div>
                <button class="btn btn-dark me-2" onclick="window.location.href='leave_file.php'">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </button>
                <form method="POST" action="export_leave_history.php" style="display:inline;">
                    <input type="hidden" name="searchTerm" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <input type="hidden" name="fromDate" value="<?php echo htmlspecialchars($fromDate); ?>">
                    <input type="hidden" name="toDate" value="<?php echo htmlspecialchars($toDate); ?>">
                    <input type="hidden" name="statusFilter" value="<?php echo htmlspecialchars($statusFilter); ?>">
                    <input type="hidden" name="timeFrame" value="<?php echo htmlspecialchars($timeFrame); ?>">
                    <button type="submit" class="btn btn-dark">
                        <i class="fas fa-file-export me-1"></i> Export
                    </button>
                </form>
            </div>
        </div>

        <!-- Employee Info Card -->
        <div class="employee-info">
            <h5 class="section-header"><i class="fas fa-user me-2"></i>Employee Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($employeeInfo['firstname'] . ' ' . $employeeInfo['lastname']); ?></p>
                    <p class="mb-2"><strong>ID:</strong> <?php echo htmlspecialchars($employeeInfo['e_id']); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2"><strong>Position:</strong> <?php echo htmlspecialchars($employeeInfo['position']); ?></p>
                    <p class="mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($employeeInfo['department']); ?></p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <h5 class="section-header"><i class="fas fa-filter me-2"></i>Filter Leave Records</h5>
            <form class="row g-3" method="GET" action="">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" name="searchTerm" placeholder="Search by Name/ID" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <input type="date" class="form-control" name="fromDate" placeholder="From Date" value="<?php echo htmlspecialchars($fromDate); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <input type="date" class="form-control" name="toDate" placeholder="To Date" value="<?php echo htmlspecialchars($toDate); ?>">
                    </div>
                </div>

                <div class="separator w-100"></div>

                <div class="col-md-4">
                    <select class="form-select" name="statusFilter">
                        <option value="">Filter by Status</option>
                        <option value="Approved" <?php if ($statusFilter == 'Approved') echo 'selected'; ?>>Approved</option>
                        <option value="Pending" <?php if ($statusFilter == 'Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Denied" <?php if ($statusFilter == 'Denied') echo 'selected'; ?>>Denied</option>
                        <option value="Supervisor Approved" <?php if ($statusFilter == 'Supervisor Approved') echo 'selected'; ?>>Supervisor Approved</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="timeFrame">
                        <option value="">Filter by Time Frame</option>
                        <option value="day" <?php if ($timeFrame == 'day') echo 'selected'; ?>>Last Day</option>
                        <option value="week" <?php if ($timeFrame == 'week') echo 'selected'; ?>>Last Week</option>
                        <option value="month" <?php if ($timeFrame == 'month') echo 'selected'; ?>>Last Month</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-dark w-100">
                        <i class="fas fa-filter me-1"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Leave History Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar-check me-1"></i> Date Applied</th>
                            <th><i class="fas fa-id-card me-1"></i> Employee ID</th>
                            <th><i class="fas fa-user me-1"></i> Employee Name</th>
                            <th><i class="fas fa-calendar-day me-1"></i> Leave Dates</th>
                            <th><i class="fas fa-tag me-1"></i> Leave Type</th>
                            <th><i class="fas fa-calculator me-1"></i> Total Days</th>
                            <th><i class="fas fa-info-circle me-1"></i> Status</th>
                            <th><i class="fas fa-user-tie me-1"></i> Supervisor</th>
                            <th><i class="fas fa-cogs me-1"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                $leave_days = calculateLeaveDays($row['start_date'], $row['end_date']);

                                // Determine status class
                                $statusClass = '';
                                $status = $row['status'];
                                if ($status === 'Approved') {
                                    $statusClass = 'status-approved';
                                } elseif ($status === 'Denied') {
                                    $statusClass = 'status-denied';
                                } elseif ($status === 'Pending') {
                                    $statusClass = 'status-pending';
                                } elseif ($status === 'Supervisor Approved' || $status === 'Supervisor Denied') {
                                    $statusClass = 'status-supervisor';
                                }
                            ?>
                            <tr>
                                <td>
                                    <?php
                                        if (isset($row['created_at'])) {
                                            echo '<span class="d-block">' . htmlspecialchars(date("F j, Y", strtotime($row['created_at']))) . '</span>';
                                            echo '<small class="text-muted">' . htmlspecialchars(date("g:i A", strtotime($row['created_at']))) . '</small>';
                                        } else {
                                            echo "Not Available";
                                        }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['e_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                                <td>
                                    <span class="d-block"><?php echo htmlspecialchars(date("F j, Y", strtotime($row['start_date']))); ?></span>
                                    <span class="d-block text-muted">to</span>
                                    <span class="d-block"><?php echo htmlspecialchars(date("F j, Y", strtotime($row['end_date']))); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                                <td>
                                    <span class="badge"><?php echo htmlspecialchars($leave_days); ?> day<?php echo $leave_days > 1 ? 's' : ''; ?></span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $supervisorName = htmlspecialchars($row['supervisor_firstname'] . ' ' . $row['supervisor_lastname']);
                                    if ($supervisorName != ' ' && ($status === 'Supervisor Approved' || $status === 'Supervisor Denied' || $status === 'Denied' || $status === 'Approved')) {
                                        echo $supervisorName;
                                    } else {
                                        echo '<span class="text-muted">N/A</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="d-flex action-buttons">
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $row['id']; ?>" <?php if ($status !== 'Pending') echo 'disabled'; ?>>
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                                <p class="mb-0">No leave records found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Confirm Cancellation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this leave request? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> No, Keep It
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-check me-1"></i> Yes, Cancel Leave
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store the ID of the item to be deleted
        let itemToDelete = null;

        // Set up event listener for delete modal
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    itemToDelete = button.getAttribute('data-id');
                });
            }
        });

        // View Button Function
        function viewItem(id) {
            alert("View button clicked for item ID: " + id);
            // You can replace this with the code for showing more details, redirecting to a view page, etc.
        }

        // Edit Button Function
        function editItem(id) {
            alert("Edit button clicked for item ID: " + id);
            // You can replace this with the code for opening an edit form or redirecting to an edit page, etc.
        }

        // Delete Button Function
        function confirmDelete() {
            if (itemToDelete) {
                alert("Cancelling leave request ID: " + itemToDelete);
                // You can replace this with code to actually delete the item, like sending a request to a server.
                // After successful deletion, you might want to reload the page or update the UI
                // window.location.reload();
            }

            // Close the modal
            const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (deleteModal) {
                deleteModal.hide();
            }
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
