<?php

session_start();
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location:index.php");
    exit;
}

$book_id    = (int)$_POST['book_id'];
$member_id  = (int)$_POST['member_id'];
$issue_date = $_POST['issue_date'];
$due_date   = $_POST['due_date'];

$issued_by = $_SESSION['user_id'];

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | CHECK BOOK STOCK
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT available_copies
        FROM books
        WHERE book_id = ?
    ");

    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    $book = $stmt->get_result()->fetch_assoc();

    if (!$book || $book['available_copies'] <= 0) {

        $conn->rollback();

        header("Location:create.php?error=nostock");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT ISSUE
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO book_issues
        (
            book_id,
            member_id,
            issued_by,
            issue_date,
            due_date,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            'issued'
        )
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

    /*
    |--------------------------------------------------------------------------
    | REDUCE STOCK
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE books
        SET available_copies = available_copies - 1
        WHERE book_id = ?
    ");

    $stmt->bind_param("i", $book_id);
    $stmt->execute();

    $conn->commit();

    header("Location:index.php?success=issued");
    exit;

} catch (Exception $e) {

    $conn->rollback();

    die(
        "Issue Error: " .
        $e->getMessage()
    );
}
?>
