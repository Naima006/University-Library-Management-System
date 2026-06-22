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

$student_id = trim($_POST['student_id'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$department = trim($_POST['department'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($student_id === '' || $first_name === '' || $last_name === '') {
    header("Location: create.php");
    exit;
}

$check = $conn->prepare("SELECT member_id FROM members WHERE student_id = ?");
$check->bind_param("s", $student_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    header("Location: index.php?error=duplicate_student_id");
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO members (student_id, first_name, last_name, department, email, phone, is_active)
     VALUES (?, ?, ?, ?, ?, ?, 1)"
);

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
    $member_id = $conn->insert_id;

    $user_id = $_SESSION['user_id'];
    $action = "Created member";
    $table_name = "members";
    $description = "Added member: $first_name $last_name (Student ID: $student_id)";

    $log = $conn->prepare(
        "INSERT INTO activity_logs (user_id, action, table_name, record_id, description)
         VALUES (?, ?, ?, ?, ?)"
    );

    $log->bind_param("issis", $user_id, $action, $table_name, $member_id, $description);
    $log->execute();

    header("Location: index.php?success=created");
    exit;
}

header("Location: index.php?error=invalid_request");
exit;
?>