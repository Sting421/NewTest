<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'boarding_house_system';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('location: admin_dashboard.php?page=manage_apartments');
    exit();
}

// Check if apartment ID is provided in the URL
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Apartment ID is required.";
    header('location: admin_dashboard.php?page=manage_apartments');
    exit();
}

$apartmentId = intval($_GET['id']);

// Fetch the apartment's current details
$sql = "SELECT id, name, location, price FROM apartments WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $apartmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Apartment not found.";
    header('location: admin_dashboard.php?page=manage_apartments');
    exit();
}

$apartment = $result->fetch_assoc();
$stmt->close();

// Handle the form submission for updating apartment details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $price = trim($_POST['price']);

    if (!empty($name) && !empty($location) && !empty($price)) {
        $updateSql = "UPDATE apartments SET name = ?, location = ?, price = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sssi", $name, $location, $price, $apartmentId);

        if ($updateStmt->execute()) {
            $_SESSION['success'] = "Apartment updated successfully.";
            header('location: admin_dashboard.php?page=manage_apartments');
            exit();
        } else {
            $_SESSION['error'] = "Error updating apartment: " . $conn->error;
        }
        $updateStmt->close();
    } else {
        $_SESSION['error'] = "Please fill in all fields.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Apartment</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .edit-apartment-form {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .form-title {
            color: #4361ee;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .btn-primary {
            background-color: #4361ee;
            border-color: #4361ee;
            padding: 0.5rem 2rem;
        }
        
        .btn-primary:hover {
            background-color: #3f37c9;
            border-color: #3f37c9;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #4361ee;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #3f37c9;
        }
        
        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="edit-apartment-form">
            <h2 class="form-title">
                <i class="fas fa-building me-2"></i>Edit Apartment
            </h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="needs-validation" novalidate>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?php echo htmlspecialchars($apartment['name']); ?>" 
                           placeholder="Apartment Name" required>
                    <label for="name">Apartment Name</label>
                    <div class="invalid-feedback">Please enter the apartment name.</div>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?php echo htmlspecialchars($apartment['location']); ?>" 
                           placeholder="Location" required>
                    <label for="location">Location</label>
                    <div class="invalid-feedback">Please enter the location.</div>
                </div>

                <div class="form-floating mb-4">
                    <input type="number" class="form-control" id="price" name="price" 
                           value="<?php echo htmlspecialchars($apartment['price']); ?>" 
                           placeholder="Price" required step="0.01" min="0">
                    <label for="price">Price</label>
                    <div class="invalid-feedback">Please enter a valid price.</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Apartment
                    </button>
                    <a href="admin_dashboard.php?page=manage_apartments" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Manage Apartments
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Form validation script -->
    <script>
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
