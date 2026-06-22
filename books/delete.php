<?php

session_start();
include("../config/db.php");

/* Only admin and staff can delete books */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$book_id = (int) $_GET['id'];

/* Get book details before deleting */
$book_stmt = $conn->prepare("
    SELECT title, author_name, isbn
    FROM books
    WHERE book_id = ?
");

$book_stmt->bind_param("i", $book_id);
$book_stmt->execute();
$book_result = $book_stmt->get_result();

if ($book_result->num_rows === 0) {
    header("Location: index.php?error=book_not_found");
    exit;
}

$book = $book_result->fetch_assoc();

/* Do not delete a currently issued/overdue book */
$issue_stmt = $conn->prepare("
    SELECT issue_id
    FROM book_issues
    WHERE book_id = ?
    AND status IN ('issued', 'overdue')
    LIMIT 1
");

$issue_stmt->bind_param("i", $book_id);
$issue_stmt->execute();

if ($issue_stmt->get_result()->num_rows > 0) {
    header("Location: index.php?error=book_currently_issued");
    exit;
}

/* Permanent delete */
$delete_stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
$delete_stmt->bind_param("i", $book_id);

if (!$delete_stmt->execute()) {
    header("Location: index.php?error=delete_failed");
    exit;
}

/* Activity Log: Delete Book */
$log_user_id = (int) $_SESSION['user_id'];
$action = "Delete Book";
$table_name = "books";
$record_id = $book_id;
$description = 'Deleted book "' . $book['title'] . '" by ' . $book['author_name'] . ' (ISBN: ' . $book['isbn'] . ').';

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

header("Location: index.php?success=deleted");
exit;