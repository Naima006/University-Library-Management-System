<?php

session_start();

include("../config/db.php");

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: index.php");
exit;