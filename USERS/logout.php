<?php
session_start();
session_destroy();
header("Location: ../ADMIN/homepage.php?message=" . urlencode("Log out successfully!"));
exit();