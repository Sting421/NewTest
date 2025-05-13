<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>DormHub - Sign Up</title>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-success text-center">
        <?php echo htmlspecialchars($_GET['message']); ?>
    </div>
  <?php endif; ?>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
  <style>
   body {
  font-family: 'Roboto', sans-serif;
  background: url('image1.jpg') no-repeat center center fixed;
  background-size: cover;
  min-height: 100vh;
  margin: 0;
  overflow-x: hidden;
  position: relative;
}

.logo-bar {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  padding: 1rem 2rem;
  background: transparent;
  z-index: 10;
  display: flex;
  align-items: center;
}
.logo-bar .bi-house-door-fill {
  font-size: 2rem;
  color: #fff;
  margin-right: 10px;
}
.logo-bar span {
  font-size: 1.75rem;
  font-weight: 700;
  color: #fff;
}

.center-content {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 2;
  position: relative;
}

.choose-title {
  color: #fff;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 2rem;
  text-shadow: 0 1px 6px rgba(0, 0, 0, 0.2);
}

/* Updated Professional Button Styles */
.signup-btn {
  width: 220px;
  font-size: 1rem;
  font-weight: 500;
  border-radius: 6px;
  padding: 0.65rem 1rem;
  margin: 0.5rem 0;
  border: 1px solid transparent;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  text-decoration: none;
  background-color: #ffffffcc;
  color: #222;
  backdrop-filter: blur(5px);
}

.signup-btn i {
  font-size: 1.1rem;
}

.signup-btn:hover {
  background-color: #ffffffee;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Admin Button */
.signup-btn.admin {
  background-color: #0d6efd;
  color: #fff;
  border: 1px solid #0d6efd;
}

.signup-btn.admin:hover {
  background-color: #084298;
  border-color: #084298;
}

/* User Button */
.signup-btn.user {
  background-color: #198754;
  color: #fff;
  border: 1px solid #198754;
}

.signup-btn.user:hover {
  background-color: #146c43;
  border-color: #146c43;
}

.homes-bg {
  position: fixed;
  left: 0;
  top: 0;
  width: 100vw;
  height: 100vh;
  z-index: 1;
  pointer-events: none;
  overflow: hidden;
}
.home-icon-bg {
  position: absolute;
  color: #fff;
  opacity: 0.05;
  filter: blur(1px);
  pointer-events: none;
}
.home1 { left: 10%; top: 15%; font-size: 8rem;}
.home2 { left: 65%; top: 10%; font-size: 7rem;}
.home3 { left: 25%; top: 55%; font-size: 9rem;}
.home4 { left: 75%; top: 60%; font-size: 8rem;}
.home5 { left: 50%; top: 35%; font-size: 10rem;}
.home6 { left: 80%; top: 30%; font-size: 6rem;}
.home7 { left: 15%; top: 70%; font-size: 7rem;}
.home8 { left: 40%; top: 80%; font-size: 6.5rem;}

@media (max-width: 576px) {
  .logo-bar {
    padding: 0.7rem 1rem;
  }
  .logo-bar .bi-house-door-fill {
    font-size: 1.5rem;
  }
  .logo-bar span {
    font-size: 1.25rem;
  }
  .choose-title {
    font-size: 1.5rem;
  }
  .signup-btn {
    width: 100%;
    font-size: 1rem;
  }
  .home1, .home2, .home3, .home4, .home5, .home6, .home7, .home8 {
    font-size: 4rem !important;
  }
}

  </style>
</head>
<body>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const adminBtn = document.querySelector('.signup-btn.admin');

    adminBtn.addEventListener('click', function(event) {
      event.preventDefault();
      const pin = prompt('Please enter the admin PIN to continue:');
      if (pin === null) return;
      if (pin.trim() === '5858') {
        window.location.href = this.href;
      } else {
        alert('Invalid PIN. Access denied.');
      }
    });
  });
</script>

  <!-- Logo Bar -->
  <div class="logo-bar">
    <i class="bi bi-house-door-fill"></i>
    <span>DormHub</span>
  </div>

  <!-- Background Home Icons -->
  <div class="homes-bg">
    <i class="bi bi-house-door-fill home-icon-bg home1"></i>
    <i class="bi bi-house-door-fill home-icon-bg home2"></i>
    <i class="bi bi-house-door-fill home-icon-bg home3"></i>
    <i class="bi bi-house-door-fill home-icon-bg home4"></i>
    <i class="bi bi-house-door-fill home-icon-bg home5"></i>
    <i class="bi bi-house-door-fill home-icon-bg home6"></i>
    <i class="bi bi-house-door-fill home-icon-bg home7"></i>
    <i class="bi bi-house-door-fill home-icon-bg home8"></i>
  </div>

  <!-- Main Buttons -->
  <div class="center-content">
    <div class="choose-title">Sign up as</div>
    <a href="admin_signup.php" class="signup-btn admin mb-2">
      <i class="bi bi-person-gear"></i>
      Admin
    </a>
    <a href="../USERS/signup.php" class="signup-btn user">
      <i class="bi bi-person"></i>
      User
    </a>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
