<?php
session_start();
require_once('../includes/db_connection.php');

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'landlord') {
    header("Location: login.php");
    exit();
}

// Fetch user ID
$user_id = $_SESSION['user_id'];

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_apartments.php?error=" . urlencode("No property ID specified"));
    exit();
}

$apartment_id = $_GET['id'];

// Check if apartment belongs to this landlord
$check_sql = "SELECT * FROM apartments WHERE id = ? AND owner_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $apartment_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_apartments.php?error=" . urlencode("You don't have permission to delete this property"));
    exit();
}

// Get apartment data
$apartment = $result->fetch_assoc();

// Check if this apartment has reservations
$check_res_sql = "SELECT COUNT(*) as count FROM reservations WHERE apartment_id = ? AND status = 'reserved'";
$check_res_stmt = $conn->prepare($check_res_sql);
$check_res_stmt->bind_param("i", $apartment_id);
$check_res_stmt->execute();
$reservation_count = $check_res_stmt->get_result()->fetch_assoc()['count'];

// Handle form submission to delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete'])) {
    if ($reservation_count > 0) {
        header("Location: manage_apartments.php?error=" . urlencode("Cannot delete property with active reservations"));
        exit();
    }
    
    // Delete apartment
    $delete_sql = "DELETE FROM apartments WHERE id = ? AND owner_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $apartment_id, $user_id);
    
    if ($delete_stmt->execute()) {
        header("Location: manage_apartments.php?success=" . urlencode("Property deleted successfully"));
        exit();
    } else {
        header("Location: manage_apartments.php?error=" . urlencode("Error deleting property: " . $conn->error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Property - Landlord Dashboard</title>
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
        
        /* Delete confirmation */
        .delete-icon {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 1rem;
        }
        
        .property-info {
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .property-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .property-location, .property-price {
            color: #6c757d;
            margin-bottom: 5px;
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
                        <span class="d-none d-sm-inline"><?php echo htmlspecialchars(ucwords(strtolower($_SESSION['name'] . " " . $_SESSION['lastname']))); ?></span>
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
        <div class="row">
            <div class="col-lg-6 mx-auto">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if ($reservation_count > 0): ?>
                            <i class="fas fa-exclamation-triangle delete-icon text-warning"></i>
                            <h2>Cannot Delete Property</h2>
                            <p class="mb-4">This property has active reservations and cannot be deleted.</p>
                            
                            <div class="property-info text-start">
                                <div class="property-name"><?php echo htmlspecialchars($apartment['name']); ?></div>
                                <div class="property-location"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($apartment['location']); ?></div>
                                <div class="property-price"><i class="fas fa-money-bill-wave me-2"></i>₱<?php echo number_format($apartment['price'], 2); ?>/month</div>
                                <div class="mt-2"><i class="fas fa-calendar-check me-2"></i><strong><?php echo $reservation_count; ?></strong> active reservations</div>
                            </div>
                            
                            <p>You must cancel all active reservations before deleting this property.</p>
                            
                            <div class="mt-4">
                                <a href="manage_apartments.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Go Back to Properties
                                </a>
                            </div>
                        <?php else: ?>
                            <i class="fas fa-trash-alt delete-icon"></i>
                            <h2>Delete Property?</h2>
                            <p class="mb-4">Are you sure you want to delete this property? This action cannot be undone.</p>
                            
                            <div class="property-info text-start">
                                <div class="property-name"><?php echo htmlspecialchars($apartment['name']); ?></div>
                                <div class="property-location"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($apartment['location']); ?></div>
                                <div class="property-price"><i class="fas fa-money-bill-wave me-2"></i>₱<?php echo number_format($apartment['price'], 2); ?>/month</div>
                                <div class="mt-2"><span class="badge <?php echo $apartment['available'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $apartment['available'] ? 'Available' : 'Not Available'; ?>
                                </span></div>
                            </div>
                            
                            <form method="POST" action="" class="mt-4">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                    <a href="manage_apartments.php" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                    <button type="submit" name="confirm_delete" class="btn btn-danger">
                                        <i class="fas fa-trash-alt me-2"></i>Confirm Delete
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 