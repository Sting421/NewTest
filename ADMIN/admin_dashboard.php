<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: homepage.php?message=You have successfully logged out.");
    exit;
}

// Get admin information (assuming it's stored in session)
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';

// Get current page from URL, default to dashboard if not set
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --primary-light: #34495e;
            --secondary-color: #3498db;
            --secondary-hover: #2980b9;
            --background-color: #f5f7fa;
            --text-color: #2c3e50;
            --sidebar-width: 250px;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
            --danger-color: #e74c3c;
            --danger-hover: #c0392b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            font-weight: 400;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', 'Inter', sans-serif;
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.8rem;
            letter-spacing: 0.5px;
            background: linear-gradient(135deg, #3498db, #2ecc71);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }

        .admin-info {
            text-align: center;
            padding: 20px 0;
        }

        .admin-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #fff;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform var(--transition-normal);
        }

        .admin-avatar:hover {
            transform: scale(1.05);
        }

        .admin-avatar i {
            font-size: 40px;
            color: var(--primary-color);
        }

        .admin-info h3 {
            font-size: 1.1rem;
            margin-top: 8px;
            font-weight: 500;
        }

        .nav-menu {
            list-style: none;
            padding: 20px 0;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all var(--transition-normal);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
            transition: transform var(--transition-fast);
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .nav-link:hover i {
            transform: scale(1.2);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            transition: width var(--transition-normal);
            z-index: -1;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .nav-link.active {
            background-color: var(--secondary-color);
            color: white;
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }

        .nav-link.active i {
            transform: scale(1.2);
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .header {
            background-color: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            transition: box-shadow var(--transition-normal);
        }

        .header:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08), 0 3px 6px rgba(0, 0, 0, 0.12);
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .content {
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            min-height: calc(100vh - 140px);
            transition: box-shadow var(--transition-normal);
        }

        .content:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08), 0 3px 6px rgba(0, 0, 0, 0.12);
        }

        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 1rem;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(231, 76, 60, 0.2);
        }

        .logout-btn i {
            margin-right: 10px;
            transition: transform var(--transition-fast);
        }

        .logout-btn:hover {
            background-color: var(--danger-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(231, 76, 60, 0.3);
        }

        .logout-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(231, 76, 60, 0.2);
        }

        .logout-btn:hover i {
            transform: translateX(-3px);
        }

        .logout-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.3);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .logout-btn:active::after {
            opacity: 1;
            transform: scale(50, 50) translate(-50%);
            transition: all 0.5s ease;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 0.9rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 15px 10px;
            }
            
            .sidebar-header h2, 
            .admin-info h3, 
            .nav-link span {
                display: none;
            }
            
            .nav-link {
                justify-content: center;
                padding: 12px;
            }
            
            .nav-link i {
                margin-right: 0;
                font-size: 1.3rem;
            }
            
            .logout-btn span {
                display: none;
            }
            
            .logout-btn i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>DormHub</h2>
            </div>
            <div class="admin-info">
                <div class="admin-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3><?php echo htmlspecialchars($admin_name); ?></h3>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="?page=dashboard" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=manage_users" class="nav-link <?php echo $current_page === 'manage_users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=manage_Rooms" class="nav-link <?php echo $current_page === 'manage_Rooms' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>Manage Rooms</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="?page=manage_reservations" class="nav-link <?php echo $current_page === 'manage_reservations' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Manage Reservations</span>
                    </a>
                </li>
            </ul>
            <a href="?logout=true" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><?php echo isset($_GET['page']) ? ucfirst(str_replace('_', ' ', $_GET['page'])) : 'Dashboard'; ?></h1>
            </div>
            <div class="content">
                <?php
                if (isset($_GET['page'])) {
                    $page = $_GET['page'];
                    switch ($page) {
                        case 'manage_users':
                            include 'manage_users.php';
                            break;
                        case 'manage_apartments':
                            include 'manage_apartments.php';
                            break;
                        case 'manage_reservations':
                            include 'manage_reservations.php';
                            break;
                        case 'manage_boarding_houses':
                            include 'manage_boarding_houses.php';
                            break;
                        default:
                            include 'dashboard_overview.php';
                    }
                } else {
                    include 'dashboard_overview.php';
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>