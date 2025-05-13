<?php
session_start();
require_once('../includes/db_connection.php');

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'landlord') {
    header("Location: login.php");
    exit();
}

// Fetch user's name
$user_id = $_SESSION['user_id'];
$user_fullname = htmlspecialchars(ucwords(strtolower($_SESSION['name'] . " " . $_SESSION['lastname'])));

$error_message = "";
$success_message = "";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_apartments.php?error=" . urlencode("No property ID specified"));
    exit();
}

$apartment_id = $_GET['id'];

// Check if apartment belongs to this landlord
$check_sql = "SELECT * FROM apartments WHERE id = ? AND owner_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $apartment_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_apartments.php?error=" . urlencode("You don't have permission to edit this property"));
    exit();
}

// Get current apartment data
$apartment = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $location = $_POST['location'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $bedrooms = $_POST['bedrooms'];
    $bathrooms = $_POST['bathrooms'];
    $furnished = isset($_POST['furnished']) ? 1 : 0;
    $pets_allowed = isset($_POST['pets_allowed']) ? 1 : 0;
    $parking = isset($_POST['parking']) ? 1 : 0;
    $available = isset($_POST['available']) ? 1 : 0;
    $image_url = $apartment['image_url']; // Keep existing image by default
    
    // Update image if a new one is uploaded
    if(isset($_POST['image_url']) && !empty($_POST['image_url'])) {
        $image_url = $_POST['image_url'];
    }
    
    // Check if the apartment has active reservations before changing availability
    if ($apartment['available'] != $available) {
        $check_reservations_sql = "SELECT COUNT(*) as count FROM reservations 
                                  WHERE apartment_id = ? AND status = 'reserved'";
        $check_res_stmt = $conn->prepare($check_reservations_sql);
        $check_res_stmt->bind_param("i", $apartment_id);
        $check_res_stmt->execute();
        $reservation_count = $check_res_stmt->get_result()->fetch_assoc()['count'];
        
        if ($reservation_count > 0) {
            $error_message = "Cannot change availability as this property has active reservations.";
            // Revert to original availability
            $available = $apartment['available'];
        }
    }
    
    // Validate inputs
    if (empty($name) || empty($location) || empty($price)) {
        $error_message = "All fields are required";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = "Price must be a positive number";
    } elseif (!is_numeric($bedrooms) || $bedrooms < 0) {
        $error_message = "Bedrooms must be a non-negative number";
    } elseif (!is_numeric($bathrooms) || $bathrooms < 0) {
        $error_message = "Bathrooms must be a non-negative number";
    } else {
        // Update apartment
        $sql = "UPDATE apartments SET name = ?, location = ?, price = ?, description = ?, image_url = ?, 
                bedrooms = ?, bathrooms = ?, furnished = ?, pets_allowed = ?, parking = ?, available = ? 
                WHERE id = ? AND owner_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssiiiiiiii", $name, $location, $price, $description, $image_url, $bedrooms, $bathrooms, 
                         $furnished, $pets_allowed, $parking, $available, $apartment_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Property updated successfully!";
            
            // Refresh apartment data
            $check_stmt->execute();
            $apartment = $check_stmt->get_result()->fetch_assoc();
        } else {
            $error_message = "Error updating property: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - Landlord Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Cloudinary Upload Widget -->
    <script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
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
            max-width: 1340px;
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
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 16px 20px;
        }
        
        .card-body {
            padding: 20px;
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
        
        /* Form controls */
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
        
        .form-label {
            font-weight: 500;
            margin-bottom: 6px;
        }
        
        /* Custom switch */
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            margin-top: 0.25em;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        /* Navbar styles */
        .custom-navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: var(--dark-color);
        }
        
        .navbar-toggler {
            border: none;
            padding: 0;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e0e0ff;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 10px;
        }
        
        .dropdown-item {
            border-radius: 6px;
            padding: 10px 15px;
            color: #495057;
            font-weight: 500;
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: rgba(67, 97, 238, 0.05);
            color: var(--primary-color);
        }
        
        .dropdown-item.danger {
            color: var(--danger-color);
        }
        
        .dropdown-item.danger:hover, .dropdown-item.danger:focus {
            background-color: rgba(239, 71, 111, 0.05);
            color: var(--danger-color);
        }
        
        /* Image upload styles */
        .image-preview {
            width: 100%;
            height: 200px;
            border: 1px dashed #ddd;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-preview.has-image .upload-prompt {
            display: none;
        }
        
        .upload-prompt {
            text-align: center;
            color: #6c757d;
        }
        
        .upload-btn {
            width: 100%;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-home me-2"></i>Landlord Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_apartments.php">
                            <i class="fas fa-building me-2"></i>My Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_reservations.php">
                            <i class="fas fa-calendar-check me-2"></i>Reservations
                        </a>
                    </li>
                </ul>
                <div class="dropdown user-dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo substr($_SESSION['name'], 0, 1); ?>
                        </div>
                        <span class="d-none d-sm-inline"><?php echo $user_fullname; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Edit Property</h2>
                <p class="text-muted">Update your rental property information</p>
            </div>
            <div>
                <a href="manage_apartments.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Properties
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-building me-2"></i>Property Details</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Property Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($apartment['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($apartment['location']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">Monthly Rent Price (₱)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" 
                                           value="<?php echo htmlspecialchars($apartment['price']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($apartment['description']); ?></textarea>
                                <div class="form-text">Enter a brief description of your property</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bedrooms" class="form-label">Bedrooms</label>
                                <input type="number" min="0" class="form-control" id="bedrooms" name="bedrooms" 
                                       value="<?php echo htmlspecialchars($apartment['bedrooms']); ?>" required>
                                <div class="form-text">Enter the number of bedrooms</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bathrooms" class="form-label">Bathrooms</label>
                                <input type="number" min="0" class="form-control" id="bathrooms" name="bathrooms" 
                                       value="<?php echo htmlspecialchars($apartment['bathrooms']); ?>" required>
                                <div class="form-text">Enter the number of bathrooms</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="furnished" name="furnished" value="1"
                                           <?php echo $apartment['furnished'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="furnished">Furnished</label>
                                </div>
                                <div class="form-text">Toggle if this property is furnished</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="pets_allowed" name="pets_allowed" value="1"
                                           <?php echo $apartment['pets_allowed'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="pets_allowed">Pets Allowed</label>
                                </div>
                                <div class="form-text">Toggle if pets are allowed in this property</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="parking" name="parking" value="1"
                                           <?php echo $apartment['parking'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="parking">Parking Available</label>
                                </div>
                                <div class="form-text">Toggle if parking is available for this property</div>
                            </div>
                            
                            <?php
                            // Check if the apartment has active reservations
                            $check_res_sql = "SELECT COUNT(*) as count FROM reservations 
                                             WHERE apartment_id = ? AND status = 'reserved'";
                            $check_res_stmt = $conn->prepare($check_res_sql);
                            $check_res_stmt->bind_param("i", $apartment_id);
                            $check_res_stmt->execute();
                            $reservation_count = $check_res_stmt->get_result()->fetch_assoc()['count'];
                            ?>
                            
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="available" name="available" 
                                           <?php echo $apartment['available'] ? 'checked' : ''; ?>
                                           <?php echo ($reservation_count > 0) ? 'disabled' : ''; ?>>
                                    <label class="form-check-label" for="available">Available for Rent</label>
                                </div>
                                <?php if ($reservation_count > 0): ?>
                                    <div class="form-text text-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        This property has active reservations and cannot be marked as unavailable.
                                    </div>
                                <?php else: ?>
                                    <div class="form-text">Toggle if this property is currently available for rent</div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Image Upload Section -->
                            <div class="mb-3">
                                <label class="form-label">Property Image</label>
                                <div class="image-preview <?php echo (!empty($apartment['image_url'])) ? 'has-image' : ''; ?>" id="imagePreview">
                                    <div class="upload-prompt">
                                        <i class="fas fa-cloud-upload-alt fa-3x mb-2"></i>
                                        <p>Click below to upload an image</p>
                                    </div>
                                    <?php if (!empty($apartment['image_url'])): ?>
                                        <img id="previewImg" src="<?php echo htmlspecialchars($apartment['image_url']); ?>" style="display: block;">
                                    <?php else: ?>
                                        <img id="previewImg" src="" style="display: none;">
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="upload_widget" class="btn btn-outline-primary upload-btn">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>Change Image
                                </button>
                                <input type="hidden" id="image_url" name="image_url" value="<?php echo htmlspecialchars($apartment['image_url'] ?? ''); ?>">
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                <a href="delete_apartment.php?id=<?php echo $apartment_id; ?>" 
                                   class="btn btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this property? This action cannot be undone.')">
                                    <i class="fas fa-trash-alt me-2"></i>Delete Property
                                </a>
                                <div>
                                    <a href="manage_apartments.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cloudinary Upload Widget Configuration
        var myWidget = cloudinary.createUploadWidget({
            cloudName: 'dap8wtx5z',
            uploadPreset: 'ml_default', // Custom unsigned upload preset
            maxFiles: 1,
            sources: ['local', 'url', 'camera'],
            showAdvancedOptions: false,
            cropping: true,
            multiple: false,
            defaultSource: 'local',
            styles: {
                palette: {
                    window: '#FFFFFF',
                    windowBorder: '#90A0B3',
                    tabIcon: '#4361EE',
                    menuIcons: '#5A616A',
                    textDark: '#000000',
                    textLight: '#FFFFFF',
                    link: '#4361EE',
                    action: '#4361EE',
                    inactiveTabIcon: '#777',
                    error: '#F44235',
                    inProgress: '#4361EE',
                    complete: '#06D6A0',
                    sourceBg: '#F5F7FA'
                }
            }
        }, (error, result) => {
            if (!error && result && result.event === "success") {
                // Update the hidden input with the URL
                document.getElementById('image_url').value = result.info.secure_url;
                
                // Display the image preview
                const previewImg = document.getElementById('previewImg');
                previewImg.src = result.info.secure_url;
                previewImg.style.display = 'block';
                
                // Add class to image preview container
                document.getElementById('imagePreview').classList.add('has-image');
                
                console.log('Upload successful:', result.info.secure_url);
            }
        });
        
        // Add click event to upload button
        document.getElementById('upload_widget').addEventListener('click', function() {
            myWidget.open();
        }, false);
    </script>
</body>
</html> 