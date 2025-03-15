<?php
   // Include QR code library
   include('../phpqrcode/qrlib.php');
   //include('config.php'); // Your database connection

   if ($_SERVER['REQUEST_METHOD'] == 'POST') {
       // Capture employee details from the form submission
       $employee_id = $_POST['e_id'];
       $firstname = $_POST['firstname'];
       $lastname = $_POST['lastname'];
       $email = $_POST['email'];
       // Other employee details...

       // Insert employee details into the database
       $query = "INSERT INTO employee_register (firstname, lastname, email) VALUES ('$employee_id, $firstname', '$lastname', '$email')";
       if (mysqli_query($conn, $query)) {
           $employee_id = mysqli_insert_id($conn);  // Get the last inserted ID for the new employee
           
           // QR code generation logic
           $tempDir = "C:/xampp/htdocs/HR2/QR/";  // Absolute path to QR directory with trailing slash
           if (!file_exists($tempDir)) {
               mkdir($tempDir, 0777, true);  // Create the directory if it doesn't exist
           }
           
           $codeContents = 'EmployeeID_' . $employee_id;  // Use employee ID as part of QR code content
           $fileName = 'employee_' . $employee_id . '.png';
           $pngAbsoluteFilePath = $tempDir . $fileName;
           $urlRelativeFilePath = $tempDir . $fileName;
           
           // Generate QR code
           QRcode::png($codeContents, $pngAbsoluteFilePath);

           echo 'Employee account created and QR code generated! <br>';
           echo 'QR Code File: ' . $urlRelativeFilePath;
           echo '<br><img src="' . $urlRelativeFilePath . '" />';  // Display the QR code
       } else {
           echo 'Error: ' . mysqli_error($conn);
       }
   }
?>
