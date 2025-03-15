<?php
date_default_timezone_set('Asia/Manila');
include 'db/db_conn.php';

if (isset($_POST['employeeId'])) {
    $employeeId = $_POST['employeeId'];
    $currentDate = date('Y-m-d');
    $currentTime = date('Y-m-d H:i:s');

    // Check existing attendance for today
    $query = "SELECT * FROM attendance_log WHERE e_id = ? AND attendance_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $employeeId, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Existing record - handle time-out
        $attendance = $result->fetch_assoc();
        if ($attendance['time_out'] === null) {
            $updateQuery = "UPDATE attendance_log SET time_out = ?, status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);

            $timeIn = $attendance['time_in'] ? new DateTime($attendance['time_in']) : null;
            $timeOut = new DateTime($currentTime);
            $status = 'Present';

            // Calculate status
            if ($timeIn) {
                $lateThreshold = new DateTime($currentDate . ' 08:00:00');
                if ($timeIn > $lateThreshold) $status = 'Late';

                $workedHours = $timeOut->diff($timeIn)->h;
                if ($workedHours < 6) $status = 'Half-Day';
            }

            $overtimeThreshold = new DateTime($currentDate . ' 17:00:00');
            if ($timeOut > $overtimeThreshold) $status = 'Overtime';

            $updateStmt->bind_param("ssi", $currentTime, $status, $attendance['id']);
            $updateStmt->execute();
            echo json_encode(["success" => true, "message" => "Time-out recorded.", "status" => $status]);
        } else {
            echo json_encode(["success" => false, "message" => "Attendance already logged."]);
        }
    } else {
        // No existing record
        $absentThreshold = new DateTime($currentDate . ' 13:00:00');
        $currentDateTime = new DateTime($currentTime);

        if ($currentDateTime > $absentThreshold) {
            // Mark as Absent (without time_in/time_out)
            $status = 'Absent';
            $insertQuery = "INSERT INTO attendance_log (e_id, attendance_date, status) VALUES (?, ?, ?)"; // Removed NULL columns
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iss", $employeeId, $currentDate, $status);

            if ($insertStmt->execute()) {
                echo json_encode(["success" => true, "message" => "Marked as Absent.", "status" => $status]);
            } else {
                error_log("Absent Insert Error: " . $insertStmt->error); // Error logging
                echo json_encode(["success" => false, "message" => "Database error. Contact admin."]);
            }
        } else {
            // Time-in before 1 PM
            $insertQuery = "INSERT INTO attendance_log (e_id, time_in, attendance_date, status) VALUES (?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);

            $timeIn = new DateTime($currentTime);
            $lateThreshold = new DateTime($currentDate . ' 08:00:00');
            $status = ($timeIn > $lateThreshold) ? 'Late' : 'Present';

            $insertStmt->bind_param("isss", $employeeId, $currentTime, $currentDate, $status);
            
            if ($insertStmt->execute()) {
                echo json_encode(["success" => true, "message" => "Time-in recorded.", "status" => $status]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to log time-in."]);
            }
        }
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "No employee ID."]);
}
?>