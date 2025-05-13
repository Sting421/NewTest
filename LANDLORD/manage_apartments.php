<?php
session_start();
require_once('../includes/db_connection.php');

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'landlord') {
    header("Location: login.php");
    exit();
}

// Fetch user's name
$user_id = $_SESSION['user_id'];
$user_fullname = htmlspecialchars(ucwords(strtolower($_SESSION['name'] . " " . $_SESSION['lastname'])));

// Get landlord's apartments
$apartments_sql = "SELECT a.*, 
                  (SELECT COUNT(*) FROM reservations r WHERE r.apartment_id = a.id AND r.status = 'reserved') as reservation_count 
                  FROM apartments a 
                  WHERE a.owner_id = ? 
                  ORDER BY a.created_at DESC";
$apartments_stmt = $conn->prepare($apartments_sql);
$apartments_stmt->bind_param("i", $user_id);
$apartments_stmt->execute();
$apartments_result = $apartments_stmt->get_result();

// Handle toggle availability
if (isset($_POST['toggle_availability'])) {
    $apartment_id = $_POST['apartment_id'];
    
    // Check if the apartment has active reservations
    $check_reservations_sql = "SELECT COUNT(*) as count FROM reservations 
                              WHERE apartment_id = ? AND status = 'reserved'";
    $check_stmt = $conn->prepare($check_reservations_sql);
    $check_stmt->bind_param("i", $apartment_id);
    $check_stmt->execute();
    $reservation_count = $check_stmt->get_result()->fetch_assoc()['count'];
    
    if ($reservation_count > 0) {
        $error_message = "Cannot change availability as this property has active reservations.";
    } else {
        // Update availability
        $update_sql = "UPDATE apartments SET available = NOT available WHERE id = ? AND owner_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $apartment_id, $user_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Property availability updated successfully!";
            header("Location: manage_apartments.php?success=" . urlencode($success_message));
            exit();
        } else {
            $error_message = "Error updating property availability.";
        }
    }
}

