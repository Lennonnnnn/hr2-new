<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="css/styles.css" rel="stylesheet" />
</head>

<body class="bg-black">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5 mb-2 bg-dark">
                                <div class="card-header border-bottom border-1 border-secondary"> 
                                    <h3 class="text-center text-light font-weight-light mt-2 mb-4">Login</h3>
                                    <?php if (isset($_GET['error'])): ?>
                                        <div id="error-alert" class="alert alert-danger text-center my-2" role="alert">
                                            <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                                        </div>
                                        
                                        <?php if (isset($_GET['banEndTime'])): ?>
                                            <div id="countdown" class="text-center fw-bold text-light mt-2">
                                                <!-- Countdown timer will be shown here -->
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body bg-dark mt-3">
                                    <form action="../HR2/login_conn.php" method="post" class="needs-validation"  novalidate>
                                        <div class="position-relative mb-3">
                                            <label class="fw-bold position-absolute text-light" style="top: -10px; left: 15px; background-color: #212529; padding: 0 5px;" for="inputEmail">
                                                Email address
                                            </label>
                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light"
                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputEmail" type="email" name="email"
                                                placeholder="name@example.com" required pattern="[^\s@]+@[^\s@]+\.[^\s@]+" />
                                            <div class="invalid-feedback">Email is required and must be in a valid format (e.g., name@example.com).</div>
                                        </div>
                                        <div class="mb-3 position-relative">
                                            <label class="fw-bold position-absolute text-light"
                                                style="top: -10px; left: 15px; background-color: #212529; padding: 0 5px;"
                                                for="inputPassword">Password</label>
                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light"
                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px; padding-right: 60px;"
                                                id="inputPassword" type="password" name="password" required placeholder="Password" />
                                            <button type="button" class="btn text-muted position-absolute me-1"
                                                    id="togglePassword" style="top: 50%; right: 15px; transform: translateY(-50%);"
                                                    onclick="togglePasswordVisibility()">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="invalid-feedback" style="position: absolute; top: 60px; bottom: -20px; left: 0;">
                                                Password is required!
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input" id="inputRememberPassword" type="checkbox" name="remember" value="" />
                                                <label class="form-check-label text-light ms-2" for="inputRememberPassword">Remember Password</label>
                                            </div>
                                            <div>
                                                <a class="small text-info" href="../employee/forgot_pass.php">Forgot Password?</a>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-2 mb-2">
                                            <button type="submit" class="btn btn-primary w-100 mt-3">Login</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center border-top border-1 border-secondary">
                                    <div class="text-center text-muted">Human Resources 2</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div id="layoutAuthentication_footer">
            <footer class="py-4 bg-dark border-top border-secondary mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2023</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.js"></script>
<script>
     // Bootstrap form validation script
     (function () {
        'use strict'

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
    })()


    const togglePassword = document.querySelector("#togglePassword");
    const passwordField = document.querySelector("#inputPassword");
    const icon = togglePassword.querySelector("i");

    togglePassword.addEventListener("click", function () {
        // Toggle the password field type
        const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
        passwordField.setAttribute("type", type);

            // Toggle the eye/eye-slash icon
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    });


    // Get the banned until time from the URL (converted to timestamp)
    const urlParams = new URLSearchParams(window.location.search);
    const banEndTime = parseInt(urlParams.get('banEndTime')) * 1000; // Convert to milliseconds
    
    if (banEndTime) {
        // Function to update the countdown
        function updateCountdown() {
            const currentTime = new Date().getTime(); // Get current time in ms
            const timeRemaining = banEndTime - currentTime; // Calculate remaining time in ms

            // If time remaining is positive, show the countdown
            if (timeRemaining > 0) {
                const hours = Math.floor((timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);

                // Format time
                const timeText = `${hours}h ${minutes}m ${seconds}s`;

                // Display the countdown
                document.getElementById("countdown").innerHTML = "Time remaining: " + timeText;
            } else {
                // If the ban time is over, show the message
                document.getElementById("countdown").innerHTML = "Your account is no longer banned.";
            }
        }

        // Update the countdown every second
        setInterval(updateCountdown, 1000);
    }
</script>
</body>
</html>
