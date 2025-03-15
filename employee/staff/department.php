<?php
session_start();

if (!isset($_SESSION['e_id'])) {
    header("Location: ../../employee/login.php");
    exit();
}

// Include the database connection
include '../../db/db_conn.php';

$employeeId = $_SESSION['e_id'];
$sql = "SELECT e_id, firstname, middlename, lastname, birthdate, email, role, position, department, phone_number, address, pfp FROM employee_register WHERE e_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
$employeeInfo = $result->fetch_assoc();

$position = $_SESSION['position']; // Ensure this is set during login
$department = $_SESSION['department']; // Ensure this is set during login

// Define the role
$role = 'employee';

// Fetch employee records where role is 'employee' and department matches the logged-in employee's department
$sql = "SELECT e_id, firstname, lastname, role, position FROM employee_register WHERE role = ? AND department = ? AND position IN ('supervisor', 'staff', 'fieldworker', 'contractual')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $role, $department);  // Bind the parameters for role and department (both strings)
$stmt->execute();
$result = $stmt->get_result();

// Fetch evaluations for this employee
$evaluatedEmployees = [];
$evalSql = "SELECT e_id FROM admin_evaluations WHERE e_id = ?";
$evalStmt = $conn->prepare($evalSql);
$evalStmt->bind_param('i', $employeeId);
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
        // Exclude the logged-in employee from the list
        if ($row['e_id'] != $employeeId) {
            $employees[] = $row;
        }
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Evaluation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href='../../css/styles.css' rel='stylesheet' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --success-color: #4ade80;
            --warning-color: #fbbf24;
            --danger-color: #f87171;
            --dark-bg: rgba(33, 37, 41) !important;
            --card-bg: #1f2937;
            --border-color: #374151;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-muted: #9ca3af;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .employee-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .employee-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--accent-color));
        }

        .employee-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .employee-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin-right: 1rem;
        }

        .employee-details {
            flex: 1;
        }

        .employee-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .employee-position {
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .employee-position i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }

        .employee-role {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary-color);
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }

        .evaluate-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .evaluate-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .evaluated-btn {
            background-color: var(--success-color);
            color: #065f46;
            cursor: default;
        }

        .evaluated-btn i {
            margin-right: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.2rem;
            color: var(--text-secondary);
        }

        /* Modal Styling */
        .modal-content {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            transition: color 0.2s;
        }

        .close-btn:hover {
            color: var(--text-primary);
        }

        .btn-submit {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-cancel {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }

        /* Evaluation Form Styling */
        .evaluation-form {
            margin-top: 1rem;
        }

        .category-section {
            margin-bottom: 2rem;
            border-radius: 10px;
            overflow: hidden;
        }

        .category-header {
            background-color: rgba(67, 97, 238, 0.1);
            padding: 1rem 1.5rem;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
        }

        .category-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
        }

        .category-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .question-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background-color: rgba(31, 41, 55, 0.5);
        }

        .question-item:last-child {
            border-bottom: none;
            border-radius: 0 0 8px 8px;
        }

        .question-text {
            margin-bottom: 1rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* Star Rating Styling */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            width: 40px;
            height: 40px;
            background-color: var(--dark-bg);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            color: var(--text-muted);
            font-size: 1.5rem;
            transition: all 0.2s ease;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: var(--warning-color);
            transform: scale(1.1);
        }

        .rating-value {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.5rem;
        }

        .rating-value span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .progress-container {
            margin-top: 2rem;
        }

        .progress-bar {
            height: 6px;
            border-radius: 3px;
            background-color: var(--primary-color);
        }

        .employee-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .employee-modal-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin-right: 1rem;
        }

        .employee-modal-details h5 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .employee-modal-details p {
            color: var(--text-secondary);
            margin: 0;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .employee-card {
                padding: 1.25rem;
            }

            .employee-avatar {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }

            .employee-name {
                font-size: 1.1rem;
            }

            .star-rating label {
                width: 35px;
                height: 35px;
                font-size: 1.25rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .progress-indicator {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--card-bg);
            border-radius: 20px;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            z-index: 1000;
            border: 1px solid var(--border-color);
        }

        .progress-text {
            margin-right: 1rem;
            font-weight: 500;
        }

        .progress-bar-container {
            flex: 1;
            height: 8px;
            background-color: var(--dark-bg);
            border-radius: 4px;
            overflow: hidden;
            width: 200px;
        }

        .evaluation-progress {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            width: 0%;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php include 'navbar.php'; ?>
    <div id="layoutSidenav">
        <?php include 'sidebar.php'; ?>
        <div id="layoutSidenav_content">
        <?php include 'sidebar.php'; ?>
            <main class="bg-black">
                <div class="page-container">
                    <div class="page-header">
                        <h1 class="page-title">Employee Evaluation</h1>
                        <p class="page-subtitle">Evaluate your colleagues in the <?php echo htmlspecialchars($department); ?> department</p>
                    </div>

                    <div class="employee-grid">
                        <?php if (!empty($employees)): ?>
                            <?php foreach ($employees as $index => $employee): ?>
                                <div class="employee-card fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <div class="employee-info">
                                        <div class="employee-avatar">
                                            <?php echo strtoupper(substr($employee['firstname'], 0, 1)); ?>
                                        </div>
                                        <div class="employee-details">
                                            <h3 class="employee-name"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></h3>
                                            <div class="employee-position">
                                                <i class="bi bi-briefcase-fill"></i>
                                                <?php echo htmlspecialchars($employee['position']); ?>
                                            </div>
                                            <span class="employee-role"><?php echo htmlspecialchars($employee['role']); ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <?php if (in_array($employee['e_id'], $evaluatedEmployees)): ?>
                                            <button class="action-btn evaluated-btn" disabled>
                                                <i class="bi bi-check-circle-fill"></i> Evaluated
                                            </button>
                                        <?php else: ?>
                                            <button class="action-btn evaluate-btn"
                                                onclick="evaluateEmployee(<?php echo $employee['e_id']; ?>, '<?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>', '<?php echo htmlspecialchars($employee['position']); ?>')">
                                                <i class="bi bi-star-fill me-2"></i> Evaluate
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <p>No employees found for evaluation in <?php echo htmlspecialchars($department); ?>.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Evaluation Modal -->
                <div class="modal fade" id="evaluationModal" tabindex="-1" aria-labelledby="evaluationModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="evaluationModalLabel">Employee Evaluation</h5>
                                <button type="button" class="close-btn" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="employee-header">
                                    <div class="employee-modal-avatar" id="employeeAvatar"></div>
                                    <div class="employee-modal-details" id="employeeDetails"></div>
                                </div>

                                <input type="hidden" id="e_id" value="<?php echo $_SESSION['e_id']; ?>">
                                <div id="evaluationForm" class="evaluation-form"></div>

                                <div class="progress-container">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Evaluation Progress</span>
                                        <span id="progressPercentage">0%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" id="evaluationProgress" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-submit" onclick="submitEvaluation()">
                                    <i class="bi bi-send-fill me-2"></i>Submit Evaluation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Indicator (visible when scrolling through evaluation) -->
                <div class="progress-indicator" id="progressIndicator" style="display: none;">
                    <span class="progress-text">Evaluation Progress:</span>
                    <div class="progress-bar-container">
                        <div class="evaluation-progress" id="floatingProgress"></div>
                    </div>
                    <span class="ms-2" id="floatingPercentage">0%</span>
                </div>
            </main>
        </div>
    </div>

    <script>
        let currentEmployeeId;
        let currentEmployeeName;
        let currentEmployeePosition;
        let totalQuestions = 0;
        let answeredQuestions = 0;

        // The categories and questions fetched from the PHP script
        const questions = <?php echo json_encode($questions); ?>;
        const categoryIcons = {
            'Quality of Work': 'bi-award',
            'Communication Skills': 'bi-chat-dots',
            'Teamwork': 'bi-people',
            'Punctuality': 'bi-clock',
            'Initiative': 'bi-lightning'
        };

        function evaluateEmployee(e_id, employeeName, employeePosition) {
            currentEmployeeId = e_id;
            currentEmployeeName = employeeName;
            currentEmployeePosition = employeePosition;

            // Reset progress tracking
            totalQuestions = 0;
            answeredQuestions = 0;
            updateProgress();

            // Set employee details in the modal
            document.getElementById('employeeAvatar').textContent = employeeName.charAt(0).toUpperCase();
            document.getElementById('employeeDetails').innerHTML = `
                <h5>${employeeName}</h5>
                <p><i class="bi bi-briefcase-fill me-2"></i>${employeePosition}</p>
            `;

            const evaluationForm = document.getElementById('evaluationForm');
            evaluationForm.innerHTML = '';

            // Create form sections for each category
            for (const [category, categoryQuestions] of Object.entries(questions)) {
                totalQuestions += categoryQuestions.length;

                const categorySection = document.createElement('div');
                categorySection.className = 'category-section';

                // Create category header
                const categoryHeader = document.createElement('div');
                categoryHeader.className = 'category-header';
                categoryHeader.innerHTML = `
                    <div class="category-icon">
                        <i class="bi ${categoryIcons[category] || 'bi-star'}"></i>
                    </div>
                    <h4 class="category-title">${category}</h4>
                `;
                categorySection.appendChild(categoryHeader);

                // Add questions for this category
                categoryQuestions.forEach((question, index) => {
                    const questionName = `${category.replace(/\s/g, '')}q${index}`;
                    const questionItem = document.createElement('div');
                    questionItem.className = 'question-item';

                    questionItem.innerHTML = `
                        <div class="question-text">${question}</div>
                        <div class="star-rating" data-question="${questionName}">
                            ${[6, 5, 4, 3, 2, 1].map(value => `
                                <input type="radio" name="${questionName}" value="${value}" id="${questionName}star${value}" onchange="updateQuestionStatus(this)">
                                <label for="${questionName}star${value}"><i class="bi bi-star-fill"></i></label>
                            `).join('')}
                        </div>
                        <div class="rating-value">
                            <span>Poor</span>
                            <span>Excellent</span>
                        </div>
                    `;

                    categorySection.appendChild(questionItem);
                });

                evaluationForm.appendChild(categorySection);
            }

            // Show the modal
            const evaluationModal = new bootstrap.Modal(document.getElementById('evaluationModal'));
            evaluationModal.show();

            // Setup scroll event for floating progress indicator
            const modalBody = document.querySelector('.modal-body');
            const progressIndicator = document.getElementById('progressIndicator');

            modalBody.addEventListener('scroll', function() {
                if (modalBody.scrollTop > 200) {
                    progressIndicator.style.display = 'flex';
                } else {
                    progressIndicator.style.display = 'none';
                }
            });
        }

        function updateQuestionStatus(input) {
            if (input.checked) {
                answeredQuestions++;
                updateProgress();
            }
        }

        function updateProgress() {
            const percentage = totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0;

            // Update progress bar in modal
            document.getElementById('evaluationProgress').style.width = `${percentage}%`;
            document.getElementById('progressPercentage').textContent = `${percentage}%`;

            // Update floating progress indicator
            document.getElementById('floatingProgress').style.width = `${percentage}%`;
            document.getElementById('floatingPercentage').textContent = `${percentage}%`;
        }

        function submitEvaluation() {
            const evaluations = [];
            const evaluationForm = document.getElementById('evaluationForm');

            evaluationForm.querySelectorAll('input[type="radio"]:checked').forEach(input => {
                evaluations.push({
                    question: input.name,
                    rating: input.value
                });
            });

            if (evaluations.length !== totalQuestions) {
                // Show error with number of remaining questions
                const remaining = totalQuestions - evaluations.length;
                Swal.fire({
                    title: 'Incomplete Evaluation',
                    text: `Please complete all questions. You have ${remaining} question${remaining > 1 ? 's' : ''} remaining.`,
                    icon: 'warning',
                    confirmButtonColor: '#4361ee'
                });
                return;
            }

            const categoryAverages = {
                QualityOfWork: calculateAverage('QualityofWork', evaluations),
                CommunicationSkills: calculateAverage('CommunicationSkills', evaluations),
                Teamwork: calculateAverage('Teamwork', evaluations),
                Punctuality: calculateAverage('Punctuality', evaluations),
                Initiative: calculateAverage('Initiative', evaluations)
            };

            const employeeId = document.getElementById('e_id').value;
            const department = '<?php echo $department; ?>';

            // Show loading state
            const submitBtn = document.querySelector('.btn-submit');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';
            submitBtn.disabled = true;

            $.ajax({
                type: 'POST',
                url: '../db/submit_evaluation.php',
                data: {
                    e_id: currentEmployeeId,
                    employeeName: currentEmployeeName,
                    employeePosition: currentEmployeePosition,
                    categoryAverages: categoryAverages,
                    employeeId: employeeId,
                    department: department
                },
                success: function (response) {
                    // Reset button state
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;

                    if (response === 'You have already evaluated this employee.') {
                        Swal.fire({
                            title: 'Already Evaluated',
                            text: response,
                            icon: 'info',
                            confirmButtonColor: '#4361ee'
                        });
                    } else {
                        // Close the modal
                        bootstrap.Modal.getInstance(document.getElementById('evaluationModal')).hide();

                        // Show success message
                        Swal.fire({
                            title: 'Success!',
                            text: 'Evaluation submitted successfully!',
                            icon: 'success',
                            confirmButtonColor: '#4361ee'
                        }).then(() => {
                            // Reload the page to update the UI
                            location.reload();
                        });
                    }
                },
                error: function (err) {
                    // Reset button state
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;

                    console.error(err);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while submitting the evaluation.',
                        icon: 'error',
                        confirmButtonColor: '#4361ee'
                    });
                }
            });
        }

        function calculateAverage(category, evaluations) {
            const categoryEvaluations = evaluations.filter(evaluation => evaluation.question.startsWith(category));

            if (categoryEvaluations.length === 0) {
                return 0;
            }

            const total = categoryEvaluations.reduce((sum, evaluation) => sum + parseInt(evaluation.rating), 0);
            return total / categoryEvaluations.length;
        }
    </script>

    <!-- SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>

</html>
