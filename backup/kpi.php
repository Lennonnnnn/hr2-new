<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Performance Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Include Chart.js for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <h1 class="text-center my-4">Supervisor Performance Dashboard</h1>

        <!-- Performance KPI Cards -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Loan Portfolio Quality</div>
                    <div class="card-body">
                        <h5 class="card-title">Non-Performing Loans: <span id="loanPortfolioQuality">5%</span></h5>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Loan Disbursement Efficiency</div>
                    <div class="card-body">
                        <h5 class="card-title">Avg. Disbursement Time: <span id="loanDisbursementEfficiency">2 Days</span></h5>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-header">Client Retention Rate</div>
                    <div class="card-body">
                        <h5 class="card-title">Retention Rate: <span id="clientRetentionRate">92%</span></h5>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-header">Portfolio Growth</div>
                    <div class="card-body">
                        <h5 class="card-title">Growth Rate: <span id="portfolioGrowth">8%</span></h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">Collection Efficiency</div>
                    <div class="card-body">
                        <canvas id="collectionEfficiencyChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">Client Satisfaction</div>
                    <div class="card-body">
                        <canvas id="clientSatisfactionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table for Team Management and Training -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-4">
                    <div class="card-header">Team Management & Training</div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Team Management Rating</th>
                                    <th>Training Sessions Conducted</th>
                                    <th>Compliance Issues</th>
                                    <th>New Client Acquisition</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td id="teamManagement">8/10</td>
                                    <td id="trainingConducted">5 Sessions</td>
                                    <td id="complianceIssues">0</td>
                                    <td id="newClientAcquisition">20 Clients</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Script to render charts -->
    <script>
        // Collection Efficiency Chart
        var ctx = document.getElementById('collectionEfficiencyChart').getContext('2d');
        var collectionEfficiencyChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Collected', 'Not Collected'],
                datasets: [{
                    data: [90, 10],  // Example data: 90% collected, 10% not collected
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            }
        });

        // Client Satisfaction Chart
        var ctx2 = document.getElementById('clientSatisfactionChart').getContext('2d');
        var clientSatisfactionChart = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Q1', 'Q2', 'Q3', 'Q4'],  // Example data per quarter
                datasets: [{
                    label: 'Satisfaction Score',
                    data: [8, 9, 7, 8],
                    backgroundColor: '#007bff'
                }]
            }
        });
    </script>

</body>
</html>
