<?php
session_start();
if (!isset($_SESSION['e_id']) || !isset($_SESSION['position']) || $_SESSION['position'] !== 'Contractual') {
    header("Location: ../../login.php");
    exit();
}

include '../../db/db_conn.php';

$employeeId = $_SESSION['e_id'];
$employeePosition = $_SESSION['position'];
// Fetch the average of the employee's evaluations
$sql = "SELECT 
            AVG(quality) AS avg_quality, 
            AVG(communication_skills) AS avg_communication_skills, 
            AVG(teamwork) AS avg_teamwork, 
            AVG(punctuality) AS avg_punctuality, 
            AVG(initiative) AS avg_initiative,
            COUNT(*) AS total_evaluations 
        FROM admin_evaluations 
        WHERE e_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

// Check if evaluations exist
if ($result->num_rows > 0) {
    $evaluation = $result->fetch_assoc();

    // Calculate the total average
    $totalAverage = (
        $evaluation['avg_quality'] +
        $evaluation['avg_communication_skills'] +
        $evaluation['avg_teamwork'] +
        $evaluation['avg_punctuality'] +
        $evaluation['avg_initiative']
    ) / 5;
} else {
    echo "No evaluations found.";
    exit;
}

// Fetch user info
$sql = "SELECT firstname, middlename, lastname, email, role, position, pfp FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Set the profile picture, default if not provided
$profilePicture = !empty($employeeInfo['pfp']) ? $employeeInfo['pfp'] : '../../img/defaultpfp.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Employee Dashboard | HR2</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <style>
        .collapse {
            transition: width 3s ease;
        }

        #searchInput.collapsing {
            width: 0;
        }

        #searchInput.collapse.show {
            width: 250px; /* Adjust the width as needed */
        }

        .search-bar {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        #search-results {
            position: absolute;
            width: 100%;
            z-index: 1000;
            display: none; /* Hidden by default */
        }

        #search-results a {
            text-decoration: none;
        }

        .form-control:focus + #search-results {
            display: block; /* Show the results when typing */
        }
        

          /* CSS for background blur */
  .blur-background {
    filter: blur(8px); /* You can adjust the blur strength */
    transition: filter 0.3s ease;
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
                                <h1 class="mb-4 text-light">Dashboard</h1>
                            </div>
                        </div>
                    </div>
                    <div class="container-fluid" id="calendarContainer" 
                        style="position: fixed; top: 7%; right: 40; z-index: 1050; 
                        max-width: 100%; display: none;">
                        <div class="row">
                            <div class="col-md-9 mx-auto">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                    </div> 
                    <div class="row mb-2">
                        <div class="col-md-6 mt-2 mb-2">
                            <div class="card bg-dark text-light" style="height: 500px;">
                                <div class="card-header text-light border-bottom border-1 border-secondary">
                                    <i class="fas fa-calendar-check me-1"></i> 
                                    <a class="text-light" href="">Attendance </a>
                                </div>
                                <div class="card-body overflow-auto" style="max-height: 400px;">
                                    <div class="d-flex justify-content-between align-items-start mb-0">
                                        <div>
                                            <h5 class="fw-bold d-inline">Today's Date: <a href="../../employee/supervisor/dashboard.php" 
                                            id="todaysDate" class="cursor-pointer text-decoration-none"><span id="todaysDateContent">Feb 21, 2025</span></a></h5>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold">Time in:</h5>
                                            <p class="text-light">08:11 AM</p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="dateFilter" class="form-label">Filter by Date:</label>
                                        <input type="date" class="form-control" id="dateFilter">
                                    </div>
                                    <hr>
                                    <div class="mb-0">
                                        <h3 class="mb-0" id="monthYearDisplay"></h3>
                                        <div class="row text-center fw-bold">
                                            <div class="col">Sun</div>
                                            <div class="col">Mon</div>
                                            <div class="col">Tue</div>
                                            <div class="col">Wed</div>
                                            <div class="col">Thu</div>
                                            <div class="col">Fri</div>
                                            <div class="col">Sat</div>
                                        </div>

                                        <!-- Calendar rows with attendance status -->
                                        <div id="ATTENDANCEcalendar" class="pt-3 text-light bg-black"></div>
                                    </div>
                                </div>
                                <div class="card-footer text-center d-flex justify-content-around">
                                    <button class="btn btn-primary d-flex align-items-center px-4 py-2 rounded-3" id="prevMonthBtn">
                                        <i class="bi bi-chevron-left"></i> Prev
                                    </button>
                                    <button class="btn btn-primary d-flex align-items-center px-4 py-2 rounded-3" id="nextMonthBtn">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2 mb-2">
                            <div class="card bg-dark">
                                <div class="card-header text-light border-bottom border-1 border-secondary">
                                    <i class="fas fa-bar-chart me-1"></i>
                                    <a class="text-light" href="">Performance Ratings</a>
                                </div>
                                <div class="card-body">
                                    <!-- Canvas for Radar Chart -->
                                    <canvas id="performanceRadarChart" width="400" height="400"></canvas>
                                </div>
                            </div>
                        </div>
                    <div class="row mb-4">
                        <div class="col-md-12 mt-2 mb-2">
                            <div class="card bg-dark text-light border-0">
                                <div class="card-header border-bottom border-1 border-secondary">
                                    <i class="fas fa-line-chart me-1"></i> 
                                    <a class="text-light" href="">Top Perfomers</a>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <!-- Performer 1 -->
                                        <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between border-0">
                                            <div class="d-flex align-items-center">
                                                <img src="../../uploads/profile_pictures/try.jpg" alt="Performer 1" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <h5 class="mb-0">John Doe</h5>
                                                    <small class="text-warning">Sales Manager</small>
                                                </div>
                                            </div>
                                            <div class="progress" style="width: 30%; height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 90%;" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </li>
                                        <!-- Performer 2 -->
                                        <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between border-0">
                                            <div class="d-flex align-items-center">
                                                <img src="../../uploads/profile_pictures/pfp3.jpg" alt="Performer 2" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <h5 class="mb-0">Jane Smith</h5>
                                                    <small class="text-warning">Marketing Specialist</small>
                                                </div>
                                            </div>
                                            <div class="progress" style="width: 30%; height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 85%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </li>
                                        <!-- Performer 3 -->
                                        <li class="list-group-item bg-dark text-light d-flex align-items-center justify-content-between border-0">
                                            <div class="d-flex align-items-center">
                                                <img src="../../uploads/profile_pictures/logo.jpg" alt="Performer 3" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <h5 class="mb-0">Michael Johnson</h5>
                                                    <small class="text-warning">HR Manager</small>
                                                </div>
                                            </div>
                                            <div class="progress" style="width: 30%; height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 80%;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
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
                <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="timeInfoModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <!-- Modal Header -->
                            <div class="modal-header border-bottom border-secondary">
                                <h5 class="modal-title fw-bold" id="timeInfoModalLabel">Attendance Information</h5>
                                <button type="button" class="btn-close bg-light" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <!-- Modal Body -->
                            <div class="modal-body">
                                <div class="d-flex flex-wrap justify-content-around align-items-start text-center gap-4">
                                    <div class="d-flex flex-column gap-4 mb-2">
                                        <div>
                                            <h6 class="fw-bold text-light text-start">Date:</h6>
                                            <p class="fw-bold text-info mb-0 text-start" id="attendanceDate"></p>
                                        </div>

                                        <div>
                                            <h6 class="fw-bold text-light text-start">Time In:</h6>
                                            <p class="fw-bold text-info mb-0 text-start" id="timeIn"></p>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-4">
                                        <div>
                                            <h6 class="fw-bold text-light text-start">Status:</h6>
                                            <p class="fw-bold mb-0 text-start" id="workStatus"></p>
                                        </div>

                                        <div>
                                            <h6 class="fw-bold text-light text-start">Time Out:</h6>
                                            <p class="fw-bold text-info mb-0 text-start" id="timeOut"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer border-top border-secondary">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="todoModal" tabindex="-1" aria-labelledby="todoModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-light">
                            <div class="modal-header">
                                <h5 class="modal-title text-info" id="todoModalLabel">To Do</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                        <i class="fas fa-plus me-2"></i>Add To Do List
                                    </button>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task1">
                                            <label class="form-check-label" for="task1">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Facial Recognition
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task2">
                                            <label class="form-check-label" for="task2">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Attendance Record
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task3">
                                            <label class="form-check-label" for="task3">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Leave Processing
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task4">
                                            <label class="form-check-label" for="task4">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Performance Processing
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task5">
                                            <label class="form-check-label" for="task5">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Payroll Processing
                                            </label>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-dark text-light fs-4 border-0 d-flex justify-content-between align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="task6">
                                            <label class="form-check-label" for="task6">
                                                <i class="bi bi-check-circle text-warning me-2"></i>Social Recognition
                                            </label>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

<script>
    // for calendar only
    let calendar; // Declare calendar variable globally

    function toggleCalendar() {
        const calendarContainer = document.getElementById('calendarContainer');
        if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
            calendarContainer.style.display = 'block';

            // Initialize the calendar if it hasn't been initialized yet
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
            height: 440,  // Set the height of the calendar to make it small
            events: {
                url: '../../db/holiday.php',  // Endpoint for fetching events
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
        const currentDate = new Date().toLocaleDateString(); // Get the current date
        currentDateElement.textContent = currentDate; // Set the date text
    });

    document.addEventListener('click', function(event) {
        const calendarContainer = document.getElementById('calendarContainer');
        const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');

        if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
            calendarContainer.style.display = 'none';
        }
    });
    // for calendar only end

    function setCurrentTime() {
        const currentTimeElement = document.getElementById('currentTime');
        const currentDateElement = document.getElementById('currentDate');

        const currentDate = new Date();

        // Convert to 12-hour format with AM/PM
        let hours = currentDate.getHours();
        const minutes = currentDate.getMinutes();
        const seconds = currentDate.getSeconds();
        const ampm = hours >= 12 ? 'PM' : 'AM';

        hours = hours % 12;
        hours = hours ? hours : 12; // If hour is 0, set to 12

        const formattedHours = hours < 10 ? '0' + hours : hours;
        const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
        const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;

        currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds} ${ampm}`;

        // Format the date in text form (e.g., "January 12, 2025")
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        currentDateElement.textContent = currentDate.toLocaleDateString('en-US', options);
    }

    setCurrentTime();
    setInterval(setCurrentTime, 1000);

// ATTENDANCE
let currentMonth = new Date().getMonth(); // January is 0, December is 11
let currentYear = new Date().getFullYear();
let employeeId = <?php echo $employeeId; ?>; // Employee ID from PHP session
let filteredDay = null; // Track the filtered day

const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

const operationStartTime = new Date();
operationStartTime.setHours(8, 10, 0, 0);

const operationEndTime = new Date();
operationEndTime.setHours(16, 0, 0, 0);

// Function to format time with AM/PM
function formatTimeWithAmPm(time24) {
    if (!time24 || time24 === 'N/A') {
        return 'No data';  // Handle cases where there's no data
    }
    
    // Split time into hours and minutes
    let [hour, minute] = time24.split(':');
    hour = parseInt(hour); // Convert hour to an integer
    const amPm = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 || 12; // Convert 0 to 12 for midnight (12 AM)
    return `${hour}:${minute} ${amPm}`;
}

// Function to calculate attendance status
function calculateAttendanceStatus(timeIn, timeOut) {
    let status = '';

    if (timeIn && timeIn !== 'Absent') {
        const timeInDate = new Date(`1970-01-01T${timeIn}:00`);
        if (timeInDate > operationStartTime) {
            status += 'Late';
        }
    }

    if (timeOut && timeOut !== 'Absent') {
        const timeOutDate = new Date(`1970-01-01T${timeOut}:00`);
        if (timeOutDate > operationEndTime) {
            if (status) {
                status += ' & Overtime';
            } else {
                status = 'Overtime';
            }
        }
    }

    return status || 'Present'; // Default to "Present" if no issues
}

function renderCalendar(month, year, attendanceRecords = {}) {
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDay = new Date(year, month, 1).getDay();

    let calendarHTML = '<div class="row text-center pt-3">';

    for (let i = 0; i < firstDay; i++) {
        calendarHTML += '<div class="col"></div>';
    }

    // Fill in the days of the month
    let dayCounter = 1;
    for (let i = firstDay; i < 7; i++) {
        const dayStatus = attendanceRecords[dayCounter];
        const status = (i === 0) ? 'Day Off' :
                       (dayStatus && dayStatus.status === 'Holiday') ? 'Holiday' :
                       dayStatus || '';

        // Check for multiple statuses
        const statusCount = Array.isArray(attendanceRecords[dayCounter]) ? attendanceRecords[dayCounter].length : 1;
        const isFilteredDay = filteredDay && filteredDay.getDate() === dayCounter && filteredDay.getMonth() === month && filteredDay.getFullYear() === year;
        const borderClass = isFilteredDay ? 'border border-2 border-light' : '';

        // Simplified status logic, adding 'text-muted' for holidays, leaves, and day off
        let statusClass = '';
        if (statusCount > 1) {
            statusClass = 'text-dark'; // Black for multiple statuses
        } else {
            statusClass = status === 'Present' ? 'text-success' : // Green for Present/Present
                          status === 'Absent' ? 'text-muted' : // Red for Absent
                          status === 'Late' ? 'text-warning' : // Yellow for Late
                          status === 'Half-Day' ? 'text-light' : // Light for Half-Day
                          status === 'Early Out' ? 'text-warning' : // warning for Early Out
                          status === 'Day Off' || status === 'Holiday' || status === 'On Leave' ? 'text-danger' : ''; // Muted for Day Off, Holidays, and On Leave
        }

        calendarHTML += `
            <div class="col">
                <button class="btn text-light p-0 ${borderClass}" data-bs-toggle="modal" data-bs-target="#attendanceModal" onclick="showAttendanceDetails(${dayCounter})">
                    <span class="fw-bold ${statusClass}">
                        ${dayCounter}
                    </span>
                </button>
            </div>
        `;
        dayCounter++;
    }
    calendarHTML += '</div>';

    // Continue filling rows for the remaining days
    while (dayCounter <= daysInMonth) {
        calendarHTML += '<div class="row text-center pt-3">';
        let dayOfWeek = 0; // Reset for each row

        for (let i = 0; i < 7 && dayCounter <= daysInMonth; i++) {
            const dayStatus = attendanceRecords[dayCounter]; // Get the status for the current day
            const status = (dayOfWeek === 0) ? 'Day Off' : // Set "Day Off" for Sundays (day 0)
                           (dayStatus && dayStatus.status === 'Holiday') ? 'Holiday' : // Check for holidays
                           dayStatus || ''; // Fallback to the status or empty string

            // Check for multiple statuses
            const statusCount = Array.isArray(attendanceRecords[dayCounter]) ? attendanceRecords[dayCounter].length : 1;
            
            const isFilteredDay = filteredDay && filteredDay.getDate() === dayCounter && filteredDay.getMonth() === month && filteredDay.getFullYear() === year;
            const borderClass = isFilteredDay ? 'border border-2 border-light' : '';

            // Simplified status logic, adding 'text-muted' for holidays, leaves, and day off
            let statusClass = '';
            if (statusCount > 1) {
                statusClass = 'text-dark'; // Black for multiple statuses
            } else {
                statusClass = status === 'Present' ? 'text-success' : // Green for Present/Present
                              status === 'Absent' ? 'text-muted' : // Red for Absent
                              status === 'Late' ? 'text-warning' : // Yellow for Late
                              status === 'Half-Day' ? 'text-light' : // Light for Half-Day
                              status === 'Early Out' ? 'text-warning' : // warning for Early Out
                              status === 'Day Off' || status === 'Holiday' || status === 'On Leave' ? 'text-danger' : ''; // Muted for Day Off, Holidays, and On Leave
            }

            calendarHTML += `
                <div class="col">
                    <button class="btn text-light p-0 ${borderClass}" data-bs-toggle="modal" data-bs-target="#attendanceModal" onclick="showAttendanceDetails(${dayCounter})">
                        <span class="fw-bold ${statusClass}">
                            ${dayCounter}
                        </span>
                    </button>
                </div>
            `;
            dayCounter++;
            dayOfWeek++;
        }

        if (dayOfWeek < 7) {
            for (let j = dayOfWeek; j < 7; j++) {
                calendarHTML += '<div class="col"></div>';
            }
        }

        calendarHTML += '</div>';
    }

    document.getElementById('ATTENDANCEcalendar').innerHTML = calendarHTML;
    document.getElementById('monthYearDisplay').textContent = `${monthNames[month]} ${year}`;
    document.getElementById('todaysDate').textContent = `${monthNames[new Date().getMonth()]} ${new Date().getDate()}, ${new Date().getFullYear()}`;
}

// Fetch attendance for the given month and year
async function fetchAttendance(month, year) {
    try {
        const response = await fetch(`/HR2/employee_db/supervisor/fetch_attendance.php?e_id=${employeeId}&month=${month + 1}&year=${year}`);
        const data = await response.json();

        if (data.error) {
            console.error('Error fetching attendance data:', data.error);
            return;
        }

        // Handle attendance records and render calendar
        renderCalendar(month, year, data); // Pass attendance data to render calendar
    } catch (error) {
        console.error('Error fetching attendance data:', error);
    }
}

// Show attendance details when a specific day is clicked
async function showAttendanceDetails(day) {
    const selectedDate = `${monthNames[currentMonth]} ${day}, ${currentYear}`;
    document.getElementById('attendanceDate').textContent = selectedDate;

    // Get the current date
    const currentDate = new Date();
    const selectedDateObj = new Date(currentYear, currentMonth, day);
    const isCurrentOrPastDay = selectedDateObj <= currentDate;

    // Check if the selected day is a Sunday
    const isSunday = selectedDateObj.getDay() === 0; // Sunday is 0 in JavaScript's getDay()

    const leaveResponse = await fetch(`/HR2/employee_db/supervisor/fetch_leave.php?e_id=${employeeId}&day=${day}&month=${currentMonth + 1}&year=${currentYear}`);
    const leaveData = await leaveResponse.json();

    if (leaveData.onLeave) {
        document.getElementById('timeIn').textContent = `On Leave`;
        document.getElementById('timeOut').textContent = `On Leave`;
        document.getElementById('workStatus').textContent = leaveData.leaveType || 'On Leave'; // Fallback to 'On Leave' if leaveType is undefined

        const statusElement = document.getElementById('workStatus');
        statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
        statusElement.classList.add('text-danger');
    } else {
        const attendanceResponse = await fetch(`/HR2/employee_db/supervisor/fetch_attendance.php?e_id=${employeeId}&day=${day}&month=${currentMonth + 1}&year=${currentYear}`);
        const data = await attendanceResponse.json();

        if (data.error) {
            console.error(data.error);
            return;
        }

        const isHoliday = data.status === 'Holiday'; // Assuming the status is returned as 'Holiday' for holidays
        const isDayOff = data.status === 'Day Off' || isSunday; // Mark Sunday as "Day Off"

        if (isHoliday) {
            document.getElementById('timeIn').textContent = 'Holiday';
            document.getElementById('timeOut').textContent = 'Holiday';
            document.getElementById('workStatus').textContent = data.holiday_name || 'Holiday';

            const statusElement = document.getElementById('workStatus');
            statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
            statusElement.classList.add('text-danger');
        } else if (isDayOff) {
            document.getElementById('timeIn').textContent = 'Day Off';
            document.getElementById('timeOut').textContent = 'Day Off';
            document.getElementById('workStatus').textContent = 'Day Off';

            const statusElement = document.getElementById('workStatus');
            statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
            statusElement.classList.add('text-danger'); // Use danger color for "Day Off"
        } else {
            // Check if it's a future day
            if (!isCurrentOrPastDay) {
                document.getElementById('timeIn').textContent = 'No Data Found';
                document.getElementById('timeOut').textContent = 'No Data Found';
                document.getElementById('workStatus').textContent = 'No Data Found';

                const statusElement = document.getElementById('workStatus');
                statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
                statusElement.classList.add('text-muted'); // Use a muted color for "No Data Found"
            }
            // Check if it's the current day or a past day and there's no attendance data
            else if (isCurrentOrPastDay && (!data.time_in && !data.time_out)) {
                document.getElementById('timeIn').textContent = 'Absent';
                document.getElementById('timeOut').textContent = 'Absent';
                document.getElementById('workStatus').textContent = 'Absent';

                const statusElement = document.getElementById('workStatus');
                statusElement.classList.remove('text-success', 'text-warning', 'text-info', 'text-light', 'text-muted', 'text-warning');
                statusElement.classList.add('text-muted'); // Use a muted color for "Absent"
            } else {
                const timeInFormatted = data.time_in ? formatTimeWithAmPm(data.time_in) : 'Absent';
                const timeOutFormatted = data.time_out ? formatTimeWithAmPm(data.time_out) : 'Absent';

                // Pass onLeave status to calculateAttendanceStatus
                const attendanceStatus = calculateAttendanceStatus(data.time_in, data.time_out, day, leaveData.onLeave);

                // Display time-in and time-out
                document.getElementById('timeIn').textContent = timeInFormatted;
                document.getElementById('timeOut').textContent = timeOutFormatted;

                // Display status with individual colors
                const statusElement = document.getElementById('workStatus');
                statusElement.innerHTML = ''; // Clear previous content

                attendanceStatus.forEach((status, index) => {
                    const span = document.createElement('span');
                    span.textContent = status;

                    // Assign color based on the status
                    switch (status) {
                        case 'Late':
                            span.classList.add('text-warning'); // Yellow for Late
                            break;
                        case 'Overtime':
                            span.classList.add('text-primary'); // Blue for Overtime
                            break;
                        case 'Present':
                            span.classList.add('text-success'); // Green for Present
                            break;
                        case 'Absent':
                            span.classList.add('text-muted'); // Red for Absent
                            break;
                        case 'Day Off':
                            span.classList.add('text-danger'); // Light for Day Off
                            break;
                        case 'Half-Day':
                            span.classList.add('text-light'); // Light for Half-Day
                            break;
                        case 'On Leave':
                            span.classList.add('text-danger'); // Red for On Leave
                            break;
                        case 'Early Out':
                            span.classList.add('text-warning'); // warning for Early Out
                            break;
                        default:
                            span.classList.add('text-dark'); // Default color
                    }

                    statusElement.appendChild(span);

                    // Add a separator (&) between statuses (except for the last one)
                    if (index < attendanceStatus.length - 1) {
                        const separatorSpan = document.createElement('span');
                        separatorSpan.textContent = ' & ';
                        separatorSpan.classList.add('text-white'); // White color for the separator
                        statusElement.appendChild(separatorSpan);
                    }
                });
            }
        }
    }
}

// Function to calculate attendance status
function calculateAttendanceStatus(timeIn, timeOut, day, onLeave = false) {
    let status = [];

    // Check if the employee is on leave
    if (onLeave) {
        status.push('On Leave');
        return status; // Return early if the employee is on leave
    }

    // Check if the day is a Sunday (0 for Sunday in JavaScript)
    const date = new Date(currentYear, currentMonth, day);
    if (date.getDay() === 0) {
        return ['Day Off'];
    }

    // If there's no time_in or time_out, return "Absent"
    if (!timeIn || !timeOut) {
        return ['Absent'];
    }

    // Convert timeIn and timeOut to Date objects for comparison
    const timeThreshold = new Date('1970-01-01T08:10:00'); // Threshold time for Late check
    const timeInDate = new Date('1970-01-01T' + timeIn);
    const timeOutDate = new Date('1970-01-01T' + timeOut);

    // Check if employee is late
    if (timeInDate > timeThreshold) {
        status.push('Late');
    }

    // Check if there's overtime (Example: work beyond 6:00 PM)
    const overtimeThreshold = new Date('1970-01-01T18:00:00');
    if (timeOutDate > overtimeThreshold) {
        status.push('Overtime');
    }

    // Check if employee left early (1 to 3 hours before operation end time)
    const operationEndTime = new Date('1970-01-01T17:00:00'); // Operation end time is 5:00 PM
    const earlyOutStart = new Date('1970-01-01T14:00:00'); // Early out starts at 2:00 PM
    if (timeOutDate >= earlyOutStart && timeOutDate < operationEndTime) {
        status.push('Early Out');
    }

    // If no specific status, return "Present"
    if (status.length === 0) {
        status.push('Present');
    }

    return status; // Return an array of statuses
}


// Function to format time in HH:MM AM/PM format
function formatTimeWithAmPm(time) {
    const [hours, minutes] = time.split(':');
    const ampm = parseInt(hours) >= 12 ? 'PM' : 'AM';
    const formattedHours = (parseInt(hours) % 12) || 12;
    return `${formattedHours}:${minutes} ${ampm}`;
}


// Event listeners for next and previous month buttons
document.getElementById('nextMonthBtn').addEventListener('click', function() {
    currentMonth++;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    fetchAttendance(currentMonth, currentYear);
});

document.getElementById('prevMonthBtn').addEventListener('click', function() {
    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    fetchAttendance(currentMonth, currentYear);
});

// Date filter functionality
document.getElementById('dateFilter').addEventListener('change', function () {
    const selectedDate = new Date(this.value); // Get the selected date
    currentMonth = selectedDate.getMonth(); // Update the current month
    currentYear = selectedDate.getFullYear(); // Update the current year
    filteredDay = selectedDate; // Track the filtered day
    fetchAttendance(currentMonth, currentYear); // Fetch and render the calendar for the selected month and year
});

// Fetch the initial calendar for the current month and year
fetchAttendance(currentMonth, currentYear);


 // PHP variables passed to JavaScript
const evaluationData = {
    avg_quality: <?php echo json_encode($evaluation['avg_quality'] ?? null); ?>,
    avg_communication_skills: <?php echo json_encode($evaluation['avg_communication_skills'] ?? null); ?>,
    avg_teamwork: <?php echo json_encode($evaluation['avg_teamwork'] ?? null); ?>,
    avg_punctuality: <?php echo json_encode($evaluation['avg_punctuality'] ?? null); ?>,
    avg_initiative: <?php echo json_encode($evaluation['avg_initiative'] ?? null); ?>,
    totalAverage: <?php echo json_encode($totalAverage ?? null); ?>
};

// Radar Chart initialization
const ctx = document.getElementById('performanceRadarChart').getContext('2d');
const performanceRadarChart = new Chart(ctx, {
    type: 'radar',
    data: {
        labels: ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'],
        datasets: [
            {
                label: 'Category Ratings',
                data: [
                    evaluationData.avg_quality,
                    evaluationData.avg_communication_skills,
                    evaluationData.avg_teamwork,
                    evaluationData.avg_punctuality,
                    evaluationData.avg_initiative
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.2)', // Light blue fill
                borderColor: 'rgba(54, 162, 235, 1)', // Blue border
                borderWidth: 2
            },
            {
                label: 'Overall Rating',
                data: [
                    evaluationData.totalAverage,
                    evaluationData.totalAverage,
                    evaluationData.totalAverage,
                    evaluationData.totalAverage,
                    evaluationData.totalAverage
                ],
                backgroundColor: 'rgba(255, 99, 132, 0.2)', // Light red fill
                borderColor: 'rgba(255, 99, 132, 1)', // Red border
                borderWidth: 2
            }
        ]
    },
    options: {
        scales: {
            r: {
                angleLines: {
                    display: true,
                    color: 'rgba(200, 200, 200, 0.2)' // Customize angle line color
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.2)' // Customize grid line color
                },
                suggestedMin: 0,
                suggestedMax: 6,
                ticks: {
                    stepSize: 1,
                    display: false // Hide the tick labels (1 to 6)
                },
                pointLabels: {
                    color: 'white', // Change label color (e.g., teal)
                    font: {
                        size: 14, // Change label font size
                        weight: 'bold', // Make label text bold
                        family: 'Arial' // Change label font family
                    },
                    padding: 15
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'left'
            },
            tooltip: {
                enabled: true, // Enable tooltips
                callbacks: {
                    label: function (context) {
                        return `${context.dataset.label}: ${context.raw}`; // Show dataset label and value in tooltip
                    }
                }
            },
            datalabels: {
                color: function (context) {
                    // Use different colors for the two datasets
                    return context.datasetIndex === 0 ? 'cyan' : 'pink'; // Customize data label colors
                },
                anchor: 'center', // Position the label at the center of the point
                align: function (context) {
                    // Align first dataset labels to top, second dataset labels to bottom
                    return context.datasetIndex === 0 ? 'top' : 'bottom';
                },
                formatter: function (value) {
                    return value; // Display the value as the label
                }
            }
        },
        responsive: true,
        maintainAspectRatio: false
    },
    plugins: [ChartDataLabels] // Enable the datalabels plugin
});
</script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/employee.js"></script>


</body>

</html>

