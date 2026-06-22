<?php

session_start();
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

/* GET DATA */

$book_id = (int)($_POST['book_id'] ?? 0);

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

    header("Location: edit.php?id=$book_id&error=invalid_isbn");
    exit;
}

/* CHECK DUPLICATE ISBN EXCLUDING CURRENT BOOK */

$check = $conn->prepare("
    SELECT book_id
    FROM books
    WHERE isbn = ?
    AND book_id != ?
");

if (!$check) {
    die("Prepare failed: " . $conn->error);
}

$check->bind_param(
    "si",
    $isbn,
    $book_id
);

$check->execute();

if ($check->get_result()->num_rows > 0) {

    header("Location: edit.php?id=$book_id&error=duplicate_isbn");
    exit;
}

/* UPDATE */

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

header("Location: index.php?success=updated");
exit;