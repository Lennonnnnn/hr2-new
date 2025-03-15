<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Employee Account Registration</title>
    <link href="../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="/HR2/face-api.js-master/dist/face-api.min.js"></script> <!-- Face API for face recognition -->

    <style>
        /* Ensures the page fills the full height */
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-color: #121212;
            color: #e0e0e0;
        }
        /* Makes the layout use the full height and pushes footer to the bottom */
        #layoutAuthentication {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        #layoutAuthentication_content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .container-fluid {
            padding: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.5);
            background-color: #1f1f1f;
            border: 1px solid #333;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card-header, .card-footer {
            background-color: #2c2c2c;
            border-color: #555 !important;
        }
        .form-floating {
            margin-bottom: 1.5rem;
            color: black;
        }
        .btn-primary {
            background-color: #333;
            border-color: #333;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .video-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        #videoFeed {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
        }
        #capturedFaceContainer {
            text-align: center;
        }
        #capturedFace {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s;
        }

        footer {
            background-color: #1f1f1f;
            border-top: 1px solid #333;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        .fade-in {
            animation: fadeIn 1s ease-in-out;
        }
    </style>
</head>

<body>
    <div id="layoutAuthentication">
        <div id="layoutAuthentication_content">
            <main>
                <div class="container-fluid">
                    <div class="row justify-content-around align-items-center">
                        <div class="col-lg-5">
                            <div class="card shadow-lg border-0 rounded-lg mt-5 my-5 fade-in">
                                <div class="card-header border-bottom border-1 border-warning">
                                    <h3 class="text-center font-weight-light my-4">Create Employee Account</h3>
                                    <div id="form-feedback" class="alert text-center" style="display: none;"></div>
                                </div>
                                <div class="card-body">
                                    <form id="registrationForm" action="../db/registeremployee_db.php" method="POST" enctype="multipart/form-data">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputFirstName" type="text"
                                                        name="firstname" placeholder="Enter your first name" required />
                                                    <label for="inputFirstName">First name</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputLastName" type="text"
                                                        name="lastname" placeholder="Enter your last name" required />
                                                    <label for="inputLastName">Last name</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select id="gender" name="gender" class="form-control form-select" required>
                                                        <option value="" disabled selected>Select gender</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                    <label for="inputGender">Gender</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <input class="form-control" id="inputEmail" type="email"
                                                        name="email" placeholder="name@example.com" required />
                                                    <label for="inputEmail">Email address</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputPassword" type="password"
                                                        name="password" placeholder="Create a password" required />
                                                    <label for="inputPassword">Password</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input class="form-control" id="inputPasswordConfirm"
                                                        type="password" name="confirm_password" placeholder="Confirm password" required />
                                                    <label for="inputPasswordConfirm">Confirm Password</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <input type="hidden" id="inputRoleHidden" name="role" value="Employee">
                                                    <input class="form-control" type="text" id="inputRole" value="Employee" disabled>
                                                    <label for="inputRole">Role</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating mb-3 mb-md-0">
                                                    <select class="form-control form-select" id="inputDepartment" name="department" required>
                                                        <option value="" disabled selected>Select a Department</option>
                                                        <option value="Finance Department">Finance Department</option>
                                                        <option value="Administration Department">Administration Department</option>
                                                        <option value="Sales Department">Sales Department</option>
                                                        <option value="Credit Department">Credit Department</option>
                                                        <option value="Human Resource Department">Human Resource Department</option>
                                                        <option value="IT Department">IT Department</option>
                                                    </select>
                                                    <label for="inputDepartment">Select Department</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-floating mt-3 mb-3">
                                            <select id="inputPosition" name="position" class="form-control form-select" required>
                                                <option value="" disabled selected>Select department first.</option>
                                            </select>
                                            <label for="inputPosition">Select Position</label>
                                        </div>
                                        <!-- Face Image Upload -->
                                        <div class="form-control mb-3 bg-light rounded">
                                            <label for="face_image" class="form-label"></label>
                                            <input type="file" id="face_image" name="photo[]" accept="image/*" multiple required>
                                            <span id="file_name" class="ms-2"></span>
                                        </div>
                                        <!-- Hidden face descriptor input -->
                                        <input type="hidden" id="faceDescriptorInput" name="face_descriptor">

                                        <div class="mt-4 mb-0">
                                            <div class="d-grid">
                                                <button class="btn btn-primary btn-block" type="submit" id="submitBtn" disabled>Create Account</button>
                                            </div>
                                            <div class="text-center">
                                                <div class="text-center mt-2 mb-2"> <a class="btn border-secondary w-100 text-light" href="../admin/employee.php">Back</a></div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center border-top border-1 border-warning">
                                    <p class="small text-center text-muted mt-1">Human Resource 2</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="card shadow-lg border-0 rounded-lg mb-4 fade-in">
                                <div class="card-body video-container">
                                    <h4>Register Face</h4>
                                    <div class="mb-3 rounded">
                                        <label for="face_image" class="form-label"></label>
                                        <video id="videoFeed" width="440" height="280" autoplay></video>
                                    </div>
                                    <input type="hidden" id="faceDescriptorInput" name="face_descriptor">
                                    <button type="button" class="btn btn-primary mb-3" id="startCameraBtn">Start Camera</button>
                                    <button type="button" class="btn btn-success" id="captureFaceBtn" disabled>Capture Face</button>
                                </div>
                            </div>
                            <div class="card bg-transparent text-light p-3 mb-2 fade-in">
                                <h5 class="text-center mb-3">Captured Face</h5>
                                <div class="d-flex justify-content-around">
                                    <div id="capturedFaceContainer">
                                        <!-- This is where the captured face will be inserted -->
                                        <img id="capturedFace" src="" alt="Captured Face" class="img-fluid rounded" style="width: 150px; height: 150px; display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div id="layoutAuthentication_footer">
            <footer class="py-4 mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; Your Website 2023</div>
                        <div>
                            <a href="#" class="text-light">Privacy Policy</a>
                            &middot;
                            <a href="#" class="text-light">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script>
        // Flag to check if models are loaded
        let modelsLoaded = false;
        let faceDetectionActive = false;

        // Load the face recognition models
        async function loadModels() {
            await faceapi.nets.ssdMobilenetv1.loadFromUri('/HR2/face-api.js-master/weights/');
            await faceapi.nets.faceLandmark68Net.loadFromUri('/HR2/face-api.js-master/weights/');
            await faceapi.nets.faceRecognitionNet.loadFromUri('/HR2/face-api.js-master/weights/');

            // Set the flag to true once models are loaded
            modelsLoaded = true;
            console.log("Models loaded successfully");
        }

        // Wait for models to load
        loadModels(); // Call to load models as soon as the page is ready

        const faceImageInput = document.getElementById('face_image');  // Changed to face_image
        const faceDescriptorInput = document.getElementById('faceDescriptorInput');
        const submitBtn = document.getElementById('submitBtn');

        // Function to process face from the uploaded image
        async function processFace() {
            if (!modelsLoaded) {
                alert("Models are not loaded yet. Please wait.");
                return;
            }

            const file = faceImageInput.files[0]; // Changed to face_image
            if (!file) return;

            // Read the image file as an HTML image element
            const img = await faceapi.bufferToImage(file);

            // Detect the face and get its descriptor
            const detections = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();

            if (!detections) {
                alert("No face detected. Please upload a valid image.");
                submitBtn.disabled = true;
                return;
            }

            // Convert the face descriptor (Float32Array) to a simple string format
            const descriptorArray = Array.from(detections.descriptor);
            faceDescriptorInput.value = JSON.stringify(descriptorArray); // Store in hidden input

            // Enable submit button after face is processed
            submitBtn.disabled = false;
        }

        // Listen for file upload changes to trigger face processing
        faceImageInput.addEventListener('change', processFace);  // Changed to face_image

        // Function to start the camera and capture face
        async function startCamera() {
            // Ensure models are loaded before starting the camera
            if (!modelsLoaded) {
                alert("Models are not loaded yet. Please wait.");
                return;
            }

            const video = document.getElementById('videoFeed');

            try {
                // Request access to the video feed
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;

                // Wait for the video to play before starting face detection
                video.addEventListener('play', () => {
                    faceDetectionActive = true;
                    detectFace(video);
                    document.body.style.overflow = 'hidden'; // Disable scrolling
                });
            } catch (error) {
                // Handle possible errors
                if (error.name === 'NotReadableError') {
                    alert("Camera is already in use by another application.");
                } else if (error.name === 'NotAllowedError') {
                    alert("Permission to access the camera was denied.");
                } else if (error.name === 'NotFoundError') {
                    alert("No camera device found.");
                } else {
                    alert("An unknown error occurred: " + error.message);
                }
            }
        }

        // Function to detect and capture face from the video feed
        async function detectFace(video) {
            if (!modelsLoaded || !faceDetectionActive) {
                return;
            }

            // Ensure faceapi canvas is set up and ready to capture the image
            const canvas = faceapi.createCanvasFromMedia(video);
            document.body.append(canvas); // You can append this canvas to the body or a specific container

            // Perform face detection
            const detections = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
            if (detections) {
                // Draw the detections on the canvas
                faceapi.draw.drawDetections(canvas, detections);
                faceapi.draw.drawFaceLandmarks(canvas, detections);

                // Capture the image from the video feed after drawing the face detections
                const imgDataUrl = canvas.toDataURL();
                const imgElement = document.getElementById('capturedFace');
                imgElement.src = imgDataUrl;
                imgElement.style.display = "block";

                // Save the captured face descriptor
                const descriptorArray = Array.from(detections.descriptor);
                faceDescriptorInput.value = JSON.stringify(descriptorArray); // Store in hidden input

                // Enable the capture button
                document.getElementById('captureFaceBtn').disabled = false;
            }

            // Request the next animation frame for continuous face detection
            if (faceDetectionActive) {
                requestAnimationFrame(() => detectFace(video));
            }
        }

        // Attach event listeners for buttons
        document.getElementById('startCameraBtn').addEventListener('click', startCamera);
        document.getElementById('captureFaceBtn').addEventListener('click', () => {
            // Save face image to local storage or computer
            const imgElement = document.getElementById('capturedFace');
            const dataUrl = imgElement.src;

            const link = document.createElement('a');
            link.href = dataUrl;
            link.download = 'captured-face.png';
            link.click();

            // Stop face detection loop
            faceDetectionActive = false;
            document.body.style.overflow = ''; // Enable scrolling
        });

        const positionsByDepartment = {
            "Finance Department": ["Financial Controller", "Accountant", "Credit Analyst", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "Administration Department": ["Facilities Manager", "Operations Manager", "Customer Service Representative", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "Sales Department": ["Sales Manager", "Sales Representative", "Marketing Coordinator", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "Credit Department": ["Loan Officer", "Loan Collection Officer", "Credit Risk Analyst", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "Human Resource Department": ["HR Manager", "Recruitment Specialists", "Training Coordinator", "Supervisor", "Staff", "Field Worker", "Contractual"],
            "IT Department": ["IT Manager", "Network Administrator", "System Administrator", "IT Support Specialist", "Supervisor", "Staff", "Field Worker", "Contractual"]
        };

        function filterPositions() {
            const departmentSelect = document.getElementById("inputDepartment");
            const positionSelect = document.getElementById("inputPosition");
            const selectedDepartment = departmentSelect.value;

            // Clear the previous options in the position dropdown
            positionSelect.innerHTML = '<option value="" disabled selected></option>';

            // Populate the position dropdown with positions relevant to the selected department
            if (positionsByDepartment[selectedDepartment]) {
                positionsByDepartment[selectedDepartment].forEach(position => {
                    const option = document.createElement("option");
                    option.value = position;
                    option.textContent = position;
                    positionSelect.appendChild(option);
                });
            }
        }

        // Attach event listener to department dropdown
        document.getElementById("inputDepartment").addEventListener("change", filterPositions);
    </script>
</body>

</html>
