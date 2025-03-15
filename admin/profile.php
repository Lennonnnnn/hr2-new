<?php
session_start();

if (!isset($_SESSION['a_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

include '../db/db_conn.php';

if (isset($_SESSION['update_success'])) {
    echo '<script>alert("' . htmlspecialchars($_SESSION['update_success']) . '");</script>';
    unset($_SESSION['update_success']);
}

// Fetch user info
$adminId = $_SESSION['a_id'];
$sql = "SELECT a_id, firstname, middlename, lastname, birthdate, email, gender, role, department, phone_number, address, pfp FROM admin_register WHERE a_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$adminInfo = $result->fetch_assoc();
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
    <title>User Profile - Dashboard</title>
    <link href="../css/styles.css" rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="../css/calendar.css" rel="stylesheet"/>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="sb-nav-fixed bg-black">
    <?php include 'navbar.php' ?>
    <div id="layoutSidenav">
       <?php include 'sidebar.php' ?>
            <div id="layoutSidenav_content">
                <main class="bg-black">
                    <div class="container-fluid position-relative px-4">
                        <div class="container-fluid" id="calendarContainer" 
                            style="position: fixed; top: 9%; right: 0; z-index: 1050; 
                            max-width: 100%; display: none;">
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
                                                <div class="d-flex justify-content-center">
                                                    <img src="<?php echo (!empty($adminInfo['pfp']) && $adminInfo['pfp'] !== 'defaultpfp.png') 
                                                        ? htmlspecialchars($adminInfo['pfp']) 
                                                        : '../img/defaultpfp.jpg'; ?>" 
                                                        class="rounded-circle border border-light img-fluid" 
                                                        style="max-width: 230px; max-height: 230px; min-width: 230px; min-height: 230px; object-fit: cover; cursor: pointer;" 
                                                        alt="Profile Picture" 
                                                        id="profilePic" data-bs-toggle="modal" data-bs-target="#profilePicModal" />
                                                </div>
                                                <div class="modal fade" id="profilePicModal" tabindex="-1" aria-labelledby="profilePicModalLabel" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered"> <!-- Set the modal size using 'modal-lg' for large -->
                                                        <div class="modal-content bg-dark text-light" style="width: 600px; height: 500px;">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="profilePicModalLabel"><?php echo htmlspecialchars($adminInfo['firstname'] . ' ' . $adminInfo['middlename'] . ' ' . $adminInfo['lastname']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <img src="<?php echo (!empty($adminInfo['pfp']) && $adminInfo['pfp'] !== 'defaultpfp.png') 
                                                                    ? htmlspecialchars($adminInfo['pfp']) 
                                                                    : '../img/defaultpfp.jpg'; ?>" 
                                                                    class="img-fluid rounded" style="width: 500px; height: 400px;" alt="Profile Picture" /> <!-- img-fluid to make it responsive -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center align-items-center mt-4 mb-3">
                                                    <button class="btn btn-primary text-center w-50" type="button" title="Profile Settings" id="editPictureDropdown" 
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="me-2 fs-5 fas fa-user-cog"></i>
                                                        Settings
                                                    </button>
                                                    <div class="dropdown">
                                                        <ul class="dropdown-menu" aria-labelledby="editPictureDropdown">
                                                            <li>
                                                                <a class="dropdown-item fw-bold text-start" title="Change Profile" href="javascript:void(0);" id="changePictureOption"> <i class="me-2 fs-5 fas fa-user-edit"></i>Change Profile</a>
                                                            </li>
                                                            <hr>
                                                            <li>
                                                                <button class="dropdown-item fw-bold text-start text-danger" title="Delete Profile" type="button" data-bs-toggle="modal" data-bs-target="#deleteProfilePictureModal">
                                                                    <i class="me-2 fs-5 fa fa-trash"></i>
                                                                    Delete
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-10 mb-4">
                                               <div class="">
                                                    <!-- Your buttons -->
                                                    <div class="d-flex justify-content-start">
                                                        <a href="../admin/change_pass.php" class="btn btn-primary text-light loading" role="status">Change password</a>
                                                    </div>                              
                                                </div>
                                                <div class="mt-3">
                                                    <div class="form-group row">
                                                        <div class="col-sm-4 mb-3 position-relative">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Name</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="fname" 
                                                                value="<?php echo htmlspecialchars($adminInfo['firstname'] . ' ' . $adminInfo['middlename'] . ' ' . $adminInfo['lastname']); ?>" readonly>
                                                        </div>

                                                        <div class="col-sm-4 position-relative">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">ID No.</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="id" 
                                                                value="<?php echo htmlspecialchars($adminInfo['a_id']); ?>" readonly>
                                                        </div>


                                                        <div class="col-sm-4 position-relative">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Gender</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="gender" value="<?php echo htmlspecialchars($adminInfo['gender']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="form-group row">
                                                        <div class="col-sm-6 position-relative mb-3">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Role</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="position" value="<?php echo htmlspecialchars($adminInfo['role']); ?>" readonly>
                                                        </div>

                                                        <div class="col-sm-6 position-relative">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Department</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="department" value="<?php echo htmlspecialchars($adminInfo['department']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="form-group row">
                                                        <div class="col-sm-12 position-relative mb-3">
                                                            <label class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Email</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" name="email" value="<?php echo htmlspecialchars($adminInfo['email']); ?>" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-header bg-dark text-light">
                                                <hr>
                                                <h3 class="card-title text-center">Edit Information</h3>
                                                <hr>
                                            </div>
                                            <div class="card-body bg-dark">
                                                <form id="infoForm" action="../db/update_profile.php" method="post">
                                                    <div class="mb-4 text-info">
                                                        <h4>Personal Details</h4>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-4 bg-dark position-relative mb-3">
                                                            <label for="inputfName" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">First Name</label>
                                                            <input type="text" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputfName" name="firstname" value="<?php echo htmlspecialchars($adminInfo['firstname']); ?>" readonly required>
                                                        </div>
                                                        <div class="col-sm-4 bg-dark position-relative mb-3">
                                                            <label for="inputmName" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Middle Name</label>
                                                            <input type="text" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputmName" name="middlename" value="<?php echo htmlspecialchars($adminInfo['middlename']); ?>" readonly required>
                                                        </div>
                                                        <div class="col-sm-4 bg-dark position-relative">
                                                            <label for="inputlName" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Last Name</label>
                                                            <input type="text" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputlName" name="lastname" value="<?php echo htmlspecialchars($adminInfo['lastname']); ?>" readonly required>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-6 bg-dark position-relative mb-3">
                                                            <label for="inputbirth" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Birthdate</label>
                                                            <input type="date" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputbirth" name="birthdate" value="<?php echo htmlspecialchars($adminInfo['birthdate']); ?>" readonly required>
                                                        </div>
                                                        <div class="col-sm-6 bg-dark position-relative">
                                                            <label for="inputEmail" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Email Address</label>
                                                            <input type="email" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputEmail" name="email" value="<?php echo htmlspecialchars($adminInfo['email']); ?>" readonly required>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-6 bg-dark position-relative mb-3">
                                                            <label for="inputPhone" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Phone Number</label>
                                                            <input type="number" class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputEmail" id="inputPhone" name="phone_number" value="<?php echo htmlspecialchars($adminInfo['phone_number']); ?>" readonly required>
                                                        </div>
                                                        <div class="col-sm-6 bg-dark position-relative">
                                                            <label for="inputAddress" class="fw-bold position-absolute text-light" 
                                                                style="top: -10px; left: 27px; background-color: #212529; padding: 0 5px;">Address</label>
                                                            <input class="form-control fw-bold bg-dark border border-2 border-secondary text-light" 
                                                                style="height: 60px; padding-top: 15px; padding-bottom: 15px;" id="inputAddress" name="address" value="<?php echo htmlspecialchars($adminInfo['address']); ?>" readonly required>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-end">
                                                        <button type="button" id="editButton" class="btn btn-primary">Update Information</button>
                                                        <button type="button" class="btn btn-primary d-none ms-2" id="saveButton" data-bs-toggle="modal" data-bs-target="#saveChangesModal">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <form action="../db/update_admin_pfp.php" method="post" enctype="multipart/form-data" id="profilePictureForm" style="display:none;">
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
                                            if (!empty($adminInfo['login_time'])) {
                                                $login = strtotime($adminInfo['login_time']); 
                                                echo date("l, F j, Y | g:i A", $login); 
                                            } else {
                                                echo "No login time available";
                                            }
                                        ?>
                                    </p>
                                    <hr>
                                    <p><strong>Your last logout was on:</strong> 
                                        <?php 
                                            if (!empty($adminInfo['last_logout_time'])) {
                                                $logout = strtotime($adminInfo['last_logout_time']); 
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
                                    <form action="../admin/logout.php" method="POST">
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
                                    <h5 class="modal-title" id="deleteProfilePictureLabel">Delete Profile Picture?</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <p>Are you sure you want to delete your profile picture?</p>
                                </div>
                                <div class="modal-footer border-top border-secondary">
                                    <form action="../db/delete_admin_pfp.php" method="post">
                                        <input type="hidden" name="adminId" value="<?php echo $adminInfo['a_id']; ?>">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger loading">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="saveChangesModal" tabindex="-1" aria-labelledby="saveChangesModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                                <div class="modal-header boder-bottom border-secondary">
                                    <h5 class="modal-title" id="saveChangesModalLabel">Confirm Save?</h5>
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
                <?php include 'footer.php' ?>
            </div>
        </div>
        <script>
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
                            url: '../db/holiday.php',  
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
        <script src="../js/admin.js"></script>
        <script src="../js/profile.js"></script>
    </body>
</html>