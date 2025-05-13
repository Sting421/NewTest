<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db_connection.php';

// Fetch all apartments with reservation count
$sql = "SELECT a.*, 
        (SELECT COUNT(*) FROM reservations r WHERE r.apartment_id = a.id) as reservation_count 
        FROM apartments a 
        ORDER BY a.id DESC";
$result = $conn->query($sql);

if ($result === false) {
    $error_message = "Error fetching apartments: " . $conn->error;
} else {
    $apartments = $result->fetch_all(MYSQLI_ASSOC);
}

// Calculate totals
$total_apartments = count($apartments);
$total_value = array_sum(array_column($apartments, 'price'));
$average_price = $total_apartments > 0 ? $total_value / $total_apartments : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Apartments</title>
    <style>
        .stats-container {
            margin-top: 1rem;
        }

        .stats-card {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .stats-info {
            flex-grow: 1;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.875rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: #2c3e50;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stats-icon.blue { background-color: #0d6efd; }
        .stats-icon.green { background-color: #198754; }
        .stats-icon.cyan { background-color: #0dcaf0; }

        .stats-icon i {
            font-size: 1.25rem;
        }

        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
        }

        .table-responsive {
            width: 100%;
        }

        .table {
            margin-bottom: 0;
            width: 100%;
        }

        .table th {
            border-top: none;
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .table td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
        }

        .apartment-icon {
            width: 32px;
            height: 32px;
            background: #e9ecef;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }

        .apartment-name-container {
            display: flex;
            align-items: center;
            flex-direction: row;
            gap: 0.75rem;
        }

        .price-badge {
            background: #198754;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .reservation-badge {
            background: #ffbd24;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .btn-add-new {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.2);
        }

        .btn-add-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.3);
            background: linear-gradient(135deg, #3f37c9 0%, #4361ee 100%);
            color: white;
        }

        .btn-add-new i {
            font-size: 1rem;
        }

        .btn-outline-secondary, .btn-outline-danger {
            color: #000;
            border-color: #dee2e6;
            background: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover, .btn-outline-danger:hover {
            background: #f8f9fa;
            color: #000;
            border-color: #dee2e6;
        }

        .empty-state {
            padding: 2rem;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 2rem;
            display: block;
            margin-bottom: 1rem;
        }
      
    </style>
</head>
<body>
    <div class="stats-container mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-info">
                        <span class="stats-label">Total Rooms</span>
                        <h3 class="stats-value"><?php echo $total_apartments; ?></h3>
                    </div>
                    <div class="stats-icon blue">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-info">
                        <span class="stats-label">Total Value</span>
                        <h3 class="stats-value">₱<?php echo number_format($total_value, 2); ?></h3>
                    </div>
                    <div class="stats-icon green">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-info">
                        <span class="stats-label">Average Price</span>
                        <h3 class="stats-value">₱<?php echo number_format($average_price, 2); ?></h3>
                    </div>
                    <div class="stats-icon cyan">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Manage Rooms</h5>
                <a href="add_apartment.php" class="btn-add-new">
                    <i class="fas fa-plus"></i>
                    Add New Rooms
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Rooms</th>
                            <th>Location</th>
                            <th>Price</th>
                            <th>Reservations</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($apartments) && count($apartments) > 0): ?>
                            <?php foreach ($apartments as $apartment): ?>
                                <tr>
                                    <td style= "align-items: center;"><?php echo $apartment['id']; ?></td>
                                    <td>
                                        <div class="apartment-name-container">
                                            <div class="apartment-icon">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <?php echo htmlspecialchars($apartment['name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-map-marker-alt text-danger me-2 "></i>
                                            <?php echo htmlspecialchars($apartment['location']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="price-badge">₱<?php echo number_format($apartment['price'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="reservation-badge">
                                            <?php echo $apartment['reservation_count']; ?> reservations
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="edit_apartment.php?id=<?php echo $apartment['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_apartment.php?id=<?php echo $apartment['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this apartment?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-building mb-3"></i>
                                        <p>No rooms found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
