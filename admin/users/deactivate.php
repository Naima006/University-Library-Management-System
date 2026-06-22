<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id = (int) $_GET['id'];

/* Prevent self-deactivation */
if ($id == $_SESSION['user_id']) {
    header("Location: index.php?error=cannot_deactivate_self");
    exit;
}

$sql = "UPDATE users SET is_active = 0 WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

/* Activity log */
$log = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description)
VALUES (?, 'Deactivated user', 'users', ?, 'User deactivated')");
$log->bind_param("ii", $_SESSION['user_id'], $id);
$log->execute();

header("Location: index.php?success=user_deactivated");
exit;