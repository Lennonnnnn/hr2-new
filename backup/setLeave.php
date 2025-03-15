<?php
session_start();
include '../db/db_conn.php';

// Fetch employees from employee_register
$sql = "SELECT e_id, firstname, lastname FROM employee_register";
$result = $conn->query($sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveType = $_POST['leaveType'];
    $e_id = $_POST['e_id'];

    if ($leaveType === 'fixed') {
        // Handle fixed leave days
        $maternity_leave = $_POST['maternity_leave'];
        $paternity_leave = $_POST['paternity_leave'];
        $sql = "UPDATE employee_leaves SET maternity_leave='$maternity_leave', paternity_leave='$paternity_leave' WHERE e_id='$e_id'";
        $conn->query($sql);
    } elseif ($leaveType === 'custom') {
        // Handle customizable leave days
        $bereavement_leave = $_POST['bereavement_leave'];
        $emergency_leave = $_POST['emergency_leave'];
        $sick_leave = $_POST['sick_leave'];
        $vacation_leave = $_POST['vacation_leave'];
        $sql = "UPDATE employee_leaves SET bereavement_leave='$bereavement_leave', emergency_leave='$emergency_leave', sick_leave='$sick_leave', vacation_leave='$vacation_leave' WHERE e_id='$e_id'";
        $conn->query($sql);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Manage Leave Days</title>
    <!-- Bootstrap CSS -->
    <link href="../css/styles.css" rel="stylesheet">
</head>
<body class="bg-black text-light">
    <div class="container mt-5">
        <div class="card bg-dark shadow-lg">
            <div class="card-header text-center border-bottom border-secondary">
                <h1 class="text-light">Manage Employee Leave Days</h1>
            </div>
            <div class="card-body">
                <form method="post" action="" class="needs-validation" novalidate>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="e_id" class="form-label">Select Employee</label>
                            <select name="e_id" id="e_id" class="form-select" required>
                                <option value="" disabled selected>Choose employee</option>
                                <option value="all">All Employees</option>
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . $row['e_id'] . "'>" . $row['firstname'] . " " . $row['lastname'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback">Please select an employee.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="leaveType" class="form-label">Leave Type</label>
                            <select name="leaveType" id="leaveType" class="form-select" required>
                                <option value="" disabled selected>Choose leave type</option>
                                <option value="fixed">Fixed</option>
                                <option value="custom">Custom</option>
                            </select>
                            <div class="invalid-feedback">Please select a leave type.</div>
                        </div>
                    </div>
                    <!-- Fixed Leave Section -->
                    <div id="fixed-leave" class="row mb-3" style="display: none;">
                        <div class="col-md-6">
                            <label for="maternity_leave" class="form-label">Maternity Leave</label>
                            <input type="number" name="maternity_leave" id="maternity_leave" value="105" class="form-control" min="0" placeholder="Enter days" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="paternity_leave" class="form-label">Paternity Leave</label>
                            <input type="number" name="paternity_leave" id="paternity_leave" value="7" class="form-control" min="0" placeholder="Enter days" readonly>
                        </div>
                    </div>

                    <!-- Custom Leave Section -->
                    <div id="custom-leave" class="row mb-3" style="display: none;">
                        <div class="col-md-6">
                            <label for="bereavement_leave" class="form-label">Bereavement Leave</label>
                            <input type="number" name="bereavement_leave" id="bereavement_leave" class="form-control" min="0" placeholder="Enter days">
                        </div>
                        <div class="col-md-6">
                            <label for="emergency_leave" class="form-label">Emergency Leave</label>
                            <input type="number" name="emergency_leave" id="emergency_leave" class="form-control" min="0" placeholder="Enter days">
                        </div>
                        <div class="col-md-6 mt-3">
                            <label for="sick_leave" class="form-label">Sick Leave</label>
                            <input type="number" name="sick_leave" id="sick_leave" class="form-control" min="0" placeholder="Enter days">
                        </div>
                        <div class="col-md-6 mt-3">
                            <label for="vacation_leave" class="form-label">Vacation Leave</label>
                            <input type="number" name="vacation_leave" id="vacation_leave" class="form-control" min="0" placeholder="Enter days">
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary mt-4">Submit</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="message" class="message mt-4 text-center"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
        document.getElementById('leaveType').addEventListener('change', function () {
            var leaveType = this.value;
            document.getElementById('fixed-leave').style.display = leaveType === 'fixed' ? 'flex' : 'none';
            document.getElementById('custom-leave').style.display = leaveType === 'custom' ? 'flex' : 'none';
        });
    </script>
</body>
</html>
