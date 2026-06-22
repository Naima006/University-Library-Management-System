<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?error=invalid_request");
    exit;
}

/* Receive form values */
$student_id = trim($_POST['student_id'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$department = trim($_POST['department'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

/* Required fields */
if ($student_id === '' || $first_name === '' || $last_name === '') {
    header("Location: index.php?error=required_fields");
    exit;
}

/* Student ID: 3–50 characters, letters/numbers/hyphens only */
if (!preg_match('/^[A-Za-z0-9-]{3,50}$/', $student_id)) {
    header("Location: index.php?error=invalid_student_id");
    exit;
}

/* Names: letters and spaces only, 2–100 characters */
if (!preg_match('/^[A-Za-z ]{2,100}$/', $first_name)) {
    header("Location: index.php?error=invalid_first_name");
    exit;
}

if (!preg_match('/^[A-Za-z ]{2,100}$/', $last_name)) {
    header("Location: index.php?error=invalid_last_name");
    exit;
}

/* Department: optional */
if ($department !== '' && !preg_match('/^[A-Za-z0-9 &-]{1,100}$/', $department)) {
    header("Location: index.php?error=invalid_department");
    exit;
}

/* Email: optional */
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?error=invalid_email");
    exit;
}

/* Phone: optional, but must be valid if provided */
if ($phone !== '' && !preg_match('/^01[3-9][0-9]{8}$/', $phone)) {
    header("Location: index.php?error=invalid_phone");
    exit;
}

/* Check whether Student ID already exists */
$checkSql = "SELECT member_id FROM members WHERE student_id = ? LIMIT 1";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("s", $student_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    header("Location: index.php?error=duplicate_student_id");
    exit;
}

/* Insert member */
$sql = "INSERT INTO members
        (student_id, first_name, last_name, department, email, phone, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssss",
    $student_id,
    $first_name,
    $last_name,
    $department,
    $email,
    $phone
);

if ($stmt->execute()) {

    /* Activity log */
    $user_id = (int) $_SESSION['user_id'];
    $member_id = $conn->insert_id;
    $action = "Created member";
    $table_name = "members";
    $description = "Added member: " . $first_name . " " . $last_name . " (" . $student_id . ")";

    $logSql = "INSERT INTO activity_logs
               (user_id, action, table_name, record_id, description)
               VALUES (?, ?, ?, ?, ?)";

    $logStmt = $conn->prepare($logSql);
    $logStmt->bind_param(
        "issis",
        $user_id,
        $action,
        $table_name,
        $member_id,
        $description
    );
    $logStmt->execute();

    header("Location: index.php?success=member_added");
    exit;
}

header("Location: index.php?error=save_failed");
exit;
?>