<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facial Recognition Attendance & Registration</title>
  <script src="/HR2/face-api.js-master/dist/face-api.min.js"></script>
  <link href="css/styles.css" rel="stylesheet"/>

  <style>
    #videoInput, #canvas {
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
  </style>
</head>
<body class="bg-light text-dark">
    <div class="container my-5">
        <div class="row">
            <div class="col-12 text-center">
                <h1 class="mb-4">Facial Recognition Attendance & Registration</h1>
            </div>

            <!-- Video Section for Attendance and Registration -->
            <div class="col-md-8 offset-md-2 text-center mb-4">
                <video id="videoInput" class="img-fluid" width="640" height="480" autoplay muted></video>
            </div>

            <!-- Canvas Section for Face Detection Display -->
            <div class="col-md-8 offset-md-2 text-center mb-4">
                <canvas id="canvas" width="640" height="480" class="d-none"></canvas>
            </div>

            <!-- Attendance Log Section -->
            <div class="col-md-8 offset-md-2">
                <h2 class="text-center mt-5">Attendance Log</h2>
                
                <!-- Attendance Log Table -->
                <table id="attendanceLogTable" class="table table-striped table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Unique ID</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Logs will be added here -->
                    </tbody>
                </table>
            </div>

            <!-- Face Registration Section with Webcam -->
            <div class="col-md-8 offset-md-2 text-center mt-5">
                <h2>Register Your Face</h2>
                <button class="btn btn-primary" id="webcamRegisterBtn">Register Face Using Webcam</button>
            </div>
        </div>
    </div>

    <script>
      let registeredFaces = new Map();  // Store registered faces
      let loggedFaces = new Map();      // Track logged faces for attendance
      let isLogging = false;            // Prevent duplicate logging within a short time

      // Load models and start video after the library is loaded
      window.onload = async function() {
        try {
          await loadModels();
          startVideo();
          detectContinuous();

          // Handle face registration from webcam
          document.getElementById('webcamRegisterBtn').addEventListener('click', handleWebcamFaceRegistration);
        } catch (error) {
          console.error("Error during onload:", error);
        }
      };

      async function loadModels() {
        try {
          // Load all necessary models
          await faceapi.nets.ssdMobilenetv1.loadFromUri('/HR2/face-api.js-master/weights/');
          await faceapi.nets.faceLandmark68Net.loadFromUri('/HR2/face-api.js-master/weights/');
          await faceapi.nets.faceRecognitionNet.loadFromUri('/HR2/face-api.js-master/weights/');
          console.log("Models loaded successfully.");
        } catch (error) {
          console.error("Error loading models:", error);
        }
      }

      async function startVideo() {
        const video = document.getElementById('videoInput');
        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: true });
          video.srcObject = stream;

          video.onloadedmetadata = () => {
            video.play();
          };
        } catch (err) {
          console.error("Error accessing webcam:", err);
          alert("Could not access webcam. Please ensure you have granted permission.");
        }
      }

      // Continuously detect faces for attendance
      async function detectContinuous() {
        const video = document.getElementById('videoInput');
        const canvas = document.getElementById('canvas');
        const displaySize = { width: video.width, height: video.height };

        faceapi.matchDimensions(canvas, displaySize);

        setInterval(async () => {
          if (isLogging) return; // Prevent duplicate logging within the cooldown period

          try {
            const detections = await faceapi.detectSingleFace(video, new faceapi.SsdMobilenetv1Options())
              .withFaceLandmarks()
              .withFaceDescriptor();

            if (detections) {
              const resizedDetections = faceapi.resizeResults(detections, displaySize);

              // Clear the previous drawings from the canvas
              const context = canvas.getContext('2d');
              context.clearRect(0, 0, canvas.width, canvas.height); // Clear the canvas

              faceapi.draw.drawDetections(canvas, resizedDetections);
              faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);

              // Get the face descriptor
              const currentDescriptor = detections.descriptor;

              // Check if the face is registered
              const matchedFace = findMatchingFace(currentDescriptor, registeredFaces);
              if (matchedFace) {
                handleAttendance(matchedFace);
              }
            }
          } catch (error) {
            console.error("Error detecting face:", error);
          }
        }, 2000); // Continuous detection (2 seconds interval)
      }

      // Handle attendance logging
      function handleAttendance(matchedFace) {
        const today = new Date().toISOString().split('T')[0];
        const loggedFace = loggedFaces.get(matchedFace.uniqueId);

        if (!loggedFace || loggedFace.date !== today) {
          // Log "Time In"
          logAttendance(matchedFace.uniqueId, 'Time In');
          loggedFaces.set(matchedFace.uniqueId, { date: today, status: 'in' });

          // Cooldown
          isLogging = true;
          setTimeout(() => { isLogging = false; }, 5000);
        } else if (loggedFace.status === 'in') {
          // Log "Time Out"
          logAttendance(matchedFace.uniqueId, 'Time Out');
          loggedFace.status = 'out';

          // Cooldown
          isLogging = true;
          setTimeout(() => { isLogging = false; }, 5000);
        }
      }

      function findMatchingFace(newDescriptor, faceMap) {
        for (const [descriptor, face] of faceMap) {
          const distance = faceapi.euclideanDistance(descriptor, newDescriptor);
          if (distance <= 0.6) {
            return face;
          }
        }
        return null;
      }

      function generateUniqueId() {
        return 'User-' + Math.floor(Math.random() * 10000);
      }

      function logAttendance(uniqueId, status) {
        const tableBody = document.querySelector('#attendanceLogTable tbody');
        const time = new Date().toLocaleTimeString();

        const row = document.createElement('tr');
        row.innerHTML = `<td>${uniqueId}</td><td>${status}</td><td>${time}</td>`;
        tableBody.appendChild(row);
      }

      // Handle face registration through the webcam
      async function handleWebcamFaceRegistration() {
        const video = document.getElementById('videoInput');

        try {
          const detections = await faceapi.detectSingleFace(video, new faceapi.SsdMobilenetv1Options())
            .withFaceLandmarks()
            .withFaceDescriptor();

          if (detections) {
            const descriptor = detections.descriptor;
            const uniqueId = generateUniqueId();
            registeredFaces.set(descriptor, { uniqueId: uniqueId });
            alert('Face registered successfully using webcam!');
          } else {
            alert('No face detected. Please make sure your face is clearly visible in the webcam.');
          }
        } catch (error) {
          console.error("Error during face registration using webcam:", error);
          alert('Error during face registration.');
        }
      }
    </script>
</body>
</html>
