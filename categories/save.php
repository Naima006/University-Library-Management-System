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

$category_name = trim($_POST['category_name'] ?? '');

if ($category_name === '') {
    header("Location: create.php?error=required");
    exit;
}

if (
    strlen($category_name) < 2 ||
    strlen($category_name) > 100 ||
    !preg_match('/^[A-Za-z0-9 &, -]+$/', $category_name)
) {
    header("Location: create.php?error=invalid_name&name=" . urlencode($category_name));
    exit;
}

/* Prevent duplicate category names, ignoring letter case */
$check_stmt = $conn->prepare("
    SELECT category_id
    FROM categories
    WHERE LOWER(category_name) = LOWER(?)
    LIMIT 1
");

$check_stmt->bind_param("s", $category_name);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows > 0) {
    header("Location: create.php?error=duplicate&name=" . urlencode($category_name));
    exit;
}

$stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");

if (!$stmt) {
    header("Location: create.php?error=create_failed");
    exit;
}

$stmt->bind_param("s", $category_name);

if ($stmt->execute()) {
    $category_id = (int)$conn->insert_id;

    addActivityLog(
        $conn,
        $_SESSION['user_id'],
        "Add Category",
        "categories",
        $category_id,
        $_SESSION['full_name'] . ' added category "' . $category_name . '".'
    );

    header("Location: index.php?success=created");
    exit;
}

header("Location: create.php?error=create_failed&name=" . urlencode($category_name));
exit;
?>