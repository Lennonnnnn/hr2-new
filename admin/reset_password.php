<?php
require '../db/db_conn.php'; // Ensure this is the correct file for the database connection
require '../phpmailer/vendor/autoload.php'; // Ensure PHPMailer is properly loaded

$message = ''; // Initialize a variable to hold the message
$expiresAt = null;
$resetSuccessful = false; // Track success of the reset
$formError = ''; // Initialize a variable to store form validation errors

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Query expiration time of the token
    $sql = "SELECT expires_at, email FROM password_resets WHERE token = ?";
    $stmt = $conn->prepare($sql); // Use the $conn connection for mysqli
    $stmt->bind_param("s", $token); // Bind token parameter to prevent SQL injection
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Fetch the row if it exists
        $row = $result->fetch_assoc();
        $expiresAt = $row['expires_at'];
        $email = $row['email'];
    } else {
        // No matching token found, handle this case
        echo "Invalid or expired token.";
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the token has expired
    if (strtotime($expiresAt) < time()) {
        $message = "<div class='alert alert-danger text-center'>Token has expired. Please request a new password reset.</div>";
    } else {
        // Ensure the required form fields are set before accessing them
        if (isset($_POST['new_password']) && isset($_POST['confirm_new_password'])) {
            $newPassword = $_POST['new_password'];
            $confirmNewPassword = $_POST['confirm_new_password'];
            
            // Validate password strength (at least 8 characters, contains a number, an uppercase, and a special character)
            if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPassword)) {
                $formError = "Password must be at least 8 characters long, include an uppercase letter, a number, and a special character.";
            } elseif ($newPassword !== $confirmNewPassword) {
                $formError = "Passwords do not match.";
            } else {
                // Proceed with password update
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $updateSql = "UPDATE admin_register SET password = ? WHERE email = (SELECT email FROM password_resets WHERE token = ? LIMIT 1)";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ss", $hashedPassword, $token); // Bind parameters
                $updateStmt->execute(); 

                $message = "<div class='alert alert-success text-center'>Password reset successfully.</div>";
                $resetSuccessful = true; // Set to true after successful reset
            }
        }
    }
}

