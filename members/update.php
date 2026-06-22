<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$member_id = (int)($_POST['member_id'] ?? 0);
$student_id = trim($_POST['student_id'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$department = trim($_POST['department'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?error=invalid_email");
    exit;
}

if ($phone !== '' && (!ctype_digit($phone) || strlen($phone) > 11)) {
    header("Location: index.php?error=invalid_phone");
    exit;
}

if ($member_id <= 0 || $student_id === '' || $first_name === '' || $last_name === '') {
    header("Location: index.php?error=invalid_request");
    exit;
}

$check = $conn->prepare(
    "SELECT member_id FROM members WHERE student_id = ? AND member_id != ?"
);
$check->bind_param("si", $student_id, $member_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    header("Location: index.php?error=duplicate_student_id");
    exit;
}

$stmt = $conn->prepare(
    "UPDATE members
     SET student_id = ?, first_name = ?, last_name = ?, department = ?, email = ?, phone = ?
     WHERE member_id = ?"
);

$stmt->bind_param(
    "ssssssi",
    $student_id,
    $first_name,
    $last_name,
    $department,
    $email,
    $phone,
    $member_id
);

if ($stmt->execute()) {
    $user_id = $_SESSION['user_id'];
    $action = "Updated member";
    $table_name = "members";
    $description = "Updated member: $first_name $last_name (Student ID: $student_id)";

    $log = $conn->prepare(
        "INSERT INTO activity_logs (user_id, action, table_name, record_id, description)
         VALUES (?, ?, ?, ?, ?)"
    );

    $log->bind_param("issis", $user_id, $action, $table_name, $member_id, $description);
    $log->execute();

    header("Location: index.php?success=updated");
    exit;
}

header("Location: index.php?error=invalid_request");
exit;
?>