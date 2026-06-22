<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id = (int) $_POST['user_id'];
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);

if ($full_name === '' || $email === '') {
    header("Location: index.php?error=missing_fields");
    exit;
}

/* Email validation */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?error=invalid_email");
    exit;
}

/* Phone validation */
if ($phone !== '' && !preg_match('/^01[3-9][0-9]{8}$/', $phone)) {
    header("Location: index.php?error=invalid_phone");
    exit;
}

$sql = "UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $full_name, $email, $phone, $id);
$stmt->execute();

/* Activity log */
$log = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, description)
VALUES (?, 'Updated user', 'users', ?, 'User details updated')");
$log->bind_param("ii", $_SESSION['user_id'], $id);
$log->execute();

header("Location: index.php?success=user_updated");
exit;