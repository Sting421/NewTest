<?php
session_start();
require_once('../includes/db_connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's name
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT name, lastname FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Check if user data exists
if (!$user) {
    // Handle the case where user data is not found
    header("Location: logout.php");
    exit();
}

$user_fullname = htmlspecialchars(ucwords(strtolower($user['name'] . " " . $user['lastname'])));

// Handle form submission for new reservation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_reservation'])) {
    $user_id = $_SESSION['user_id'];
    
    // Validate that required fields are present
    if (!isset($_POST['apartment_id']) || empty($_POST['apartment_id'])) {
        $error_message = "Please select an apartment.";
    } elseif (!isset($_POST['reservation_date']) || empty($_POST['reservation_date'])) {
        $error_message = "Please select a reservation date.";
    } elseif (!isset($_POST['duration']) || empty($_POST['duration'])) {
        $error_message = "Please select a duration.";
    } else {
        $apartment_id = $_POST['apartment_id'];
        $reservation_date = $_POST['reservation_date'];
        $duration = $_POST['duration'];
        
        // Check if apartment is already reserved for this date
        $check_sql = "SELECT * FROM reservations 
                    WHERE apartment_id = ? 
                    AND reservation_date = ? 
                    AND status != 'canceled'";
        
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $apartment_id, $reservation_date);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This apartment is already reserved for the selected date and time.";
        } else {
            // Insert with default status
            $sql = "INSERT INTO reservations (user_id, apartment_id, reservation_date, duration) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisi", $user_id, $apartment_id, $reservation_date, $duration);
            
            if ($stmt->execute()) {
                $success_message = "Reservation added successfully!";
            } else {
                $error_message = "Error adding reservation: " . $conn->error;
            }
        }
    }
}