// Handle resend link request
if (isset($_POST['resend_token'])) {
    // Generate a new token
    date_default_timezone_set('Asia/Manila');
    $newToken = bin2hex(random_bytes(32));
    $newExpiresAt = date("Y-m-d H:i:s", strtotime("+3 minutes")); // Set expiration time to 30 minutes from now
    
    // Update the most recent reset request for the email
    $insertSql = "UPDATE password_resets 
                  SET token = ?, expires_at = ? 
                  WHERE email = ? 
                  ORDER BY created_at DESC LIMIT 1";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("sss", $newToken, $newExpiresAt, $email); // Bind parameters
    $insertStmt->execute();

    // Send the reset email with PHPMailer
    try {
        // Check if the email exists in the admin_register table
        $sql = "SELECT firstname, lastname FROM admin_register WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch the first and last name
            $row = $result->fetch_assoc();
            $userName = $row['firstname'] . ' ' . $row['lastname'];
        $mail = new PHPMailer\PHPMailer\PHPMailer;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Use Gmail's SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'microfinancehr2@gmail.com'; // Your Gmail address
        $mail->Password = 'yjla pidq jfdr qbnz'; // Your Gmail password or app password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('microfinancehr2@gmail.com', 'Microfinance');
        $mail->addAddress($email);
        $mail->Subject = 'Password Reset Request';
        $resetLink = "http://localhost/HR2/admin/reset_password.php?token=$newToken"; // Update the URL with the new token
        $mail->isHTML(true);

        $mail->Body = '
        <div style="font-family: Arial, sans-serif; padding: 20px; background-color: rgba(24, 25, 26); color: #f8f9fa;">
            <div style="max-width: 600px; margin: 0 auto; background-color: rgba(33, 37, 41); padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h2 style="text-align: center; color: #007bff;">Password Reset Request</h2>
                <p style="font-size: 16px; color: #f8f9fa;">Hello, ' . $userName . '</p>
                <p style="font-size: 16px; color: #f8f9fa;">We received a request to reset your password. Click the button below to reset your password:</p>
                <p style="text-align: center; margin-top: 30px;">
                    <a href="' . $resetLink . '" style="padding: 10px 20px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px; display: inline-block;">
                        Reset Password
                    </a>
                </p>
                <p style="font-size: 16px; color: #999; text-align: center;">This link will expire in 3 minutes.</p>
                <hr style="border-top: 1px solid #ddd; margin-top: 20px;">
                <p style="font-size: 14px; color: #f8f9fa;">If you didn\'t request a password reset, you can safely ignore this email.</p>
                <p style="font-size: 14px; color: #999; text-align: center;">Microfinance HR 2 System</p>
            </div>
        </div>
    ';

    $mail->send();
            // Show success message without redirecting
            $message = "<div class='alert alert-success text-center'>A new password reset link has been sent to your email. Please check your inbox.</div>";
        } else {
            $message = "<div class='alert alert-danger text-center'>Failed to send the reset link. Please try again later.</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger text-center'>Mailer Error: {$mail->ErrorInfo}</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="../css/styles.css" rel="stylesheet" />
</head>
<body class="bg-black">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-lg-5">
                <div class="card shadow-lg border-0 rounded-lg mt-5 bg-dark">
                    <div class="card-header border-bottom border-1 border-warning">
                        <h3 class="text-center text-light font-weight-light my-4">Reset Your Password</h3>
                            <?php if (!empty($message)) echo $message; ?>
                            <?php if (!empty($formError)): ?>
                                <div class="alert alert-danger text-center"><?php echo $formError; ?></div>
                            <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (!$resetSuccessful): ?>
                            <p class="small mb-3 text-light">Token expires in: <span id="countdown"></span></p>
                            <p class="small text-info text-center">Change your password immediately before the token expires.</p>
                            <form method="POST" action="" id="resetForm">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />

                                <div class="form-floating mb-3">
                                    <input class="form-control" id="new_password" name="new_password" type="password" placeholder="New Password" required />
                                    <label for="new_password">New Password</label>
                                    <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y me-2" id="toggleNewPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-floating mb-3">
                                    <input class="form-control" id="confirm_new_password" name="confirm_new_password" type="password" placeholder="Confirm New Password" required />
                                    <label for="confirm_new_password">Confirm New Password</label>
                                    <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y me-2" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                    <button type="submit" class="btn btn-primary w-100" id="submitButton">Reset Password</button>
                                </div>
                            </form>
                            <div id="expiredTime" class="text-center text-danger small" style="display: none;">The token has expired, resend the link again to reset your password.</div>
                            
                            <!-- Resend Link Form -->
                            <form method="POST" action="" style="display: none;" id="resendForm">
                                <button type="submit" name="resend_token" class="btn btn-warning w-100 mt-3">Resend Link</button>
                            </form>
                        <?php endif; ?>

                        <div class="text-center mt-3 mb-4">
                            <a class="btn border-secondary w-100 text-light border border-1" href="../admin/login.php">Back</a>
                        </div>
                    </div>
                    <div class="card-footer border-top border-warning">
                        <div class="text-center text-muted">Human Resource 2</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var resetSuccessful = <?php echo json_encode($resetSuccessful); ?>;
            if (!resetSuccessful) {
                var expiresAt = new Date("<?php echo $expiresAt; ?>").getTime();
                var countdownElement = document.getElementById('countdown');
                var resetForm = document.getElementById('resetForm');
                var submitButton = document.getElementById('submitButton');
                var expiredTimeDiv = document.getElementById('expiredTime');
                var resendForm = document.getElementById('resendForm');

                var countdownInterval = setInterval(function () {
                    var now = new Date().getTime();
                    var distance = expiresAt - now;

                    if (distance < 0) {
                        clearInterval(countdownInterval);
                        countdownElement.innerHTML = "Token expired.";
                        resetForm.style.display = 'none'; // Hide the form
                        submitButton.disabled = true; // Disable the submit button
                        expiredTimeDiv.style.display = 'block'; // Show token expired message
                        resendForm.style.display = 'block'; // Show resend button
                    } else {
                        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        countdownElement.innerHTML = minutes + "m " + seconds + "s ";
                    }
                }, 1000);
            }
        });


    // Toggle visibility of New Password
    const toggleNewPassword = document.querySelector("#toggleNewPassword");
    const newPasswordField = document.querySelector("#new_password");
    const newPasswordIcon = toggleNewPassword.querySelector("i");

    toggleNewPassword.addEventListener("click", function () {
        // Toggle the password field type
        const type = newPasswordField.getAttribute("type") === "password" ? "text" : "password";
        newPasswordField.setAttribute("type", type);

        // Toggle the eye/eye-slash icon
        newPasswordIcon.classList.toggle("fa-eye");
        newPasswordIcon.classList.toggle("fa-eye-slash");
    });

    // Toggle visibility of Confirm New Password
    const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
    const confirmPasswordField = document.querySelector("#confirm_new_password");
    const confirmPasswordIcon = toggleConfirmPassword.querySelector("i");

    toggleConfirmPassword.addEventListener("click", function () {
        // Toggle the password field type
        const type = confirmPasswordField.getAttribute("type") === "password" ? "text" : "password";
        confirmPasswordField.setAttribute("type", type);

        // Toggle the eye/eye-slash icon
        confirmPasswordIcon.classList.toggle("fa-eye");
        confirmPasswordIcon.classList.toggle("fa-eye-slash");
    });
    </script>
</body>
</html>
