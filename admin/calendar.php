<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/adminlogin.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/styles.css">
    <title>Non-Working Days</title>
</head>
<body class="bg-black">
    <div class="container mt-5 text-light">
        <h2>Set Non-Working Days</h2>
        <form id="nonWorkingDayForm">
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" class="form-control" id="date" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <input type="text" class="form-control" id="description" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Non-Working Day</button>
        </form>

        <hr>

        <h3>Existing Non-Working Days</h3>
        <table class="table text-light" id="nonWorkingDaysTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Type>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Existing non-working days will be populated here -->
            </tbody>
        </table>
    </div>

    <script>
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
                        alert('Non-working day added successfully!');
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
                tbody.innerHTML = '';
                data.forEach(day => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${day.date}</td>
                        <td>${day.description}</td>
                        <td>${day.type}</td> <!-- Added type display -->
                        <td>
                            <button class="btn btn-danger btn-sm" onclick="deleteNonWorkingDay('${day.date}')">Delete</button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            });
        }

        function deleteNonWorkingDay(date) {
            fetch('../db/del_nowork.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ date }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Non-working day deleted successfully!');
                    fetchNonWorkingDays();  // Refresh the table
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An unexpected error occurred.');
            });
        }
    </script>
</body>
</html>
