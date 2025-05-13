<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db_connection.php'; // Update with your actual path

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = intval($_GET['delete']); // Sanitize the input
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
        $_SESSION['error_message'] = "Error preparing the delete statement: " . $conn->error;
    }
    // Redirect to avoid repeated deletion on page refresh
    header("Location: manage_users.php");
    exit();
}

// Fetch users with additional information
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM reservations WHERE user_id = u.id) as reservation_count
        FROM users u
        ORDER BY u.created_at DESC";
$result = $conn->query($sql);

if ($result === false) {
    $error_message = "Error fetching users: " . $conn->error;
} else {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="user-management">
        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Actions Bar -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2><i class="fas fa-users"></i> Manage Users</h2>
            <a href="add_user.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
        </div>

        <!-- Users Table -->
        <?php if (isset($users) && count($users) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Reservations</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php echo htmlspecialchars($user['name'] . ' ' . $user['lastname']); ?>
                                        <?php if ($user['reservation_count'] > 0): ?>
                                            <span class="badge bg-success ms-2">Active</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo $user['reservation_count']; ?> reservations</td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="manage_users.php?delete=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this user? This will also delete all their reservations.');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                <p class="text-muted">No users found. Add your first user to get started!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
