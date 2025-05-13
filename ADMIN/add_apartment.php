3<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $price = trim($_POST['price']);

    if (!empty($name) && !empty($location) && !empty($price)) {
        $sql = "INSERT INTO apartments (name, location, price) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        // Corrected bind_param type definition
        $stmt->bind_param("sss", $name, $location, $price);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Apartment added successfully!";
            header("Location: manage_apartments.php");
            exit();
        } else {
            $error_message = "Error adding apartment: " . $mysqli->error;
        }
        $stmt->close();
    } else {
        $error_message = "Please fill in all fields.";
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Apartment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .form-label {
            font-weight: 500;
        }
        .input-group-text {
            background-color: transparent;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="h3 mb-0">Add New Apartment</h1>
                            <a href="admin_dashboard.php?page=manage_apartments" class="btn btn-outline-secondary btn-sm">
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

                        <form action="" method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">Apartment Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    <input type="text" class="form-control" id="name" name="name" required
                                           placeholder="Enter apartment name">
                                    <div class="invalid-feedback">
                                        Please provide an apartment name.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                    <input type="text" class="form-control" id="location" name="location" required
                                           placeholder="Enter location">
                                    <div class="invalid-feedback">
                                        Please provide a location.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                    <input type="text" class="form-control" id="price" name="price" required
                                           placeholder="Enter price">
                                    <div class="invalid-feedback">
                                        Please provide a price.
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Add Apartment
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
    
    <!-- Form validation script -->
    <script>
        (() => {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
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
