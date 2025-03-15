<?php
session_start();

if (!isset($_SESSION['e_id']) || !isset($_SESSION['position']) || $_SESSION['position'] !== 'Staff') {
    header("Location: ../../login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time In/Out Recording System</title>
    <link href="../../css/timesheet.css" rel="stylesheet">
</head>
<body>
    <header>
        <h1>Time In Recording System</h1>
    </header>

    <section class="scanner">
        <video id="video" autoplay></video>
        <canvas id="canvas" class="hidden"></canvas>
        <p id="outputMessage">Scan your QR code to record Time In/Out.</p>
        <p id="outputData"></p>
    </section>

    <section class="records">
        <h2>Today's Records</h2>
        <table id="recordsTable">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Action</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <!-- Records will be dynamically added here -->
            </tbody>
        </table>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.3.1/dist/jsQR.js"></script>
    <script src="../../js/Display.js"></script>
</body>
</html>
    