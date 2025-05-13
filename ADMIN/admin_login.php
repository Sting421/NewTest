<?php
session_start(); // Start the session

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
    // Get form data and sanitize it
    $usernameOrEmail = trim($_POST['usernameOrEmail']);
    $password = trim($_POST['password']);

    // Prepare an SQL statement to fetch user data
    $stmt = $mysqli->prepare("SELECT password FROM admins WHERE email = ? OR name = ?");
    
    if ($stmt) {
        // Bind parameters (s for string)
        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);

        // Execute the statement
        $stmt->execute();
        
        // Store result to check if user exists
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // User exists, now fetch the hashed password
            $stmt->bind_result($hashedPassword);
            $stmt->fetch();

            // Verify the password
            if (password_verify($password, $hashedPassword)) {
                $_SESSION['admin'] = $usernameOrEmail; // Store username/email in session
                header("Location: admin_dashboard.php"); // Redirect to admin dashboard
                exit;
            } else {
                $errorMsg = "Invalid password.";
            }
        } else {
            $errorMsg = "No user found with that username or email.";
        }

        // Close the statement
        $stmt->close();
    } else {
        $errorMsg = "Error preparing statement: " . $mysqli->error;
    }
}

// Close the connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Admin Login</title>
</head>
<body>
    <div class="container">
        <?php
        // Display logout success message if set in URL parameters
        if (isset($_GET['message'])) {
            echo "<div class='success-message'>" . htmlspecialchars($_GET['message']) . "</div>";
        }
        // Display error message if set
        if (!empty($errorMsg)) {
            echo "<div class='error-message'>$errorMsg</div>";
        }
        ?>

        <h2>Admin Login</h2>

        <form action="admin_login.php" method="POST">
            <label for="usernameOrEmail">Username or Email</label>
            <input type="text" id="usernameOrEmail" name="usernameOrEmail" required />

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required />

            <input type="submit" value="Log In" />
        </form>
        <p>
            Don't have an account? <a href="admin_signup.php">Sign Up</a>
        </p>
    </div>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: url('image3.webp') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
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

        .success-message {
            background-color: #e6f7e6;
            color: #2e7d32;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            font-weight: 500;
            font-size: 0.95rem;
            box-shadow: 0 1px 4px rgba(46, 125, 50, 0.15);
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

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #1877f2;
            box-shadow: 0 0 6px rgba(24, 119, 242, 0.35);
            outline: none;
        }

        input[type="submit"] {
            width: 100%;
            padding: 14px 0;
            background-color: #1877f2;
            border: none;
            border-radius: 8px;
            color: #ffffff;
            font-weight: 600;
            font-size: 1.05rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(24, 119, 242, 0.25);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-family: 'Montserrat', sans-serif;
            position: relative;
            overflow: hidden;
        }

        input[type="submit"]:hover,
        input[type="submit"]:focus {
            background-color: #165eab;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(24, 119, 242, 0.4);
        }

        input[type="submit"]:active {
            transform: translateY(1px);
            box-shadow: 0 3px 10px rgba(24, 119, 242, 0.3);
            transition: all 0.1s ease;
        }

        p {
            margin-top: 1.5rem;
            font-size: 0.95rem;
            color: #555;
        }

        a {
            color: #1877f2;
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
            background-color: #165eab;
            transition: width 0.3s ease;
        }

        a:hover,
        a:focus {
            color: #165eab;
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
</body>
</html>
