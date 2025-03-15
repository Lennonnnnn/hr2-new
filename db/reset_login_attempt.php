<?php
session_start();

// Include the database connection
include '../db/db_conn.php';

// Check if the reset button was clicked (POST request)
if (isset($_POST['reset_login_attempt'])) {
    // Retrieve the email from POST data (entered by the user)
    $email = isset($_POST['email']) ? $_POST['email'] : null;

    if ($email) {
        // Debugging: Log if the button was clicked and the email being used
        error_log("Reset login attempts button was clicked for email: " . $email);

        // Check if the email exists in the login_attempts table
        $check_sql = "SELECT COUNT(*) FROM login_attempts WHERE email = ?";
        if ($stmt = $conn->prepare($check_sql)) {
            // Bind the email parameter
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($email_count);
            $stmt->fetch();
            $stmt->close();

            if ($email_count > 0) {
                // The email exists, proceed with deleting the login attempts

                // Prepare and execute SQL query to delete login attempts for the email
                $delete_sql = "DELETE FROM login_attempts WHERE email = ?";
                if ($stmt = $conn->prepare($delete_sql)) {
                    // Bind the email parameter
                    $stmt->bind_param("s", $email);

                    // Execute the query
                    if ($stmt->execute()) {
                        // Successfully reset the login attempts
                        $success = urlencode("Login attempts have been reset.");
                        header("Location: ../admin/login.php?success=$success");
                        exit();
                    } else {
                        // Log error if query execution fails
                        error_log("Failed to execute query: " . $stmt->error);
                        $error = urlencode("Failed to reset login attempts. Please try again.");
                        header("Location: ../admin/login.php?error=$error");
                        exit();
                    }
                } else {
                    // Log error if preparing the statement fails
                    error_log("Error preparing the SQL statement: " . $conn->error);
                    $error = urlencode("Error preparing the SQL statement. Please try again.");
                    header("Location: ../admin/login.php?error=$error");
                    exit();
                }
            } else {
                // If email not found in the login_attempts table
                error_log("Email not found in login attempts table: " . $email);
                $error = urlencode("Email not found. Please try again.");
                header("Location: ../admin/login.php?error=$error");
                exit();
            }
        } else {
            // Log error if preparing the check query fails
            error_log("Error preparing the SQL check query: " . $conn->error);
            $error = urlencode("Error checking email existence. Please try again.");
            header("Location: ../admin/login.php?error=$error");
            exit();
        }
    } else {
        // If email is not available
        error_log("Email not available for resetting login attempts.");
        $error = urlencode("Email not found. Please try again.");
        header("Location: ../admin/login.php?error=$error");
        exit();
    }
} else {
    // If reset button is not clicked or not set
    $error = urlencode("Reset action was not triggered. Please click the button to reset.");
    header("Location: ../admin/login.php?error=$error");
    exit();
}
?>
