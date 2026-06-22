<?php

session_start();
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$title = trim($_POST['title'] ?? '');
$author = trim($_POST['author_name'] ?? '');
$isbn = trim($_POST['isbn'] ?? '');

$year = !empty($_POST['published_year'])
    ? (int)$_POST['published_year']
    : null;

$total = (int)($_POST['total_copies'] ?? 0);
$available = (int)($_POST['available_copies'] ?? 0);
$category = (int)($_POST['category_id'] ?? 0);

/* VALIDATION */

if (
    empty($title) ||
    empty($author) ||
    empty($isbn)
) {
    die("Required fields missing.");
}

/* ISBN FORMAT */

if (!preg_match('/^[0-9-]{10,30}$/', $isbn)) {

    header("Location: create.php?error=invalid_isbn");
    exit;
}

/* CHECK EXACT DUPLICATE ISBN */

$check = $conn->prepare("
    SELECT book_id
    FROM books
    WHERE isbn = ?
");

if (!$check) {
    die("Prepare failed: " . $conn->error);
}

$check->bind_param("s", $isbn);
$check->execute();

if ($check->get_result()->num_rows > 0) {

    header("Location: create.php?error=duplicate_isbn");
    exit;
}

/* INSERT */

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
    VALUES
    (?, ?, ?, ?, ?, ?, ?)
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

header("Location: index.php?success=created");
exit;