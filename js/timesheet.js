const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const context = canvas.getContext('2d');
const outputMessage = document.getElementById('outputMessage');
const outputData = document.getElementById('outputData');
const recordsTable = document.getElementById('recordsTable').getElementsByTagName('tbody')[0];

// Store last scanned QR code and time to prevent spamming
let lastScannedCode = null;
let lastScanTime = 0;

// Use getUserMedia API to stream video from the camera
navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    .then(stream => {
        video.srcObject = stream;
        video.setAttribute('playsinline', true); // For iOS Safari
        requestAnimationFrame(tick);
    })
    .catch(err => {
        console.error('Error accessing camera: ', err);
    });

function tick() {
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert',
        });
        if (code) {
            const now = Date.now();

            // Check for duplicate within cooldown (e.g., 3000ms or 3 seconds)
            if (code.data !== lastScannedCode || now - lastScanTime > 3000) {
                lastScannedCode = code.data;
                lastScanTime = now;

                outputMessage.hidden = true;
                outputData.innerText = `Employee ID: ${code.data}`;
                recordTime(code.data);
            }
        } else {
            outputMessage.hidden = false;
            outputData.innerText = '';
        }
    }
    requestAnimationFrame(tick);
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
