<?php 
date_default_timezone_set('Asia/Manila'); // Set the time zone to Philippine time 
include 'db/db_conn.php'; // Ensure correct file path 
// Get today's date in the format 'Y-m-d' 
$today = date('Y-m-d'); 
// Fetch attendance data for the current day along with employee details 
$query = " 
SELECT 
a.e_id, 
er.firstname, 
er.lastname, 
a.time_in, 
a.time_out, 
a.attendance_date, 
a.status 
FROM attendance_log AS a 
LEFT JOIN employee_register AS er ON a.e_id = er.e_id 
WHERE a.attendance_date = ? 
"; 
// Prepare the statement to bind the date parameter 
$stmt = $conn->prepare($query); 
$stmt->bind_param('s', $today); // Bind today's date to the query 
$stmt->execute(); 
$result = $stmt->get_result(); 
// Store data in an array for the table 
$attendanceLogs = []; 
while ($row = $result->fetch_assoc()) { 
$attendanceLogs[] = $row; 
} 
$stmt->close(); 
$conn->close(); 
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
<meta charset="UTF-8"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title>Facial Recognition Attendance</title> 
<script src="/HR2/face-api.js-master/dist/face-api.min.js"></script> 
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet"> 
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
<link href="css/styles.css" rel="stylesheet"/> 
<style> 
:root { 
--dark-bg: #121212; 
--card-bg: #1e1e1e; 
--light-gray: #e0e0e0; 
--medium-gray: #757575; 
--accent: #3d85c6; 
--accent-hover: #2a75b6; 
--success: #4caf50; 
--danger: #f44336; 
} 
body { 
background-color: var(--dark-bg); 
color: var(--light-gray); 
font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
} 
.page-header { 
padding: 1.5rem 0; 
border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
margin-bottom: 2rem; 
} 
.main-card { 
background-color: var(--card-bg); 
border-radius: 12px; 
box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3); 
overflow: hidden; 
margin-bottom: 2rem; 
} 
.card-header { 
background-color: rgba(0, 0, 0, 0.2); 
border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
padding: 1rem 1.5rem; 
font-weight: 600; 
} 
#videoInput { 
width: 100%; 
height: 100%; 
border-radius: 8px; 
background-color: #000; 
box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5); 
object-fit: cover; 
} 
.video-container { 
position: relative; 
width: 100%; 
height: 380px; 
border-radius: 8px; 
overflow: hidden; 
margin-bottom: 1.5rem; 
} 
.recognition-btn { 
background-color: var(--accent); 
color: white; 
border: none; 
padding: 0.75rem 1.5rem; 
border-radius: 8px; 
font-weight: 600; 
transition: all 0.3s ease; 
margin-bottom: 1.5rem; 
width: 100%; 
} 
.recognition-btn:hover { 
background-color: var(--accent-hover); 
transform: translateY(-2px); 
box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); 
} 
.recognition-btn:active { 
transform: translateY(0); 
} 
.recognition-btn i { 
margin-right: 0.5rem; 
} 
.table { 
color: var(--light-gray); 
border-collapse: separate; 
border-spacing: 0; 
} 
.table thead th { 
background-color: rgba(0, 0, 0, 0.2); 
color: white; 
font-weight: 600; 
border-bottom: 2px solid var(--accent); 
padding: 1rem; 
} 
.table tbody td { 
padding: 1rem; 
border-bottom: 1px solid rgba(255, 255, 255, 0.05); 
vertical-align: middle; 
} 
.table tbody tr:hover { 
background-color: rgba(255, 255, 255, 0.05); 
} 
.status-badge { 
padding: 0.35rem 0.75rem; 
border-radius: 50px; 
font-size: 0.85rem; 
font-weight: 600; 
} 
.status-present { 
background-color: rgba(76, 175, 80, 0.2); 
color: #4caf50; 
} 
.status-late { 
background-color: rgba(255, 152, 0, 0.2); 
color: #ff9800; 
} 
.status-absent { 
background-color: rgba(244, 67, 54, 0.2); 
color: #f44336; 
} 
.modal-content { 
background-color: var(--card-bg); 
color: var(--light-gray); 
border-radius: 12px; 
} 
.modal-header { 
border-bottom: 1px solid rgba(255, 255, 255, 0.1); 
} 
.modal-footer { 
border-top: 1px solid rgba(255, 255, 255, 0.1); 
} 
.btn-close { 
filter: invert(1); 
} 
.date-display { 
font-size: 1.1rem; 
margin-bottom: 1rem; 
color: var(--medium-gray); 
} 
.empty-state { 
padding: 2rem; 
text-align: center; 
color: var(--medium-gray); 
} 
.empty-state i { 
font-size: 3rem; 
margin-bottom: 1rem; 
opacity: 0.5; 
} 
@media (max-width: 992px) { 
.container { 
max-width: 100%; 
padding: 0 1rem; 
} 
.row { 
flex-direction: column; 
} 
.col-lg-5, .col-lg-7 { 
width: 100%; 
} 
} 
</style> 
</head> 
<body> 
<div class="container"> 
<div class="page-header text-center"> 
<h1><i class="fas fa-user-clock me-2"></i>Attendance Log</h1> 
<p class="date-display"><?php echo date("F j, Y"); ?></p> 
</div> 
<div class="row g-4"> 
<!-- Facial Recognition Section --> 
<div class="col-lg-5"> 
<div class="main-card h-100"> 
<div class="card-header"> 
<i class="fas fa-camera me-2"></i>Facial Recognition 
</div> 
<div class="card-body p-4"> 
<button id="startBtn" class="recognition-btn"> 
<i class="fas fa-play-circle"></i>Start Facial Recognition 
</button> 
<div class="video-container"> 
<video id="videoInput" autoplay muted></video> 
</div> 
<div class="text-center text-muted"> 
<small><i class="fas fa-info-circle me-1"></i>Position your face in the frame for recognition</small> 
</div> 
</div> 
</div> 
</div> 
<!-- Attendance Records Section --> 
<div class="col-lg-7"> 
<div class="main-card h-100"> 
<div class="card-header d-flex justify-content-between align-items-center"> 
<div> 
<i class="fas fa-clipboard-list me-2"></i>Employee Attendance Records 
</div> 
<div class="badge bg-dark text-white">Today</div> 
</div> 
<div class="card-body p-0"> 
<div class="table-responsive"> 
<table id="datatablesSimple" class="table table-hover mb-0"> 
<thead> 
<tr> 
<th>Employee ID</th> 
<th>Name</th> 
<th>Time-In</th> 
<th>Time-Out</th> 
<th>Date</th> 
<th>Status</th> 
</tr> 
</thead> 
<tbody> 
<?php if (empty($attendanceLogs)): ?> 
<tr> 
<td colspan="6"> 
<div class="empty-state"> 
<i class="fas fa-calendar-xmark"></i> 
<p>No attendance records available for today</p> 
<small>Records will appear here once employees check in</small> 
</div> 
</td> 
</tr> 
<?php else: ?> 
<?php foreach ($attendanceLogs as $row): ?> 
<tr> 
<td><?php echo htmlspecialchars(trim($row['e_id'] ?? 'N/A')); ?></td> 
<td><?php echo htmlspecialchars(trim($row['firstname'] . ' ' . $row['lastname'] ?? 'N/A')); ?></td> 
<td> 
<?php if (!empty($row['time_in'])): ?> 
<i class="fas fa-sign-in-alt text-success me-1"></i> 
<?php echo date('h:i A', strtotime($row['time_in'])); ?> 
<?php else: ?> 
<span class="text-muted">N/A</span> 
<?php endif; ?> 
</td> 
<td> 
<?php if (!empty($row['time_out'])): ?> 
<i class="fas fa-sign-out-alt text-danger me-1"></i> 
<?php echo date('h:i A', strtotime($row['time_out'])); ?> 
<?php else: ?> 
<span class="text-muted">N/A</span> 
<?php endif; ?> 
</td> 
<td><?php echo htmlspecialchars(trim(date("M j", strtotime($row['attendance_date'])))); ?></td> 
<td> 
<?php 
$statusClass = ''; 
switch(strtolower(trim($row['status'] ?? ''))) { 
case 'present': 
$statusClass = 'status-present'; 
break; 
case 'late': 
$statusClass = 'status-late'; 
break; 
case 'absent': 
$statusClass = 'status-absent'; 
break; 
default: 
$statusClass = ''; 
} 
?> 
<span class="status-badge <?php echo $statusClass; ?>"> 
<?php echo htmlspecialchars(trim($row['status'] ?? 'N/A')); ?> 
</span> 
</td> 
</tr> 
<?php endforeach; ?> 
<?php endif; ?> 
</tbody> 
</table> 
</div> 
</div> 
</div> 
</div> 
</div> 
</div> 
<!-- Attendance Status Modal --> 
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true"> 
<div class="modal-dialog modal-dialog-centered"> 
<div class="modal-content"> 
<div class="modal-header"> 
<h5 class="modal-title" id="attendanceModalLabel"> 
<i class="fas fa-bell me-2"></i>Attendance Status 
</h5> 
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> 
</div> 
<div class="modal-body" id="modalMessage"> 
<!-- Success or error message will be displayed here --> 
</div> 
<div class="modal-footer"> 
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> 
</div> 
</div> 
</div> 
</div> 
<script> 
let employeeData = []; // To store employee data with face descriptors 
let lastFacePosition = null; // Track the last face position for liveness detection 
let lastFrameData = null; // Track the last video frame for liveness detection 
let lastHeadPosition = null; // Track the last head position for liveness detection 
// Initialize when window is loaded 
window.onload = async function() { 
try { 
await loadModels(); 
await fetchEmployeeData(); // Fetch employee data from the database 
} catch (error) { 
console.error("Error during onload:", error); 
showModal("Error during initial setup. Please refresh the page and try again."); 
} 
}; 
// Fetch employee data from the database (ID and face image) 
async function fetchEmployeeData() { 
try { 
console.log("Fetching employee data..."); 
const response = await fetch('/HR2/fetch_employee_data.php'); // Fetch from PHP backend 
const data = await response.json(); 
console.log("Employee data received:", data); 
// Clear the employeeData array to avoid duplicate entries on each reload 
employeeData = []; 
for (const employee of data) { 
if (employee.face_descriptor) { 
let descriptor = employee.face_descriptor; 
// Check if the descriptor is a string and needs conversion to a Float32Array 
if (typeof descriptor === 'string') { 
descriptor = JSON.parse(descriptor); 
} 
// Check if descriptor is valid 
if (Array.isArray(descriptor) && descriptor.length === 128) { 
employeeData.push({ 
e_id: employee.e_id, 
descriptor: new Float32Array(descriptor) // Convert to Float32Array for matching 
}); 
} else { 
console.error(`Invalid face descriptor for employee ${employee.e_id}`); 
} 
} else { 
console.error(`No face descriptor found for employee ${employee.e_id}`); 
} 
} 
console.log("Employee data loaded."); 
} catch (error) { 
console.error("Error fetching employee data:", error); 
showModal("Error fetching employee data. Please check your connection and try again."); 
} 
} 
async function loadModels() { 
try { 
console.log("Loading face-api models..."); 
// Show loading indicator 
const startBtn = document.getElementById('startBtn'); 
startBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading models...'; 
startBtn.disabled = true; 
await faceapi.nets.ssdMobilenetv1.loadFromUri('/HR2/face-api.js-master/weights/'); 
await faceapi.nets.faceLandmark68Net.loadFromUri('/HR2/face-api.js-master/weights/'); 
await faceapi.nets.faceRecognitionNet.loadFromUri('/HR2/face-api.js-master/weights/'); 
console.log("Models loaded successfully."); 
// Reset button 
startBtn.innerHTML = '<i class="fas fa-play-circle"></i> Start Facial Recognition'; 
startBtn.disabled = false; 
} catch (error) { 
console.error("Error loading models:", error); 
showModal("Error loading face recognition models. Please check your connection and try again."); 
} 
} 
// Start the video and face recognition process when the Start button is clicked 
document.getElementById('startBtn').addEventListener('click', async () => { 
const startBtn = document.getElementById('startBtn'); 
if (startBtn.classList.contains('active')) { 
// Stop the video if it's already running 
stopVideo(); 
startBtn.classList.remove('active'); 
startBtn.innerHTML = '<i class="fas fa-play-circle"></i> Start Facial Recognition'; 
startBtn.style.backgroundColor = 'var(--accent)'; 
} else { 
// Start the video 
await startVideo(); 
startBtn.classList.add('active'); 
startBtn.innerHTML = '<i class="fas fa-stop-circle"></i> Stop Recognition'; 
startBtn.style.backgroundColor = 'var(--danger)'; 
// Start detecting faces every few seconds 
window.recognitionInterval = setInterval(detectFace, 5000); 
} 
}); 
async function startVideo() { 
const video = document.getElementById('videoInput'); 
try { 
const stream = await navigator.mediaDevices.getUserMedia({ 
video: { width: 640, height: 480, frameRate: { ideal: 10, max: 15 } } 
}); 
console.log("Video stream started successfully."); 
video.srcObject = stream; 
video.onloadedmetadata = () => { 
video.play(); 
}; 
video.onerror = (error) => { 
console.error("Error with video playback:", error); 
showModal("Error with video playback. Please check your camera."); 
}; 
} catch (err) { 
console.error("Error accessing webcam:", err.name, err.message); 
if (err.name === 'NotReadableError') { 
showModal("The webcam is already in use by another application."); 
} else if (err.name === 'NotAllowedError') { 
showModal("Permission to access the camera has been denied."); 
} else if (err.name === 'OverconstrainedError') { 
showModal("The webcam does not meet the specified constraints."); 
} else { 
showModal("Error accessing webcam. Please check your browser settings."); 
} 
} 
} 
function stopVideo() { 
const video = document.getElementById('videoInput'); 
if (video.srcObject) { 
const tracks = video.srcObject.getTracks(); 
tracks.forEach(track => track.stop()); 
video.srcObject = null; 
} 
// Clear the recognition interval 
if (window.recognitionInterval) { 
clearInterval(window.recognitionInterval); 
} 
} 
// Function to check if the user is moving their head 
function isHeadMoving(landmarks) { 
const noseTip = landmarks.getNose()[3]; // Nose tip landmark 
const currentPosition = { x: noseTip.x, y: noseTip.y }; 
if (lastHeadPosition) { 
const movement = Math.abs(currentPosition.x - lastHeadPosition.x) + 
Math.abs(currentPosition.y - lastHeadPosition.y); 
lastHeadPosition = currentPosition; 
return movement > 5; // Threshold for head movement 
} 
lastHeadPosition = currentPosition; 
return false; 
} 
// Detect and match the face from the video stream with stored employee data 
async function detectFace() { 
const video = document.getElementById('videoInput'); 
// Check if video is playing 
if (!video.srcObject) { 
return; 
} 
// Add visual feedback that scanning is happening 
const videoContainer = video.parentElement; 
videoContainer.style.boxShadow = '0 0 0 3px var(--accent)'; 
setTimeout(() => { 
videoContainer.style.boxShadow = ''; 
}, 500); 
// Check if the video feed is live (not a static image) 
if (!isVideoLive(video)) { 
showModal("Static frame detected. Please ensure you are using a live camera."); 
return; 
} 
const detections = await faceapi.detectAllFaces(video) 
.withFaceLandmarks() 
.withFaceDescriptors(); 
if (detections.length === 0) { 
console.log("No face detected."); 
return; 
} 
// Liveness check: Ensure the face is moving 
const landmarks = detections[0].landmarks; 
if (!isHeadMoving(landmarks)) { 
console.log("No head movement detected. Possible spoofing attempt."); 
showModal("Please move your head slightly to prove liveness."); 
return; 
} 
// Proceed with face matching 
let faceMatched = false; 
for (let detection of detections) { 
const bestMatch = findBestMatch(detection.descriptor); 
if (bestMatch) { 
logAttendance(bestMatch); 
faceMatched = true; 
} 
} 
if (!faceMatched) { 
showModal("Unknown person detected! Access denied."); 
} 
} 
// Check if the video feed is live by comparing frames 
function isVideoLive(video) { 
const canvas = document.createElement('canvas'); 
canvas.width = video.videoWidth; 
canvas.height = video.videoHeight; 
const ctx = canvas.getContext('2d'); 
ctx.drawImage(video, 0, 0, canvas.width, canvas.height); 
const currentFrameData = ctx.getImageData(0, 0, canvas.width, canvas.height).data; 
if (lastFrameData) { 
let difference = 0; 
for (let i = 0; i < currentFrameData.length; i++) { 
difference += Math.abs(currentFrameData[i] - lastFrameData[i]); 
} 
if (difference < 100000) { // Threshold for frame difference 
console.log("Static frame detected. Possible spoofing attempt."); 
return false; 
} 
} 
lastFrameData = currentFrameData; 
return true; 
} 
// Find the best match for the detected face 
function findBestMatch(descriptor) { 
let bestMatch = null; 
let bestMatchDistance = Number.MAX_VALUE; 
for (let i = 0; i < employeeData.length; i++) { 
const distance = faceapi.euclideanDistance(descriptor, employeeData[i].descriptor); 
if (distance < bestMatchDistance) { 
bestMatch = employeeData[i]; 
bestMatchDistance = distance; 
} 
} 
// If the best match is less than 0.6 distance, it is considered a match 
return bestMatchDistance < 0.6 ? bestMatch : null; 
} 
async function logAttendance(employee) { 
const formData = new FormData(); 
formData.append('employeeId', employee.e_id); 
try { 
const response = await fetch('/HR2/attendanceLog.php', { 
method: 'POST', 
body: formData 
}); 
const result = await response.json(); 
showModal(result.message, result.success); 
// Refresh the page after successful attendance logging to update the table 
if (result.success) { 
setTimeout(() => { 
location.reload(); 
}, 2000); 
} 
} catch (error) { 
console.error("Error logging attendance:", error); 
showModal("Error logging attendance. Please try again."); 
} 
} 
function showModal(message, success = false) { 
const modalMessage = document.getElementById('modalMessage'); 
const modal = new bootstrap.Modal(document.getElementById('attendanceModal')); 
// Set message with appropriate styling 
if (success) { 
modalMessage.innerHTML = ` 
<div class="text-center"> 
<i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i> 
<p class="mt-3">${message}</p> 
</div> 
`; 
} else { 
modalMessage.innerHTML = ` 
<div class="text-center"> 
<i class="fas fa-exclamation-circle text-danger" style="font-size: 3rem;"></i> 
<p class="mt-3">${message}</p> 
</div> 
`; 
} 
modal.show(); 
} 
</script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script> 
<script src="js/datatables-simple-demo.js"></script> 
</body> 
</html>
