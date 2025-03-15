document.addEventListener('DOMContentLoaded', function () {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const outputMessage = document.getElementById('outputMessage');
    const recordsTableBody = document.querySelector('#recordsTable tbody');

    // Set up video stream
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            video.srcObject = stream;
            video.setAttribute('playsinline', true); // required for iOS
            video.play();
            requestAnimationFrame(tick);
        })
        .catch(err => {
            console.error('Error accessing camera:', err);
        });

    function tick() {
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.height = video.videoHeight;
            canvas.width = video.videoWidth;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, canvas.width, canvas.height, {
                inversionAttempts: "dontInvert",
            });

            if (code) {
                outputMessage.textContent = 'QR code detected: ' + code.data;
                handleQRCode(code.data);
            } else {
                outputMessage.textContent = 'Scan your QR code to record Time In/Out.';
            }
        }
        requestAnimationFrame(tick);
    }

    function handleQRCode(data) {
        const [employeeID, action] = data.split('|'); // Assuming QR code contains "employeeID|action"

        fetch('process_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `employee_id=${encodeURIComponent(employeeID)}&action=${encodeURIComponent(action)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addRecordToTable(data.record);
            } else {
                outputMessage.textContent = 'Failed to record the data: ' + data.message;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function addRecordToTable(record) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${record.employee_id}</td>
            <td>${record.action}</td>
            <td>${record.timestamp}</td>
        `;
        recordsTableBody.prepend(row);
    }

    // Optionally: Load today's records on page load
    fetch('get_records.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.records.forEach(record => addRecordToTable(record));
            } else {
                console.error('Failed to load records:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading records:', error);
        });
});
