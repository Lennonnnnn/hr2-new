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

// Get filter parameters from POST request
$searchTerm = isset($_POST['searchTerm']) ? $_POST['searchTerm'] : '';
$fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '';
$toDate = isset($_POST['toDate']) ? $_POST['toDate'] : '';
$statusFilter = isset($_POST['statusFilter']) ? $_POST['statusFilter'] : '';
$timeFrame = isset($_POST['timeFrame']) ? $_POST['timeFrame'] : '';

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

// Set headers to force download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="leaveHitory.xls"');
header('Cache-Control: max-age=0');

// Start output buffering
ob_start();
?>

<table border="1">
    <thead>
        <tr>
            <th>Date Applied</th>
            <th>Employee ID</th>
            <th>Employee Name</th>
            <th>Leave Dates</th>
            <th>Leave Type</th>
            <th>Total Leave Days</th>
            <th>Status</th>
            <th>Supervisor</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php
                $leave_days = calculateLeaveDays($row['start_date'], $row['end_date']);
            ?>
            <tr>
                <td><?php echo htmlspecialchars(date("F j, Y", strtotime($row['created_at']))); ?></td>
                <td><?php echo htmlspecialchars($row['e_id']); ?></td>
                <td><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></td>
                <td><?php echo htmlspecialchars(date("F j, Y", strtotime($row['start_date']))) . ' - ' . htmlspecialchars(date("F j, Y", strtotime($row['end_date']))); ?></td>
                <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                <td><?php echo htmlspecialchars($leave_days); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['supervisor_firstname'] . ' ' . $row['supervisor_lastname']); ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" class="text-center">No records found</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<?php
// Flush the output buffer
ob_end_flush();
?>