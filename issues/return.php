<?php

session_start();
include("../config/db.php");
include("../config/activity_log.php");

$id = (int)$_GET['id'];

$issue = $conn->query("
    SELECT *
    FROM book_issues
    WHERE issue_id = $id
")->fetch_assoc();

if (!$issue) {
    die("Issue record not found.");
}

$fine = 0;

if (strtotime(date('Y-m-d')) > strtotime($issue['due_date'])) {

    $daysLate = floor(
        (strtotime(date('Y-m-d')) - strtotime($issue['due_date'])) / 86400
    );

    $fine = $daysLate * 10;
}

/* UPDATE ISSUE */
$stmt = $conn->prepare("
    UPDATE book_issues
    SET status='returned',
        return_date=CURDATE(),
        fine_amount=?
    WHERE issue_id=?
");

$stmt->bind_param("di", $fine, $id);
$stmt->execute();

/* INCREASE BOOK COPIES */
$conn->query("
    UPDATE books
    SET available_copies = available_copies + 1
    WHERE book_id = {$issue['book_id']}
");

/* =========================
   ACTIVITY LOG: RETURN BOOK
   ========================= */

$user_id = (int)$_SESSION['user_id'];

$bookInfo = $conn->query("
    SELECT title, isbn
    FROM books
    WHERE book_id = {$issue['book_id']}
")->fetch_assoc();

$memberInfo = $conn->query("
    SELECT first_name, last_name, student_id
    FROM members
    WHERE member_id = {$issue['member_id']}
")->fetch_assoc();

$action = "Returned Book";
$table_name = "book_issues";
$record_id = $id;

$description = $bookInfo['title']
    . " returned by "
    . $memberInfo['first_name'] . " " . $memberInfo['last_name']
    . " (Student ID: " . $memberInfo['student_id'] . ")"
    . ($fine > 0 ? " (Fine: $fine)" : "");

addActivityLog(
    $conn,
    $user_id,
    $action,
    $table_name,
    $record_id,
    $description
);

header("Location:index.php?success=returned");
exit;
?>