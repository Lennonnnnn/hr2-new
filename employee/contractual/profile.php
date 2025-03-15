<?php
session_start();
include '../../db/db_conn.php';

if (!isset($_SESSION['e_id']) || !isset($_SESSION['position']) || $_SESSION['position'] !== 'Contractual') {
    header("Location: ../../login.php");
    exit();
}


if (isset($_SESSION['update_success'])) {
    echo '<script>alert("' . htmlspecialchars($_SESSION['update_success']) . '");</script>';
    unset($_SESSION['update_success']);
}

// Fetch user info
$employeeId = $_SESSION['e_id'];
$sql = "SELECT 
    e.e_id, e.firstname, e.middlename, e.lastname, e.birthdate, e.gender, e.email, e.created_at,
    e.role, e.position, e.department, e.phone_number, e.address, e.pfp, 
    ua.login_time, 
    -- Fetch the last valid logout time
    (SELECT ua2.logout_time 
     FROM user_activity ua2 
     WHERE ua2.user_id = e.e_id 
     AND ua2.logout_time IS NOT NULL 
     ORDER BY ua2.logout_time ASC 
     LIMIT 1) AS last_logout_time
FROM 
    employee_register e
LEFT JOIN 
    user_activity ua ON e.e_id = ua.user_id
WHERE 
    e.e_id = ? 
ORDER BY 
    ua.login_time DESC 
LIMIT 1";



$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $employeeInfo = $result->fetch_assoc();
} else {
    $employeeInfo = null;
}

