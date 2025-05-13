<?php
require_once '../includes/db_connection.php';

// Get counts for various entities
$user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$apartment_count = $conn->query("SELECT COUNT(*) as count FROM apartments")->fetch_assoc()['count'];
$reservation_count = $conn->query("SELECT COUNT(*) as count FROM reservations")->fetch_assoc()['count'];

// Get recent reservations
$recent_reservations = $conn->query("
    SELECT r.*, 
           CONCAT(u.name, ' ', u.lastname) as user_name, 
           a.name as apartment_name,
           r.reservation_date as created_at
    FROM reservations r 
    JOIN users u ON r.user_id = u.id 
    JOIN apartments a ON r.apartment_id = a.id 
    ORDER BY r.reservation_date DESC 
    LIMIT 5
");

if (!$recent_reservations) {
    error_log("Query error: " . $conn->error);
}
?>

<style>
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-card i {
        font-size: 2.5em;
        color: var(--primary-color);
        margin-bottom: 10px;
    }

    .stat-card h3 {
        font-size: 2em;
        color: var(--secondary-color);
        margin: 10px 0;
    }

    .stat-card p {
        color: #666;
        font-size: 0.9em;
    }

    .recent-activity {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .recent-activity h2 {
        margin-bottom: 20px;
        color: var(--primary-color);
    }

    .activity-list {
        list-style: none;
    }

    .activity-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 30px;
    }

    .action-btn {
        background: var(--secondary-color);
        color: white;
        padding: 15px;
        border-radius: 5px;
        text-decoration: none;
        text-align: center;
        transition: background-color 0.3s;
    }

    .action-btn:hover {
        background: #2980b9;
    }
</style>

<div class="dashboard-stats">
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <h3><?php echo $user_count; ?></h3>
        <p>Total Users</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-building"></i>
        <h3><?php echo $apartment_count; ?></h3>
        <p>Total Rooms</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-check"></i>
        <h3><?php echo $reservation_count; ?></h3>
        <p>Total Reservations</p>
    </div>
</div>

<div class="recent-activity">
    <h2>Recent Reservations</h2>
    <div class="activity-list">
        <?php if ($recent_reservations && $recent_reservations->num_rows > 0): ?>
            <?php while ($reservation = $recent_reservations->fetch_assoc()): ?>
                <div class="activity-item">
                    <div>
                        <strong><?php echo htmlspecialchars($reservation['user_name']); ?></strong> reserved 
                        <strong><?php echo htmlspecialchars($reservation['apartment_name']); ?></strong>
                    </div>
                    <div>
                        <?php echo date('M d, Y', strtotime($reservation['created_at'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No recent reservations</p>
        <?php endif; ?>
    </div>
</div>

<div class="quick-actions">
    <a href="?page=manage_users&action=add" class="action-btn">
        <i class="fas fa-user-plus"></i> Add New User
    </a>
    <a href="?page=manage_apartments&action=add" class="action-btn">
        <i class="fas fa-plus-circle"></i> Add New Rooms
    </a>
    <a href="?page=manage_reservations" class="action-btn">
        <i class="fas fa-list"></i> View All Reservations
    </a>
</div>
