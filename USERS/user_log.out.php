<?php
session_start();

// Destroy all session data
session_destroy();

// Redirect to login page with a logout message
header("Location: login.php?message=You have been logged out successfully.");
exit();
?>