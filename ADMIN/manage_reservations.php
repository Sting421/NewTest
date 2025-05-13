<?php
ob_start(); // Start output buffering
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db_connection.php';

// Handle reservation status update
if (isset($_POST['update_status'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $new_status = $_POST['new_status'];
    
    $update_sql = "UPDATE reservations SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $new_status, $reservation_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Reservation status updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating reservation status: " . $conn->error;
    }
    $stmt->close();
    
    // Use JavaScript for redirect
    echo "<script>window.location.href='admin_dashboard.php?page=manage_reservations';</script>";
    exit();
}

// Handle reservation deletion
if (isset($_GET['delete'])) {
    $reservation_id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $reservation_id);
    
    if ($stmt->execute()) {
        $success_message = "Reservation deleted successfully.";
    } else {
        $error_message = "Error deleting reservation: " . $conn->error;
    }
    $stmt->close();
}

// Build the SQL query with filters
$sql = "SELECT r.*, 
               u.name as user_name, u.lastname as user_lastname, u.email as user_email,
               a.name as apartment_name, a.location as apartment_location, a.price
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN apartments a ON r.apartment_id = a.id
        WHERE 1=1";

$params = array();
$types = "";

// Status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $sql .= " AND r.status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

// Date range filter
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $sql .= " AND DATE(r.reservation_date) >= ?";
    $params[] = $_GET['date_from'];
    $types .= "s";
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $sql .= " AND DATE(r.reservation_date) <= ?";
    $params[] = $_GET['date_to'];
    $types .= "s";
}

// Search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $sql .= " AND (u.name LIKE ? OR u.lastname LIKE ? OR u.email LIKE ? OR a.name LIKE ?)";
    $params = array_merge($params, array($search, $search, $search, $search));
    $types .= "ssss";
}

$sql .= " ORDER BY r.reservation_date DESC";

// Prepare and execute the query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result === false) {
    $error_message = "Error fetching reservations: " . $conn->error;
} else {
    $reservations = $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get appropriate status badge class
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'reserved':
            return 'status-reserved';
        case 'canceled':
            return 'status-canceled';
        default:
            return 'status-default';
    }
}

// Get unique statuses for filter dropdown
$status_query = "SELECT DISTINCT status FROM reservations ORDER BY status";
$statuses = $conn->query($status_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .reservations-management {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }

        .filters-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            color: #666;
            font-size: 0.9rem;
        }

        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .filter-input:focus {
            border-color: #4361ee;
            outline: none;
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-filter {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-apply {
            background: #4361ee;
            color: white;
        }

        .btn-reset {
            background: #e0e0e0;
            color: #333;
        }

        .btn-filter:hover {
            transform: translateY(-1px);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-reserved {
            background-color: #4CAF50;
            color: white;
        }

        .status-canceled {
            background-color: #f44336;
            color: white;
        }

        .status-default {
            background-color: #9e9e9e;
            color: white;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }

        .reservations-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reservations-table th,
        .reservations-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .reservations-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .reservations-table tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-status,
        .btn-delete {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-status {
            background-color: #4361ee;
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-update-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 15px;
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn-update-status:hover {
            background-color: #3247b8;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }

        .btn-update-status:active {
            transform: translateY(0);
            box-shadow: 0 2px 3px rgba(0,0,0,0.1);
        }

        .btn-update-status i {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="reservations-management">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <form action="" method="GET" id="filterForm">
                <input type="hidden" name="page" value="manage_reservations">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="filter-input">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status['status']); ?>"
                                    <?php echo (isset($_GET['status']) && $_GET['status'] === $status['status']) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($status['status'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date_from">From Date</label>
                        <input type="date" id="date_from" name="date_from" class="filter-input"
                               value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                    </div>

                    <div class="filter-group">
                        <label for="date_to">To Date</label>
                        <input type="date" id="date_to" name="date_to" class="filter-input"
                               value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                    </div>

                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" class="filter-input" 
                               placeholder="Search by name, email, or apartment"
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="button" class="btn-filter btn-reset" onclick="resetFilters()">Reset</button>
                    <button type="submit" class="btn-filter btn-apply">Apply Filters</button>
                </div>
            </form>
        </div>

        <?php if (isset($reservations) && count($reservations) > 0): ?>
            <div class="table-responsive">
                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Apartment</th>
                            <th>Location</th>
                            <th>Price</th>
                            <th>Reservation Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td>
                                    <div>
                                        <?php echo htmlspecialchars($reservation['user_name'] . ' ' . $reservation['user_lastname']); ?>
                                        <div style="font-size: 0.8em; color: #666;">
                                            <?php echo htmlspecialchars($reservation['user_email']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($reservation['apartment_name']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['apartment_location']); ?></td>
                                <td>₱<?php echo number_format($reservation['price'], 2); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($reservation['reservation_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($reservation['status']); ?>">
                                        <?php echo htmlspecialchars($reservation['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-status" onclick="openStatusModal(<?php echo $reservation['id']; ?>)">
                                            <i class="fas fa-exchange-alt"></i> Status
                                        </button>
                                        <a href="?page=manage_reservations&delete=<?php echo $reservation['id']; ?>" 
                                           class="btn-delete"
                                           onclick="return confirm('Are you sure you want to delete this reservation?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <p>No reservations found</p>
                <p class="secondary-text">No reservations match your filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Update Reservation Status</h2>
            <form action="?page=manage_reservations" method="POST">
                <input type="hidden" id="reservation_id" name="reservation_id">
                <div class="mb-3">
                    <label for="new_status" class="form-label">New Status</label>
                    <select name="new_status" id="new_status" class="form-control" required>
                        <option value="">--Select--</option>
                        <option value="reserved">Reserved</option>
                        <option value="canceled">Canceled</option>
                    </select>
                </div>
                <div class="text-end">
                    <button type="submit" name="update_status" class="btn-update-status">
                        <i class="fas fa-sync-alt"></i> 
                        <span>Update Reservation Status</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            display: block;
        }

        .text-end {
            text-align: right;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }
    </style>

    <script>
        // Reset filters
        function resetFilters() {
            document.getElementById('status').value = '';
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            document.getElementById('search').value = '';
            document.getElementById('filterForm').submit();
        }

        // Status modal functionality
        var modal = document.getElementById("statusModal");
        var span = document.getElementsByClassName("close")[0];

        function openStatusModal(reservationId) {
            document.getElementById("reservation_id").value = reservationId;
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
