<?php
session_start(); // Start the session

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to admin login page with a logout message
header("Location: index.php.php?message=Logout successful");
exit();
?>