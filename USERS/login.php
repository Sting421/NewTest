<?php
session_start();

// Check if the role parameter is set and valid
$validRoles = ['tenant', 'landlord'];
$role = isset($_GET['role']) && in_array($_GET['role'], $validRoles) ? $_GET['role'] : '';

// Database configuration
$host = 'localhost'; 
$dbname = 'boarding_house_system'; 
$username = 'root'; 
$password = ''; 

$mysqli = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$errorMsg = ""; // Initialize error message variable

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameOrEmail = trim($_POST['usernameOrEmail']);
    $password = trim($_POST['password']);
    $submitRole = isset($_POST['role']) ? $_POST['role'] : '';

    // Prepare the query to check both user credentials and role if a role is specified
    if (!empty($submitRole)) {
        $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE (email = ? OR name = ?) AND role = ?");
        $stmt->bind_param("sss", $usernameOrEmail, $usernameOrEmail, $submitRole);
    } else {
        $stmt = $mysqli->prepare("SELECT id, password, role FROM users WHERE email = ? OR name = ?");
        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
    }

    if ($stmt) {
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            if (!empty($submitRole)) {
                $stmt->bind_result($userId, $hashedPassword);
            } else {
                $stmt->bind_result($userId, $hashedPassword, $userRole);
            }
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $_SESSION['user_id'] = $userId;
                $_SESSION['user'] = $usernameOrEmail;
                if (!empty($submitRole)) {
                    $_SESSION['role'] = $submitRole;
                } else {
                    $_SESSION['role'] = $userRole;
                }
                header("Location: dashboard.php");
                exit;
            } else {
                $errorMsg = "Invalid password.";
            }
        } else {
            if (!empty($submitRole)) {
                $errorMsg = "No " . ucfirst($submitRole) . " account found with that username or email.";
            } else {
                $errorMsg = "No user found with that username or email.";
            }
        }
        $stmt->close();
    } else {
        $errorMsg = "Error preparing statement: " . $mysqli->error;
    }
}
$mysqli->close();

