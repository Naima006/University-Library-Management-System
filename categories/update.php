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

$category_id = (int)($_POST['category_id'] ?? 0);
$category_name = trim($_POST['category_name'] ?? '');

if ($category_id <= 0) {
    header("Location: index.php?error=not_found");
    exit;
}

if ($category_name === '') {
    header("Location: edit.php?id=$category_id&error=required");
    exit;
}

if (
    strlen($category_name) < 2 ||
    strlen($category_name) > 100 ||
    !preg_match('/^[A-Za-z0-9 &, -]+$/', $category_name)
) {
    header("Location: edit.php?id=$category_id&error=invalid_name");
    exit;
}

/* Confirm category exists and preserve old name for the activity log */
$category_stmt = $conn->prepare("
    SELECT category_name
    FROM categories
    WHERE category_id = ?
    LIMIT 1
");

$category_stmt->bind_param("i", $category_id);
$category_stmt->execute();

$category = $category_stmt->get_result()->fetch_assoc();

if (!$category) {
    header("Location: index.php?error=not_found");
    exit;
}

$old_name = $category['category_name'];

/* Prevent duplicate category names except this category */
$check_stmt = $conn->prepare("
    SELECT category_id
    FROM categories
    WHERE LOWER(category_name) = LOWER(?)
      AND category_id != ?
    LIMIT 1
");

$check_stmt->bind_param("si", $category_name, $category_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows > 0) {
    header("Location: edit.php?id=$category_id&error=duplicate");
    exit;
}

$stmt = $conn->prepare("
    UPDATE categories
    SET category_name = ?
    WHERE category_id = ?
");

$stmt->bind_param("si", $category_name, $category_id);

if ($stmt->execute()) {

    if ($old_name !== $category_name) {
        addActivityLog(
            $conn,
            $_SESSION['user_id'],
            "Edit Category",
            "categories",
            $category_id,
            $_SESSION['full_name'] . ' updated category "' . $old_name . '" to "' . $category_name . '".'
        );
    }

    header("Location: index.php?success=updated");
    exit;
}

header("Location: edit.php?id=$category_id&error=update_failed");
exit;
?>