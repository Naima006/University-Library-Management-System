<?php

session_start();
include("../config/db.php");

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
        (strtotime(date('Y-m-d')) - strtotime($issue['due_date']))
        / 86400
    );

    $fine = $daysLate * 10;
}

/*
|--------------------------------------------------------------------------
| UPDATE ISSUE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE book_issues
    SET
        status='returned',
        return_date=CURDATE(),
        fine_amount=?
    WHERE issue_id=?
");

$stmt->bind_param("di", $fine, $id);

$stmt->execute();

/*
|--------------------------------------------------------------------------
| INCREASE BOOK COPIES
|--------------------------------------------------------------------------
*/

$conn->query("
    UPDATE books
    SET available_copies = available_copies + 1
    WHERE book_id = {$issue['book_id']}
");

header("Location:index.php?success=returned");
exit;