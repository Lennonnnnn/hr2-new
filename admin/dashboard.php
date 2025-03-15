<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT firstname, middlename, lastname, email, role, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);

// Check if statement preparation failed
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

// Fetch user information
$adminInfo = $result->fetch_assoc();

// Set profile picture or use default if not set
$profilePicture = !empty($adminInfo['pfp']) ? $adminInfo['pfp'] : '../img/defaultpfp.jpg';

// Close statement and connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Admin Dashboard | HR2</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/styles.css" rel="stylesheet" />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
    /* Dark Mode Styles */
    .custom-modal {
        background: #121212; /* Dark background */
        color: #ffffff; /* White font */
        border-radius: 10px;
        box-shadow: 0px 8px 16px rgba(255, 255, 255, 0.05);
    }

    /* Dark Border for Separation */
    .border-dark {
        border-color: #333 !important;
    }

    /* Make Notifications More Interactive */
    .custom-body {
        max-height: 400px;
        overflow-y: auto;
        padding: 15px;
    }

    .custom-item {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border-radius: 8px;
        padding: 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.3s ease-in-out;
        border: 1px solid #333; /* Dark border for separation */
    }

    .custom-item.unread {
        font-weight: bold;
        border-left: 4px solid #ff4757;
    }

    .custom-item.read {
        opacity: 0.8;
    }

    .custom-item:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: scale(1.02);
    }

    .notif-icon {
        margin-right: 10px;
        font-size: 18px;
    }

    .time-stamp {
        font-size: 12px;
        opacity: 0.7;
    }

    /* Button Outline Light */
    .btn-outline-light:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>
