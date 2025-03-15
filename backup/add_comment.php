<?php
session_start();
include '../db/db_conn.php';

if ($_SESSION['role'] != 'Admin') {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['comment_content'])) {
    $user_id = $_SESSION['user_id'];  // Employee ID from session
    $post_id = $_POST['post_id'];
    $comment_content = $_POST['comment_content'];

    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment_content);
    $stmt->execute();
    $stmt->close();

    header("Location: ../admin/rating.php");
}
?>