// Set success or error messages from URL parameters
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - Landlord Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --danger-color: #ef476f;
            --danger-hover: #d23d60;
            --success-color: #06d6a0;
            --warning-color: #ffd166;
            --dark-color: #1d3557;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1340px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        h2, h4, h5 {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 16px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Button Styles with Transitions */
        .btn {
            font-weight: 500;
            letter-spacing: 0.3px;
            border-radius: 6px;
            padding: 8px 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.3);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .btn:active::after {
            opacity: 1;
            transform: scale(50, 50) translate(-50%);
            transition: all 0.5s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
            transform: translateY(-1px);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            box-shadow: 0 2px 4px rgba(239, 71, 111, 0.2);
        }
        
        .btn-danger:hover {
            background-color: var(--danger-hover);
            border-color: var(--danger-hover);
            box-shadow: 0 4px 8px rgba(239, 71, 111, 0.3);
            transform: translateY(-1px);
        }
        
        /* Table Styles */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table th {
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
            border-top: none;
        }
        
        .table td, .table th {
            padding: 12px 16px;
            vertical-align: middle;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        /* Badge Styles */
        .badge {
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .bg-success {
            background-color: var(--success-color) !important;
        }
        
        .bg-warning {
            background-color: var(--warning-color) !important;
            color: #333 !important;
        }
        
        .bg-danger {
            background-color: var(--danger-color) !important;
        }
        
        /* Navbar styles */
        .custom-navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: var(--dark-color);
        }
        
        .navbar-toggler {
            border: none;
            padding: 0;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e0e0ff;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 10px;
        }
        
        .dropdown-item {
            border-radius: 6px;
            padding: 10px 15px;
            color: #495057;
            font-weight: 500;
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: rgba(67, 97, 238, 0.05);
            color: var(--primary-color);
        }
        
        .dropdown-item.danger {
            color: var(--danger-color);
        }
        
        .dropdown-item.danger:hover, .dropdown-item.danger:focus {
            background-color: rgba(239, 71, 111, 0.05);
            color: var(--danger-color);
        }
        
        /* Property card */
        .property-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .property-price {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .property-location {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .property-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        /* Snackbar */
        #snackbar {
            visibility: hidden;
            min-width: 280px;
            margin-left: -140px;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 1050;
            left: 50%;
            bottom: 30px;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s, visibility 0s linear 0.3s;
        }

        #snackbar.success {
            background-color: var(--success-color);
        }

        #snackbar.error {
            background-color: var(--danger-color);
        }

        #snackbar.show {
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s, transform 0.3s, visibility 0s linear 0s;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-home me-2"></i>Landlord Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_apartments.php">
                            <i class="fas fa-building me-2"></i>My Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_reservations.php">
                            <i class="fas fa-calendar-check me-2"></i>Reservations
                        </a>
                    </li>
                </ul>
                <div class="dropdown user-dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo substr($_SESSION['name'], 0, 1); ?>
                        </div>
                        <span class="d-none d-sm-inline"><?php echo $user_fullname; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>My Properties</h2>
                <p class="text-muted">Manage your rental properties</p>
            </div>
            <div>
                <a href="add_apartment.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Property
                </a>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Properties Grid -->
        <div class="row">
            <?php if ($apartments_result->num_rows > 0): ?>
                <?php while($apartment = $apartments_result->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card property-card h-100" onclick="window.location.href='view_apartment.php?id=<?php echo $apartment['id']; ?>'" style="cursor: pointer;">
                            <div class="position-relative">
                                <?php if (!empty($apartment['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($apartment['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($apartment['name']); ?>" style="height: 180px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 180px;">
                                        <i class="fas fa-home fa-3x text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="badge property-badge <?php echo $apartment['available'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $apartment['available'] ? 'Available' : 'Occupied'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($apartment['name']); ?></h5>
                                <p class="property-location">
                                    <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($apartment['location']); ?>
                                </p>
                                <p class="property-price">₱<?php echo number_format($apartment['price'], 2); ?>/month</p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="text-muted small">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        <?php echo $apartment['reservation_count']; ?> reservation(s)
                                    </span>
                                    <span class="text-muted small">
                                        <i class="fas fa-clock me-1"></i>
                                        Added: <?php echo date('M j, Y', strtotime($apartment['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-0 pb-3">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                    <a href="edit_apartment.php?id=<?php echo $apartment['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </a>
                                    <?php if ($apartment['reservation_count'] == 0): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="apartment_id" value="<?php echo $apartment['id']; ?>">
                                            <button type="submit" name="toggle_availability" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas <?php echo $apartment['available'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-1"></i>
                                                <?php echo $apartment['available'] ? 'Mark as Unavailable' : 'Mark as Available'; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Cannot change availability while there are active reservations">
                                            <i class="fas fa-lock me-1"></i>Status Locked
                                        </button>
                                    <?php endif; ?>
                                    <a href="delete_apartment.php?id=<?php echo $apartment['id']; ?>" class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this property? This action cannot be undone.')">
                                        <i class="fas fa-trash-alt me-1"></i>Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-building mb-3" style="font-size: 4rem; color: #ccc;"></i>
                            <h4>No Properties Found</h4>
                            <p class="mb-4">You haven't added any rental properties yet.</p>
                            <a href="add_apartment.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Your First Property
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Snackbar for notifications -->
    <div id="snackbar"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showSnackbar(message, type) {
            var snackbar = document.getElementById("snackbar");
            snackbar.textContent = message;
            snackbar.className = "show " + type;
            setTimeout(function(){ 
                snackbar.className = snackbar.className.replace("show", ""); 
            }, 3000);
        }
        
        // Check for success or error messages and show snackbar
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($success_message)): ?>
                showSnackbar("<?php echo $success_message; ?>", "success");
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                showSnackbar("<?php echo $error_message; ?>", "error");
            <?php endif; ?>
        });
    </script>
</body>
</html> 