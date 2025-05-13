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

// Check if apartment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_apartments.php?error=No apartment specified");
    exit();
}

$apartment_id = $_GET['id'];

// Get apartment details
$apartment_sql = "SELECT a.*, 
                 (SELECT COUNT(*) FROM reservations r WHERE r.apartment_id = a.id AND r.status = 'reserved') as reservation_count
                 FROM apartments a 
                 WHERE a.id = ? AND a.owner_id = ?";
$apartment_stmt = $conn->prepare($apartment_sql);
$apartment_stmt->bind_param("ii", $apartment_id, $user_id);
$apartment_stmt->execute();
$apartment_result = $apartment_stmt->get_result();

// Check if apartment exists and belongs to current landlord
if ($apartment_result->num_rows == 0) {
    header("Location: manage_apartments.php?error=Apartment not found or access denied");
    exit();
}

$apartment = $apartment_result->fetch_assoc();

// Get tenant information if apartment is occupied
$tenant = null;
if (!$apartment['available'] && $apartment['tenant_id']) {
    $tenant_sql = "SELECT name, lastname, email, phone, created_at FROM users WHERE id = ?";
    $tenant_stmt = $conn->prepare($tenant_sql);
    $tenant_stmt->bind_param("i", $apartment['tenant_id']);
    $tenant_stmt->execute();
    $tenant = $tenant_stmt->get_result()->fetch_assoc();
}

// Get active reservations for this apartment
$reservations_sql = "SELECT r.*, u.name, u.lastname, u.email, u.phone 
                    FROM reservations r 
                    JOIN users u ON r.user_id = u.id 
                    WHERE r.apartment_id = ? AND r.status = 'reserved'
                    ORDER BY r.start_date";
