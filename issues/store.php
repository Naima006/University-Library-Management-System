<?php

session_start();
include("../config/db.php");
include("../config/activity_log.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

/* Login protection */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$book_id    = (int) ($_POST['book_id'] ?? 0);
$member_id  = (int) ($_POST['member_id'] ?? 0);
$issue_date = trim($_POST['issue_date'] ?? '');
$due_date   = trim($_POST['due_date'] ?? '');
$issued_by  = (int) $_SESSION['user_id'];

$today = date('Y-m-d');

/*
|--------------------------------------------------------------------------
| SERVER-SIDE DATE VALIDATION
|--------------------------------------------------------------------------
| Browser min="" restrictions can be bypassed, so validate again here.
*/

/* Check whether both dates were provided */
if ($issue_date === '' || $due_date === '') {
    header("Location: create.php?error=missing_dates");
    exit;
}

/* Check valid YYYY-MM-DD format */
$issueDateObject = DateTime::createFromFormat('Y-m-d', $issue_date);
$dueDateObject = DateTime::createFromFormat('Y-m-d', $due_date);

$isValidIssueDate = $issueDateObject &&
    $issueDateObject->format('Y-m-d') === $issue_date;

$isValidDueDate = $dueDateObject &&
    $dueDateObject->format('Y-m-d') === $due_date;

if (!$isValidIssueDate || !$isValidDueDate) {
    header("Location: create.php?error=invalid_date");
    exit;
}

/* Issue date cannot be before today */
if ($issue_date < $today) {
    header("Location: create.php?error=past_issue_date");
    exit;
}

/* Due date must be after the issue date */
if ($due_date <= $issue_date) {
    header("Location: create.php?error=invalid_due_date");
    exit;
}

/* Basic ID validation */
if ($book_id <= 0 || $member_id <= 0) {
    header("Location: create.php?error=invalid_selection");
    exit;
}

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | CHECK BOOK STOCK
    |--------------------------------------------------------------------------
    */
    $stmt = $conn->prepare("
        SELECT title, isbn, available_copies
        FROM books
        WHERE book_id = ?
    ");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();

    if (!$book || $book['available_copies'] <= 0) {
        $conn->rollback();
        header("Location: create.php?error=nostock");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK MEMBER
    |--------------------------------------------------------------------------
    | Prevent issuing a book to an inactive or deleted member if someone
    | manually submits an old/invalid member ID.
    */
    $stmt = $conn->prepare("
        SELECT first_name, last_name, student_id
        FROM members
        WHERE member_id = ?
        AND is_active = 1
        AND is_deleted = 0
    ");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();

    if (!$member) {
        $conn->rollback();
        header("Location: create.php?error=invalid_member");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT ISSUE
    |--------------------------------------------------------------------------
    */
    $stmt = $conn->prepare("
        INSERT INTO book_issues
        (book_id, member_id, issued_by, issue_date, due_date, status)
        VALUES (?, ?, ?, ?, ?, 'issued')
    ");

    $stmt->bind_param(
        "iiiss",
        $book_id,
        $member_id,
        $issued_by,
        $issue_date,
        $due_date
    );

    $stmt->execute();

    $issue_id = $conn->insert_id;

    /*
    |--------------------------------------------------------------------------
    | REDUCE BOOK STOCK
    |--------------------------------------------------------------------------
    */
    $stmt = $conn->prepare("
        UPDATE books
        SET available_copies = available_copies - 1
        WHERE book_id = ?
    ");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOG
    |--------------------------------------------------------------------------
    */
    $action = "Issued Book";
    $table_name = "book_issues";

    $description = $book['title']
        . " issued to "
        . $member['first_name'] . " " . $member['last_name']
        . " (Student ID: " . $member['student_id'] . ")"
        . " (ISBN: " . $book['isbn'] . ")"
        . " | Issue Date: " . $issue_date
        . " | Due Date: " . $due_date;

    addActivityLog(
        $conn,
        $issued_by,
        $action,
        $table_name,
        $issue_id,
        $description
    );

    $conn->commit();

    header("Location: index.php?success=issued");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Issue Error: " . $e->getMessage());
}
?>