<?php
// Database configuration
$host = 'localhost';
$dbname = 'boarding_house_system';
$username = 'root';
$password = '';

// Check if the role parameter is set and valid
$validRoles = ['tenant', 'landlord'];
$role = isset($_GET['role']) && in_array($_GET['role'], $validRoles) ? $_GET['role'] : 'tenant'; // Default to tenant

$mysqli = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Initialize message variable
$message = ""; // Variable to hold success or error messages

// Start session to store success message
session_start();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize it
    $name = trim($_POST['name']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confipassword = trim($_POST['confipassword']);
    $userRole = isset($_POST['role']) ? $_POST['role'] : 'tenant';

    // Validate passwords match
    if ($password !== $confipassword) {
        $message = "Passwords do not match.";
    } else {
        // Check if the email already exists
        $checkEmailStmt = $mysqli->prepare("SELECT email FROM users WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $checkEmailStmt->store_result();

        if ($checkEmailStmt->num_rows > 0) {
            $message = "Email already exists. Please use a different email.";
        } else {
            // Hash the password for security
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Prepare an SQL statement to insert user data with role
            $stmt = $mysqli->prepare("INSERT INTO users (name, lastname, email, password, role) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt) {
                // Bind parameters (s for string)
                $stmt->bind_param("sssss", $name, $lastname, $email, $hashedPassword, $userRole);

                // Execute the statement
                if ($stmt->execute()) {
                    // Set success message and redirect to login page
                    $_SESSION['message'] = "Registered Successfully as " . ucfirst($userRole) . "! Proceed to Log In!";
                    header("Location: login.php?role=" . $userRole);
                    exit();
                } else {
                    $message = "Error: Could not register. " . $stmt->error;
                }

                // Close the statement
                $stmt->close();
            } else {
                $message = "Error preparing statement: " . $mysqli->error;
            }
        }
        $checkEmailStmt->close();
    }
}

// Close the connection
$mysqli->close();

// Set page title based on role
$pageTitle = 'Sign Up as ' . ucfirst($role);
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
        
        .tenant-theme .login-link a {
            color: var(--tenant-color);
        }
        
        .tenant-theme .login-link a:hover {
            color: var(--tenant-hover);
        }
        
        .landlord-theme .login-link a {
            color: var(--landlord-color);
        }
        
        .landlord-theme .login-link a:hover {
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
                
                <div class="card <?php echo $role . '-theme'; ?>">
                    <div class="card-header">
                        <h2>Create Account</h2>
                        <span class="role-badge"><?php echo ucfirst($role); ?></span>
                        <p class="subtitle">Sign up to start finding your perfect place</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <form action="signup.php?role=<?php echo $role; ?>" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="name" class="form-label">First Name</label>
                                        <div class="input-group">
                                            <i class="fas fa-user input-icon"></i>
                                            <input type="text" class="form-control input-with-icon" id="name" name="name" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="lastname" class="form-label">Last Name</label>
                                        <div class="input-group">
                                            <i class="fas fa-user input-icon"></i>
                                            <input type="text" class="form-control input-with-icon" id="lastname" name="lastname" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" class="form-control input-with-icon" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <i class="fas fa-lock input-icon"></i>
                                            <input type="password" class="form-control input-with-icon" id="password" name="password" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="confipassword" class="form-label">Confirm Password</label>
                                        <div class="input-group">
                                            <i class="fas fa-lock input-icon"></i>
                                            <input type="password" class="form-control input-with-icon" id="confipassword" name="confipassword" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="role" value="<?php echo $role; ?>">
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </form>
                        
                        <div class="login-link">
                            <p>Already have an account? <a href="login.php?role=<?php echo $role; ?>">Log In</a></p>
                        </div>
                        
                        <div class="switch-role">
                            <?php if ($role === 'tenant'): ?>
                                Want to rent out property instead? <a href="signup.php?role=landlord">Sign Up as Landlord</a>
                            <?php else: ?>
                                Looking for a place to stay? <a href="signup.php?role=tenant">Sign Up as Tenant</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
