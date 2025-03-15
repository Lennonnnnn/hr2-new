<?php
header('Content-Type: application/json'); // Ensure the response is JSON
date_default_timezone_set('Asia/Manila'); // Set the time zone to Philippine time

include '../../db/db_conn.php';

// Check if necessary parameters are provided
if (!isset($_GET['e_id'], $_GET['month'], $_GET['year'])) {
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$employee_id = $_GET['e_id'];
$month = $_GET['month'];
$year = $_GET['year'];

try {
    // Get the total number of days in the requested month
    $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $attendanceRecords = array_fill(1, $totalDaysInMonth, 'Absent'); // Fill array with 'Absent' for all days

    // Mark Sundays as "Day Off"
    for ($day = 1; $day <= $totalDaysInMonth; $day++) {
        $date = "$year-$month-$day";
        if (date('w', strtotime($date)) === '0') { // Check if the day is a Sunday
            $attendanceRecords[$day] = 'Day Off';
        }
    }

    // Fetch non-working days (holidays) for the given month and year
    $holidaySql = "SELECT DAY(date) AS day, description FROM non_working_days 
                   WHERE MONTH(date) = ? AND YEAR(date) = ?";
    $holidayStmt = $conn->prepare($holidaySql);
    $holidayStmt->bind_param("ii", $month, $year);
    $holidayStmt->execute();
    $holidayResult = $holidayStmt->get_result();

    $nonWorkingDays = [];
    while ($row = $holidayResult->fetch_assoc()) {
        $nonWorkingDays[$row['day']] = $row['description']; // Store day as key and holiday name as value
    }
    $holidayStmt->close();

    // Mark holidays in the attendance records (override Sundays if necessary)
    foreach ($nonWorkingDays as $day => $holidayName) {
        if ($day >= 1 && $day <= $totalDaysInMonth) {
            $attendanceRecords[$day] = [
                'status' => 'Holiday',
                'description' => $holidayName
            ];
        }
    }

    // Fetch leave data for the employee in the given month and year
    $leaveSql = "SELECT start_date, end_date FROM leave_requests WHERE e_id = ? AND status = 'Approved' AND (YEAR(start_date) = ? AND MONTH(start_date) = ?)";
    $leaveStmt = $conn->prepare($leaveSql);
    $leaveStmt->bind_param("iii", $employee_id, $year, $month);
    $leaveStmt->execute();
    $leaveResult = $leaveStmt->get_result();

    // Mark leave days in the attendance records (override Sundays and holidays if necessary)
    while ($leaveRow = $leaveResult->fetch_assoc()) {
        $startDate = new DateTime($leaveRow['start_date']);
        $endDate = new DateTime($leaveRow['end_date']);

        // Iterate through each day in the leave period
        for ($date = $startDate; $date <= $endDate; $date->modify('+1 day')) {
            $day = (int)$date->format('d');
            if ($day >= 1 && $day <= $totalDaysInMonth) {
                $attendanceRecords[$day] = 'On Leave';
            }
        }
    }
    $leaveStmt->close();

    // If 'day' parameter is provided, fetch attendance for a specific day
    if (isset($_GET['day'])) {
        $day = $_GET['day'];

        // Check if the day is a holiday
        if (array_key_exists($day, $nonWorkingDays)) {
            echo json_encode([
                'status' => 'Holiday',
                'holiday_name' => $nonWorkingDays[$day] // Include the holiday name
            ]);
            exit;
        }

        // Check if the day is a leave day
        $leaveSql = "SELECT start_date, end_date FROM leave_requests WHERE e_id = ? AND status = 'Approved' AND ? BETWEEN DAY(start_date) AND DAY(end_date) AND MONTH(start_date) = ? AND YEAR(start_date) = ?";
        $leaveStmt = $conn->prepare($leaveSql);
        $leaveStmt->bind_param("iiii", $employee_id, $day, $month, $year);
        $leaveStmt->execute();
        $leaveResult = $leaveStmt->get_result();

        if ($leaveResult->num_rows > 0) {
            echo json_encode(['status' => 'On Leave']);
            exit;
        }

        // Fetch attendance details for the given day
        $sql = "SELECT time_in, time_out FROM attendance_log WHERE e_id = ? AND DAY(attendance_date) = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $employee_id, $day, $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();

        $attendanceDetails = [];
        if ($row = $result->fetch_assoc()) {
            $timeIn = $row['time_in'];
            $timeOut = $row['time_out'];

            if ($timeIn === null || $timeOut === null) {
                $attendanceDetails['status'] = 'Absent';
            } else {
                // Calculate worked minutes
                $start = new DateTime($timeIn);
                $end = new DateTime($timeOut);
                $workedMinutes = $end->diff($start)->h * 60 + $end->diff($start)->i;

                // Determine status
                if (($timeIn <= '08:10:00' && $timeOut >= '12:00:00' && $timeOut <= '13:00:00') || // Morning shift
                    ($timeIn >= '13:00:00' && $timeIn <= '17:00:00' && $timeOut >= '17:00:00')) { // Afternoon shift
                    $attendanceDetails['status'] = 'Half-Day';
                } elseif ($workedMinutes < 240) { // Less than 4 hours
                    $attendanceDetails['status'] = 'Half-Day';
                } elseif ($timeOut > '18:00:00') { // Overtime threshold changed to 6:00 PM
                    $attendanceDetails['status'] = 'Overtime';
                } elseif ($timeOut >= '14:00:00' && $timeOut < '17:00:00') { // Early Out (1 to 3 hours before operation end time)
                    $attendanceDetails['status'] = 'Early Out';
                } elseif ($timeIn > '08:10:00') {
                    $attendanceDetails['status'] = 'Late';
                } else {
                    $attendanceDetails['status'] = 'Present';
                }
            }

            $attendanceDetails['time_in'] = $timeIn ? $timeIn : 'No data';
            $attendanceDetails['time_out'] = $timeOut ? $timeOut : 'No data';
        }

        if (empty($attendanceDetails)) {
            echo json_encode(['message' => 'No attendance details found for the given day']);
        } else {
            echo json_encode($attendanceDetails);
        }

        $stmt->close();
    } else {
        // Fetch attendance for the entire month
        $sql = "SELECT DAY(attendance_date) AS day, time_in, time_out FROM attendance_log WHERE e_id = ? AND MONTH(attendance_date) = ? AND YEAR(attendance_date) = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $employee_id, $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();

        // Update attendanceRecords based on existing data
        while ($row = $result->fetch_assoc()) {
            $day = (int)$row['day'];
            $timeIn = $row['time_in'];
            $timeOut = $row['time_out'];

            if ($timeIn === null || $timeOut === null) {
                $attendanceRecords[$day] = 'Absent';
            } else {
                // Calculate worked minutes
                $start = new DateTime($timeIn);
                $end = new DateTime($timeOut);
                $workedMinutes = $end->diff($start)->h * 60 + $end->diff($start)->i;

                // Determine status
                if (($timeIn <= '08:10:00' && $timeOut >= '12:00:00' && $timeOut <= '13:00:00') || // Morning shift
                    ($timeIn >= '13:00:00' && $timeIn <= '17:00:00' && $timeOut >= '17:00:00')) { // Afternoon shift
                    $attendanceRecords[$day] = 'Half-Day';
                } elseif ($workedMinutes < 240) { // Less than 4 hours
                    $attendanceRecords[$day] = 'Half-Day';
                } elseif ($timeOut >= '14:00:00' && $timeOut < '17:00:00') { // Early Out (1 to 3 hours before operation end time)
                    $attendanceRecords[$day] = 'Early Out';
                } elseif ($timeIn > '08:10:00') {
                    $attendanceRecords[$day] = 'Late';
                } else {
                    $attendanceRecords[$day] = 'Present';
                }
            }
        }

        echo json_encode($attendanceRecords);
        $stmt->close();
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>