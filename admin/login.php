<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="../css/styles.css" rel="stylesheet" />
</head>
<body class="bg-black">
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container mt-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5 mb-2 bg-dark">
                                <div class="card-header border-bottom border-1 border-warning">
                                    <h3 class="text-center text-light font-weight-light mt-2 mb-4">Admin Login</h3>
                                    <!-- Display error message if exists -->
                                    <?php if (isset($_GET['error'])): ?>
                                        <div id="error-alert" class="alert alert-danger text-center my-2" role="alert">
                                            <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body bg-dark">
                                    <form action="../db/adminlogin_conn.php" method="post">
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputEmail" type="email" name="email"
                                                placeholder="name@example.com" required />
                                            <label for="inputEmail">Email address:</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputPassword" type="password" name="password" placeholder="Password" required />
                                            <label for="inputPassword">Password:</label>
                                            <button type="button" class="btn position-absolute top-50 end-0 translate-middle-y me-2" id="togglePassword">
                                                <i class="fas fa-eye"></i> <!-- Default icon (eye) -->
                                            </button>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1 mb-2">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input" id="inputRememberPassword" type="checkbox" name="remember" value="" />
                                                <label class="form-check-label text-light ms-2" for="inputRememberPassword">Remember Password</label>
                                            </div>
                                            <a class="small text-info" href="../admin/forgot_pass.php">Forgot Password?</a>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-2 mb-2">
                                            <button type="submit" class="btn btn-primary w-100">Login</button>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-center mt-3 mb-0"> <a class="btn border-secondary w-100 text-light border border-1" href="../index.php">Back</a></div>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center border-top border-1 border-warning">
                                    <div class="text-center text-muted">Human Resource 2</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div id="layoutAuthentication_footer">
            <footer class="py-4 bg-dark mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Human Resource 2</div>
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
<script>
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
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
