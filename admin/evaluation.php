<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

include '../db/db_conn.php';

// Fetch the admin's ID
$adminId = $_SESSION['a_id'];

// Fetch the admin's info
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();

// Function to get evaluation progress by department for a specific admin
function getAdminEvaluationProgress($conn, $department, $adminId) {
    // Get total employees in the department
    $employeeQuery = "SELECT COUNT(*) as total FROM employee_register WHERE department = '$department'";
    $employeeResult = $conn->query($employeeQuery);
    $totalEmployees = $employeeResult->fetch_assoc()['total'];

    // Get total employees evaluated by the admin in the department
    $evaluatedQuery = "SELECT COUNT(*) as evaluated FROM admin_evaluations WHERE department = '$department' AND a_id = '$adminId'";
    $evaluatedResult = $conn->query($evaluatedQuery);
    $evaluated = $evaluatedResult->fetch_assoc()['evaluated'];

    $pendingEmployees = $totalEmployees - $evaluated;

    return array('total' => $totalEmployees, 'evaluated' => $evaluated, 'pending' => $pendingEmployees);
}

// Fetch data for different departments for the logged-in admin
$financeData = getAdminEvaluationProgress($conn, 'Finance Department', $adminId);
$hrData = getAdminEvaluationProgress($conn, 'Human Resource Department', $adminId);
$administrationData = getAdminEvaluationProgress($conn, 'Administration Department', $adminId);
$salesData = getAdminEvaluationProgress($conn, 'Sales Department', $adminId);
$creditData = getAdminEvaluationProgress($conn, 'Credit Department', $adminId);
$itData = getAdminEvaluationProgress($conn, 'IT Department', $adminId);

// Check if it is the first week of the month
$currentDay = date('j'); // Current day of the month (1-31)
$isFirstWeek = ($currentDay <= 7); // First week is days 1-7

