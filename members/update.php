<?php
session_start();
include("../config/db.php");
include("../config/activity_log.php");

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

/* Required fields */
if ($member_id <= 0 || $student_id === '' || $first_name === '' || $last_name === '') {
    header("Location: index.php?error=required_fields");
    exit;
}

/* Student ID validation */
if (!preg_match('/^[A-Za-z0-9-]{3,50}$/', $student_id)) {
    header("Location: index.php?error=invalid_student_id");
    exit;
}

/* First name validation */
if (!preg_match('/^[A-Za-z\s]{2,100}$/', $first_name)) {
    header("Location: index.php?error=invalid_first_name");
    exit;
}

/* Last name validation */
if (!preg_match('/^[A-Za-z\s]{2,100}$/', $last_name)) {
    header("Location: index.php?error=invalid_last_name");
    exit;
}

/* Department validation */
if ($department !== '' && !preg_match('/^[A-Za-z0-9\s&-]{2,100}$/', $department)) {
    header("Location: index.php?error=invalid_department");
    exit;
}

/* Email validation */
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?error=invalid_email");
    exit;
}

/* Bangladesh phone validation */
if ($phone !== '' && !preg_match('/^01[0-9]{9}$/', $phone)) {
    header("Location: index.php?error=invalid_phone");
    exit;
}

/* Prevent editing a soft-deleted member */
$memberCheck = $conn->prepare(
    "SELECT member_id
     FROM members
     WHERE member_id = ? AND is_deleted = 0"
);

$memberCheck->bind_param("i", $member_id);
$memberCheck->execute();

if ($memberCheck->get_result()->num_rows === 0) {
    header("Location: index.php?error=invalid_request");
    exit;
}

/* Duplicate student ID check only among non-deleted members */
$check = $conn->prepare(
    "SELECT member_id
     FROM members
     WHERE student_id = ?
     AND member_id != ?
     AND is_deleted = 0"
);

$check->bind_param("si", $student_id, $member_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    header("Location: index.php?error=duplicate_student_id");
    exit;
}

$stmt = $conn->prepare(
    "UPDATE members
     SET student_id = ?,
         first_name = ?,
         last_name = ?,
         department = ?,
         email = ?,
         phone = ?
     WHERE member_id = ?
     AND is_deleted = 0"
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

    $user_id = (int)$_SESSION['user_id'];
    $action = "Updated member";
    $table_name = "members";
    $description = "Updated member: $first_name $last_name (Student ID: $student_id)";

    addActivityLog(
        $conn,
        $user_id,
        $action,
        $table_name,
        $member_id,
        $description
    );

    header("Location: index.php?success=updated");
    exit;
}

header("Location: index.php?error=invalid_request");
exit;
?>