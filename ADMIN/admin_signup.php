<?php
session_start(); // Start session

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

$errorMsg = ""; // To hold error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $name = trim($_POST['username']); // 'username' from the form, but maps to 'name' in DB
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        $errorMsg = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Invalid email format.";
    } else {
        // Check if name or email already exists
        $stmt = $mysqli->prepare("SELECT id FROM admins WHERE name = ? OR email = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $name, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errorMsg = "Username or email already exists.";
            } else {
                // Hash password securely
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new admin
                $insertStmt = $mysqli->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
                if ($insertStmt) {
                    $insertStmt->bind_param("sss", $name, $email, $hashedPassword);
                    if ($insertStmt->execute()) {
                        $_SESSION['message'] = "Admin registered successfully! Please log in.";
                        header("Location: admin_login.php");
                        exit();
                    } else {
                        $errorMsg = "Error during registration: " . $insertStmt->error;
                    }
                    $insertStmt->close();
                } else {
                    $errorMsg = "Error preparing insert statement: " . $mysqli->error;
                }
            }
            $stmt->close();
        } else {
            $errorMsg = "Error preparing select statement: " . $mysqli->error;
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Montserrat', sans-serif;
      background: url('image2.webp') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      color: #1e1e1e;
    }

    .container {
      background-color: #ffffff;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      max-width: 400px;
      width: 90%;
      text-align: center;
      backdrop-filter: blur(4px);
    }

    h2 {
      margin-bottom: 1.75rem;
      font-weight: 700;
      font-size: 2rem;
      color: #222;
      letter-spacing: 0.015em;
    }

    .error-message {
      background-color: #fcebea;
      color: #b02a37;
      padding: 0.75rem 1rem;
      border-radius: 6px;
      margin-bottom: 1.25rem;
      font-weight: 500;
      font-size: 0.95rem;
      box-shadow: 0 1px 4px rgba(176, 42, 55, 0.15);
    }

    label {
      display: block;
      text-align: left;
      margin-bottom: 6px;
      font-weight: 600;
      font-size: 0.9rem;
      color: #444;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px 14px;
      margin-bottom: 1.25rem;
      border: 1.25px solid #cbd5e1;
      border-radius: 8px;
      font-size: 1rem;
      color: #333;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
      font-family: 'Montserrat', sans-serif;
    }

    input[type="text"]::placeholder,
    input[type="email"]::placeholder,
    input[type="password"]::placeholder {
      color: #a0aec0;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: #3b82f6;
      box-shadow: 0 0 6px rgba(59, 130, 246, 0.35);
      outline: none;
    }

    input[type="submit"] {
      width: 100%;
      padding: 14px 0;
      background-color: #3b82f6;
      border: none;
      border-radius: 8px;
      color: #ffffff;
      font-weight: 600;
      font-size: 1.05rem;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      font-family: 'Montserrat', sans-serif;
      position: relative;
      overflow: hidden;
    }

    input[type="submit"]:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.1);
      transform: translateX(-100%);
      transition: transform 0.4s ease-out;
    }

    input[type="submit"]:hover,
    input[type="submit"]:focus {
      background-color: #2563eb;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
    }

    input[type="submit"]:active {
      transform: translateY(1px);
      box-shadow: 0 3px 10px rgba(37, 99, 235, 0.3);
      transition: all 0.1s ease;
    }

    p {
      margin-top: 1.5rem;
      font-size: 0.95rem;
      color: #555;
    }

    a {
      color: #3b82f6;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      position: relative;
    }

    a:after {
      content: '';
      position: absolute;
      width: 0;
      height: 2px;
      bottom: -2px;
      left: 0;
      background-color: #2563eb;
      transition: width 0.3s ease;
    }

    a:hover,
    a:focus {
      color: #2563eb;
    }

    a:hover:after,
    a:focus:after {
      width: 100%;
    }

    @media (max-width: 480px) {
      .container {
        padding: 1.75rem 1.25rem;
      }

      h2 {
        font-size: 1.5rem;
      }
    }
</style>
</head>
<body>
    <div class="container">
        <h2>Admin Signup</h2>
        <!-- Error message display (if any) -->
        <div class="error-message" style="display:none;">
            Error message here
        </div>

        <form action="#" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter password" required>

            <input type="submit" value="Sign Up">
        </form>

        <p>Already have an account? <a href="admin_login.php">Login</a></p>
    </div>
</body>
</html>
