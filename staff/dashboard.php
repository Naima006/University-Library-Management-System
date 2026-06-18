<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../auth/login.php");
    exit;
}
?>

<h1>Staff Dashboard</h1>
<p>Welcome Staff 👋</p>