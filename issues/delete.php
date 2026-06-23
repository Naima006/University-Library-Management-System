<?php

session_start();
include("../config/db.php");

/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/

if ($_SESSION['role'] !== 'admin') {

    die("Unauthorized Access");
}

if (!isset($_GET['id'])) {

    header("Location:index.php");
    exit;
}

$issue_id = (int)$_GET['id'];

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | FIND ISSUE
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT *
        FROM book_issues
        WHERE issue_id = ?
    ");

    $stmt->bind_param("i", $issue_id);
    $stmt->execute();

    $issue = $stmt->get_result()->fetch_assoc();

    if (!$issue) {

        $conn->rollback();

        header("Location:index.php");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | RESTORE STOCK IF ACTIVE ISSUE
    |--------------------------------------------------------------------------
    */

    if ($issue['status'] !== 'returned') {

        $stmt = $conn->prepare("
            UPDATE books
            SET available_copies = available_copies + 1
            WHERE book_id = ?
        ");

        $stmt->bind_param(
            "i",
            $issue['book_id']
        );

        $stmt->execute();
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE RECORD
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE FROM book_issues
        WHERE issue_id = ?
    ");

    $stmt->bind_param(
        "i",
        $issue_id
    );

    $stmt->execute();

    $conn->commit();

    header("Location:index.php?success=deleted");
    exit;

} catch (Exception $e) {

    $conn->rollback();

    die(
        "Delete Error: " .
        $e->getMessage()
    );
}
?>
