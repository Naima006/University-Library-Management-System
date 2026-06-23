<?php
session_start();

include("../config/db.php");
include("../config/activity_log.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    header("Location: index.php?error=not_found");
    exit;
}

/* Get category name before deletion for activity log */
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

$category_name = $category['category_name'];

/* Do not allow deletion when books use this category */
$book_check = $conn->prepare("
    SELECT COUNT(*) AS total_books
    FROM books
    WHERE category_id = ?
");

$book_check->bind_param("i", $category_id);
$book_check->execute();

$total_books = (int)$book_check->get_result()->fetch_assoc()['total_books'];

if ($total_books > 0) {
    header("Location: index.php?error=in_use");
    exit;
}

$delete_stmt = $conn->prepare("
    DELETE FROM categories
    WHERE category_id = ?
");

$delete_stmt->bind_param("i", $category_id);

if ($delete_stmt->execute()) {

    addActivityLog(
        $conn,
        $_SESSION['user_id'],
        "Delete Category",
        "categories",
        $category_id,
        $_SESSION['full_name'] . ' deleted category "' . $category_name . '".'
    );

    header("Location: index.php?success=deleted");
    exit;
}

header("Location: index.php?error=delete_failed");
exit;
?>