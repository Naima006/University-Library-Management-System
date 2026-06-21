<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../auth/login.php");
    exit;
}
?>

<h1>Staff Dashboard</h1>
<div class="text-sm font-semibold text-white truncate"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
<div class="text-xs text-gray-400 truncate"><?php echo htmlspecialchars($_SESSION['email']); ?></div>