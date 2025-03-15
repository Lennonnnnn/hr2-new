<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch admin's ID from the session
$adminId = $_SESSION['a_id'];

// Fetch admin info
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Fetch all leave requests that have been approved or denied by the supervisor
$sql = "SELECT lr.leave_id, e.e_id, e.firstname, e.lastname, e.department, lr.start_date, lr.end_date, lr.leave_type, lr.proof, lr.status, lr.created_at
        FROM leave_requests lr
        JOIN employee_register e ON lr.e_id = e.e_id
        WHERE lr.supervisor_approval = 'Supervisor Approved' AND lr.status = 'Supervisor Approved' ORDER BY created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Fetch employee data from the database
$employees_sql = "SELECT e_id, firstname, lastname, position, gender FROM employee_register";
$employees_result = $conn->query($employees_sql);

// Store the employee data in an array
$employees = [];
while ($employee = $employees_result->fetch_assoc()) {
    $employees[] = $employee;
}

// Pass the employee data to JavaScript
echo "<script>const employees = " . json_encode($employees) . ";</script>";

// Handle adding, editing, or deleting questions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_question'])) {
        $category = $_POST['category'];
        $question = $_POST['question'];

        $sql = "INSERT INTO evaluation_questions (category, question) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $category, $question);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['edit_question'])) {
        $id = $_POST['id'];
        $new_question = $_POST['new_question'];

        $sql = "UPDATE evaluation_questions SET question = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_question, $id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['delete_question'])) {
        $id = $_POST['id'];

        $sql = "DELETE FROM evaluation_questions WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

$sql = "SELECT * FROM evaluation_questions ORDER BY category, id";
$result = $conn->query($sql);