$reservations_stmt = $conn->prepare($reservations_sql);
$reservations_stmt->bind_param("i", $apartment_id);
$reservations_stmt->execute();
$reservations_result = $reservations_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($apartment['name']); ?> - Property Details</title>
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
        
        /* Property Styles */
        .property-header {
            position: relative;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .property-price {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .property-badge {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .property-detail-section {
            margin-top: 30px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-icon {
            font-size: 1.2rem;
            color: var(--primary-color);
            width: 40px;
            text-align: center;
        }
        
        .detail-content {
            flex: 1;
        }
        
        .detail-label {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #6c757d;
        }
        
        .reservation-card {
            border-left: 4px solid var(--primary-color);
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
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="mb-0"><?php echo htmlspecialchars($apartment['name']); ?></h2>
                    <div>
                        <a href="edit_apartment.php?id=<?php echo $apartment_id; ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-1"></i>Edit Property
                        </a>
                        <a href="manage_apartments.php" class="btn btn-outline-secondary">
                            <i class="fas fa-chevron-left me-1"></i>Back to Properties
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Property Header with Image -->
                <div class="card mb-4">
                    <?php if (!empty($apartment['image_url'])): ?>
                    <div class="property-image">
                        <img src="<?php echo htmlspecialchars($apartment['image_url']); ?>" alt="<?php echo htmlspecialchars($apartment['name']); ?>" class="img-fluid w-100" style="max-height: 400px; object-fit: cover;">
                    </div>
                    <?php endif; ?>
                    <div class="card-body property-header">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="mb-0"><?php echo htmlspecialchars($apartment['name']); ?></h4>
                                <p class="text-muted mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($apartment['location']); ?></p>
                            </div>
                            <div class="property-price text-end">
                                $<?php echo number_format($apartment['price'], 2); ?><span class="text-muted fs-6">/month</span>
                            </div>
                        </div>
                        
                        <?php if ($apartment['available']): ?>
                            <span class="badge bg-success property-badge">Available</span>
                        <?php else: ?>
                            <span class="badge bg-danger property-badge">Not Available</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Property Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Property Details</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($apartment['description'])): ?>
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">Description</h6>
                                <p><?php echo nl2br(htmlspecialchars($apartment['description'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Features</h6>
                                <div class="detail-item">
                                    <div class="detail-icon"><i class="fas fa-bed"></i></div>
                                    <div class="detail-content">
                                        <strong>Bedrooms:</strong> <?php echo $apartment['bedrooms']; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon"><i class="fas fa-bath"></i></div>
                                    <div class="detail-content">
                                        <strong>Bathrooms:</strong> <?php echo $apartment['bathrooms']; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon"><i class="fas fa-couch"></i></div>
                                    <div class="detail-content">
                                        <strong>Furnished:</strong> <?php echo $apartment['furnished'] ? 'Yes' : 'No'; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Amenities</h6>
                                <div class="detail-item">
                                    <div class="detail-icon"><i class="fas fa-paw"></i></div>
                                    <div class="detail-content">
                                        <strong>Pets Allowed:</strong> <?php echo $apartment['pets_allowed'] ? 'Yes' : 'No'; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon"><i class="fas fa-car"></i></div>
                                    <div class="detail-content">
                                        <strong>Parking:</strong> <?php echo $apartment['parking'] ? 'Yes' : 'No'; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-icon"><i class="fas fa-wifi"></i></div>
                                    <div class="detail-content">
                                        <strong>Internet:</strong> <?php echo $apartment['internet'] ? 'Yes' : 'No'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Current Reservations</h5>
                        <span class="badge bg-primary"><?php echo $apartment['reservation_count']; ?></span>
                    </div>
                    <div class="card-body">
                        <?php if ($reservations_result->num_rows > 0): ?>
                            <?php while($reservation = $reservations_result->fetch_assoc()): ?>
                                <div class="card reservation-card mb-3">
                                    <div class="card-body">
                                        <h6><?php echo htmlspecialchars($reservation['name'] . ' ' . $reservation['lastname']); ?></h6>
                                        <div class="small text-muted mb-2">
                                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($reservation['email']); ?><br>
                                            <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($reservation['phone']); ?>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div><i class="fas fa-calendar-check me-1"></i> <?php echo date('M j, Y', strtotime($reservation['start_date'])); ?></div>
                                                <div><i class="fas fa-calendar-times me-1"></i> <?php echo date('M j, Y', strtotime($reservation['end_date'])); ?></div>
                                            </div>
                                            <a href="manage_reservations.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-day mb-3" style="font-size: 2.5rem; color: #ccc;"></i>
                                <p>No active reservations for this property.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Property Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($apartment['reservation_count'] == 0): ?>
                                <form method="POST" action="manage_apartments.php">
                                    <input type="hidden" name="apartment_id" value="<?php echo $apartment['id']; ?>">
                                    <button type="submit" name="toggle_availability" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="fas <?php echo $apartment['available'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-1"></i>
                                        <?php echo $apartment['available'] ? 'Mark as Unavailable' : 'Mark as Available'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary w-100 mb-2" disabled title="Cannot change availability while there are active reservations">
                                    <i class="fas fa-lock me-1"></i>Status Locked (Has Reservations)
                                </button>
                            <?php endif; ?>
                            <a href="delete_apartment.php?id=<?php echo $apartment['id']; ?>" class="btn btn-outline-danger w-100"
                               onclick="return confirm('Are you sure you want to delete this property? This action cannot be undone.')">
                                <i class="fas fa-trash-alt me-1"></i>Delete Property
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if (!$apartment['available'] && $tenant): ?>
                <!-- Current Tenant Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Current Tenant</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="user-avatar me-3" style="width: 50px; height: 50px; font-size: 1.2rem;">
                                <?php echo substr($tenant['name'], 0, 1); ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($tenant['name'] . ' ' . $tenant['lastname']); ?></h6>
                                <small class="text-muted">Tenant since <?php echo date('M j, Y', strtotime($tenant['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Email</div>
                                <div class="detail-value"><?php echo htmlspecialchars($tenant['email']); ?></div>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Phone</div>
                                <div class="detail-value"><?php echo htmlspecialchars($tenant['phone']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 