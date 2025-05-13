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
    header('location: admin_dashboard.php?page=manage_users');
    exit();
}

// Check if user ID is provided in the URL
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "User ID is required.";
    header('location: admin_dashboard.php?page=manage_users');
    exit();
}

$userId = intval($_GET['id']);

// Fetch the user's current details
$sql = "SELECT id, name, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "User not found.";
    header('location: admin_dashboard.php?page=manage_users');
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Handle the form submission for updating user details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (!empty($name) && !empty($email)) {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Please enter a valid email address.";
        } else {
            $updateSql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssi", $name, $email, $userId);

            if ($updateStmt->execute()) {
                $_SESSION['success'] = "User updated successfully.";
                header('location: admin_dashboard.php?page=manage_users');
                exit();
            } else {
                $_SESSION['error'] = "Error updating user: " . $conn->error;
            }
            $updateStmt->close();
        }
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
    <title>Edit User</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .edit-user-form {
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
        
        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="edit-user-form">
            <h2 class="form-title">
                <i class="fas fa-user-edit me-2"></i>Edit User
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
                           value="<?php echo htmlspecialchars($user['name']); ?>" 
                           placeholder="Name" required>
                    <label for="name">Name</label>
                    <div class="invalid-feedback">Please enter the user's name.</div>
                </div>

                <div class="form-floating mb-4">
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           placeholder="Email" required>
                    <label for="email">Email</label>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update User
                    </button>
                    <a href="admin_dashboard.php?page=manage_users" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Manage Users
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
