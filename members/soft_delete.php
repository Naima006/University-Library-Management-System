<?php
session_start();
include("../config/db.php");
include("../config/activity_log.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$member_id = (int)($_GET['id'] ?? 0);

if ($member_id <= 0) {
    header("Location: index.php?error=invalid_request");
    exit;
}

/* Get member details before hiding the member from the UI */
$getMember = $conn->prepare(
    "SELECT first_name, last_name, student_id
     FROM members
     WHERE member_id = ? AND is_deleted = 0"
);

if (!$getMember) {
    header("Location: index.php?error=invalid_request");
    exit;
}

$getMember->bind_param("i", $member_id);
$getMember->execute();

$member = $getMember->get_result()->fetch_assoc();

if (!$member) {
    header("Location: index.php?error=invalid_request");
    exit;
}

/*
    Soft delete:
    The record stays in the database,
    but is hidden from both Admin and Staff UI.
*/
$stmt = $conn->prepare(
    "UPDATE members
     SET is_deleted = 1
     WHERE member_id = ?"
);

if (!$stmt) {
    header("Location: index.php?error=invalid_request");
    exit;
}

$stmt->bind_param("i", $member_id);

if ($stmt->execute()) {

    $user_id = (int)$_SESSION['user_id'];
    $action = "Deleted member";
    $table_name = "members";
    $description = "Soft deleted member: {$member['first_name']} {$member['last_name']} (Student ID: {$member['student_id']})";

    addActivityLog(
        $conn,
        $user_id,
        $action,
        $table_name,
        $member_id,
        $description
    );

    header("Location: index.php?success=deleted");
    exit;
}

header("Location: index.php?error=invalid_request");
exit;
?>