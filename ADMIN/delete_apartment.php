<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db_connection.php';

// Initialize variables
$message = '';
$message_type = '';

// Check if apartment ID is provided in the URL
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Apartment ID is required.";
    header("Location: manage_apartments.php");
    exit;
}

$apartmentId = intval($_GET['id']);

// Fetch the apartment's current details before deletion
try {
    $sql = "SELECT id, name, location, price FROM apartments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $apartmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Apartment not found.";
        header("Location: manage_apartments.php");
        exit;
    }

    $apartment = $result->fetch_assoc();
    $stmt->close();

    // Handle the form submission for deleting apartment
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check for existing reservations
        $check_reservations = $conn->prepare("SELECT COUNT(*) as reservation_count FROM reservations WHERE apartment_id = ?");
        $check_reservations->bind_param("i", $apartmentId);
        $check_reservations->execute();
        $reservation_result = $check_reservations->get_result()->fetch_assoc();
        
        if ($reservation_result['reservation_count'] > 0) {
            $message = "Cannot delete apartment. There are existing reservations.";
            $message_type = 'danger';
        } else {
            $deleteSql = "DELETE FROM apartments WHERE id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param("i", $apartmentId);

            if ($deleteStmt->execute()) {
                $_SESSION['success'] = "Apartment deleted successfully.";
                header("Location: admin_dashboard.php?page=manage_apartments");
                exit;
            } else {
                $message = "Error deleting apartment: " . $conn->error;
                $message_type = 'danger';
            }
            $deleteStmt->close();
        }
    }
} catch (Exception $e) {
    $message = "An error occurred: " . $e->getMessage();
    $message_type = 'danger';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Apartment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .delete-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .delete-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .delete-title {
            color: #333;
            margin-bottom: 15px;
        }

        .delete-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .delete-details p {
            margin: 5px 0;
            color: #666;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 0 10px;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: 2px solid #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: 2px solid #6c757d;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="delete-container">
        <i class="fas fa-trash-alt delete-icon"></i>
        <h2 class="delete-title">Delete Apartment</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="delete-details">
            <p><strong>Apartment Name:</strong> <?php echo htmlspecialchars($apartment['name']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($apartment['location']); ?></p>
            <p><strong>Price:</strong> ₱<?php echo number_format($apartment['price'], 2); ?></p>
        </div>

        <p>Are you sure you want to permanently delete this apartment?</p>

        <form action="" method="POST">
            <a href="admin_dashboard.php?page=manage_apartments" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Confirm Delete
            </button>
        </form>
    </div>
</body>
</html>
