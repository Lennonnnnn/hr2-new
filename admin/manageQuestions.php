<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db/db_conn.php';

// Handle adding, editing, or deleting questions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_question'])) {
        $category = $_POST['category'];
        $question = $_POST['question'];
        $position = $_POST['position']; // Get the selected position

        // Insert into database
        $sql = "INSERT INTO evaluation_questions (category, question, position) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $category, $question, $position);
        $stmt->execute();
        $stmt->close();

        // Redirect back to the settings page
        header("Location: ../admin/settings.php#questions"); // Replace "settings.php" with the actual page name
        exit();
    }

    if (isset($_POST['edit_question'])) {
        $id = $_POST['id'];
        $new_question = $_POST['new_question'];

        // Update the question and position in the database
        $sql = "UPDATE evaluation_questions SET question = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_question, $id);
        $stmt->execute();
        $stmt->close();

        // Redirect back to the settings page
        header("Location: ../admin/settings.php#questions"); // Replace "settings.php" with the actual page name
        exit();
    }

    if (isset($_POST['delete_question'])) {
        $id = $_POST['id'];

        // Delete the question from the database
        $sql = "DELETE FROM evaluation_questions WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Redirect back to the settings page
        header("Location: ../admin/settings.php#questions"); // Replace "settings.php" with the actual page name
        exit();
    }
}

// Fetch all questions and categories
$sql = "SELECT * FROM evaluation_questions ORDER BY position, category, id";
$result = $conn->query($sql);

$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

// Close the database connection
$conn->close();
?>