<?php
session_start();
include '../db/db_conn.php';

if ($_SESSION['role'] != 'Admin') {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['post_content'])) {
    $user_id = $_SESSION['user_id'];  // Admin ID from session
    $post_content = $_POST['post_content'];

    $stmt = $conn->prepare("INSERT INTO posts (user_id, post_content) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $post_content);
    $stmt->execute();
    $stmt->close();

    header("Location: ../admin/rating.php");
}
?>
