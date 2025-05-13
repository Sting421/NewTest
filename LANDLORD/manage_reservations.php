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

// Handle Accept Reservation
if (isset($_POST['accept_reservation'])) {
    $reservation_id = $_POST['reservation_id'];
    $apartment_id = $_POST['apartment_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get user_id from reservation
        $get_user_sql = "SELECT user_id FROM reservations WHERE id = ?";
        $get_user_stmt = $conn->prepare($get_user_sql);
        $get_user_stmt->bind_param("i", $reservation_id);
        $get_user_stmt->execute();
        $user_result = $get_user_stmt->get_result();
        $tenant_id = $user_result->fetch_assoc()['user_id'];
        
        // Update apartment to unavailable and set tenant_id
        $update_apt_sql = "UPDATE apartments SET available = 0, tenant_id = ? WHERE id = ? AND owner_id = ?";
        $update_apt_stmt = $conn->prepare($update_apt_sql);
        $update_apt_stmt->bind_param("iii", $tenant_id, $apartment_id, $user_id);
        $update_apt_stmt->execute();
        
        // Update reservation status to accepted
        $update_res_sql = "UPDATE reservations SET status = 'accepted' WHERE id = ? AND apartment_id = ?";
        $update_res_stmt = $conn->prepare($update_res_sql);
        $update_res_stmt->bind_param("ii", $reservation_id, $apartment_id);
        $update_res_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $success_message = "Reservation accepted successfully!";
        
        // Refresh the page to update data
        header("Location: manage_reservations.php?success=" . urlencode($success_message));
        exit();
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $conn->rollback();
        $error_message = "Error accepting reservation: " . $e->getMessage();
    }
}

// Handle Reject Reservation
if (isset($_POST['reject_reservation'])) {
    $reservation_id = $_POST['reservation_id'];
    
    // Update the reservation status to canceled
    $update_sql = "UPDATE reservations SET status = 'canceled' 
                  WHERE id = ? AND EXISTS (
                      SELECT 1 FROM apartments 
                      WHERE apartments.id = reservations.apartment_id 
                      AND apartments.owner_id = ?
                  )";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $reservation_id, $user_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Reservation rejected successfully!";
        header("Location: manage_reservations.php?success=" . urlencode($success_message));
        exit();
    } else {
        $error_message = "Error rejecting reservation.";
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$apartment_filter = isset($_GET['apartment']) ? $_GET['apartment'] : 'all';

// Get landlord's apartments for filter dropdown
$apartments_sql = "SELECT id, name FROM apartments WHERE owner_id = ? ORDER BY name ASC";
$apartments_stmt = $conn->prepare($apartments_sql);
$apartments_stmt->bind_param("i", $user_id);
$apartments_stmt->execute();
$apartments_result = $apartments_stmt->get_result();
$apartments = [];
while ($row = $apartments_result->fetch_assoc()) {
    $apartments[$row['id']] = $row['name'];
}

// Build query based on filters
$query = "SELECT r.*, a.name as apartment_name, a.price as apartment_price, a.location as apartment_location,
          u.name as tenant_name, u.lastname as tenant_lastname, u.email as tenant_email
          FROM reservations r
          JOIN apartments a ON r.apartment_id = a.id
          JOIN users u ON r.user_id = u.id
          WHERE a.owner_id = ?";

$params = [$user_id];
$types = "i";

if ($status_filter != 'all') {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($apartment_filter != 'all') {
    $query .= " AND a.id = ?";
    $params[] = $apartment_filter;
    $types .= "i";
}

$query .= " ORDER BY r.reservation_date DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reservations_result = $stmt->get_result();

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
    <title>Manage Reservations - Landlord Dashboard</title>
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
        
        /* Filter cards */
        .filter-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .filter-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #495057;
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
                        <a class="nav-link" href="manage_apartments.php">
                            <i class="fas fa-building me-2"></i>My Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_reservations.php">
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
                <h2>Manage Reservations</h2>
                <p class="text-muted">View and manage tenant reservation requests</p>
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
        
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="filter-card">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-5">
                            <label class="filter-label">Filter by Property</label>
                            <select class="form-select" name="apartment" onchange="this.form.submit()">
                                <option value="all" <?php echo ($apartment_filter == 'all') ? 'selected' : ''; ?>>All Properties</option>
                                <?php foreach ($apartments as $id => $name): ?>
                                    <option value="<?php echo $id; ?>" <?php echo ($apartment_filter == $id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="filter-label">Filter by Status</label>
                            <select class="form-select" name="status" onchange="this.form.submit()">
                                <option value="all" <?php echo ($status_filter == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="reserved" <?php echo ($status_filter == 'reserved') ? 'selected' : ''; ?>>Reserved</option>
                                <option value="accepted" <?php echo ($status_filter == 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                                <option value="canceled" <?php echo ($status_filter == 'canceled') ? 'selected' : ''; ?>>Canceled</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="manage_reservations.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-sync-alt me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Reservations Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Reservations</h5>
            </div>
            <div class="card-body">
                <?php if ($reservations_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Tenant</th>
                                    <th>Date</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($reservation = $reservations_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div><?php echo htmlspecialchars($reservation['apartment_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($reservation['apartment_location']); ?></small>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($reservation['tenant_name'] . ' ' . $reservation['tenant_lastname']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($reservation['tenant_email']); ?></small>
                                        </td>
                                        <td><?php echo date('F j, Y', strtotime($reservation['reservation_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $days = isset($reservation['duration']) ? $reservation['duration'] : 30;
                                            if ($days >= 365) {
                                                echo floor($days / 365) . " year(s)";
                                            } else {
                                                echo floor($days / 30) . " month(s)";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php 
                                                if ($reservation['status'] == 'reserved') echo 'bg-warning';
                                                elseif ($reservation['status'] == 'accepted') echo 'bg-success';
                                                else echo 'bg-danger'; 
                                                ?>">
                                                <?php echo ucfirst($reservation['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($reservation['status'] == 'reserved'): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                    <input type="hidden" name="apartment_id" value="<?php echo $reservation['apartment_id']; ?>">
                                                    <button type="submit" name="accept_reservation" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check me-1"></i>Accept
                                                    </button>
                                                    <button type="submit" name="reject_reservation" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Are you sure you want to reject this reservation?')">
                                                        <i class="fas fa-times me-1"></i>Reject
                                                    </button>
                                                </form>
                                            <?php elseif ($reservation['status'] == 'accepted'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" disabled>
                                                    <i class="fas fa-check-circle me-1"></i>Accepted
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">No actions available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-alt mb-3" style="font-size: 3rem; color: #ccc;"></i>
                        <h4>No Reservations Found</h4>
                        <p class="text-muted">No reservations match your current filters.</p>
                    </div>
                <?php endif; ?>
            </div>
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