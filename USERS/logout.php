<?php
session_start();
session_destroy();
header("Location: ../index.html?message=" . urlencode("Log out successfully!"));
exit();