$stmt->close();
$conn->close();

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="User Profile Dashboard" />
    <meta name="author" content="Your Name" />
    <title>My Profile | HR2</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
    <body class="sb-nav-fixed bg-black">
        <?php include 'navbar.php'; ?>
        <div id="layoutSidenav">
            <?php include 'sidebar.php'; ?>
            <div id="layoutSidenav_content">
                <main class="bg-black">
                    <div class="container-fluid position-relative px-4">
                        <div class="container-fluid" id="calendarContainer" 
                        style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050; 
                        width: 80%; height: 80%; display: none;">
                            <div class="row">
                                <div class="col-12 col-md-10 col-lg-8 mx-auto">
                                    <div id="calendar" class="p-2"></div>
                                </div>
                            </div>
                        </div>
                        <h1 class="big mb-2 text-light">Profile</h1>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card mb-4">
                                    <div class="card-header bg-dark text-light">
                                        <h3 class="card-title text-start">User Information</h3>
                                        <hr>
                                    </div>
                                    <div class="card-body bg-dark">
                                        <div class="row">
                                            <div class="col-xl-2">
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <img src="<?php echo (!empty($employeeInfo['pfp']) && $employeeInfo['pfp'] !== 'defaultpfp.png') 
                                                        ? htmlspecialchars($employeeInfo['pfp']) 
                                                        : '../../img/defaultpfp.jpg'; ?>" 
                                                        class="rounded-circle border border-light img-fluid" 
                                                        style="max-width: 100%; height: auto; object-fit: cover; cursor: pointer;" 
                                                        alt="Profile Picture"id="profilePic" data-bs-toggle="modal" data-bs-target="#profilePicModal" />  
                                                </div>
 
                                                <div class="modal fade" id="profilePicModal" tabindex="-1" aria-labelledby="profilePicModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered"> <!-- Set the modal size using 'modal-lg' for large -->
                                                        <div class="modal-content bg-dark text-light" style="width: 600px; height: 500px;">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="profilePicModalLabel"><?php echo htmlspecialchars($employeeInfo['firstname'] . ' ' . $employeeInfo['middlename'] . ' ' . $employeeInfo['lastname']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <img src="<?php echo (!empty($employeeInfo['pfp']) && $employeeInfo['pfp'] !== 'defaultpfp.png') 
                                                                    ? htmlspecialchars($employeeInfo['pfp']) 
                                                                    : '../../img/defaultpfp.jpg'; ?>" 
                                                                    class="img-fluid rounded" style="width: 500px; height: 400px;" alt="Profile Picture" /> <!-- img-fluid to make it responsive -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center align-items-center mt-4 mb-3">
                                                    <button class="btn btn-light text-center" type="button" id="editPictureDropdown" 
                                                        data-bs-toggle="dropdown" aria-expanded="false"> Edit Profile
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <div class="dropdown">
                                                        <ul class="dropdown-menu" aria-labelledby="editPictureDropdown">
                                                            <li>
                                                                <a class="dropdown-item fw-bold" href="javascript:void(0);" id="changePictureOption">Change Profile Picture</a>
                                                            </li>
                                                            <hr>
                                                            <li>
                                                                <button class="dropdown-item fw-bold text-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteProfilePictureModal">
                                                                    Delete Profile Picture
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-10 mb-4">
                                                <div class="">
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../../employee/supervisor/change_pass.php" class="btn btn-primary"> Change password </a>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="form-group row">
                                                        <div class="col-sm-4 bg-dark form-floating mb-3">
                                                            <input class="form-control fw-bold" name="fname" value="<?php echo htmlspecialchars($employeeInfo['firstname'] . ' ' . $employeeInfo['middlename'] . ' ' . $employeeInfo['lastname']); ?>" readonly>
                                                            <label class="fw-bold">Name:</label>
                                                        </div>

                                                        <div class="col-sm-4 bg-dark form-floating">
                                                            <input class="form-control fw-bold" name="id" value="<?php echo htmlspecialchars($employeeInfo['e_id']); ?>" readonly>
                                                            <label class="fw-bold">ID No.:</label>
                                                        </div>

                                                        <div class="col-sm-4 bg-dark form-floating">
                                                            <input class="form-control fw-bold" name="id" value="<?php echo htmlspecialchars($employeeInfo['gender']); ?>" readonly>
                                                            <label class="fw-bold">Gender:</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="form-group row">
                                                        <div class="col-sm-6 bg-dark form-floating mb-3">
                                                            <input class="form-control fw-bold" name="position" value="<?php echo htmlspecialchars($employeeInfo['position']); ?>" readonly>
                                                            <label class="fw-bold">Role:</label>
                                                        </div>

                                                        <div class="col-sm-6 bg-dark form-floating">
                                                            <input class="form-control fw-bold" name="department" value="<?php echo htmlspecialchars($employeeInfo['department']); ?>" readonly>
                                                            <label class="fw-bold">Department:</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="form-group row">
                                                        <div class="col-sm-12 bg-dark form-floating mb-3">
                                                            <input class="form-control fw-bold" name="email" value="<?php echo htmlspecialchars($employeeInfo['email']); ?>" readonly>
                                                            <label class="fw-bold">Email:</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-header bg-dark text-light">
                                                <hr>
                                                <h3 class="card-title text-center">Edit Information</h3>
                                            </div>
                                            <div class="card-body bg-dark">
                                                <form id="infoForm" action="../../employee_db/supervisor/update_profile.php" method="post">
                                                    <div class="row mb-3">
                                                        <div class="col-sm-4 bg-dark form-floating mb-3">
                                                            <input type="text" class="form-control fw-bold" id="inputfName" name="firstname" value="<?php echo htmlspecialchars($employeeInfo['firstname']); ?>" readonly required>
                                                            <label for="inputfName" class="fw-bold">First Name:</label>
                                                        </div>
                                                        <div class="col-sm-4 bg-dark form-floating mb-3">
                                                            <input type="text" class="form-control fw-bold" id="inputmName" name="middlename" value="<?php echo htmlspecialchars($employeeInfo['middlename']); ?>" readonly required>
                                                            <label for="inputmName" class="fw-bold">Middle Name:</label>
                                                        </div>
                                                        <div class="col-sm-4 bg-dark form-floating">
                                                            <input type="text" class="form-control fw-bold" id="inputlName" name="lastname" value="<?php echo htmlspecialchars($employeeInfo['lastname']); ?>" readonly required>
                                                            <label for="inputlName" class="fw-bold">Last Name:</label>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-6 bg-dark form-floating mb-3">
                                                            <input type="date" class="form-control fw-bold" id="inputbirth" name="birthdate" value="<?php echo htmlspecialchars($employeeInfo['birthdate']); ?>" readonly required>
                                                            <label for="inputbirth" class="fw-bold">Birthdate:</label>
                                                        </div>
                                                        <div class="col-sm-6 bg-dark form-floating">
                                                            <input type="email" class="form-control fw-bold" id="inputEmail" name="email" value="<?php echo htmlspecialchars($employeeInfo['email']); ?>" readonly required>
                                                            <label for="inputEmail" class="fw-bold">Email Address:</label>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-6 bg-dark form-floating mb-3">
                                                            <input type="number" class="form-control fw-bold" id="inputEmail" id="inputPhone" name="phone_number" value="<?php echo htmlspecialchars($employeeInfo['phone_number']); ?>" readonly required>
                                                            <label for="inputPhone" class="fw-bold">Phone Number:</label>
                                                        </div>
                                                        <div class="col-sm-6 bg-dark form-floating">
                                                            <input class="form-control fw-bold" id="inputAddress" name="address" value="<?php echo htmlspecialchars($employeeInfo['address']); ?>" readonly required>
                                                            <label for="inputAddress" class="fw-bold">Address:</label>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-end">
                                                        <button type="button" id="editButton" class="btn btn-primary">Update Information</button>
                                                        <button type="button" class="btn btn-primary d-none ms-2" id="saveButton" data-bs-toggle="modal" data-bs-target="#saveChangesModal">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <form action="../../employee_db/supervisor/update_employee_pfp.php" method="post" enctype="multipart/form-data" id="profilePictureForm" style="display:none;">
                                            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" onchange="showConfirmationModal();">
                                        </form>
                                        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content bg-dark text-light" style="width: 500px; height: 400px;">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="confirmationModalLabel">Confirm Profile Picture Update</h5>
                                                        <button type="button" class="close text-light bg-dark" data-bs-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to update your profile picture with the selected image?</p>
                                                        <!-- Center the image in the modal -->
                                                        <div class="d-flex justify-content-center align-items-center img-fluid rounded">
                                                            <img id="modalProfilePicturePreview" src="#" alt="Selected Profile Picture"  style="width: 150px; height: 150px;">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="button" class="btn btn-primary" onclick="submitProfilePictureForm()">Update</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 mb-4">
                            <div class="card bg-dark text-light">
                                <div class="card-header border-bottom border-secondary">
                                    <h3 class="mb-0">User Activity</h3>
                                </div>
                                <div class="card-body">
                                    <p><strong>Your last login was on:</strong> 
                                        <?php 
                                            if (!empty($employeeInfo['login_time'])) {
                                                $login = strtotime($employeeInfo['login_time']); 
                                                echo date("l, F j, Y | g:i A", $login); 
                                            } else {
                                                echo "No login time available";
                                            }
                                        ?>
                                    </p>
                                    <hr>
                                    <p><strong>Your last logout was on:</strong> 
                                        <?php 
                                            if (!empty($employeeInfo['last_logout_time'])) {
                                                $logout = strtotime($employeeInfo['last_logout_time']); 
                                                echo date("l, F j, Y | g:i A", $logout); 
                                            } else {
                                                echo "No logout time available";
                                            }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header border-bottom border-secondary">
                                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to log out?
                                </div>
                                <div class="modal-footer border-top border-secondary">
                                    <button type="button" class="btn border-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                    <form action="../../employee/logout.php" method="POST">
                                        <button type="submit" class="btn btn-danger">Logout</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>  
                    <div class="modal fade" id="deleteProfilePictureModal" tabindex="-1" aria-labelledby="deleteProfilePictureLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header border-bottom border-secondary">
                                    <h5 class="modal-title" id="deleteProfilePictureLabel">Delete Profile Picture</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <p>Are you sure you want to delete your profile picture?</p>
                                </div>
                                <div class="modal-footer border-top border-secondary">
                                    <form action="../../employee_db/supervisor/delete_employee_pfp.php" method="post">
                                        <input type="hidden" name="employeeId" value="<?php echo $employeeInfo['e_id']; ?>">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="saveChangesModal" tabindex="-1" aria-labelledby="saveChangesModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header boder-bottom border-secondary">
                                    <h5 class="modal-title" id="saveChangesModalLabel">Confirm Save</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to save the changes to your information?
                                </div>
                                <div class="modal-footer boder-bottom border-secondary">
                                    <button type="button" class="btn btn-outline-secondary text-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="confirmSave">Save Changes</button>
                                </div>
                            </div>
                        </div>
               <?php include 'footer.php'; ?>
            </div>
        </div>
        <div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-transparent border-0">
                    <div class="modal-body d-flex flex-column align-items-center justify-content-center">
                            <!-- Bouncing coin spinner -->
                            <div class="coin-spinner"></div>
                            <div class="mt-3 text-light fw-bold">Please wait...</div>
                        </div>
                    </div>
                </div>
           </div>
        <script>
              document.addEventListener('DOMContentLoaded', function () {
                const buttons = document.querySelectorAll('.loading');
                const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

                // Loop through each button and add a click event listener
                buttons.forEach(button => {
                    button.addEventListener('click', function (event) {
                        // Show the loading modal
                        loadingModal.show();

                        // Disable the button to prevent multiple clicks
                        this.classList.add('disabled');

                        // Handle form submission buttons
                        if (this.closest('form')) {
                            event.preventDefault(); // Prevent the default form submit

                            // Submit the form after a short delay
                            setTimeout(() => {
                                this.closest('form').submit();
                            }, 1500);
                        }
                        // Handle links
                        else if (this.tagName.toLowerCase() === 'a') {
                            event.preventDefault(); // Prevent the default link behavior

                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = this.href;
                            }, 1500);
                        }
                    });
                });

                // Hide the loading modal when navigating back and enable buttons again
                window.addEventListener('pageshow', function (event) {
                    if (event.persisted) { // Check if the page was loaded from cache (back button)
                        loadingModal.hide();

                        // Re-enable all buttons when coming back
                        buttons.forEach(button => {
                            button.classList.remove('disabled');
                        });
                        
                    }
                });
            });
            //CALENDAR 
            let calendar;
                function toggleCalendar() {
                    const calendarContainer = document.getElementById('calendarContainer');
                        if (calendarContainer.style.display === 'none' || calendarContainer.style.display === '') {
                            calendarContainer.style.display = 'block';
                            if (!calendar) {
                                initializeCalendar();
                            }
                        } else {
                            calendarContainer.style.display = 'none';
                        }
                }

                function initializeCalendar() {
                    const calendarEl = document.getElementById('calendar');
                        calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: 'dayGridMonth',
                            headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                            },
                            height: 440,  
                            events: {
                            url: '../../db/holiday.php',  
                            method: 'GET',
                            failure: function() {
                            alert('There was an error fetching events!');
                            }
                            }
                        });

                        calendar.render();
                }

                document.addEventListener('DOMContentLoaded', function () {
                    const currentDateElement = document.getElementById('currentDate');
                    const currentDate = new Date().toLocaleDateString(); 
                    currentDateElement.textContent = currentDate; 
                });

                document.addEventListener('click', function(event) {
                    const calendarContainer = document.getElementById('calendarContainer');
                    const calendarButton = document.querySelector('button[onclick="toggleCalendar()"]');

                        if (!calendarContainer.contains(event.target) && !calendarButton.contains(event.target)) {
                            calendarContainer.style.display = 'none';
                            }
                });
                //CALENDAR END

                //TIME 
                function setCurrentTime() {
                const currentTimeElement = document.getElementById('currentTime');
                const currentDateElement = document.getElementById('currentDate');

                const currentDate = new Date();

                // Convert to 12-hour format with AM/PM
                let hours = currentDate.getHours();
                const minutes = currentDate.getMinutes();
                const seconds = currentDate.getSeconds();
                const ampm = hours >= 12 ? 'PM' : 'AM';

                hours = hours % 12;
                hours = hours ? hours : 12; // If hour is 0, set to 12

                const formattedHours = hours < 10 ? '0' + hours : hours;
                const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
                const formattedSeconds = seconds < 10 ? '0' + seconds : seconds;

                currentTimeElement.textContent = `${formattedHours}:${formattedMinutes}:${formattedSeconds} ${ampm}`;

                // Format the date in text form (e.g., "January 12, 2025")
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                currentDateElement.textContent = currentDate.toLocaleDateString('en-US', options);
            }

            setCurrentTime();
            setInterval(setCurrentTime, 1000);
                //TIME END


                document.addEventListener('DOMContentLoaded', function() {
                // Check if there is a message to show
                <?php if (isset($message)) : ?>
                    var modalMessage = "<?php echo $message; ?>";
                    var messageType = "<?php echo $messageType; ?>";

                    // Set the modal content based on success or error
                    document.getElementById('modalMessage').textContent = modalMessage;
                    
                    // Change modal appearance based on message type (optional)
                    if (messageType === "success") {
                        document.getElementById('messageModalLabel').textContent = "Success";
                        document.querySelector('.modal-content').classList.add('bg-success', 'text-white');
                    } else {
                        document.getElementById('messageModalLabel').textContent = "Error";
                        document.querySelector('.modal-content').classList.add('bg-danger', 'text-white');
                    }

                    // Show the modal
                    var myModal = new bootstrap.Modal(document.getElementById('messageModal'));
                    myModal.show();
                <?php endif; ?>
            });


        //SAVE CHANGES (MODAL)
            document.getElementById('confirmSave').addEventListener('click', function() {
            // Add your form submission logic here
            document.getElementById('infoForm').submit(); // Submit the form
        });
        //END
        </script>
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'> </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="../../js/employee.js"></script>
        <script src="../../js/profile.js"></script>
    </body>
</html>