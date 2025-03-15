<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/adminlogin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Admin Account Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <style>
        /* Ensures the page fills the full height */
        html, body {
            height: 100%;
        }
        /* Makes the layout use the full height and pushes footer to the bottom */
        #layoutAuthentication {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        #layoutAuthentication_content {
            flex-grow: 1;
        }
    </style>
</head>

<body class="bg-black">
    <div id="layoutAuthentication" class="d-flex flex-column">
        <div id="layoutAuthentication_content" class="flex-grow-1">
            <main>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-7">
                            <div class="card shadow-lg border-0 rounded-lg mt-5 my-5 bg-dark">
                                <div class="card-header border-bottom border-1 border-warning">
                                    <h3 class="text-center text-light font-weight-light my-4">Create Admin Account</h3>
                                    <div id="form-feedback" class="alert text-center" style="display: none;"></div>
                                </div>
                                <div class="card-body">
                                    <form id="registrationForm">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputFirstName" type="text"
                                                    name="firstname" placeholder="Enter your first name" autocomplete="given-name" required />
                                                    <label for="inputFirstName">First name</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating"> 
                                                    <input class="form-control" id="inputLastName" type="text"
                                                    name="lastname" placeholder="Enter your last name" autocomplete="family-name" required />
                                                    <label for="inputLastName">Last name</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputEmail" type="email"
                                                    name="email" placeholder="name@example.com" autocomplete="email" required />
                                                    <label for="inputEmail">Email address</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating mb-md-0">
                                                    <select id="inputGender" name="gender" class="form-select" required>
                                                        <option value="" disabled selected>Select gender</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                    <label for="inputGender">Select Gender</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputPassword" type="password"
                                                    name="password" placeholder="Create a password" autocomplete="new-password" required />
                                                    <label for="inputPassword">Password</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputPasswordConfirm"
                                                    type="password" name="confirm_password" placeholder="Confirm password" autocomplete="new-password" required />
                                                    <label for="inputPasswordConfirm">Confirm Password</label>
                                                </div>
                                            </div> 
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <select id="inputRole" name="role" class="form-select" autocomplete="organization-title" required>
                                                            <option value="" disabled selected>Select a role</option>
                                                            <option value="Admin">Admin</option>
                                                            <option value="Employee">Employee</option>
                                                            <option value="Manager">Manager</option>
                                                            <option value="HR">HR</option>
                                                    </select>
                                                    <label for="inputRole">Select Role</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputPhoneNumber"
                                                    type="tel" name="phone_number" placeholder="Phone number" autocomplete="tel" required />
                                                    <label for="inputPhoneNumber">Phone Number</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <input class="form-control" id="inputAddress" type="text"
                                            name="address" placeholder="Address" autocomplete="street-address" required />
                                            <label for="inputAddress">Address</label>
                                        </div>
                                        <div class="mt-4 mb-0">
                                            <div class="d-grid">
                                                <button class="btn btn-primary btn-block" type="submit">Create Account</button>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-center mt-2 mb-2"> <a class="btn border-secondary w-100 text-light" href="../admin/admin.php">Back</a></div>
                                            </div>  
                                        </div> 
                                    </form>
                                </div>
                                <div class="card-footer text-center border-top border-1 border-warning">
                                    <p class="small text-center text-muted mt-1">Human Resource 2</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <footer class="py-4 bg-dark mt-auto border-top border-1 border-warning">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
    <script src="../js/registeradmin.js"></script>
</body>

</html>