$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="User Profile Dashboard" />
    <meta name="author" content="Your Name" />
    <title>Settings</title>
    <link href="../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        }
        
        .sb-nav-fixed {
            padding-top: 56px;
        }
        
        .container-fluid {
            padding: 1.5rem;
        }
        
        .section-title {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
            color: var(--text-primary);
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 3px;
        }
        
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            background-color: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        .form-control, .form-select {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--darker-bg);
            border-color: var(--primary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .form-floating > .form-control,
        .form-floating > .form-select {
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
        }
        
        .form-floating > label {
            padding: 1rem 0.75rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: #fff;
        }
        
        .btn-warning:hover {
            background-color: #d35400;
            border-color: #d35400;
            color: #fff;
            transform: translateY(-2px);
        }
        
        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .accordion {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .accordion-item {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }
        
        .accordion-button {
            padding: 1.25rem 1.5rem;
            font-weight: 500;
            background-color: var(--card-bg);
            color: var(--text-primary);
            border: none;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .accordion-button:focus {
            box-shadow: none;
            border-color: var(--border-color);
        }
        
        .accordion-button::after {
            filter: brightness(0) invert(1);
        }
        
        .accordion-button:not(.collapsed)::after {
            filter: brightness(0) saturate(100%) invert(37%) sepia(74%) saturate(1217%) hue-rotate(213deg) brightness(91%) contrast(98%);
        }
        
        .accordion-body {
            padding: 1.5rem;
            background-color: var(--darker-bg);
        }
        
        .list-group-item {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-primary);
            padding: 1rem 1.25rem;
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
        
        .modal-content {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }
        
        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }
        
        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }
        
        .modal-title {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .nav-tabs {
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
            border-radius: 0;
            margin-bottom: -1px;
            transition: all 0.2s;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--text-primary);
            background-color: rgba(255, 255, 255, 0.05);
            border-color: transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.1), rgba(255,255,255,0));
            margin: 2rem 0;
        }
        
        .floating-label {
            position: absolute;
            top: -10px;
            left: 15px;
            background-color: var(--card-bg);
            padding: 0 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
            transition: all 0.2s;
        }
        
        .form-control:focus + .floating-label,
        .form-select:focus + .floating-label {
            color: var(--primary-color);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon .form-control {
            padding-left: 3rem;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .form-control:focus ~ .input-icon {
            color: var(--primary-color);
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem;
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
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative px-4 py-4">
                    <div class="container-fluid" id="calendarContainer"
                        style="position: fixed; top: 9%; right: 0; z-index: 1050;
                        max-width: 100%; display: none;">
                        <div class="row">
                            <div class="col-12 col-md-10 col-lg-8 mx-auto">
                                <div id="calendar" class="p-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 fade-in">
                        <h1 class="section-title mb-0">Admin Settings</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-secondary">Dashboard</a></li>
                                <li class="breadcrumb-item active text-light" aria-current="page">Settings</li>
                            </ol>
                        </nav>
                    </div>
                    
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs fade-in" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="leave-tab" data-bs-toggle="tab" data-bs-target="#leave" type="button" role="tab" aria-controls="leave" aria-selected="true">
                                <i class="fas fa-calendar-alt me-2"></i>Leave Management
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab" aria-controls="performance" aria-selected="false">
                                <i class="fas fa-chart-line me-2"></i>Performance Management
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-settings" type="button" role="tab" aria-controls="calendar-settings" aria-selected="false">
                                <i class="fas fa-calendar-check me-2"></i>Calendar Settings
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content fade-in" id="settingsTabContent">
                        <!-- Leave Management Tab -->
                        <div class="tab-pane fade show active" id="leave" role="tabpanel" aria-labelledby="leave-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h3><i class="fas fa-user-clock me-2"></i>Leave Allocation</h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="../db/setLeave.php" class="needs-validation" novalidate>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-group position-relative">
                                                    <select name="gender" id="gender" class="form-select" required onchange="updateEmployeeList()">
                                                        <option value="" disabled selected>Select Gender</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                    <span class="floating-label">Select Gender</span>
                                                    <div class="invalid-feedback">Please select a gender.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-group position-relative">
                                                    <select name="employee_id" id="employee_id" class="form-select" required>
                                                        <option value="" disabled selected>Select Employee</option>
                                                        <!-- Employees will be populated here dynamically -->
                                                    </select>
                                                    <span class="floating-label">Select Employee</span>
                                                    <div class="invalid-feedback">Please select an employee.</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Male Leave Section -->
                                        <div id="male-leave" class="row" style="display: none;">
                                            <div class="col-12">
                                                <h5 class="text-primary mb-3">Male Leave Allocation</h5>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="bereavement_leave_male" id="bereavement_leave_male" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-calendar-day input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Bereavement Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="emergency_leave_male" id="emergency_leave_male" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-exclamation-circle input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Emergency Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="parental_leave_male" id="parental_leave_male" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-baby input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Parental Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="paternity_leave_male" id="paternity_leave_male" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-child input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Paternity Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="service_incentive_leave_male" id="service_incentive_leave_male" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-award input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Service Incentive Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="sick_leave_male" id="sick_leave_male" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-procedures input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Sick Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="vacation_leave_male" id="vacation_leave_male" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-umbrella-beach input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Vacation Leave</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Female Leave Section -->
                                        <div id="female-leave" class="row" style="display: none;">
                                            <div class="col-12">
                                                <h5 class="text-primary mb-3">Female Leave Allocation</h5>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="bereavement_leave" id="bereavement_leave" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-calendar-day input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Bereavement Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="emergency_leave" id="emergency_leave" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-exclamation-circle input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Emergency Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="maternity_leave" id="maternity_leave" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-baby-carriage input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Maternity Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="mcw_special_leave" id="mcw_special_leave" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-user-shield input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">MCW Special Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="parental_leave" id="parental_leave" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-baby input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Parental Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="service_incentive_leave" id="service_incentive_leave" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-award input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Service Incentive Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="sick_leave" id="sick_leave" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-procedures input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Sick Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="vacation_leave" id="vacation_leave" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-umbrella-beach input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">Vacation Leave</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-group position-relative">
                                                    <div class="input-with-icon">
                                                        <input type="number" name="vawc_leave" id="vawc_leave" class="form-control" min="0" placeholder="Enter days">
                                                        <i class="fas fa-hand-holding-heart input-icon"></i>
                                                    </div>
                                                    <span class="floating-label">VAWC Leave</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end mt-3">
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmSetAllocationModal">
                                                <i class="fas fa-save me-2"></i>Set Allocations
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Performance Management Tab -->
                        <div class="tab-pane fade" id="performance" role="tabpanel" aria-labelledby="performance-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h3><i class="fas fa-tasks me-2"></i>Manage Evaluation Questions</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-5 mb-4">
                                            <div class="card h-100">
                                                <div class="card-header bg-primary bg-opacity-25">
                                                    <h5 class="mb-0">Add New Question</h5>
                                                </div>
                                                <div class="card-body">
                                                    <form method="POST" action="../admin/manageQuestions.php" class="needs-validation" novalidate>
                                                        <div class="form-group position-relative mb-3">
                                                            <select name="position" class="form-select" required>
                                                                <option value="" disabled selected>Select Position</option>
                                                                <option value="Admin">Admin</option>
                                                                <option value="Supervisor">Supervisor</option>
                                                                <option value="Staff">Staff</option>
                                                                <option value="Field Worker">Field Worker</option>
                                                                <option value="Contractual">Contractual</option>
                                                            </select>
                                                            <span class="floating-label">Position</span>
                                                        </div>
                                                        <div class="form-group position-relative mb-3">
                                                            <select name="category" class="form-select" required>
                                                                <option value="" disabled selected>Select Category</option>
                                                                <option value="Communication Skills">Communication Skills</option>
                                                                <option value="Initiative">Initiative</option>
                                                                <option value="Punctuality">Punctuality</option>
                                                                <option value="Quality of Work">Quality of Work</option>
                                                                <option value="Teamwork">Teamwork</option>
                                                            </select>
                                                            <span class="floating-label">Category</span>
                                                        </div>
                                                        <div class="form-group position-relative mb-3">
                                                            <textarea name="question" class="form-control" rows="4" required placeholder="Enter evaluation question"></textarea>
                                                            <span class="floating-label">Question</span>
                                                        </div>
                                                        <div class="d-flex justify-content-end">
                                                            <button type="submit" name="add_question" class="btn btn-primary">
                                                                <i class="fas fa-plus-circle me-2"></i>Add Question
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-7">
                                            <div class="card h-100">
                                                <div class="card-header bg-primary bg-opacity-25">
                                                    <h5 class="mb-0">Current Questions</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div id="questions">
                                                        <div class="accordion" id="questionAccordion">
                                                            <?php if (!empty($questions)): ?>
                                                                <?php
                                                                // Group questions by category and then by position
                                                                $categories = [];
                                                                foreach ($questions as $question) {
                                                                    $categories[$question['category']][$question['position']][] = $question;
                                                                }

                                                                // Define the desired order of positions
                                                                $positionOrder = ['Admin', 'Supervisor', 'Staff', 'Field Worker', 'Contractual'];
                                                                ?>

                                                                <?php foreach ($categories as $category => $positions): ?>
                                                                    <?php
                                                                    // Sanitize category names for use in id and data-target
                                                                    $categoryId = str_replace(' ', '_', $category);
                                                                    ?>
                                                                    <div class="accordion-item">
                                                                        <h2 class="accordion-header" id="heading-<?php echo htmlspecialchars($categoryId); ?>">
                                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                                    data-bs-target="#collapse-<?php echo htmlspecialchars($categoryId); ?>"
                                                                                    aria-expanded="false" aria-controls="collapse-<?php echo htmlspecialchars($categoryId); ?>">
                                                                                <i class="fas fa-list-alt me-2"></i><?php echo htmlspecialchars($category); ?>
                                                                            </button>
                                                                        </h2>
                                                                        <div id="collapse-<?php echo htmlspecialchars($categoryId); ?>"
                                                                            class="accordion-collapse collapse"
                                                                            aria-labelledby="heading-<?php echo htmlspecialchars($categoryId); ?>"
                                                                            data-bs-parent="#questionAccordion">
                                                                            <div class="accordion-body">
                                                                                <div class="accordion" id="positionAccordion-<?php echo htmlspecialchars($categoryId); ?>">
                                                                                    <?php
                                                                                    // Sort positions based on the defined order
                                                                                    uksort($positions, function($a, $b) use ($positionOrder) {
                                                                                        return array_search($a, $positionOrder) <=> array_search($b, $positionOrder);
                                                                                    });
                                                                                    ?>

                                                                                    <?php foreach ($positions as $position => $questionsList): ?>
                                                                                        <?php
                                                                                        // Sanitize position names for use in id and data-target
                                                                                        $positionId = str_replace(' ', '_', $position);
                                                                                        ?>
                                                                                        <div class="accordion-item">
                                                                                            <h2 class="accordion-header" id="heading-<?php echo htmlspecialchars($categoryId . '_' . $positionId); ?>">
                                                                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                                                        data-bs-target="#collapse-<?php echo htmlspecialchars($categoryId . '_' . $positionId); ?>"
                                                                                                        aria-expanded="false" aria-controls="collapse-<?php echo htmlspecialchars($categoryId . '_' . $positionId); ?>">
                                                                                                    <i class="fas fa-user-tag me-2"></i><?php echo htmlspecialchars($position); ?>
                                                                                                </button>
                                                                                            </h2>
                                                                                            <div id="collapse-<?php echo htmlspecialchars($categoryId . '_' . $positionId); ?>"
                                                                                                class="accordion-collapse collapse"
                                                                                                aria-labelledby="heading-<?php echo htmlspecialchars($categoryId . '_' . $positionId); ?>"
                                                                                                data-bs-parent="#positionAccordion-<?php echo htmlspecialchars($categoryId); ?>">
                                                                                                <div class="accordion-body">
                                                                                                    <ul class="list-group">
                                                                                                        <?php foreach ($questionsList as $question): ?>
                                                                                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                                                                <span><?php echo htmlspecialchars($question['question']); ?></span>
                                                                                                                <div class="btn-group">
                                                                                                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editQuestionModal"
                                                                                                                            data-qid="<?php echo $question['id']; ?>"
                                                                                                                            data-question="<?php echo htmlspecialchars($question['question']); ?>"
                                                                                                                            data-position="<?php echo htmlspecialchars($question['position']); ?>">
                                                                                                                        <i class="fas fa-edit me-1"></i>Edit
                                                                                                                    </button>
                                                                                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteQuestionModal" data-qid="<?php echo $question['id']; ?>">
                                                                                                                        <i class="fas fa-trash-alt me-1"></i>Delete
                                                                                                                    </button>
                                                                                                                </div>
                                                                                                            </li>
                                                                                                        <?php endforeach; ?>
                                                                                                    </ul>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <div class="d-flex justify-content-center align-items-center p-5">
                                                                    <div class="text-center text-secondary">
                                                                        <i class="fas fa-question-circle fa-3x mb-3"></i>
                                                                        <p class="mb-0">No questions found. Add your first evaluation question.</p>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Calendar Settings Tab -->
                        <div class="tab-pane fade" id="calendar-settings" role="tabpanel" aria-labelledby="calendar-tab">
                            <div class="card">
                                <div class="card-header">
                                    <h3><i class="fas fa-calendar-alt me-2"></i>Set Non-Working Days</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-5 mb-4">
                                            <div class="card h-100">
                                                <div class="card-header bg-primary bg-opacity-25">
                                                    <h5 class="mb-0">Add Non-Working Day</h5>
                                                </div>
                                                <div class="card-body">
                                                    <form id="nonWorkingDayForm">
                                                        <div class="form-group position-relative mb-3">
                                                            <div class="input-with-icon">
                                                                <input type="date" id="date" class="form-control" required>
                                                                <i class="fas fa-calendar-day input-icon"></i>
                                                            </div>
                                                            <span class="floating-label">Date</span>
                                                        </div>
                                                        <div class="form-group position-relative mb-3">
                                                            <div class="input-with-icon">
                                                                <input type="text" id="description" class="form-control" placeholder="Add description" required>
                                                                <i class="fas fa-info-circle input-icon"></i>
                                                            </div>
                                                            <span class="floating-label">Description</span>
                                                        </div>
                                                        <div class="d-flex justify-content-end">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fas fa-plus-circle me-2"></i>Add Non-Working Day
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-7">
                                            <div class="card h-100">
                                                <div class="card-header bg-primary bg-opacity-25">
                                                    <h5 class="mb-0">Existing Non-Working Days</h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover" id="nonWorkingDaysTable">
                                                            <thead>
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Description</th>
                                                                    <th>Type</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <!-- Existing non-working days will be populated here -->
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Edit Question Modal -->
            <div class="modal fade" id="editQuestionModal" tabindex="-1" role="dialog" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editQuestionModalLabel">Edit Question</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="manageQuestions.php">
                                <input type="hidden" name="id" id="editQId">
                                <div class="form-group position-relative mb-3">
                                    <textarea name="new_question" id="editNewQuestion" class="form-control" rows="4" required></textarea>
                                    <span class="floating-label">New Question</span>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="edit_question" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Logout Modal -->
            <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to log out?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form action="../admin/logout.php" method="POST">
                                <button type="submit" class="btn btn-danger">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Success Modal for Adding Non-Working Day -->
            <div class="modal fade" id="successAddModal" tabindex="-1" aria-labelledby="successAddModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="successAddModalLabel">Success</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="successAddMessage">
                            Non-working day added successfully!
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirm Delete Modal -->
            <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmDeleteLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this non-working day?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Modal for Deleting Non-Working Day -->
            <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="successModalLabel">Success</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="successMessage">
                            Non-working day deleted successfully!
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirmation Modal for Setting Allocations -->
            <div class="modal fade" id="confirmSetAllocationModal" tabindex="-1" aria-labelledby="confirmSetAllocationLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmSetAllocationLabel">Confirm Allocation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to set these leave allocations?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmSetAllocationBtn">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal for confirming question deletion -->
            <div class="modal fade" id="confirmDeleteQuestionModal" tabindex="-1" aria-labelledby="confirmDeleteQuestionLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmDeleteQuestionLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this question?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="POST" action="../admin/manageQuestions.php" class="d-inline">
                                <input type="hidden" name="id" id="deleteQuestionId">
                                <button type="submit" name="delete_question" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="py-4 bg-dark text-light mt-auto border-top border-secondary">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2024</div>
                        <div>
                            <a href="#" class="text-decoration-none text-secondary">Privacy Policy</a>
                            &middot;
                            <a href="#" class="text-decoration-none text-secondary">Terms & Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="../js/admin.js"></script>
    <script>
    // Bootstrap form validation script
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
        })();

        // Toggle display of leave fields
        document.getElementById('gender').addEventListener('change', function () {
            var gender = this.value;
            document.getElementById('male-leave').style.display = gender === 'Male' ? 'flex' : 'none';
            document.getElementById('female-leave').style.display = gender === 'Female' ? 'flex' : 'none';
        });

            //EVALUATION QUESTIONS
            // Populate the edit modal with question data
            $('#editQuestionModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var qid = button.data('qid'); // Extract info from data-* attributes
                var question = button.data('question'); // Extract the question
                var position = button.data('position'); // Extract the position

                var modal = $(this);
                modal.find('#editQId').val(qid); // Insert question ID into the modal's input
                modal.find('#editNewQuestion').val(question); // Insert question into the modal's textarea
                modal.find('#editPosition').val(position);
            });

            // Populate the delete modal with question ID
            $('#confirmDeleteQuestionModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var qid = button.data('qid'); // Extract info from data-* attributes

                var modal = $(this);
                modal.find('#deleteQuestionId').val(qid); // Insert question ID into the modal's input
            });
            //EVALUATION QUESTIONS END

            //FETCH EMPLOYEE
            function updateEmployeeList() {
                console.log("updateEmployeeList function called"); // Debugging line
                const gender = document.getElementById('gender').value;
                console.log("Gender value:", gender); // Debugging line to check the gender value

                const employeeSelect = document.getElementById('employee_id');
                console.log("Employee select element:", employeeSelect); // Ensure element exists

                // Clear existing options
                employeeSelect.innerHTML = '<option value="" disabled selected>Select Employee</option>';

                if (gender) {
                    console.log("Selected gender:", gender); // Debugging line
                    const filteredEmployees = employees.filter(emp => emp.gender.toLowerCase() === gender.toLowerCase());
                    console.log("Filtered employees:", filteredEmployees); // Debugging line

                    // Check if there are any employees to populate
                    if (filteredEmployees.length > 0) {
                        // Add "All Employees" option
                        const allEmployeesOption = document.createElement('option');
                        allEmployeesOption.value = 'all';
                        allEmployeesOption.textContent = 'All Employees';
                        employeeSelect.appendChild(allEmployeesOption);

                        // Populate the employee dropdown
                        filteredEmployees.forEach(emp => {
                            const option = document.createElement('option');
                            option.value = emp.e_id;
                            option.textContent = `${emp.firstname} ${emp.lastname}`; // Use template literals
                            employeeSelect.appendChild(option);
                        });

                        // Enable the employee dropdown
                        employeeSelect.disabled = false;
                        console.log("Employee dropdown disabled status:", employeeSelect.disabled); // Debugging line
                    } else {
                        const noResultsOption = document.createElement('option');
                        noResultsOption.disabled = true;
                        noResultsOption.textContent = "No employees found for the selected gender";
                        employeeSelect.appendChild(noResultsOption);
                        employeeSelect.disabled = true;
                    }
                } else {
                    // Disable the employee dropdown if no gender is selected
                    employeeSelect.disabled = true;
                }
            }

            // Initialize the employee list based on the selected gender (if any)
            document.addEventListener('DOMContentLoaded', function() {
                console.log("DOM fully loaded and parsed"); // Debugging line
                updateEmployeeList();
            });

            // Add event listener for when gender changes dynamically (if applicable)
            document.getElementById('gender').addEventListener('change', function() {
                updateEmployeeList();
            });

        //FETCH EMPLOYEE END

        document.addEventListener('DOMContentLoaded', function() {
            fetchNonWorkingDays();

            document.getElementById('nonWorkingDayForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const date = document.getElementById('date').value;
                const description = document.getElementById('description').value;

                fetch('../db/nowork_days.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ date, description }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Show the success modal
                        var successModal = new bootstrap.Modal(document.getElementById('successAddModal'));
                        successModal.show();

                        document.getElementById('nonWorkingDayForm').reset();
                        fetchNonWorkingDays();  // Refresh the table
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
            });
        });

        function fetchNonWorkingDays() {
            fetch('../db/nowork_days.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('nonWorkingDaysTable').querySelector('tbody');
                    tbody.innerHTML = ''; // Clear existing rows

                    if (data.length === 0) {
                        // If no data, display "No non-working days found"
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No non-working days found</td></tr>';
                    } else {
                        // Populate the table with data
                        data.forEach(day => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${day.date}</td>
                                <td>${day.description}</td>
                                <td>${day.type}</td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="deleteNonWorkingDay('${day.date}')">
                                        <i class="fas fa-trash-alt me-1"></i>Delete
                                    </button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching non-working days:', error);
                    const tbody = document.getElementById('nonWorkingDaysTable').querySelector('tbody');
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>';
                });
        }

        // Call the function to fetch and display non-working days
        fetchNonWorkingDays();

        let selectedDate = null;

        function deleteNonWorkingDay(date) {
            selectedDate = date;
            var deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            deleteModal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
            if (selectedDate) {
                fetch('../db/del_nowork.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ date: selectedDate }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                        successModal.show();
                        fetchNonWorkingDays();  // Refresh the table
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });

                var deleteModal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                deleteModal.hide();
            }
        });

        document.getElementById('date').addEventListener('focus', function() {
            this.showPicker(); // Opens the native date picker
        });

        // Confirmation for setting leave allocations
        document.getElementById('confirmSetAllocationBtn').addEventListener('click', function () {
            document.querySelector('form[action="../db/setLeave.php"]').submit();
            var confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmSetAllocationModal'));
            confirmModal.hide();
        });
    </script>
</body>
</html>