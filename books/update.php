<?php

session_start();
include("../config/db.php");

/* Only admin and staff can edit books */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

/* Get data */
$book_id = (int) ($_POST['book_id'] ?? 0);

$title = trim($_POST['title'] ?? '');
$author = trim($_POST['author_name'] ?? '');
$isbn = trim($_POST['isbn'] ?? '');

$year = !empty($_POST['published_year'])
    ? (int) $_POST['published_year']
    : null;

$total = (int) ($_POST['total_copies'] ?? 0);
$available = (int) ($_POST['available_copies'] ?? 0);
$category = (int) ($_POST['category_id'] ?? 0);

/* Validation */
if ($book_id <= 0 || empty($title) || empty($author) || empty($isbn)) {
    header("Location: index.php?error=invalid_data");
    exit;
}

/* ISBN-13 VALIDATION: exactly 13 digits, no spaces or hyphens */
if (!preg_match('/^\d{13}$/', $isbn)) {
    header("Location: edit.php?id=$book_id&error=invalid_isbn");
    exit;
}

/* Check duplicate ISBN excluding current book */
$check = $conn->prepare("
    SELECT book_id
    FROM books
    WHERE isbn = ?
    AND book_id != ?
");

if (!$check) {
    die("Prepare failed: " . $conn->error);
}

$check->bind_param("si", $isbn, $book_id);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    header("Location: edit.php?id=$book_id&error=duplicate_isbn");
    exit;
}

/* Update book */
$stmt = $conn->prepare("
    UPDATE books
    SET
        title = ?,
        author_name = ?,
        isbn = ?,
        published_year = ?,
        total_copies = ?,
        available_copies = ?,
        category_id = ?
    WHERE book_id = ?
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "sssiiiii",
    $title,
    $author,
    $isbn,
    $year,
    $total,
    $available,
    $category,
    $book_id
);

if (!$stmt->execute()) {
    die("Update failed: " . $stmt->error);
}

/* Activity Log: Edit Book */
$log_user_id = (int) $_SESSION['user_id'];
$action = "Edit Book";
$table_name = "books";
$record_id = $book_id;
$description = 'Updated book "' . $title . '" by ' . $author . ' (ISBN: ' . $isbn . ').';

$log_stmt = $conn->prepare("
    INSERT INTO activity_logs
    (user_id, action, table_name, record_id, description)
    VALUES (?, ?, ?, ?, ?)
");

if ($log_stmt) {
    $log_stmt->bind_param(
        "issis",
        $log_user_id,
        $action,
        $table_name,
        $record_id,
        $description
    );
    $log_stmt->execute();
}

header("Location: index.php?success=updated");
exit;