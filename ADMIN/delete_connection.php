<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db_connection.php'; // Update with your actual path

// Check if `id` is provided for deletion
if (isset($_GET['id'])) {
    $userId = intval($_GET['id']); // Sanitize the input
    $deleteSql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($deleteSql);

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Error preparing delete statement: " . $conn->error;
    }
} else {
    $_SESSION['error_message'] = "No user ID provided for deletion.";
}

// Redirect back to the Manage Users page
header("Location: manage_users.php");
exit();
