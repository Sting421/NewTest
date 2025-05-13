<?php
session_start();

$host = 'localhost'; 
$dbname = 'boarding_house_system'; 
$username = 'root'; 
$password = ''; 

$mysqli = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if reservation ID is provided in the URL
if (!isset($_GET['id'])) {
    header("Location: manage_reservations.php");
    exit();
}

$reservationId = intval($_GET['id']);

// Fetch the reservation's current details with user and apartment information
$sql = "SELECT r.*, 
               u.name as user_name, u.email as user_email,
               a.name as apartment_name, a.location as apartment_location, a.price
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN apartments a ON r.apartment_id = a.id
        WHERE r.id = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Reservation not found.";
    header("Location: manage_reservations.php");
    exit();
}

$reservation = $result->fetch_assoc();
$stmt->close();

// Handle the form submission for updating reservation status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $validStatuses = ['pending', 'approved', 'rejected', 'completed'];

    if (in_array($status, $validStatuses)) {
        // Update reservation status
        $updateSql = "UPDATE reservations SET status = ? WHERE id = ?";
        $updateStmt = $mysqli->prepare($updateSql);
        $updateStmt->bind_param("si", $status, $reservationId);

        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Reservation status updated successfully.";
            header("Location: manage_reservations.php");
            exit();
        } else {
            $error_message = "Error updating reservation status: " . $mysqli->error;
        }
        $updateStmt->close();
    } else {
        $error_message = "Invalid status selected.";
    }
}

$mysqli->close();

// Function to get appropriate status badge class
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'approved':
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
        case 'completed':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Reservation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .detail-label {
            font-weight: 500;
            color: #6c757d;
        }
        .reservation-info {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0">Edit Reservation #<?php echo $reservationId; ?></h1>
                            <a href="manage_reservations.php" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <div class="reservation-info p-3 mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <p class="detail-label mb-1">User Information</p>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-person text-primary me-2"></i>
                                        <?php echo htmlspecialchars($reservation['user_name']); ?>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-envelope me-2"></i>
                                        <?php echo htmlspecialchars($reservation['user_email']); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p class="detail-label mb-1">Apartment Details</p>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-building text-primary me-2"></i>
                                        <?php echo htmlspecialchars($reservation['apartment_name']); ?>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        <?php echo htmlspecialchars($reservation['apartment_location']); ?>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-currency-dollar me-2"></i>
                                        <?php echo htmlspecialchars($reservation['price']); ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <p class="detail-label mb-1">Reservation Date</p>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-calendar-event text-primary me-2"></i>
                                        <?php echo date('F d, Y', strtotime($reservation['reservation_date'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form action="" method="POST">
                            <div class="mb-4">
                                <label for="status" class="form-label">Update Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pending" <?php if ($reservation['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                                    <option value="approved" <?php if ($reservation['status'] === 'approved') echo 'selected'; ?>>Approved</option>
                                    <option value="rejected" <?php if ($reservation['status'] === 'rejected') echo 'selected'; ?>>Rejected</option>
                                    <option value="completed" <?php if ($reservation['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                                </select>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="me-2">Current Status:</span>
                                    <span class="badge <?php echo getStatusBadgeClass($reservation['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($reservation['status'])); ?>
                                    </span>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2-circle me-2"></i>Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
