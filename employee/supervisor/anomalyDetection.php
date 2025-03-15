<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anomaly Detection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Employee Anomaly Detection</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Leave Days</th>
                    <th>Performance Score</th>
                    <th>Attendance</th>
                    <th>Anomaly</th>
                </tr>
            </thead>
            <tbody id="results">
                <!-- Results will be populated here -->
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
  async function fetchResults() {
    try {
        const response = await axios.get('http://localhost/HR2/employee/supervisor/ml.js');
        console.log('API Response:', response.data); // Log the response data

        const results = response.data;

        if (!Array.isArray(results)) {
            throw new Error('Expected an array but got:', results);
        }

        const tableBody = document.getElementById('results');
        results.forEach((row, index) => {
            // Create the main row for the employee
            const tr = document.createElement('tr');
            const performanceScore = parseFloat(row.performance_score) || 0; // Ensure it's a number
            tr.innerHTML = `
                <td>${row.e_id}</td>
                <td>${row.total_leave_days}</td>
                <td>${performanceScore.toFixed(2)}</td>
                <td>${row.attendance_rate}</td>
                <td>${row.anomaly ? 'Yes' : 'No'}</td>
            `;
            tableBody.appendChild(tr);

            // Add a new row for specific anomalies (if any)
            if (row.anomaly) {
                const anomalyRow = document.createElement('tr');
                anomalyRow.classList.add('anomaly-detail'); // Add a class for styling
                let anomalyDetails = [];

                // Check for specific anomalies
                if (row.total_leave_days > 10) {
                    anomalyDetails.push(`Leave Days (${row.total_leave_days}) is too high`);
                }
                if (performanceScore < 3) { // Adjusted threshold for 6-star rating
                    anomalyDetails.push(`Performance Score (${performanceScore.toFixed(2)}) is too low`);
                }
                if (row.attendance_rate < 0.7) {
                    anomalyDetails.push(`Attendance Rate (${row.attendance_rate}) is too low`);
                }

                // Display the specific anomalies
                anomalyRow.innerHTML = `
                    <td colspan="5" class="text-danger">
                        <strong>Anomaly Details:</strong> ${anomalyDetails.join(', ')}
                    </td>
                `;
                tableBody.appendChild(anomalyRow);
            }
        });
    } catch (error) {
        console.error('Error fetching or processing data:', error);
    }
}

fetchResults();
    </script>
</body>
</html>