// Set the evaluation period to the previous month if it is the first week
if ($isFirstWeek) {
    $evaluationMonth = date('m', strtotime('last month')); // Previous month
    $evaluationYear = date('Y', strtotime('last month'));  // Year of the previous month
    $evaluationPeriod = date('F Y', strtotime('last month')); // Format: February 2024

    // Calculate the end date of the evaluation period (7th day of the current month)
    $evaluationEndDate = date('F j, Y', strtotime(date('Y-m-07'))); // Format: March 7, 2024
} else {
    // If it is not the first week, evaluations are closed
    $evaluationMonth = null;
    $evaluationYear = null;
    $evaluationPeriod = null;
    $evaluationEndDate = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Dashboard | HR2</title>
    <link href="../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<style>
    .star-rating {
        display: flex;
        direction: rtl;
        unicode-bidi: bidi-override;
    }

    .star-rating input[type="radio"] {
        display: none;
    }

    .star-rating label {
        font-size: 24px;
        color: #ddd;
        cursor: pointer;
        padding: 0 2px;
    }

    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input[type="radio"]:checked ~ label {
        color: #ffc107;
    }

    .star-rating input[type="radio"]:checked ~ label {
        color: #ffc107;
    }

    .btn-close-white {
        border: none;
        background: none;
    }

    .btn-close-white:hover {
        background-color: #ffc107;
        color: white;
    }
</style>

<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
       <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
            <main class="bg-black">
                <div class="container-fluid position-relative px-4">
                    <h1 class="mb-4 text-light">Evaluation</h1>
                </div>
                <div class="container-fluid px-4">
                    <div class="row justify-content-center">
                        <!-- Navigation Links for Departments -->
                        <div class="col-xl-12 col-md-12 mt-5">
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#finance">Finance Department</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#hr">Human Resource Department</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#administration">Administration Department</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#sales">Sales Department</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#credit">Credit Department</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#it">IT Department</a>
                                </li>
                            </ul>
                        </div>

                        <!-- Tab Content for Departments -->
                        <div class="tab-content">
                            <!-- Finance Department -->
                            <div class="tab-pane fade show active" id="finance">
                                <div class="card mb-4">
                                    <div class="card-body bg-secondary text-center">
                                        <h5 class="text-center mb-4 text-light">Finance Department Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $financeData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $financeData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $financeData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($financeData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($financeData['evaluated'] / $financeData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $financeData['evaluated']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $financeData['total']; ?>">
                                                    Evaluated (<?php echo $financeData['evaluated']; ?>)
                                                </div>
                                                <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($financeData['pending'] / $financeData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $financeData['pending']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $financeData['total']; ?>">
                                                    Pending (<?php echo $financeData['pending']; ?>)
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar"
                                                    aria-valuenow="0"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <?php if ($evaluationPeriod): ?>
                                        <div class="alert alert-info" role="alert">
                                            <strong>Evaluation Period:</strong> <?php echo $evaluationPeriod; ?>
                                            <br>
                                            <strong>Evaluation End Date:</strong> <?php echo $evaluationEndDate; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning" role="alert">
                                            Evaluations are currently closed.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                                // Include the database connection
                                include '../db/db_conn.php';

                                // Define the values for role and department
                                $role = 'employee';
                                $department = 'Finance Department';

                                // Fetch employee records where role is 'employee' and department is 'Finance Department'
                                $sql = "SELECT e_id, firstname, lastname, role, position FROM employee_register WHERE role = ? AND department = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param('ss', $role, $department);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                // Fetch evaluations for this admin
                                $adminId = $_SESSION['a_id'];
                                $evaluatedEmployees = [];
                                $evalSql = "SELECT e_id FROM admin_evaluations WHERE a_id = ?";
                                $evalStmt = $conn->prepare($evalSql);
                                $evalStmt->bind_param('i', $adminId);
                                $evalStmt->execute();
                                $evalResult = $evalStmt->get_result();
                                if ($evalResult->num_rows > 0) {
                                    while ($row = $evalResult->fetch_assoc()) {
                                        $evaluatedEmployees[] = $row['e_id'];
                                    }
                                }

                                // Fetch evaluation questions from the database for each category
                                $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                                $questions = [];

                                foreach ($categories as $category) {
                                    $categorySql = "SELECT question FROM evaluation_questions WHERE category = ?";
                                    $categoryStmt = $conn->prepare($categorySql);
                                    $categoryStmt->bind_param('s', $category);
                                    $categoryStmt->execute();
                                    $categoryResult = $categoryStmt->get_result();
                                    $questions[$category] = [];

                                    if ($categoryResult->num_rows > 0) {
                                        while ($row = $categoryResult->fetch_assoc()) {
                                            $questions[$category][] = $row['question'];
                                        }
                                    }
                                }

                                // Check if any records are found
                                $employees = [];
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $employees[] = $row;
                                    }
                                }

                                // Close the database connection
                                $conn->close();
                                ?>

                                <div class="container mt-5">
                                    <h2 class="text-center text-primary mb-4">Finance Department Evaluation</h2>

                                    <!-- Employee Evaluation Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover text-dark">
                                            <thead class="thead-dark text-white">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Position</th>
                                                    <th>Role</th>
                                                    <th>Evaluation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($employees)): ?>
                                                    <?php foreach ($employees as $employee): ?>
                                                        <tr>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                            <td>
                                                                <button class="btn btn-success"
                                                                    onclick="evaluateEmployee(<?php echo $employee['e_id']; ?>, '<?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>', '<?php echo htmlspecialchars($employee['position']); ?>', 'Finance Department')"
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'Evaluated' : 'Evaluate'; ?>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in Finance Department.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Evaluation Modal -->
                                <div class="modal fade" id="evaluationModal" tabindex="-1" aria-labelledby="evaluationModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="evaluationModalLabel">Employee Evaluation</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div id="employeeDetails"></div>
                                                <div id="questions"></div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-primary" onclick="submitEvaluation()">Submit</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Modal -->
                                <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="statusModalLabel">Status</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- Status message will be injected here -->
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Human Resource Department -->
                            <div class="tab-pane fade" id="hr">
                                <div class="card mb-4">
                                    <div class="card-body bg-secondary text-center">
                                        <h5 class="text-center mb-4 text-light">Human Resource Department Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $hrData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $hrData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $hrData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($hrData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($hrData['evaluated'] / $hrData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $hrData['evaluated']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $hrData['total']; ?>">
                                                    Evaluated (<?php echo $hrData['evaluated']; ?>)
                                                </div>
                                                <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($hrData['pending'] / $hrData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $hrData['pending']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $hrData['total']; ?>">
                                                    Pending (<?php echo $hrData['pending']; ?>)
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar"
                                                    aria-valuenow="0"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <?php if ($evaluationPeriod): ?>
                                        <div class="alert alert-info" role="alert">
                                            <strong>Evaluation Period:</strong> <?php echo $evaluationPeriod; ?>
                                            <br>
                                            <strong>Evaluation End Date:</strong> <?php echo $evaluationEndDate; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning" role="alert">
                                            Evaluations are currently closed.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                                // Include the database connection
                                include '../db/db_conn.php';

                                // Define the values for role and department
                                $role = 'employee';
                                $department = 'Human Resource Department';

                                // Fetch employee records where role is 'employee' and department is 'Human Resource Department'
                                $sql = "SELECT e_id, firstname, lastname, role, position FROM employee_register WHERE role = ? AND department = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param('ss', $role, $department);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                // Fetch evaluations for this admin
                                $adminId = $_SESSION['a_id'];
                                $evaluatedEmployees = [];
                                $evalSql = "SELECT e_id FROM admin_evaluations WHERE a_id = ?";
                                $evalStmt = $conn->prepare($evalSql);
                                $evalStmt->bind_param('i', $adminId);
                                $evalStmt->execute();
                                $evalResult = $evalStmt->get_result();
                                if ($evalResult->num_rows > 0) {
                                    while ($row = $evalResult->fetch_assoc()) {
                                        $evaluatedEmployees[] = $row['e_id'];
                                    }
                                }

                                // Fetch evaluation questions from the database for each category
                                $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                                $questions = [];

                                foreach ($categories as $category) {
                                    $categorySql = "SELECT question FROM evaluation_questions WHERE category = ?";
                                    $categoryStmt = $conn->prepare($categorySql);
                                    $categoryStmt->bind_param('s', $category);
                                    $categoryStmt->execute();
                                    $categoryResult = $categoryStmt->get_result();
                                    $questions[$category] = [];

                                    if ($categoryResult->num_rows > 0) {
                                        while ($row = $categoryResult->fetch_assoc()) {
                                            $questions[$category][] = $row['question'];
                                        }
                                    }
                                }

                                // Check if any records are found
                                $employees = [];
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $employees[] = $row;
                                    }
                                }

                                // Close the database connection
                                $conn->close();
                                ?>

                                <div class="container mt-5">
                                    <h2 class="text-center text-primary mb-4">Human Resource Department Evaluation</h2>

                                    <!-- Employee Evaluation Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover text-dark">
                                            <thead class="thead-dark text-white">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Position</th>
                                                    <th>Role</th>
                                                    <th>Evaluation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($employees)): ?>
                                                    <?php foreach ($employees as $employee): ?>
                                                        <tr>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                            <td>
                                                                <button class="btn btn-success"
                                                                    onclick="evaluateEmployee(<?php echo $employee['e_id']; ?>, '<?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>', '<?php echo htmlspecialchars($employee['position']); ?>', 'Human Resource Department')"
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'Evaluated' : 'Evaluate'; ?>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in Human Resource Department.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <!-- Administration Department -->
                            <div class="tab-pane fade" id="administration">
                                <div class="card mb-4">
                                    <div class="card-body bg-secondary text-center">
                                        <h5 class="text-center mb-4 text-light">Administration Department Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $administrationData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $administrationData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $administrationData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($administrationData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($administrationData['evaluated'] / $administrationData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $administrationData['evaluated']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $administrationData['total']; ?>">
                                                    Evaluated (<?php echo $administrationData['evaluated']; ?>)
                                                </div>
                                                <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($administrationData['pending'] / $administrationData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $administrationData['pending']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $administrationData['total']; ?>">
                                                    Pending (<?php echo $administrationData['pending']; ?>)
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar"
                                                    aria-valuenow="0"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <?php if ($evaluationPeriod): ?>
                                        <div class="alert alert-info" role="alert">
                                            <strong>Evaluation Period:</strong> <?php echo $evaluationPeriod; ?>
                                            <br>
                                            <strong>Evaluation End Date:</strong> <?php echo $evaluationEndDate; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning" role="alert">
                                            Evaluations are currently closed.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                                // Include the database connection
                                include '../db/db_conn.php';

                                // Define the values for role and department
                                $role = 'employee';
                                $department = 'Administration Department';

                                // Fetch employee records where role is 'employee' and department is 'Administration Department'
                                $sql = "SELECT e_id, firstname, lastname, role, position FROM employee_register WHERE role = ? AND department = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param('ss', $role, $department);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                // Fetch evaluations for this admin
                                $adminId = $_SESSION['a_id'];
                                $evaluatedEmployees = [];
                                $evalSql = "SELECT e_id FROM admin_evaluations WHERE a_id = ?";
                                $evalStmt = $conn->prepare($evalSql);
                                $evalStmt->bind_param('i', $adminId);
                                $evalStmt->execute();
                                $evalResult = $evalStmt->get_result();
                                if ($evalResult->num_rows > 0) {
                                    while ($row = $evalResult->fetch_assoc()) {
                                        $evaluatedEmployees[] = $row['e_id'];
                                    }
                                }

                                // Fetch evaluation questions from the database for each category
                                $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                                $questions = [];

                                foreach ($categories as $category) {
                                    $categorySql = "SELECT question FROM evaluation_questions WHERE category = ?";
                                    $categoryStmt = $conn->prepare($categorySql);
                                    $categoryStmt->bind_param('s', $category);
                                    $categoryStmt->execute();
                                    $categoryResult = $categoryStmt->get_result();
                                    $questions[$category] = [];

                                    if ($categoryResult->num_rows > 0) {
                                        while ($row = $categoryResult->fetch_assoc()) {
                                            $questions[$category][] = $row['question'];
                                        }
                                    }
                                }

                                // Check if any records are found
                                $employees = [];
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $employees[] = $row;
                                    }
                                }

                                // Close the database connection
                                $conn->close();
                                ?>

                                <div class="container mt-5">
                                    <h2 class="text-center text-primary mb-4">Administration Department Evaluation</h2>

                                    <!-- Employee Evaluation Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover text-dark">
                                            <thead class="thead-dark text-white">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Position</th>
                                                    <th>Role</th>
                                                    <th>Evaluation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($employees)): ?>
                                                    <?php foreach ($employees as $employee): ?>
                                                        <tr>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                            <td>
                                                                <button class="btn btn-success"
                                                                    onclick="evaluateEmployee(<?php echo $employee['e_id']; ?>, '<?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>', '<?php echo htmlspecialchars($employee['position']); ?>', 'Administration Department')"
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'Evaluated' : 'Evaluate'; ?>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in Administration Department.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <!-- Sales Department -->
                            <div class="tab-pane fade" id="sales">
                                <div class="card mb-4">
                                    <div class="card-body bg-secondary text-center">
                                        <h5 class="text-center mb-4 text-light">Sales Department Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $salesData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $salesData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $salesData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($salesData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($salesData['evaluated'] / $salesData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $salesData['evaluated']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $salesData['total']; ?>">
                                                    Evaluated (<?php echo $salesData['evaluated']; ?>)
                                                </div>
                                                <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($salesData['pending'] / $salesData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $salesData['pending']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $salesData['total']; ?>">
                                                    Pending (<?php echo $salesData['pending']; ?>)
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar"
                                                    aria-valuenow="0"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <?php if ($evaluationPeriod): ?>
                                        <div class="alert alert-info" role="alert">
                                            <strong>Evaluation Period:</strong> <?php echo $evaluationPeriod; ?>
                                            <br>
                                            <strong>Evaluation End Date:</strong> <?php echo $evaluationEndDate; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning" role="alert">
                                            Evaluations are currently closed.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                                // Include the database connection
                                include '../db/db_conn.php';

                                // Define the values for role and department
                                $role = 'employee';
                                $department = 'Sales Department';

                                // Fetch employee records where role is 'employee' and department is 'Sales Department'
                                $sql = "SELECT e_id, firstname, lastname, role, position FROM employee_register WHERE role = ? AND department = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param('ss', $role, $department);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                // Fetch evaluations for this admin
                                $adminId = $_SESSION['a_id'];
                                $evaluatedEmployees = [];
                                $evalSql = "SELECT e_id FROM admin_evaluations WHERE a_id = ?";
                                $evalStmt = $conn->prepare($evalSql);
                                $evalStmt->bind_param('i', $adminId);
                                $evalStmt->execute();
                                $evalResult = $evalStmt->get_result();
                                if ($evalResult->num_rows > 0) {
                                    while ($row = $evalResult->fetch_assoc()) {
                                        $evaluatedEmployees[] = $row['e_id'];
                                    }
                                }

                                // Fetch evaluation questions from the database for each category
                                $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                                $questions = [];

                                foreach ($categories as $category) {
                                    $categorySql = "SELECT question FROM evaluation_questions WHERE category = ?";
                                    $categoryStmt = $conn->prepare($categorySql);
                                    $categoryStmt->bind_param('s', $category);
                                    $categoryStmt->execute();
                                    $categoryResult = $categoryStmt->get_result();
                                    $questions[$category] = [];

                                    if ($categoryResult->num_rows > 0) {
                                        while ($row = $categoryResult->fetch_assoc()) {
                                            $questions[$category][] = $row['question'];
                                        }
                                    }
                                }

                                // Check if any records are found
                                $employees = [];
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $employees[] = $row;
                                    }
                                }

                                // Close the database connection
                                $conn->close();
                                ?>

                                <div class="container mt-5">
                                    <h2 class="text-center text-primary mb-4">Sales Department Evaluation</h2>

                                    <!-- Employee Evaluation Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover text-dark">
                                            <thead class="thead-dark text-white">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Position</th>
                                                    <th>Role</th>
                                                    <th>Evaluation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($employees)): ?>
                                                    <?php foreach ($employees as $employee): ?>
                                                        <tr>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                            <td>
                                                                <button class="btn btn-success"
                                                                    onclick="evaluateEmployee(<?php echo $employee['e_id']; ?>, '<?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>', '<?php echo htmlspecialchars($employee['position']); ?>', 'Sales Department')"
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'Evaluated' : 'Evaluate'; ?>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in Sales Department.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <!-- Credit Department -->
                            <div class="tab-pane fade" id="credit">
                                <div class="card mb-4">
                                    <div class="card-body bg-secondary text-center">
                                        <h5 class="text-center mb-4 text-light">Credit Department Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $creditData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $creditData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $creditData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($creditData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($creditData['evaluated'] / $creditData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $creditData['evaluated']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $creditData['total']; ?>">
                                                    Evaluated (<?php echo $creditData['evaluated']; ?>)
                                                </div>
                                                <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($creditData['pending'] / $creditData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $creditData['pending']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $creditData['total']; ?>">
                                                    Pending (<?php echo $creditData['pending']; ?>)
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar"
                                                    aria-valuenow="0"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <?php if ($evaluationPeriod): ?>
                                        <div class="alert alert-info" role="alert">
                                            <strong>Evaluation Period:</strong> <?php echo $evaluationPeriod; ?>
                                            <br>
                                            <strong>Evaluation End Date:</strong> <?php echo $evaluationEndDate; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning" role="alert">
                                            Evaluations are currently closed.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                                // Include the database connection
                                include '../db/db_conn.php';

                                // Define the values for role and department
                                $role = 'employee';
                                $department = 'Credit Department';

                                // Fetch employee records where role is 'employee' and department is 'Credit Department'
                                $sql = "SELECT e_id, firstname, lastname, role, position FROM employee_register WHERE role = ? AND department = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param('ss', $role, $department);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                // Fetch evaluations for this admin
                                $adminId = $_SESSION['a_id'];
                                $evaluatedEmployees = [];
                                $evalSql = "SELECT e_id FROM admin_evaluations WHERE a_id = ?";
                                $evalStmt = $conn->prepare($evalSql);
                                $evalStmt->bind_param('i', $adminId);
                                $evalStmt->execute();
                                $evalResult = $evalStmt->get_result();
                                if ($evalResult->num_rows > 0) {
                                    while ($row = $evalResult->fetch_assoc()) {
                                        $evaluatedEmployees[] = $row['e_id'];
                                    }
                                }

                                // Fetch evaluation questions from the database for each category
                                $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                                $questions = [];

                                foreach ($categories as $category) {
                                    $categorySql = "SELECT question FROM evaluation_questions WHERE category = ?";
                                    $categoryStmt = $conn->prepare($categorySql);
                                    $categoryStmt->bind_param('s', $category);
                                    $categoryStmt->execute();
                                    $categoryResult = $categoryStmt->get_result();
                                    $questions[$category] = [];

                                    if ($categoryResult->num_rows > 0) {
                                        while ($row = $categoryResult->fetch_assoc()) {
                                            $questions[$category][] = $row['question'];
                                        }
                                    }
                                }

                                // Check if any records are found
                                $employees = [];
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $employees[] = $row;
                                    }
                                }

                                // Close the database connection
                                $conn->close();
                                ?>

                                <div class="container mt-5">
                                    <h2 class="text-center text-primary mb-4">Credit Department Evaluation</h2>

                                    <!-- Employee Evaluation Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover text-dark">
                                            <thead class="thead-dark text-white">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Position</th>
                                                    <th>Role</th>
                                                    <th>Evaluation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($employees)): ?>
                                                    <?php foreach ($employees as $employee): ?>
                                                        <tr>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                            <td>
                                                                <button class="btn btn-success"
                                                                    onclick="evaluateEmployee(<?php echo $employee['e_id']; ?>, '<?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>', '<?php echo htmlspecialchars($employee['position']); ?>', 'Credit Department')"
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'Evaluated' : 'Evaluate'; ?>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in Credit Department.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <!-- IT Department -->
                            <div class="tab-pane fade" id="it">
                                <div class="card mb-4">
                                    <div class="card-body bg-secondary text-center">
                                        <h5 class="text-center mb-4 text-light">IT Department Evaluation Status</h5>
                                        <div class="text-center mb-3">
                                            <span class="badge badge-primary mx-1">Total Employees: <?php echo $itData['total']; ?></span>
                                            <span class="badge badge-success mx-1">Evaluated: <?php echo $itData['evaluated']; ?></span>
                                            <span class="badge badge-warning mx-1">Pending: <?php echo $itData['pending']; ?></span>
                                        </div>
                                        <div class="progress mb-2">
                                            <?php if ($itData['total'] > 0): ?>
                                                <div class="progress-bar bg-success font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($itData['evaluated'] / $itData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $itData['evaluated']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $itData['total']; ?>">
                                                    Evaluated (<?php echo $itData['evaluated']; ?>)
                                                </div>
                                                <div class="progress-bar bg-warning text-dark font-weight-bold" role="progressbar"
                                                    style="width: <?php echo ($itData['pending'] / $itData['total']) * 100; ?>%;"
                                                    aria-valuenow="<?php echo $itData['pending']; ?>"
                                                    aria-valuemin="0"
                                                    aria-valuemax="<?php echo $itData['total']; ?>">
                                                    Pending (<?php echo $itData['pending']; ?>)
                                                </div>
                                            <?php else: ?>
                                                <div class="progress-bar bg-secondary font-weight-bold w-100" role="progressbar"
                                                    aria-valuenow="0"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                    No employees available
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <?php if ($evaluationPeriod): ?>
                                        <div class="alert alert-info" role="alert">
                                            <strong>Evaluation Period:</strong> <?php echo $evaluationPeriod; ?>
                                            <br>
                                            <strong>Evaluation End Date:</strong> <?php echo $evaluationEndDate; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning" role="alert">
                                            Evaluations are currently closed.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php
                                // Include the database connection
                                include '../db/db_conn.php';

                                // Define the values for role and department
                                $role = 'employee';
                                $department = 'IT Department';

                                // Fetch employee records where role is 'employee' and department is 'IT Department'
                                $sql = "SELECT e_id, firstname, lastname, role, position FROM employee_register WHERE role = ? AND department = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param('ss', $role, $department);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                // Fetch evaluations for this admin
                                $adminId = $_SESSION['a_id'];
                                $evaluatedEmployees = [];
                                $evalSql = "SELECT e_id FROM admin_evaluations WHERE a_id = ?";
                                $evalStmt = $conn->prepare($evalSql);
                                $evalStmt->bind_param('i', $adminId);
                                $evalStmt->execute();
                                $evalResult = $evalStmt->get_result();
                                if ($evalResult->num_rows > 0) {
                                    while ($row = $evalResult->fetch_assoc()) {
                                        $evaluatedEmployees[] = $row['e_id'];
                                    }
                                }

                                // Fetch evaluation questions from the database for each category
                                $categories = ['Quality of Work', 'Communication Skills', 'Teamwork', 'Punctuality', 'Initiative'];
                                $questions = [];

                                foreach ($categories as $category) {
                                    $categorySql = "SELECT question FROM evaluation_questions WHERE category = ?";
                                    $categoryStmt = $conn->prepare($categorySql);
                                    $categoryStmt->bind_param('s', $category);
                                    $categoryStmt->execute();
                                    $categoryResult = $categoryStmt->get_result();
                                    $questions[$category] = [];

                                    if ($categoryResult->num_rows > 0) {
                                        while ($row = $categoryResult->fetch_assoc()) {
                                            $questions[$category][] = $row['question'];
                                        }
                                    }
                                }

                                // Check if any records are found
                                $employees = [];
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $employees[] = $row;
                                    }
                                }

                                // Close the database connection
                                $conn->close();
                                ?>

                                <div class="container mt-5">
                                    <h2 class="text-center text-primary mb-4">IT Department Evaluation</h2>

                                    <!-- Employee Evaluation Table -->
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover text-dark">
                                            <thead class="thead-dark text-white">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Position</th>
                                                    <th>Role</th>
                                                    <th>Evaluation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($employees)): ?>
                                                    <?php foreach ($employees as $employee): ?>
                                                        <tr>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['position']); ?></td>
                                                            <td class="text-light"><?php echo htmlspecialchars($employee['role']); ?></td>
                                                            <td>
                                                                <button class="btn btn-success"
                                                                    onclick="evaluateEmployee(<?php echo $employee['e_id']; ?>, '<?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>', '<?php echo htmlspecialchars($employee['position']); ?>', 'IT Department')"
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'disabled' : ''; ?>>
                                                                    <?php echo in_array($employee['e_id'], $evaluatedEmployees) ? 'Evaluated' : 'Evaluate'; ?>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td class="text-light text-center" colspan="4">No employees found for evaluation in IT Department.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body d-flex flex-column align-items-center justify-content-center">
                    <!-- Bouncing coin spinner -->
                    <div class="coin-spinner"></div>
                    <div class="mt-3 text-light fw-bold">Please wait...</div>
                </div>
            </div>
        </div>
    </div>

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

        //EVALUATION TOGGLE
        // Add event listener to all elements with class "department-toggle"
        document.querySelectorAll('.department-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                const target = this.getAttribute('data-target');
                const icon = this.querySelector('i');

                // Toggle the collapse
                $(target).collapse('toggle');

                // Toggle the icon classes between angle-down and angle-up
                icon.classList.toggle('fa-angle-down');
                icon.classList.toggle('fa-angle-up');
            });
        });
        //EVALUATION TOGGLE END

        let currentEmployeeId;
        let currentEmployeeName;
        let currentEmployeePosition;

        // The categories and questions fetched from the PHP script
        const questions = <?php echo json_encode($questions); ?>;

        function evaluateEmployee(e_id, employeeName, employeePosition, department) {
            currentEmployeeId = e_id;
            currentEmployeeName = employeeName;
            currentEmployeePosition = employeePosition;

            const employeeDetails = `<strong>Name: ${employeeName} <br> Position: ${employeePosition}</strong>`;
            document.getElementById('employeeDetails').innerHTML = employeeDetails;
            document.getElementById('department').value = department;

            const questionsDiv = document.getElementById('questions');
            questionsDiv.innerHTML = '';

            // Start the table structure
            let tableHtml = `
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Question</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>`;

            // Loop through categories and questions to add them into the table
            for (const [category, categoryQuestions] of Object.entries(questions)) {
                categoryQuestions.forEach((question, index) => {
                    const questionName = `${category.replace(/\s/g, '')}q${index}`; // Unique name per question
                    tableHtml += `
                    <tr>
                        <td>${index === 0 ? category : ''}</td>
                        <td>${question}</td>
                        <td>
                            <div class="star-rating">
                                ${[6, 5, 4, 3, 2, 1].map(value => `
                                    <input type="radio" name="${questionName}" value="${value}" id="${questionName}star${value}">
                                    <label for="${questionName}star${value}">&#9733;</label>
                                `).join('')}
                            </div>
                        </td>
                    </tr>`;
                });
            }

            // Close the table structure
            tableHtml += `
                </tbody>
            </table>`;

            questionsDiv.innerHTML = tableHtml;

            // Display the evaluation form in the respective department section
            const evaluationModal = new bootstrap.Modal(document.getElementById('evaluationModal'));
            evaluationModal.show();
        }

        function submitEvaluation() {
            const evaluations = [];
            const questionsDiv = document.getElementById('questions');

            questionsDiv.querySelectorAll('input[type="radio"]:checked').forEach(input => {
                evaluations.push({
                    question: input.name,
                    rating: input.value
                });
            });

            const totalQuestions = questionsDiv.querySelectorAll('.star-rating').length;

            if (evaluations.length !== totalQuestions) {
                showStatusModal('Please complete the evaluation before submitting.');
                return;
            }

            const categoryAverages = {
                QualityOfWork: calculateAverage('Quality of Work', evaluations),
                CommunicationSkills: calculateAverage('Communication Skills', evaluations),
                Teamwork: calculateAverage('Teamwork', evaluations),
                Punctuality: calculateAverage('Punctuality', evaluations),
                Initiative: calculateAverage('Initiative', evaluations)
            };

            const adminId = document.getElementById('a_id').value;
            const department = document.getElementById('department').value;

            $.ajax({
                type: 'POST',
                url: '../db/submit_evaluation.php',
                data: {
                    e_id: currentEmployeeId,
                    employeeName: currentEmployeeName,
                    employeePosition: currentEmployeePosition,
                    categoryAverages: JSON.stringify(categoryAverages),
                    adminId: adminId,
                    department: department
                },
                success: function (response) {
                    console.log(response);
                    if (response === 'You have already evaluated this employee.') {
                        showStatusModal(response);
                    } else {
                        showStatusModal('Evaluation submitted successfully!');
                        // Clear the evaluation form
                        document.getElementById('questions').innerHTML = '';
                    }
                },
                error: function (err) {
                    console.error(err);
                    showStatusModal('An error occurred while submitting the evaluation.');
                }
            });
        }

        function calculateAverage(category, evaluations) {
            const categoryEvaluations = evaluations.filter(evaluation => evaluation.question.startsWith(category.replace(/\s/g, '')));

            if (categoryEvaluations.length === 0) {
                return 0;
            }

            const total = categoryEvaluations.reduce((sum, evaluation) => sum + parseInt(evaluation.rating), 0);
            return total / categoryEvaluations.length;
        }

        function showStatusModal(message) {
            const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            document.querySelector('#statusModal .modal-body').innerHTML = `<p>${message}</p>`;
            statusModal.show();
            setTimeout(() => {
                statusModal.hide();
            }, 2000);
        }

        // Add event listener to the close button to remove the border
        document.addEventListener('DOMContentLoaded', function () {
            const closeButtons = document.querySelectorAll('.btn-close-white');
            closeButtons.forEach(button => {
                button.addEventListener('click', function () {
                    this.style.border = 'none';
                });
            });
        });
    </script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>
