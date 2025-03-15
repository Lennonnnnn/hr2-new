<?php
session_start();

// Unset only admin-specific session variables
unset($_SESSION['a_id']);
unset($_SESSION['role']);


header("Location: ../login.php");
exit();
?>