</head>
        
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php';?>        
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative px-4">
                    <h1 class="mb-4 text-light">Dashboard</h1>
                    <div class="container-fluid" id="calendarContainer" 
                        style="position: fixed; top: 7%; right: 40; z-index: 1050; 
                        max-width: 100%; display: none;">
                        <div class="row">
                            <div class="col-md-9 mx-auto">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                    </div> 
                <!-- Leave Request Status and Employee Performance Section -->
                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                    <i class="fas fa-chart-pie me-1"></i> 
                                    <a class="text-light" href="../admin/leave_requests.php">Leave Request Status </a>
                                </div>
                                <div class="card-body bg-dark">
                                    <canvas id="leaveStatusChart" width="300" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                    <i class="fas fa-chart-line me-1"></i>
                                    <a class="text-light" href="#">Employee Performance</a>
                                </div>
                                <div class="card-body bg-dark">
                                    <canvas id="employeePerformanceChart" width="300" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Employee Count per Department Section -->
                    <div class="row">
                        <div class="col-xl-9">
                            <div class="card mb-4">
                                <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                    <i class="fas fa-users me-1"></i>
                                    <a class="text-light" href="#">Employee Count per Department</a>
                                </div>
                                <div class="card-body bg-dark">
                                    <canvas id="employeeDepartmentChart" width="300" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3">
                            <div class="card mb-4">
                                <div class="card-header bg-dark text-light border-bottom border-1 border-secondary">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    <a class="text-light" href="#">Department Attendance Record</a>
                                </div>
                                <div class="card-body bg-dark">
                                    <select class="department-select form-control mb-3" id="departmentSelect">
                                        <option value="">Show All Departments</option>
                                        <option value="hr">HR Department</option>
                                        <option value="it">IT Department</option>
                                        <option value="sales">Sales Department</option>
                                        <option value="marketing">Marketing Department</option>
                                    </select>
                                    <div class="chart-container">
                                        <canvas id="attendanceChart" width="500" height="345"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- for leaveStatusChart -->
                    <?php

                        include '../db/db_conn.php';

                        $sql = "SELECT status, COUNT(*) as count FROM leave_requests GROUP BY status";
                        $result = $conn->query($sql);

                        $status_counts = [
                            'Approved' => 0,
                            'Supervisor Approved' => 0,
                            'Denied' => 0,
                        ];
                        while ($row = $result->fetch_assoc()) {
                            $status = $row['status'];
                            if (isset($status_counts[$status])) {
                                $status_counts[$status] = $row['count'];
                            }
                        }

                        $conn->close();
                    ?>
                </div>
            </main>
            <!-- All Notifications Modal -->
                <div class="modal fade" id="allNotificationsModal" tabindex="-1" aria-labelledby="allNotificationsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content custom-modal">
                            <div class="modal-header border-bottom border-dark">
                                <h5 class="modal-title" id="allNotificationsModalLabel">🔔 All Notifications</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body custom-body" id="allNotificationsModalBody">
                                <!-- All notifications will be loaded here -->
                                <ul class="list-group">
                                    <?php foreach ($notifications as $notification): ?>
                                        <li class="list-group-item custom-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                            <span class="notif-icon">
                                                <?php if (!$notification['is_read']): ?>
                                                    🔴
                                                <?php else: ?>
                                                    ⚪
                                                <?php endif; ?>
                                            </span>
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                            <span class="time-stamp">Just now</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="modal-footer border-top border-dark">
                                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
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
                <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-transparent border-0">
                            <div class="modal-body d-flex flex-column align-items-center justify-content-center">
                                <div class="coin-spinner"></div>
                                <div class="mt-3 text-light fw-bold">Please wait...</div>
                            </div>
                        </div>
                    </div>
                </div>   
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
                const buttons = document.querySelectorAll('.loading');
                const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

                // Loop through each button and add a click event listener
                buttons.forEach(button => {
                    button.addEventListener('click', function (event) {
                        // Show the loading modal
                        loadingModal.show();

                        // Disable the button to prevent multiple clicks
                        this.classList.add('disabled');

                        // Handle form submission buttons
                        if (this.closest('form')) {
                            event.preventDefault(); // Prevent the default form submit

                            // Submit the form after a short delay
                            setTimeout(() => {
                                this.closest('form').submit();
                            }, 1500);
                        }
                        // Handle links
                        else if (this.tagName.toLowerCase() === 'a') {
                            event.preventDefault(); // Prevent the default link behavior

                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = this.href;
                            }, 1500);
                        }
                    });
                });

                // Hide the loading modal when navigating back and enable buttons again
                window.addEventListener('pageshow', function (event) {
                    if (event.persisted) { // Check if the page was loaded from cache (back button)
                        loadingModal.hide();

                        // Re-enable all buttons when coming back
                        buttons.forEach(button => {
                            button.classList.remove('disabled');
                        });
                        
                    }
                });
            });
        // Doughnut chart data
        const data = {
            labels: ['Approved', 'Pending', 'Denied'],
            datasets: [{
                data: [
                    <?php echo $status_counts['Approved']; ?>,
                    <?php echo $status_counts['Supervisor Approved']; ?>,
                    <?php echo $status_counts['Denied']; ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545']
            }]
        };

        // Doughnut chart configuration
        const leaveStatusCtx = document.getElementById('leaveStatusChart').getContext('2d');
        const leaveStatusChart = new Chart(leaveStatusCtx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'left', // Change legend position to bottom
                        labels: {
                            color: 'white', // Change legend text color
                            font: {
                                size: 14, // Change legend font size
                                weight: 'bold' // Make legend text bold
                            },
                            padding: 20 // Add padding between legend items
                        }
                    },
                    title: {
                        display: false,
                        text: 'Leave Request Statuses'
                    }
                }
            }
        });
        //for leaveStatusChart end


    // Dummy Data for Employee Performance
    // Employee performance data with daily disbursements for a month
    const employees = [
        {
            name: "John Doe",
            dailyDisbursements: [6, 6, 6, 4, 5, 3, 5, 6, 2, 5, 6, 4, 2, 2, 5, 6, 3, 5, 6, 4, 6, 6, 6, 3, 2, 6, 6, 2, 5, 5], // Day 1 to Day 30
        },
        {
            name: "Jane Smith",
            dailyDisbursements: [3, 6, 4, 6, 6, 4, 3, 4, 4, 4, 6, 5, 4, 5, 4, 2, 5, 6, 5, 3, 6, 5, 4, 4, 3, 6, 5, 2, 3, 4], // Day 1 to Day 30
        },
        {
            name: "Alice Johnson",
            dailyDisbursements: [4, 5, 6, 3, 6, 4, 1, 4, 6, 4, 6, 2, 3, 6, 3, 1, 4, 4, 5, 5, 6, 3, 6, 6, 5, 4, 3, 1, 4, 6], // Day 1 to Day 30
        },
    ];

