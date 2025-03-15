<?php
// Set the default timezone
date_default_timezone_set('Asia/Manila');

// Include the database connection
include '../db/db_conn.php';

// Prepare the SQL query using prepared statements to prevent SQL injection
$sql = "SELECT al.timestamp, al.admin_name, al.action_type, al.affected_feature, al.details, al.ip_address
        FROM activity_logs al
        JOIN admin_register a ON al.admin_id = a.a_id
        WHERE al.affected_feature IN (?, ?, ?, ?, ?, ?)
        ORDER BY al.timestamp DESC";

// Initialize the statement
$stmt = $conn->prepare($sql);

// Bind parameters (hardcoded in this case, but they can be dynamic if needed)
$affectedFeature1 = 'Employee Details';
$affectedFeature2 = 'Employee Management';
$affectedFeature3 = 'Leave Information';
$affectedFeature4 = 'Admin Details';
$affectedFeature5 = 'Admin Management';
$affectedFeature6 = 'Evaluation';
$stmt->bind_param("ssssss", $affectedFeature1, $affectedFeature2, $affectedFeature3, $affectedFeature4, $affectedFeature5, $affectedFeature6);

// Execute the statement
if (!$stmt->execute()) {
    die("Error executing query: " . $conn->error);
}

// Fetch the result set
$result = $stmt->get_result();

// Close the statement early since it's no longer needed
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Activity Log</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
            line-height: 1.6;
        }

        .container {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
            position: relative;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-block;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background: linear-gradient(90deg, rgba(67, 97, 238, 0.1), rgba(76, 201, 240, 0.1));
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header i {
            margin-right: 0.75rem;
            color: var(--accent-color);
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .table {
            color: var(--text-primary);
            border-color: var(--border-color);
            margin-bottom: 0;
        }

        .table th {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-color: var(--border-color);
            white-space: nowrap;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: var(--border-color);
            font-size: 12px; /* Increased font size */
        }

        .table tbody tr {
            background-color: var(--card-bg);
            transition: background-color 0.2s;
        }

        .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
        }

        .badge-changes {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }

        .badge-created {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .badge-deleted {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .datatable-wrapper .datatable-top,
        .datatable-wrapper .datatable-bottom {
            padding: 0.75rem 1.5rem;
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }

        .datatable-wrapper .datatable-search input {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.5rem 1rem;
        }

        .datatable-wrapper .datatable-selector {
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.5rem;
        }

        .datatable-wrapper .datatable-info {
            color: var(--text-secondary);
        }

        .datatable-wrapper .datatable-pagination ul li a {
            color: var(--text-primary);
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin: 0 2px;
        }

        .datatable-wrapper .datatable-pagination ul li.active a {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .datatable-wrapper .datatable-pagination ul li:not(.active) a:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }

        .feature-badge {
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary-color);
            border: 1px solid rgba(67, 97, 238, 0.3);
            border-radius: 6px;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .timestamp {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .ip-address {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .admin-name {
            font-weight: 500;
        }

        .details-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .details-cell:hover {
            white-space: normal;
            overflow: visible;
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
        @media (max-width: 992px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-header .btn {
                margin-top: 1rem;
                align-self: flex-end;
            }

            .table-responsive {
                border: none;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.75rem;
            }

            .container {
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
        }
        /* Custom CSS to change the placeholder text color to white */
#datatablesSimple .datatable-input::placeholder {
    color: white !important;
    opacity: 1; /* Ensure full visibility */
}
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header fade-in">
            <h1 class="page-title">Activity Log</h1>
            <p class="page-subtitle">Track all admin actions and system changes</p>
        </div>

        <div class="card fade-in" style="animation-delay: 0.1s;">
            <div class="card-header">
                <div>
                    <i class="fas fa-history"></i>
                    <span>Admin Activity History</span>
                </div>
                <a class="btn btn-primary" href="">
                    <i class="fas fa-file-export"></i>Export Log
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatablesSimple" class="">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Feature</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Check if there are records and display them
                            if ($result->num_rows > 0) {
                                // Loop through the results and display each row in the table
                                while($row = $result->fetch_assoc()) {
                                    // Format timestamp to be more readable
                                    $timestamp = new DateTime($row['timestamp']);
                                    $formattedDate = $timestamp->format('M d, Y');
                                    $formattedTime = $timestamp->format('h:i A');

                                    echo "<tr>";
                                    echo "<td class='timestamp'><div>{$formattedDate}</div><div>{$formattedTime}</div></td>";
                                    echo "<td class='admin-name'>" . htmlspecialchars($row['admin_name']) . "</td>";

                                    // Apply badge classes to action_type
                                    echo "<td>";
                                    if (strtolower($row['action_type']) == 'changes') {
                                        echo "<span class='badge badge-changes'>Changes</span>";
                                    } elseif (strtolower($row['action_type']) == 'created') {
                                        echo "<span class='badge badge-created'>Created</span>";
                                    } elseif (strtolower($row['action_type']) == 'deleted') {
                                        echo "<span class='badge badge-deleted'>Deleted</span>";
                                    } else {
                                        echo "<span class='badge bg-secondary'>" . htmlspecialchars($row['action_type']) . "</span>";
                                    }
                                    echo "</td>";

                                    echo "<td><span class='feature-badge'>" . htmlspecialchars($row['affected_feature']) . "</span></td>";
                                    echo "<td class='details-cell'>" . htmlspecialchars($row['details']) . "</td>";
                                    echo "<td class='ip-address'>" . htmlspecialchars($row['ip_address']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                // If no records found
                                echo "<tr><td colspan='6' class='text-center py-5'>";
                                echo "<i class='fas fa-search fa-3x mb-3 text-secondary'></i>";
                                echo "<p class='mb-0 text-secondary'>No activity records found.</p>";
                                echo "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Include required JS libraries -->
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Initialize DataTable with custom options
        window.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple, {
                    perPage: 10,
                    perPageSelect: [5, 10, 15, 20, 25],
                    searchable: true,
                    sortable: true,
                    fixedHeight: false,
                    labels: {
                        placeholder: "Search logs...",
                        perPage: "{select} entries per page",
                        noRows: "No activity logs found",
                        info: "Showing {start} to {end} of {rows} entries",
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
