<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($id <= 0) {
    header("Location: index.php?error=user_not_found");
    exit;
}

if ($full_name === '' || $email === '') {
    header("Location: edit.php?id=$id&error=missing_fields");
    exit;
}

/* Email validation */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: edit.php?id=$id&error=invalid_email");
    exit;
}

/* Phone validation */
if ($phone !== '' && !preg_match('/^01[3-9][0-9]{8}$/', $phone)) {
    header("Location: edit.php?id=$id&error=invalid_phone");
    exit;
}

/* Check whether another user already uses this email */
$email_check = $conn->prepare("
    SELECT user_id
    FROM users
    WHERE email = ?
    AND user_id != ?
");

$email_check->bind_param("si", $email, $id);
$email_check->execute();
$email_result = $email_check->get_result();

if ($email_result->num_rows > 0) {
    header("Location: edit.php?id=$id&error=email_exists");
    exit;
}

/*
|--------------------------------------------------------------------------
| Password validation
|--------------------------------------------------------------------------
| Both fields can stay empty.
| If one password field is entered, both must match.
*/

$password_changed = false;

if ($new_password !== '' || $confirm_password !== '') {

    if ($new_password !== $confirm_password) {
        header("Location: edit.php?id=$id&error=password_mismatch");
        exit;
    }

    if (strlen($new_password) < 6) {
        header("Location: edit.php?id=$id&error=password_too_short");
        exit;
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $sql = "
        UPDATE users
        SET full_name = ?, email = ?, phone = ?, password = ?
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssi",
        $full_name,
        $email,
        $phone,
        $hashed_password,
        $id
    );

    $password_changed = true;

} else {

    $sql = "
        UPDATE users
        SET full_name = ?, email = ?, phone = ?
        WHERE user_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssi",
        $full_name,
        $email,
        $phone,
        $id
    );
}

$stmt->execute();

/* Activity log */
$description = $password_changed
    ? "User details and password were updated by an administrator"
    : "User details were updated by an administrator";

$log = $conn->prepare("
    INSERT INTO activity_logs (
        user_id,
        action,
        table_name,
        record_id,
        description
    )
    VALUES (?, 'Updated user', 'users', ?, ?)
");

$log->bind_param(
    "iis",
    $_SESSION['user_id'],
    $id,
    $description
);

$log->execute();

header("Location: index.php?success=user_updated");
exit;
?>