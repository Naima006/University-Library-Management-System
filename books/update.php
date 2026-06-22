<?php

session_start();
include("../config/db.php");

$stmt = $conn->prepare("
UPDATE books
SET
title=?,
author_name=?,
isbn=?,
published_year=?,
total_copies=?,
available_copies=?,
category_id=?
WHERE book_id=?
");

$stmt->bind_param(
    "sssiiiii",
    $_POST['title'],
    $_POST['author_name'],
    $_POST['isbn'],
    $_POST['published_year'],
    $_POST['total_copies'],
    $_POST['available_copies'],
    $_POST['category_id'],
    $_POST['book_id']
);

$stmt->execute();

header("Location: index.php");
exit;