// Handle Delete Reservation
if (isset($_POST['delete_reservation'])) {
    $reservation_id = $_POST['reservation_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify ownership and delete
    $delete_sql = "DELETE FROM reservations WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $reservation_id, $user_id);
    
    if ($delete_stmt->execute()) {
        $success_message = "Reservation cancelled successfully!";
    } else {
        $error_message = "Error cancelling reservation.";
    }
}

// Handle Edit Reservation
if (isset($_POST['edit_reservation'])) {
    $reservation_id = $_POST['reservation_id'];
    
    // Validate that required fields are present
    if (!isset($_POST['new_reservation_date']) || empty($_POST['new_reservation_date'])) {
        $error_message = "Please select a new reservation date.";
    } elseif (!isset($_POST['new_duration']) || empty($_POST['new_duration'])) {
        $error_message = "Please select a new duration.";
    } else {
        $new_date = $_POST['new_reservation_date'];
        $new_duration = $_POST['new_duration'];
        $user_id = $_SESSION['user_id'];
        
        // Check if new date is already reserved
        $check_sql = "SELECT * FROM reservations 
                    WHERE apartment_id = ? 
                    AND reservation_date = ? 
                    AND id != ? 
                    AND status != 'canceled'";
        
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("isi", $_POST['apartment_id'], $new_date, $reservation_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This apartment is already reserved for the selected date and time.";
        } else {
            // Update reservation
            $update_sql = "UPDATE reservations 
                        SET reservation_date = ?, duration = ? 
                        WHERE id = ? AND user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("siii", $new_date, $new_duration, $reservation_id, $user_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Reservation updated successfully!";
            } else {
                $error_message = "Error updating reservation.";
            }
        }
    }
}

// Fetch available apartments
$apartments_sql = "SELECT * FROM apartments WHERE available = 1";
$apartments_result = $conn->query($apartments_sql);

// Create a copy for the sidebar
$available_rooms_sql = "SELECT * FROM apartments WHERE available = 1";
$available_rooms_result = $conn->query($available_rooms_sql);

// Fetch user's reservations
$user_id = $_SESSION['user_id'];
$reservations_sql = "SELECT r.*, a.name as apartment_name, a.price as apartment_price 
                     FROM reservations r 
                     JOIN apartments a ON r.apartment_id = a.id 
                     WHERE r.user_id = ? 
                     ORDER BY r.reservation_date DESC";
$stmt = $conn->prepare($reservations_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Reservations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --danger-color: #ef476f;
            --danger-hover: #d23d60;
            --success-color: #06d6a0;
            --warning-color: #ffd166;
            --dark-color: #1d3557;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        h2, h4, h5 {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 24px;
            transition: all 0.2s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 16px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Modern Header */
        .dashboard-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .user-welcome {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .user-welcome h2 {
            margin: 0;
            color: white;
            font-size: 1.5rem;
        }
        
        .user-welcome p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        /* Button Styles with Transitions */
        .btn {
            font-weight: 500;
            letter-spacing: 0.3px;
            border-radius: 6px;
            padding: 8px 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn::after {
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
        
        .btn:active::after {
            opacity: 1;
            transform: scale(50, 50) translate(-50%);
            transition: all 0.5s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
            transform: translateY(-1px);
        }
        
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            box-shadow: 0 2px 4px rgba(239, 71, 111, 0.2);
        }
        
        .btn-danger:hover {
            background-color: var(--danger-hover);
            border-color: var(--danger-hover);
            box-shadow: 0 4px 8px rgba(239, 71, 111, 0.3);
            transform: translateY(-1px);
        }
        
        .btn-danger:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(239, 71, 111, 0.2);
        }
        
        /* Form Controls */
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }
        
        /* Table Styles */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table th {
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
            border-top: none;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table td, .table th {
            padding: 12px 16px;
            vertical-align: middle;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .table tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        /* Badge Styles */
        .badge {
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .bg-success {
            background-color: var(--success-color) !important;
        }
        
        .bg-warning {
            background-color: var(--warning-color) !important;
            color: #333 !important;
        }
        
        .bg-danger {
            background-color: var(--danger-color) !important;
        }
        
        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 16px 20px;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        /* Snackbar with improved animation */
        #snackbar {
            visibility: hidden;
            min-width: 300px;
            margin-left: -150px;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 9999;
            left: 50%;
            bottom: 30px;
            font-size: 0.95rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s, visibility 0s linear 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #snackbar.success {
            background-color: var(--success-color);
        }

        #snackbar.error {
            background-color: var(--danger-color);
        }
        
        #snackbar::before {
            content: '';
            display: inline-block;
            width: 24px;
            height: 24px;
            background-size: contain;
            background-repeat: no-repeat;
            margin-right: 10px;
        }
        
        #snackbar.success::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M0 0h24v24H0V0z' fill='none'/%3E%3Cpath d='M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z'/%3E%3C/svg%3E");
        }
        
        #snackbar.error::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M0 0h24v24H0V0z' fill='none'/%3E%3Cpath d='M11 15h2v2h-2zm0-8h2v6h-2zm.99-5C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z'/%3E%3C/svg%3E");
        }

        #snackbar.show {
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s, transform 0.3s, visibility 0s linear 0s;
        }

        /* Sidebar Styles */
        .sidebar-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .sidebar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .sidebar-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            color: white;
            padding: 16px 20px;
            font-weight: 600;
            border: none;
        }

        .list-group-item {
            border-left: none;
            border-right: none;
            padding: 16px 20px;
            transition: background-color 0.2s ease;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }

        .list-group-item:first-child {
            border-top: none;
        }

        .room-price-badge {
            background: linear-gradient(135deg, #06d6a0 0%, #05b586 100%);
            color: white;
            padding: 5px 12px;
            font-weight: 500;
            border-radius: 20px;
            box-shadow: 0 2px 6px rgba(6, 214, 160, 0.3);
        }
        
        /* New apartment card styles */
        .apartment-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            margin-bottom: 20px;
            position: relative;
        }
        
        .apartment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .apartment-card .card-img-top {
            height: 160px;
            background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .apartment-card .card-img-top::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMDAgMjAwIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJub25lIiBzdHJva2U9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiIHN0cm9rZS13aWR0aD0iMiIgLz48L3N2Zz4=');
            opacity: 0.3;
        }
        
        .apartment-card .card-img-icon {
            font-size: 4rem;
            color: white;
            position: relative;
            z-index: 2;
        }
        
        .apartment-card .card-body {
            padding: 20px;
        }
        
        .apartment-card .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .apartment-features {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .feature-item i {
            color: var(--primary-color);
        }

        .feature-badges {
            margin: 12px 0;
        }
        
        .feature-badges .badge {
            padding: 6px 10px;
            font-weight: 500;
            margin-right: 5px;
            margin-bottom: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .apartment-card .btn-group {
            margin-top: auto;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .amenity-item i {
            color: var(--success-color);
        }
        
        .amenity-unavailable i {
            color: #ccc;
        }

        .total-price {
            font-weight: 600;
            font-size: 1.1rem;
            color: #4361ee;
            margin-top: 10px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .price-details {
            background-color: #f8f9ff;
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 15px;
            border: 1px solid rgba(67, 97, 238, 0.15);
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .price-row.total {
            font-weight: 600;
            padding-top: 8px;
            margin-top: 8px;
            border-top: 1px dashed rgba(0, 0, 0, 0.1);
        }

        .location-text {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 3px;
        }

        .location-text i {
            color: #ef476f;
            font-size: 0.9rem;
        }

        @media (max-width: 992px) {
            .sidebar-card {
                margin-top: 2rem;
                position: static !important;
            }
        }

        .apartment-modal-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%);
            color: white;
            border-bottom: none;
        }
        
        .apartment-modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }
        
        .apartment-modal-header .btn-close:hover {
            opacity: 1;
        }
        
        .modal-content {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .apartment-detail-image {
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        
        .apartment-detail-image i {
            font-size: 6rem;
            color: var(--primary-color);
            opacity: 0.8;
            z-index: 1;
        }
        
        .apartment-detail-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMDAgMjAwIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJub25lIiBzdHJva2U9InJnYmEoMCwwLDAsMC4wNSkiIHN0cm9rZS13aWR0aD0iMiIgLz48L3N2Zz4=');
            opacity: 0.5;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            padding: 8px 12px;
            border-radius: 6px;
            background-color: #f8f9fa;
            transition: all 0.2s ease;
        }
        
        .amenity-item:hover {
            background-color: #f0f1f5;
        }
        
        .amenity-item i {
            color: var(--success-color);
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        
        .amenity-unavailable {
            opacity: 0.6;
        }
        
        .amenity-unavailable i {
            color: #ccc;
        }
        
        .apartment-price-display {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: baseline;
        }
        
        .apartment-price-display small {
            font-size: 0.9rem;
            font-weight: 400;
            color: #6c757d;
            margin-left: 5px;
        }
        
        .description-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
            border-left: 4px solid var(--primary-color);
        }

        /* Reservation form */
        .reservation-form-container {
            background-color: #fff;
            padding: 0;
            overflow: hidden;
        }
        
        .reservation-form {
            padding: 20px;
        }
        
        .reservation-form label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .reservation-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            margin-right: 8px;
        }
        
        .price-details {
            background-color: #f8f9ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid rgba(67, 97, 238, 0.15);
            animation: fadeIn 0.5s ease;
        }

        .reservations-card {
            border-radius: 12px;
            overflow: hidden;
            margin-top: 30px;
        }
        
        .reservations-card .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            border-bottom: none;
            padding: 16px 20px;
        }
        
        .reservation-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.8rem;
            text-align: center;
            min-width: 100px;
        }
        
        .reservation-status.confirmed {
            background-color: rgba(6, 214, 160, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(6, 214, 160, 0.2);
        }
        
        .reservation-status.pending {
            background-color: rgba(255, 209, 102, 0.1);
            color: #ffa500;
            border: 1px solid rgba(255, 209, 102, 0.2);
        }
        
        .reservation-status.cancelled {
            background-color: rgba(239, 71, 111, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 71, 111, 0.2);
        }
        
        .edit-modal .modal-content {
            border: none;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <!-- Snackbar div -->
    <div id="snackbar"></div>

    <!-- Dashboard Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="user-welcome">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h2>Welcome, <?php echo $user_fullname; ?>!</h2>
                        <p>Manage your reservations and discover new apartments</p>
                    </div>
                </div>
                <div>
                    <a href="logout.php" class="btn btn-light"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="container">
                    <?php
                    if (isset($success_message)) {
                        echo "<script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    showSnackbar('" . addslashes($success_message) . "', 'success');
                                });
                              </script>";
                    }
                    if (isset($error_message)) {
                        echo "<script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    showSnackbar('" . addslashes($error_message) . "', 'error');
                                });
                              </script>";
                    }
                    ?>

                    <!-- Header Section with Add Reservation Button -->
                    <div class="card mb-4">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-building me-2"></i>Available Apartments</h4>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newReservationModal">
                                <i class="fas fa-plus-circle me-2"></i>Make New Reservation
                            </button>
                        </div>
                    </div>
                    
                    <!-- Apartments Grid -->
                    <div class="row g-4">
                        <?php 
                        if($available_rooms_result->num_rows > 0): 
                            // Reset result pointer
                            $available_rooms_result->data_seek(0);
                            while($room = $available_rooms_result->fetch_assoc()): 
                        ?>
                            <div class="col-lg-4 col-md-6 mb-0">
                                <div class="apartment-card card h-100">
                                    <div class="card-img-top">
                                        <div class="card-img-icon">
                                            <i class="fas fa-building"></i>
                                        </div>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                                        <div class="location-text mb-2">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars($room['location']); ?>
                                        </div>
                                        <div class="feature-item mb-3">
                                            <i class="fas fa-tag"></i>
                                            <span class="fw-semibold">₱<?php echo number_format($room['price'], 2); ?>/month</span>
                                        </div>
                                        <div class="feature-badges">
                                            <span class="badge bg-light text-dark"><i class="fas fa-bed text-primary me-1"></i> <?php echo $room['bedrooms']; ?> Bed</span>
                                            <span class="badge bg-light text-dark"><i class="fas fa-bath text-primary me-1"></i> <?php echo $room['bathrooms']; ?> Bath</span>
                                            <?php if ($room['furnished']): ?>
                                            <span class="badge bg-light text-dark"><i class="fas fa-couch text-success me-1"></i> Furnished</span>
                                            <?php endif; ?>
                                            <?php if ($room['internet']): ?>
                                            <span class="badge bg-light text-dark"><i class="fas fa-wifi text-success me-1"></i> WiFi</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-grid gap-2 mt-auto">
                                            <button type="button" class="btn btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#apartmentModal<?php echo $room['id']; ?>">
                                                <i class="fas fa-info-circle me-1"></i> View Details
                                            </button>
                                            <button type="button" class="btn btn-outline-primary reserve-btn" 
                                                    data-apartment-id="<?php echo $room['id']; ?>"
                                                    data-apartment-name="<?php echo htmlspecialchars($room['name']); ?>"
                                                    data-apartment-price="<?php echo $room['price']; ?>">
                                                <i class="fas fa-calendar-plus me-1"></i> Reserve Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Apartment Details Modal -->
                            <div class="modal fade" id="apartmentModal<?php echo $room['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header apartment-modal-header">
                                            <h5 class="modal-title"><?php echo htmlspecialchars($room['name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="apartment-detail-image mb-4">
                                                        <i class="fas fa-building"></i>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="apartment-price-display mb-3">
                                                        ₱<?php echo number_format($room['price'], 2); ?> <small>/month</small>
                                                    </div>
                                                    <div class="location-text mb-4">
                                                        <i class="fas fa-map-marker-alt"></i> 
                                                        <?php echo htmlspecialchars($room['location']); ?>
                                                    </div>
                                                    
                                                    <h5 class="mt-4 mb-3 d-flex align-items-center">
                                                        <i class="fas fa-clipboard-list text-primary me-2"></i> Property Features
                                                    </h5>
                                                    <div class="row mb-3">
                                                        <div class="col-6">
                                                            <div class="amenity-item">
                                                                <i class="fas fa-bed"></i>
                                                                <span><?php echo $room['bedrooms']; ?> Bedroom(s)</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="amenity-item">
                                                                <i class="fas fa-bath"></i>
                                                                <span><?php echo $room['bathrooms']; ?> Bathroom(s)</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="amenity-item <?php echo $room['furnished'] ? '' : 'amenity-unavailable'; ?>">
                                                                <i class="fas fa-couch"></i>
                                                                <span>Furnished</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="amenity-item <?php echo $room['pets_allowed'] ? '' : 'amenity-unavailable'; ?>">
                                                                <i class="fas fa-paw"></i>
                                                                <span>Pets Allowed</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mt-2">
                                                        <div class="col-6">
                                                            <div class="amenity-item <?php echo $room['parking'] ? '' : 'amenity-unavailable'; ?>">
                                                                <i class="fas fa-car"></i>
                                                                <span>Parking</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="amenity-item <?php echo $room['internet'] ? '' : 'amenity-unavailable'; ?>">
                                                                <i class="fas fa-wifi"></i>
                                                                <span>Internet</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if(isset($room['description']) && !empty($room['description'])): ?>
                                            <div class="description-section mt-4">
                                                <h5 class="mb-3 d-flex align-items-center">
                                                    <i class="fas fa-align-left text-primary me-2"></i> Description
                                                </h5>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-center mt-4 pt-3 border-top">
                                                <button type="button" class="btn btn-primary btn-lg reserve-btn" 
                                                        data-apartment-id="<?php echo $room['id']; ?>" 
                                                        data-apartment-name="<?php echo htmlspecialchars($room['name']); ?>"
                                                        data-apartment-price="<?php echo $room['price']; ?>"
                                                        data-bs-dismiss="modal">
                                                    <i class="fas fa-calendar-plus me-2"></i> Reserve This Apartment
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="fas fa-info-circle mb-3 text-muted" style="font-size: 3rem;"></i>
                                        <h5>No Available Apartments</h5>
                                        <p class="text-muted">There are no apartments available for reservation at the moment. Please check back later.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Reservation Modal -->
                    <div class="modal fade" id="newReservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header apartment-modal-header">
                                    <h5 class="modal-title text-white" id="reservationModalLabel">
                                        <i class="fas fa-calendar-plus me-2"></i>Make a New Reservation
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <div class="row g-0">
                                        <div class="col-md-4 d-none d-md-block">
                                            <div class="h-100 d-flex align-items-center justify-content-center" 
                                                 style="background: linear-gradient(135deg, #4361ee 0%, #3a56d4 100%); min-height: 100%;">
                                                <div class="text-center text-white p-4">
                                                    <i class="fas fa-home" style="font-size: 5rem; margin-bottom: 20px;"></i>
                                                    <h4>Find Your Perfect Home</h4>
                                                    <p class="mb-0 opacity-75">Book your stay in just a few simple steps</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="reservation-form">
                                                <form method="POST" action="" id="reservationForm">
                                                    <div class="mb-4">
                                                        <label for="apartment_id" class="form-label">
                                                            <span class="reservation-icon"><i class="fas fa-building"></i></span>
                                                            Select Apartment
                                                        </label>
                                                        <select class="form-select" name="apartment_id" id="apartment_id" required>
                                                            <option value="">Choose an apartment...</option>
                                                            <?php 
                                                            // Reset the pointer
                                                            $apartments_result->data_seek(0);
                                                            while($apartment = $apartments_result->fetch_assoc()): 
                                                            ?>
                                                                <option value="<?php echo $apartment['id']; ?>" data-price="<?php echo $apartment['price']; ?>">
                                                                    <?php echo htmlspecialchars($apartment['name']); ?> - 
                                                                    ₱<?php echo number_format($apartment['price'], 2, '.', ','); ?>/month
                                                                </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-4">
                                                        <label for="reservation_date" class="form-label">
                                                            <span class="reservation-icon"><i class="fas fa-calendar-alt"></i></span>
                                                            Reservation Date and Time
                                                        </label>
                                                        <input type="datetime-local" 
                                                               class="form-control" 
                                                               name="reservation_date" 
                                                               id="reservation_date"
                                                               min="<?php echo date('Y-m-d\TH:i'); ?>"
                                                               required>
                                                    </div>
                                                    <div class="mb-4">
                                                        <label for="duration" class="form-label">
                                                            <span class="reservation-icon"><i class="fas fa-clock"></i></span>
                                                            Duration of Stay
                                                        </label>
                                                        <select class="form-select" name="duration" id="duration" required>
                                                            <option value="">Select duration...</option>
                                                            <option value="30">1 Month (30 days)</option>
                                                            <option value="60">2 Months (60 days)</option>
                                                            <option value="90">3 Months (90 days)</option>
                                                            <option value="180">6 Months (180 days)</option>
                                                            <option value="365">1 Year (365 days)</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div id="price-calculation" class="price-details" style="display: none;">
                                                        <h6 class="mb-3 text-primary fw-bold">Price Summary</h6>
                                                        <div class="price-row">
                                                            <span>Monthly Rate:</span>
                                                            <span id="daily-rate">₱0.00</span>
                                                        </div>
                                                        <div class="price-row">
                                                            <span>Duration:</span>
                                                            <span id="duration-text">0 days</span>
                                                        </div>
                                                        <div class="price-row total">
                                                            <span>Total Price:</span>
                                                            <span id="total-price">₱0.00</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mt-4 d-flex justify-content-between">
                                                        <button type="submit" name="submit_reservation" class="btn btn-primary">
                                                            <i class="fas fa-check-circle me-1"></i> Confirm Reservation
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Display User's Reservations -->
                    <div class="card reservations-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Your Reservations</h4>
                        </div>
                        <div class="card-body">
                            <?php if($reservations_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Apartment</th>
                                            <th>Price</th>
                                            <th>Reservation Date</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Reset the pointer
                                        $reservations_result->data_seek(0);
                                        while($reservation = $reservations_result->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2" style="color: var(--primary-color);"><i class="fas fa-building"></i></span>
                                                        <span><?php echo htmlspecialchars($reservation['apartment_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">₱<?php echo number_format($reservation['apartment_price'], 2, '.', ','); ?>/month</div>
                                                    <div class="small text-muted">
                                                        Total: ₱<?php 
                                                            $days = isset($reservation['duration']) ? $reservation['duration'] : 30; // Default to 30 days if not set
                                                            $months = $days / 30;
                                                            echo number_format($reservation['apartment_price'] * $months, 2, '.', ','); 
                                                        ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div><?php echo date('F j, Y', strtotime($reservation['reservation_date'])); ?></div>
                                                    <div class="small text-muted"><?php echo date('g:i A', strtotime($reservation['reservation_date'])); ?></div>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $days = isset($reservation['duration']) ? $reservation['duration'] : 30; // Default to 30 days if not set
                                                    if ($days >= 365) {
                                                        echo floor($days / 365) . " year(s)";
                                                    } else {
                                                        echo floor($days / 30) . " month(s)";
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="reservation-status <?php echo strtolower($reservation['status']); ?>">
                                                        <?php echo $reservation['status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                                data-bs-target="#editModal<?php echo $reservation['id']; ?>">
                                                            <i class="fas fa-edit me-1"></i> Edit
                                                        </button>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                            <button type="submit" name="delete_reservation" class="btn btn-sm btn-danger ms-1" 
                                                                    onclick="return confirm('Are you sure you want to cancel this reservation?')">
                                                                <i class="fas fa-times me-1"></i> Cancel
                                                            </button>
                                                        </form>
                                                    </div>
                                                    
                                                    <!-- Edit Modal -->
                                                    <div class="modal fade edit-modal" id="editModal<?php echo $reservation['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header apartment-modal-header">
                                                                    <h5 class="modal-title text-white">
                                                                        <i class="fas fa-edit me-2"></i>Edit Reservation
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <form method="POST" action="">
                                                                        <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                                        <input type="hidden" name="apartment_id" value="<?php echo $reservation['apartment_id']; ?>">
                                                                        <input type="hidden" class="apartment-price" value="<?php echo $reservation['apartment_price']; ?>">
                                                                        
                                                                        <div class="alert alert-info mb-4">
                                                                            <div class="d-flex">
                                                                                <div class="me-3">
                                                                                    <i class="fas fa-info-circle fa-2x"></i>
                                                                                </div>
                                                                                <div>
                                                                                    <h6 class="alert-heading mb-1">Editing Reservation for:</h6>
                                                                                    <p class="mb-0"><?php echo htmlspecialchars($reservation['apartment_name']); ?></p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label class="form-label">
                                                                                <span class="reservation-icon"><i class="fas fa-calendar-alt"></i></span>
                                                                                New Reservation Date and Time
                                                                            </label>
                                                                            <input type="datetime-local" class="form-control" 
                                                                                   name="new_reservation_date" 
                                                                                   value="<?php echo date('Y-m-d\TH:i', strtotime($reservation['reservation_date'])); ?>" 
                                                                                   min="<?php echo date('Y-m-d\TH:i'); ?>"
                                                                                   required>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="form-label">
                                                                                <span class="reservation-icon"><i class="fas fa-clock"></i></span>
                                                                                New Duration of Stay
                                                                            </label>
                                                                            <select class="form-select edit-duration" name="new_duration" required>
                                                                                <option value="">Select duration...</option>
                                                                                <option value="30" <?php echo (!isset($reservation['duration']) || $reservation['duration'] == 30) ? 'selected' : ''; ?>>1 Month (30 days)</option>
                                                                                <option value="60" <?php echo (isset($reservation['duration']) && $reservation['duration'] == 60) ? 'selected' : ''; ?>>2 Months (60 days)</option>
                                                                                <option value="90" <?php echo (isset($reservation['duration']) && $reservation['duration'] == 90) ? 'selected' : ''; ?>>3 Months (90 days)</option>
                                                                                <option value="180" <?php echo (isset($reservation['duration']) && $reservation['duration'] == 180) ? 'selected' : ''; ?>>6 Months (180 days)</option>
                                                                                <option value="365" <?php echo (isset($reservation['duration']) && $reservation['duration'] == 365) ? 'selected' : ''; ?>>1 Year (365 days)</option>
                                                                            </select>
                                                                        </div>
                                                                        
                                                                        <div class="price-details mb-4">
                                                                            <h6 class="mb-3 text-primary fw-bold">Price Summary</h6>
                                                                            <div class="price-row">
                                                                                <span>Monthly Rate:</span>
                                                                                <span>₱<?php echo number_format($reservation['apartment_price'], 2, '.', ','); ?></span>
                                                                            </div>
                                                                            <div class="price-row">
                                                                                <span>Selected Duration:</span>
                                                                                <span class="edit-duration-text">
                                                                                    <?php 
                                                                                    $days = isset($reservation['duration']) ? $reservation['duration'] : 30; // Default to 30 days if not set
                                                                                    if ($days >= 365) {
                                                                                        echo floor($days / 365) . " year(s)";
                                                                                    } else {
                                                                                        echo floor($days / 30) . " month(s)";
                                                                                    }
                                                                                    ?>
                                                                                </span>
                                                                            </div>
                                                                            <div class="price-row total">
                                                                                <span>Total Price:</span>
                                                                                <span class="edit-total-price">
                                                                                    ₱<?php 
                                                                                        $days = isset($reservation['duration']) ? $reservation['duration'] : 30; // Default to 30 days if not set
                                                                                        $months = $days / 30;
                                                                                        echo number_format($reservation['apartment_price'] * $months, 2, '.', ','); 
                                                                                    ?>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="d-flex justify-content-between">
                                                                            <button type="submit" name="edit_reservation" class="btn btn-primary">
                                                                                <i class="fas fa-save me-2"></i> Save Changes
                                                                            </button>
                                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                                                Cancel
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="fas fa-calendar-times text-muted" style="font-size: 4rem;"></i>
                                    </div>
                                    <h5>No Reservations Yet</h5>
                                    <p class="text-muted">You haven't made any reservations. Browse available apartments and make your first reservation!</p>
                                    <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#newReservationModal">
                                        <i class="fas fa-plus-circle me-2"></i>Make a Reservation
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function showSnackbar(message, type) {
            var snackbar = document.getElementById("snackbar");
            snackbar.textContent = message;
            snackbar.className = "show " + type;
            setTimeout(function(){ 
                snackbar.className = snackbar.className.replace("show", ""); 
            }, 4000);
        }

        // Price calculation
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Enhance form selects with animation
            const formSelects = document.querySelectorAll('select.form-select');
            formSelects.forEach(select => {
                select.addEventListener('change', function() {
                    if (this.value) {
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                    }
                });
            });
            
            const apartmentSelect = document.getElementById('apartment_id');
            const durationSelect = document.getElementById('duration');
            const priceCalculation = document.getElementById('price-calculation');
            const dailyRate = document.getElementById('daily-rate');
            const durationText = document.getElementById('duration-text');
            const totalPrice = document.getElementById('total-price');
            const reservationModal = new bootstrap.Modal(document.getElementById('newReservationModal'));

            function calculatePrice() {
                if (apartmentSelect.value && durationSelect.value) {
                    const selectedOption = apartmentSelect.options[apartmentSelect.selectedIndex];
                    const price = parseFloat(selectedOption.dataset.price);
                    const days = parseInt(durationSelect.value);
                    
                    if (!isNaN(price) && !isNaN(days)) {
                        const months = days / 30; // Convert days to months
                        const total = price * months;
                        
                        // Format the display
                        dailyRate.textContent = '₱' + price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        // Format duration text
                        if (days >= 365) {
                            durationText.textContent = Math.floor(days / 365) + ' year(s)';
                        } else {
                            durationText.textContent = Math.floor(days / 30) + ' month(s)';
                        }
                        
                        totalPrice.textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        // Show with animation
                        if (priceCalculation.style.display === 'none') {
                            priceCalculation.style.display = 'block';
                            priceCalculation.style.animation = 'fadeIn 0.5s ease';
                        }
                    }
                } else {
                    priceCalculation.style.display = 'none';
                }
            }

            apartmentSelect.addEventListener('change', calculatePrice);
            durationSelect.addEventListener('change', calculatePrice);
            
            // Handle "Reserve Now" buttons
            const reserveButtons = document.querySelectorAll('.reserve-btn');
            reserveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const apartmentId = this.getAttribute('data-apartment-id');
                    const apartmentName = this.getAttribute('data-apartment-name');
                    const apartmentPrice = this.getAttribute('data-apartment-price');
                    
                    // Set values in the reservation modal
                    if (apartmentSelect) {
                        for (let i = 0; i < apartmentSelect.options.length; i++) {
                            if (apartmentSelect.options[i].value === apartmentId) {
                                apartmentSelect.selectedIndex = i;
                                apartmentSelect.classList.add('is-valid');
                                calculatePrice();
                                break;
                            }
                        }
                    }
                    
                    // Focus on the date field
                    setTimeout(() => {
                        document.getElementById('reservation_date').focus();
                    }, 500);
                    
                    // Show the reservation modal
                    reservationModal.show();
                });
            });

            // Edit modal price calculation
            const editDurationSelects = document.querySelectorAll('.edit-duration');
            
            editDurationSelects.forEach(select => {
                select.addEventListener('change', function() {
                    const modal = this.closest('.modal-content');
                    const priceInput = modal.querySelector('.apartment-price');
                    const durationText = modal.querySelector('.edit-duration-text');
                    const totalPrice = modal.querySelector('.edit-total-price');
                    
                    if (this.value && priceInput) {
                        const price = parseFloat(priceInput.value);
                        const days = parseInt(this.value);
                        
                        if (!isNaN(price) && !isNaN(days)) {
                            const months = days / 30; // Convert days to months
                            const total = price * months;
                            
                            // Format duration text
                            if (days >= 365) {
                                durationText.textContent = Math.floor(days / 365) + ' year(s)';
                            } else {
                                durationText.textContent = Math.floor(days / 30) + ' month(s)';
                            }
                            
                            totalPrice.textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>