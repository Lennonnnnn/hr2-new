<?php

include '../../db/db_conn.php'; // Use the correct path to your db_conn.php

// Get the search query from the URL
$query = isset($_GET['query']) ? $conn->real_escape_string($_GET['query']) : '';

if (!empty($query)) {
    // SQL query to search for relevant files or records
    $sql = "
        SELECT 'Leave Management' AS category, leave_file_name AS name, '/employee/supervisor/leave_file.php' AS path 
        FROM leave_files 
        WHERE leave_file_name LIKE '%$query%'
        UNION
        SELECT 'Employee Records' AS category, CONCAT(firstname, ' ', lastname) AS name, '/employee/profile.php' AS path 
        FROM employee_register 
        WHERE firstname LIKE '%$query%' OR lastname LIKE '%$query%'
        UNION
        SELECT 'Evaluations' AS category, evaluation_title AS name, '/evaluations/evaluation_file.php' AS path 
        FROM admin_evaluations 
        WHERE evaluation_title LIKE '%$query%'
        LIMIT 10"; // Limit results for performance

    $result = $conn->query($sql); // Use $conn instead of $mysqli

    if ($result->num_rows > 0) {
        echo "<h3>Search Results for: " . htmlspecialchars($query) . "</h3>";
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            // Construct full URL for each result
            $base_url = 'http://localhost/hr2'; // Your base URL
            $full_url = $base_url . $row['path'];

            // Display the result with a link to the file or page
            echo "<li><strong>" . $row['category'] . ":</strong> <a href='" . $full_url . "'>" . $row['name'] . "</a></li>";
        }
        echo "</ul>";
    } else {
        echo "No results found for: " . htmlspecialchars($query);
    }
} else {
    echo "Please enter a search query.";
}

$conn->close(); // Close the connection using $conn
?>
