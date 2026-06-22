<?php

session_start();

include("../config/db.php");

$title = $_POST['title'];
$author = $_POST['author_name'];
$isbn = $_POST['isbn'];
$year = $_POST['published_year'];
$total = $_POST['total_copies'];
$available = $_POST['available_copies'];
$category = $_POST['category_id'];

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
(
?,?,?,?,?,?,?
)
");

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

$stmt->execute();

header("Location: index.php");
exit;