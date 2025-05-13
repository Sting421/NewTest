<?php
session_start();
require_once('../includes/db_connection.php');

// If user is already logged in and is a landlord, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'landlord') {
    header("Location: dashboard.php");
    exit();
}

$error_message = "";
$success_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize it
    $name = trim($_POST['name']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate inputs
    if (empty($name) || empty($lastname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long";
    } else {
        // Check if email already exists
        $check_email_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_email_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Email already exists. Please use a different email or login.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new landlord
            $insert_sql = "INSERT INTO users (name, lastname, email, password, role) VALUES (?, ?, ?, ?, 'landlord')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $name, $lastname, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $success_message = "Registration successful! You can now log in.";
                
                // Optionally redirect to login page after successful registration
                header("Location: login.php?success=" . urlencode($success_message));
                exit();
            } else {
                $error_message = "Error during registration: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Signup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --accent-color: #7c3aed;
            --landlord-color: #2ecc71;
            --landlord-hover: #27ae60;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #334155;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
            text-align: center;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-left:20%;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background-color: #fff;
            padding: 30px 30px 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }
        
        .card-header::after {
            content: "";
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--landlord-color), var(--accent-color));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }
        
        .card:hover .card-header::after {
            transform: scaleX(1);
        }
        
        .card-body {
            padding: 30px;
            background-color: #fff;
        }
        
        h2 {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 8px;
            font-size: 1.75rem;
        }
        
        .subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo i {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 10px;
        }
        
        .role-badge {
            background: linear-gradient(90deg, var(--landlord-color), var(--accent-color));
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #475569;
            font-size: 0.95rem;
            text-align: left;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background-color: #f8fafc;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            background-color: #fff;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 10;
        }
        
        .input-with-icon {
            padding-left: 45px;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--landlord-color), var(--accent-color));
            border-color: linear-gradient(90deg, var(--landlord-color), var(--accent-color));
            font-weight: 600;
            letter-spacing: 0.3px;
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.12);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.2);
            transform: translateY(-2px);
        }
        
        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 1.25rem;
            padding: 12px 16px;
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 1rem;
        }
        
        .success-message {
            color: #16a34a;
            font-size: 0.875rem;
            margin-top: 1.25rem;
            padding: 12px 16px;
            background-color: #dcfce7;
            border-left: 4px solid #16a34a;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }
        
        .success-message i {
            margin-right: 10px;
            font-size: 1rem;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
            color: #64748b;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        
        .login-link a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .icon-feature {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: #475569;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .icon-feature:hover {
            background-color: #f1f5f9;
            transform: translateX(5px);
        }
        
        .icon-feature i {
            background: linear-gradient(135deg, var(--landlord-color), var(--accent-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 12px;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container position-relative">
        <a href="../index.html" class="btn btn-outline-secondary position-absolute top-0 start-0 mt-3">
            <i class="fas fa-home me-2"></i>Back to Home
        </a>
        
        <div class="logo-container">
            <div class="logo">
                <i class="fas fa-building"></i>
                <span>Dorm Hub</span>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2>Create Account</h2>
                        <span class="role-badge">Landlord</span>
                        <p class="subtitle">Join Dorm Hub to start managing your rental properties</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="success-message">
                                <i class="fas fa-check-circle"></i>
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">First Name</label>
                                    <div class="input-group">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" class="form-control input-with-icon" id="name" name="name" placeholder="John" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastname" class="form-label">Last Name</label>
                                    <div class="input-group">
                                        <i class="fas fa-user input-icon"></i>
                                        <input type="text" class="form-control input-with-icon" id="lastname" name="lastname" placeholder="Doe" value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" class="form-control input-with-icon" id="email" name="email" placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="••••••••" required>
                                </div>
                                <div class="form-text">Password must be at least 6 characters long</div>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" class="form-control input-with-icon" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <p class="fw-medium mb-2">As a landlord, you will be able to:</p>
                                <div class="icon-feature">
                                    <i class="fas fa-building"></i>
                                    <span>Manage multiple rental properties</span>
                                </div>
                                <div class="icon-feature">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Handle tenant reservations and requests</span>
                                </div>
                                <div class="icon-feature">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Track property performance and statistics</span>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Create Landlord Account</button>
                            </div>
                        </form>
                        
                        <div class="login-link">
                            <p>Already have a landlord account? <a href="login.php">Log In</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
