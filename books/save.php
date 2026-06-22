<?php

session_start();
include("../config/db.php");

/* Only admin and staff can add books */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

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
if (empty($title) || empty($author) || empty($isbn)) {
    header("Location: create.php?error=required_fields");
    exit;
}

/* ISBN-13 VALIDATION: exactly 13 digits, no spaces or hyphens */
if (!preg_match('/^\d{13}$/', $isbn)) {
    header("Location: create.php?error=invalid_isbn");
    exit;
}

/* Check duplicate ISBN */
$check = $conn->prepare("SELECT book_id FROM books WHERE isbn = ?");

if (!$check) {
    die("Prepare failed: " . $conn->error);
}

$check->bind_param("s", $isbn);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    header("Location: create.php?error=duplicate_isbn");
    exit;
}

/* Insert book */
$stmt = $conn->prepare("
    INSERT INTO books
    (
        title,
        author_name,
        isbn,
        published_year,
        total_copies,
        available_copies,
        category_id
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "sssiiii",
    $title,
    $author,
    $isbn,
    $year,
    $total,
    $available,
    $category
);

if (!$stmt->execute()) {
    die("Insert failed: " . $stmt->error);
}

$book_id = $conn->insert_id;

/* Activity Log: Add Book */
$log_user_id = (int) $_SESSION['user_id'];
$action = "Add Book";
$table_name = "books";
$record_id = $book_id;
$description = 'Added book "' . $title . '" by ' . $author . ' (ISBN: ' . $isbn . ').';

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

header("Location: index.php?success=created");
exit;