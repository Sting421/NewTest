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

// Check for success message from signup
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error_message = "All fields are required";
    } else {
        // Check if user exists and is a landlord
        $sql = "SELECT id, name, lastname, password, role FROM users WHERE email = ? AND role = 'landlord'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid password";
            }
        } else {
            $error_message = "No landlord account found with this email";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            margin-left:12%;
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
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
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
        
        .signup-link {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
            color: #64748b;
        }
        
        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        
        .signup-link a:hover {
            color: var(--accent-color);
            text-decoration: underline;
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
        
        .form-check {
            margin-bottom: 1rem;
            text-align: left;
        }
        
        .form-check-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .forgot-password {
            text-align: right;
            font-size: 0.9rem;
        }
        
        .forgot-password a {
            color: #64748b;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .forgot-password a:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container position-relative">
        <a href="../index.html" class="btn btn-outline-secondary position-absolute top-0 start-0 mt-3">
            <i class="fas fa-home me-2"></i>Back to Home
        </a>
        
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="logo-container">
                    <div class="logo">
                        <i class="fas fa-building"></i>
                        <span>Dorm Hub</span>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Welcome Back</h2>
                         <span class="role-badge">Landlord</span>
                        <p class="subtitle">Log in to access your property management dashboard</p>
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
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" class="form-control input-with-icon" id="email" name="email" placeholder="your@email.com" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" class="form-control input-with-icon" id="password" name="password" placeholder="••••••••" required>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                </div>
                                <div class="col-6 forgot-password">
                                    <a href="#">Forgot password?</a>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="signup-link">
                            <p>Don't have a landlord account? <a href="../LANDLORD/signup.php">Create Account</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 