// Set page title based on role
$pageTitle = !empty($role) ? 'Login as ' . ucfirst($role) : 'User Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
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
            --tenant-color: #4285f4;
            --tenant-hover: #3367d6;
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
            margin: 0;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: #fff;
            margin-left: 12%;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background-color: #fff;
            padding: 40px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            width: 100%;
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

        .tenant-theme .card-header::after {
            background: linear-gradient(90deg, var(--tenant-color), var(--accent-color));
        }

        .landlord-theme .card-header::after {
            background: linear-gradient(90deg, var(--landlord-color), var(--accent-color));
        }
        
        .card-body {
            background-color: #fff;
            width: 100%;
            padding: 40px;
        }
        
        h2 {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 8px;
            font-size: 1.75rem;
            text-align: center;
        }
        
        .subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #475569;
            font-size: 0.95rem;
            text-align: left;
            display: block;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background-color: #f8fafc;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            background-color: #fff;
            outline: none;
        }
        
        .tenant-theme .form-control:focus {
            border-color: var(--tenant-color);
            box-shadow: 0 0 0 4px rgba(66, 133, 244, 0.15);
        }
        
        .landlord-theme .form-control:focus {
            border-color: var(--landlord-color);
            box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.15);
        }
        
        .input-group {
            position: relative;
            margin-bottom: 20px;
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
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
            letter-spacing: 0.3px;
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.12);
            color: white;
            width: 100%;
            cursor: pointer;
            font-size: 1rem;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.2);
            transform: translateY(-2px);
        }
        
        .tenant-theme .btn-primary {
            background-color: var(--tenant-color);
            border-color: var(--tenant-color);
            box-shadow: 0 4px 6px rgba(66, 133, 244, 0.12);
        }
        
        .tenant-theme .btn-primary:hover {
            background-color: var(--tenant-hover);
            border-color: var(--tenant-hover);
            box-shadow: 0 6px 12px rgba(66, 133, 244, 0.2);
        }
        
        .landlord-theme .btn-primary {
            background-color: var(--landlord-color);
            border-color: var(--landlord-color);
            box-shadow: 0 4px 6px rgba(46, 204, 113, 0.12);
        }
        
        .landlord-theme .btn-primary:hover {
            background-color: var(--landlord-hover);
            border-color: var(--landlord-hover);
            box-shadow: 0 6px 12px rgba(46, 204, 113, 0.2);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 1.25rem;
            margin-bottom: 1.25rem;
            padding: 12px 16px;
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            border-radius: 6px;
            display: flex;
            align-items: center;
            text-align: left;
        }
        
        .error-message i {
            margin-right: 10px;
            font-size: 1rem;
        }
        
        .success-message {
            color: #16a34a;
            font-size: 0.875rem;
            margin-top: 1.25rem;
            margin-bottom: 1.25rem;
            padding: 12px 16px;
            background-color: #dcfce7;
            border-left: 4px solid #16a34a;
            border-radius: 6px;
            display: flex;
            align-items: center;
            text-align: left;
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
        
        .tenant-theme .signup-link a {
            color: var(--tenant-color);
        }
        
        .tenant-theme .signup-link a:hover {
            color: var(--tenant-hover);
        }
        
        .landlord-theme .signup-link a {
            color: var(--landlord-color);
        }
        
        .landlord-theme .signup-link a:hover {
            color: var(--landlord-hover);
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
        
        .tenant-theme .logo i {
            background: linear-gradient(135deg, var(--tenant-color), var(--accent-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .landlord-theme .logo i {
            background: linear-gradient(135deg, var(--landlord-color), var(--accent-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .role-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
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
        
        .tenant-theme .role-badge {
            background: linear-gradient(135deg, var(--tenant-color), var(--accent-color));
        }
        
        .landlord-theme .role-badge {
            background: linear-gradient(135deg, var(--landlord-color), var(--accent-color));
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
        
        .switch-role {
            text-align: center;
            margin-top: 16px;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        
        .back-home i {
            margin-right: 5px;
        }
        
        .back-home:hover {
            color: var(--primary-color);
        }
        
        .tenant-theme .back-home:hover {
            color: var(--tenant-color);
        }
        
        .landlord-theme .back-home:hover {
            color: var(--landlord-color);
        }
    </style>
</head>
<body>
    <a href="../index.html" class="back-home">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    
    <div class="container" style="min-height: 100vh; display: flex; align-items: center;">
        <div class="row justify-content-center w-100">
            <div class="col-md-7 col-lg-6 mx-auto text-center">
                <div class="logo-container">
                    <div class="logo">
                        <i class="fas fa-home"></i>
                        <span>Dorm Hub</span>
                    </div>
                </div>
                
                <div class="card <?php echo !empty($role) ? $role . '-theme' : ''; ?>">
                    <div class="card-header">
                        <h2>Welcome Back</h2>
                        <span class="role-badge">Tenant</span>
                        <p class="subtitle">Log in to access your dashboard</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errorMsg)): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errorMsg); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="success-message">
                                <i class="fas fa-check-circle"></i>
                                <?php 
                                echo htmlspecialchars($_SESSION['message']); 
                                unset($_SESSION['message']);
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="login.php<?php echo !empty($role) ? '?role=' . $role : ''; ?>" method="POST">
                            <div class="mb-4">
                                <label for="usernameOrEmail" class="form-label">Username or Email</label>
                                <div class="input-group">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" class="form-control input-with-icon" id="usernameOrEmail" name="usernameOrEmail" placeholder="username or email" required>
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
                            
                            <?php if (!empty($role)): ?>
                                <input type="hidden" name="role" value="<?php echo $role; ?>">
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>
                        
                        <div class="signup-link">
                            <p>Don't have an account? <a href="signup.php<?php echo !empty($role) ? '?role=' . $role : ''; ?>">Create Account</a></p>
                        </div>
                        
                        <?php if (!empty($role)): ?>
                            <div class="switch-role">
                                <?php if ($role === 'tenant'): ?>
                                    Are you a landlord? <a href="login.php?role=landlord">Login as Landlord</a>
                                <?php else: ?>
                                    Are you a tenant? <a href="login.php?role=tenant">Login as Tenant</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
