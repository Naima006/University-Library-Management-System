<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$member_id = (int)($_GET['id'] ?? 0);

if ($member_id <= 0) {
    header("Location: index.php?error=invalid_request");
    exit;
}

$getMember = $conn->prepare(
    "SELECT first_name, last_name, student_id FROM members WHERE member_id = ?"
);
$getMember->bind_param("i", $member_id);
$getMember->execute();

$member = $getMember->get_result()->fetch_assoc();

if (!$member) {
    header("Location: index.php?error=invalid_request");
    exit;
}

$stmt = $conn->prepare("UPDATE members SET is_active = 0 WHERE member_id = ?");
$stmt->bind_param("i", $member_id);

if ($stmt->execute()) {
    $user_id = $_SESSION['user_id'];
    $action = "Deactivated member";
    $table_name = "members";
    $description = "Marked member inactive: {$member['first_name']} {$member['last_name']} (Student ID: {$member['student_id']})";

    $log = $conn->prepare(
        "INSERT INTO activity_logs (user_id, action, table_name, record_id, description)
         VALUES (?, ?, ?, ?, ?)"
    );

    $log->bind_param("issis", $user_id, $action, $table_name, $member_id, $description);
    $log->execute();

    header("Location: index.php?success=deactivated");
    exit;
}

header("Location: index.php?error=invalid_request");
exit;
?>