// Generate labels for days of the month (Day 1 to Day 30)
const daysOfMonth = Array.from({ length: 30 }, (_, i) => `Day ${i + 1}`);

// Employee Performance Line Chart
const employeePerformanceCtx = document.getElementById("employeePerformanceChart").getContext("2d");
new Chart(employeePerformanceCtx, {
    type: "line", // Use a line chart
    data: {
        labels: daysOfMonth, // X-axis labels (Day 1 to Day 30)
        datasets: employees.map(emp => ({
            label: emp.name, // Employee name as dataset label
            data: emp.dailyDisbursements, // Daily disbursements as data
            borderColor: `#${Math.floor(Math.random() * 16777215).toString(16)}`, // Random color for each employee
            borderWidth: 2,
            fill: false, // Do not fill under the line
        })),
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: "top",
            },
            tooltip: {
                enabled: true,
                callbacks: {
                    label: function (context) {
                        const label = context.dataset.label || "";
                        const value = context.raw || 0;
                        return `${label}: ${value}`; // Display employee name and daily disbursement
                    },
                },
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: "black", // Grid color
                },
                ticks: {
                    color: "#fff", // Y-axis text color
                },
                title: {
                    display: true,
                    text: "Daily Disbursements", // Y-axis title
                    color: "#fff",
                },
            },
            x: {
                grid: {
                    color: "black", // Grid color
                },
                ticks: {
                    color: "#fff", // X-axis text color
                },
                title: {
                    display: true,
                    text: "Days of the Month", // X-axis title
                    color: "#fff",
                },
            },
        },
    },
});

// Fetch Employee Count Data from the Backend
async function fetchEmployeeCount() {
    try {
        const response = await fetch('../db/employeeCount.php'); // Adjust the path to your PHP endpoint
        if (!response.ok) {
            throw new Error('Failed to fetch employee count data');
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error fetching employee count data:', error);
        return []; // Return an empty array in case of error
    }
}

// Render Employee Count Bar Chart
async function renderEmployeeCountChart() {
    // Fetch real employee count data
    const departments = await fetchEmployeeCount();

    // Get the canvas context
    const employeeDepartmentCtx = document.getElementById("employeeDepartmentChart").getContext("2d");

    // Render the chart
    new Chart(employeeDepartmentCtx, {
        type: "bar",
        data: {
            labels: departments.map(dept => dept.name), // Use department names as labels
            datasets: [{
                label: "Employee Count",
                data: departments.map(dept => dept.count), // Use employee counts as data
                backgroundColor: "#1abc9c", // Bar color
                borderColor: "#34495e", // Border color
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false, // Hide legend for bar chart
                },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function (context) {
                            const label = context.label || "";
                            const value = context.raw || 0;
                            return `${label}: ${value} employees`;
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: "black", // Grid color
                    },
                    ticks: {
                        color: "#fff", // Y-axis text color
                    },
                },
                x: {
                    grid: {
                        color: "black", // Grid color
                    },
                    ticks: {
                        color: "#fff", // X-axis text color
                    },
                },
            },
        },
    });
}

