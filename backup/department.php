<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #343a40; /* Dark background */
            color: white; /* White text */
            overflow: hidden; /* Prevent scrolling */
        }
        .sidebar {
            height: 100vh;
            background-color: #212529; /* Dark sidebar */
            padding: 20px;
            transition: width 0.3s, padding 0.3s;
            width: 250px;
            position: relative;
        }
        .sidebar.collapsed {
            width: 0; /* Fully hide sidebar */
            padding: 0; /* Remove padding */
        }
        .sidebar a {
            color: #ffc107; /* Yellow text */
            text-decoration: none;
            display: flex; /* Use flexbox for centering */
            align-items: center; /* Center vertically */
            justify-content: center; /* Center horizontally */
            margin: 10px 0;
            padding: 15px; /* Increased padding for better touch target */
            border: 1px solid #ffc107; /* Yellow border */
            border-radius: 5px;
            transition: transform 0.3s, opacity 0.3s;
            transform: translateX(0);
            opacity: 1; /* Fully visible */
        }
        .sidebar.collapsed a {
            opacity: 0; /* Hide text when collapsed */
            transform: translateX(-20px); /* Move left */
        }
        .sidebar h4 {
            margin-left: 15px; /* Indent heading */
            transition: opacity 0.3s;
        }
        .sidebar.collapsed h4 {
            opacity: 0; /* Hide heading when collapsed */
        }
        .sidebar a:hover {
            text-decoration: underline;
            background-color: rgba(255, 255, 255, 0.1); /* Hover effect */
        }
        .toggle-btn {
            cursor: pointer;
            color: #ffc107;
            font-size: 20px;
            margin-left: 15px; /* Indent toggle button */
        }
        .content {
            flex-grow: 1;
            padding: 20px;
            transition: margin-left 0.3s;
            margin-left: 250px; /* Initial margin for content */
        }
        .content.collapsed {
            margin-left: 0; /* Adjust margin when sidebar is collapsed */
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar" id="sidebar">
            <span class="toggle-btn text-decoration-none" onclick="toggleSidebar()">&#x276E;</span>
            <h4>Departments</h4>
            <a href="../employee/e_finance.php">Finance</a>
            <a href="../employee/e_hr.php">Human Resource</a>
            <a href="../employee/e_operations.php">Operations</a>
            <a href="../employee/e_risk.php">Risk</a>
            <a href="../employee/e_marketing.php">Marketing</a>
            <a href="../employee/e_it.php">IT</a>
        </div>
        <div class="content" id="content">
            <h1 class="text-center">Company Dashboard</h1>
            <p class="text-center">Select a department from the sidebar.</p>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="../js/department.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('collapsed');
            const toggleBtn = sidebar.querySelector('.toggle-btn');
            toggleBtn.innerHTML = sidebar.classList.contains('collapsed') ? '&#x276E;' : '&#x276F;';
        }
    </script>
</body>
</html>