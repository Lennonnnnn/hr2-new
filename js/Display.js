const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const context = canvas.getContext('2d');
const outputMessage = document.getElementById('outputMessage');
const outputData = document.getElementById('outputData');
const recordsTable = document.getElementById('recordsTable').getElementsByTagName('tbody')[0];

let stream; // Variable to store the video stream
let lastScannedCode = null; // Store the last scanned QR code
let lastScanTime = 0; // Store the timestamp of the last scan
const scanCooldown = 2000; // 2 seconds cooldown
let scanningActive = true; // To manage scanning state

// Use getUserMedia API to stream video from the camera
navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    .then(mediaStream => {
        stream = mediaStream; // Store the stream
        video.srcObject = stream;
        video.setAttribute('playsinline', true); // For iOS Safari
        requestAnimationFrame(tick);
    })
    .catch(err => {
        console.error('Error accessing camera: ', err);
    });

// Stop the video stream and close the camera
function stopCamera() {
    if (stream) {
        const tracks = stream.getTracks();
        tracks.forEach(track => track.stop()); // Stop each track to turn off the camera
        video.srcObject = null;
    }
}

function tick() {
    if (video.readyState === video.HAVE_ENOUGH_DATA && scanningActive) {
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert',
        });
        
        const currentTime = Date.now(); // Get current timestamp
        
        if (code) {
            // Check if this code was scanned recently
            if (code.data !== lastScannedCode || (currentTime - lastScanTime) > scanCooldown) {
                lastScannedCode = code.data; // Update last scanned code
                lastScanTime = currentTime; // Update last scan time
                
                outputMessage.hidden = true;
                outputData.innerText = `Employee ID: ${code.data}`;
                recordTime(code.data);
                
                stopCamera(); // Stop the camera after QR code detection
                scanningActive = false; // Disable further scans
            }
        } else {
            outputMessage.hidden = false;
            outputData.innerText = '';
        }
    }
    if (scanningActive) {
        requestAnimationFrame(tick); // Only keep requesting frames if scanning is active
    }
}

function recordTime(employeeId) {
    const now = new Date();
    const row = recordsTable.insertRow();
    const employeeIdCell = row.insertCell(0);
    const actionCell = row.insertCell(1);
    const timestampCell = row.insertCell(2);

    employeeIdCell.textContent = employeeId;
    actionCell.textContent = determineAction(employeeId);
    timestampCell.textContent = now.toLocaleString();
}

function determineAction(employeeId) {
    const lastRow = recordsTable.rows[recordsTable.rows.length - 1];
    if (lastRow && lastRow.cells[0].textContent === employeeId && lastRow.cells[1].textContent === 'Time In') {
        return 'Time Out';
    }
    return 'Time In';
}

// Optional: Restart scanning after a cooldown period
function restartScanning() {
    scanningActive = true; // Enable scanning again
    lastScannedCode = null; // Reset the last scanned code
    lastScanTime = 0; // Reset the last scan time
    startCamera(); // Restart camera
}

// Start camera function for restarting
function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(mediaStream => {
            stream = mediaStream; // Store the stream
            video.srcObject = stream;
            video.setAttribute('playsinline', true); // For iOS Safari
            requestAnimationFrame(tick);
        })
        .catch(err => {
            console.error('Error accessing camera: ', err);
        });
}