// Call the function to render the chart when the page loads
document.addEventListener('DOMContentLoaded', function () {
    renderEmployeeCountChart();
});

        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('attendanceChart').getContext('2d');
            const departmentSelect = document.getElementById('departmentSelect');

            // Temporary data for departments and their employees' attendance
            const departmentData = {
                hr: {
                    labels: ['John Doe', 'Jane Smith', 'Alice Johnson', 'Bob Brown'],
                    attendance: ['Present', 'Absent', 'Present', 'Present'] // Attendance status
                },
                it: {
                    labels: ['Mike Ross', 'Harvey Specter', 'Rachel Zane', 'Louis Litt'],
                    attendance: ['Present', 'Present', 'Absent', 'Present']
                },
                sales: {
                    labels: ['Tom Cruise', 'Emma Watson', 'Chris Evans', 'Scarlett Johansson'],
                    attendance: ['Absent', 'Present', 'Present', 'Absent']
                },
                marketing: {
                    labels: ['Tony Stark', 'Steve Rogers', 'Natasha Romanoff', 'Bruce Banner'],
                    attendance: ['Present', 'Present', 'Present', 'Absent']
                }
            };

            // Calculate total present and absent employees for each department
            const departmentSummary = {
                labels: ['HR', 'IT', 'Sales', 'Marketing'],
                present: [
                    departmentData.hr.attendance.filter(status => status === 'Present').length,
                    departmentData.it.attendance.filter(status => status === 'Present').length,
                    departmentData.sales.attendance.filter(status => status === 'Present').length,
                    departmentData.marketing.attendance.filter(status => status === 'Present').length
                ],
                absent: [
                    departmentData.hr.attendance.filter(status => status === 'Absent').length,
                    departmentData.it.attendance.filter(status => status === 'Absent').length,
                    departmentData.sales.attendance.filter(status => status === 'Absent').length,
                    departmentData.marketing.attendance.filter(status => status === 'Absent').length
                ]
            };

            let attendanceChart;

            // Function to create or update the chart
            function updateChart(labels, presentData, absentData = null, label = 'Department Attendance') {
                if (attendanceChart) {
                    attendanceChart.destroy(); // Destroy the existing chart
                }

                const datasets = [
                    {
                        label: 'Present',
                        data: presentData,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ];

                if (absentData) {
                    datasets.push({
                        label: 'Absent',
                        data: absentData,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    });
                }

                attendanceChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Employees'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: label === 'Department Attendance' ? 'Departments' : 'Employees'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                enabled: true
                            }
                        }
                    }
                });
            }

            // Initial chart showing present and absent employees for all departments
            updateChart(departmentSummary.labels, departmentSummary.present, departmentSummary.absent, 'Department Attendance');

            // Event listener for department selection
            departmentSelect.addEventListener('change', function () {
                const selectedDepartment = departmentSelect.value;

                if (selectedDepartment && departmentData[selectedDepartment]) {
                    const { labels, attendance } = departmentData[selectedDepartment];
                    const presentData = attendance.map(status => status === 'Present' ? 1 : 0);
                    const absentData = attendance.map(status => status === 'Absent' ? 1 : 0);
                    updateChart(labels, presentData, absentData, 'Employee Attendance');
                } else {
                    // Show present and absent employees for all departments if no specific department is selected
                    updateChart(departmentSummary.labels, departmentSummary.present, departmentSummary.absent, 'Department Attendance');
                }
            });
        });
    </script>

    <!-- Notification Details Modal -->
    <!-- <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="notificationModalLabel">Notification Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="notificationModalBody">
                    Notification details will be loaded here -->
                <!-- </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="errorMessage"></p>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- All Notifications Modal -->
    <div class="modal fade" id="allNotificationsModal" tabindex="-1" aria-labelledby="allNotificationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content custom-modal">
                <div class="modal-header border-bottom border-dark">
                    <h5 class="modal-title" id="allNotificationsModalLabel">🔔 All Notifications</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body custom-body" id="allNotificationsModalBody">
                    <!-- All notifications will be loaded here -->
                    <ul class="list-group">
                        <?php foreach ($notifications as $notification): ?>
                            <li class="list-group-item custom-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                <span class="notif-icon">
                                    <?php if (!$notification['is_read']): ?>
                                        🔴
                                    <?php else: ?>
                                        ⚪
                                    <?php endif; ?>
                                </span>
                                <?php echo htmlspecialchars($notification['message']); ?>
                                <span class="time-stamp">Just now</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="modal-footer border-top border-dark">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../js/admin.js"></script>
    <script>
</script>
