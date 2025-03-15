<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Form</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5 bg-dark">
        <h2 class="mb-4 text-light text-center">Leave Request Form</h2>
        <!-- Leave Request Form -->
        <form id="leaveForm" action="submit_leave.php" method="post">
            <div class="form-group text-light">
                <label for="employee_name">Employee Name:</label>
                <input type="text" class="form-control" id="employee_name" name="employee_name" required>
            </div>

            <div class="form-group text-light">
                <label for="employee_id">Employee ID:</label>
                <input type="text" class="form-control" id="employee_id" name="employee_id" required>
            </div>

            <div class="form-group text-light">
                <label for="Department">Department:</label>
                <select id="Department" name="Department" class="form-control" required>
                    <option value="Marketing and Business Department">Marketing and Business Department</option>
                    <option value="Finance and Accounting Department">Finance and Accounting Department</option>
                    <option value="Human Resources Department">Human Resources Department</option>
                    <option value="Customer Service and Service Dep">Customer Service and Service Department</option>
                </select>

            <div class="form-group text-light">
                <label for="leave_type">Type of Leave:</label>
                <select id="leave_type" name="leave_type" class="form-control" required>
                    <option value="Sick Leave">Sick Leave</option>
                    <option value="Vacation Leave">Vacation Leave</option>
                    <option value="Emergency Leave">Emergency Leave</option>
                    <option value="Maternity Leave">Maternity Leave</option>
                </select>
            </div>

            <div class="form-group text-light">
                <label for="start_date">Start Date:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>

            <div class="form-group text-light">
                <label for="end_date">End Date:</label>
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>

            <div class="form-group text-light">
                <label for="reason">Reason:</label>
                <textarea id="reason" name="reason" class="form-control" rows="4" required></textarea>
            </div>

            <!-- Button to trigger modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#confirmationModal">
                Submit Leave Request
            </button>
        </form>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Submission</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to submit this leave request?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSubmit">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Waiting for Approval Modal -->
    <div class="modal fade" id="waitingForApprovalModal" tabindex="-1" role="dialog" aria-labelledby="waitingForApprovalModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="waitingForApprovalModalLabel">Waiting for Approval</h5>
                </div>
                <div class="modal-body">
                    Your leave request is being processed. Please wait for approval.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="okayButton">Okay</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>

        document.getElementById('confirmSubmit').addEventListener('click', function() {
            // Hide the confirmation modal
            $('#confirmationModal').modal('hide');
            
            // Show the waiting for approval modal
            $('#waitingForApprovalModal').modal('show');
        });

        document.getElementById('okayButton').addEventListener('click', function() {
            // Hide the waiting for approval modal
            $('#waitingForApprovalModal').modal('hide');
            
            // Submit the form
            document.getElementById('leaveForm').submit();
        });
        
    </script>
</body>
</html>