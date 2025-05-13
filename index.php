<?php
// Check if a role parameter is provided
if (isset($_GET['role']) && in_array($_GET['role'], ['tenant', 'landlord'])) {
    $role = $_GET['role'];
    // Redirect to login page with role parameter
    header("Location: USERS/login.php?role=" . $role);
} else {
    // If no role is specified, serve the index.html page
    include('index.html');
}
exit;
?>
