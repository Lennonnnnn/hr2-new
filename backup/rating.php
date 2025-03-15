<?php
session_start();
include '../db/db_conn.php';  // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['a_id'])) {
    header('Location: ../admin/adminlogin.php');  // Redirect to login if not logged in
    exit;
}

// Fetch posts along with the poster's name
$result = $conn->query("SELECT posts.*, 
    CASE 
        WHEN admin_register.a_id IS NOT NULL THEN CONCAT(admin_register.firstname)
        ELSE CONCAT(employee_register.firstname, ' ', employee_register.lastname)
    END AS poster_name 
    FROM posts 
    LEFT JOIN admin_register ON posts.user_id = admin_register.a_id 
    LEFT JOIN employee_register ON posts.user_id = employee_register.e_id 
    ORDER BY post_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> <!-- Bootstrap CSS -->
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center">Admin Dashboard</h1>
    <div class="mb-4">
        <!-- Admin post form -->
        <?php if ($_SESSION['role'] == 'Admin'): ?>
        <form action="../db/add_post.php" method="post">
            <div class="form-group">
                <textarea class="form-control" name="post_content" placeholder="What's on your mind?" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Post</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Fetch and display posts -->
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class='post card mb-3'>
            <div class='card-body'>
                <h5 class='card-title'><?php echo htmlspecialchars($row['poster_name']); ?> posted:</h5>
                <p class='card-text'><?php echo htmlspecialchars($row['post_content']); ?></p>
                <small class='text-muted'>Posted on <?php echo $row['post_date']; ?></small>
            </div>

            <!-- Comment form visible to all roles -->
            <div class="card-footer">
                <form action='../db/add_comment.php' method='post'>
                    <input type='hidden' name='post_id' value='<?php echo $row['post_id']; ?>'>
                    <div class="form-group">
                        <textarea class='form-control' name='comment_content' placeholder='Write a comment...' required></textarea>
                    </div>
                    <button type='submit' class='btn btn-secondary'>Comment</button>
                </form>
            </div>

            <!-- Fetch and display comments for each post -->
            <div class='card-body'>
                <h6>Comments:</h6>
                <?php
                $post_id = $row['post_id'];
                // Fetch comments with the commenter names
                $comments = $conn->query("SELECT comments.*, 
                    CASE 
                        WHEN admin_register.a_id IS NOT NULL THEN CONCAT(admin_register.firstname)
                        ELSE CONCAT(employee_register.firstname, ' ', employee_register.lastname)
                    END AS commenter_name 
                    FROM comments 
                    LEFT JOIN admin_register ON comments.user_id = admin_register.a_id 
                    LEFT JOIN employee_register ON comments.user_id = employee_register.e_id 
                    WHERE post_id = $post_id 
                    ORDER BY comment_date ASC");

                while ($comment = $comments->fetch_assoc()): ?>
                    <div class='comment'>
                        <p><strong><?php echo htmlspecialchars($comment['commenter_name']); ?>:</strong> <?php echo htmlspecialchars($comment['comment_content']); ?></p>
                        <small class='text-muted'>Commented on <?php echo $comment['comment_date']; ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
