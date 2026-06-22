<?php
session_start();
include("../config/db.php");

/* Log logout before session data is destroyed */
if (isset($_SESSION['user_id'])) {

    $user_id = (int) $_SESSION['user_id'];
    $full_name = $_SESSION['full_name'] ?? 'User';
    $role = $_SESSION['role'] ?? '';

    $action = "User Logout";
    $table_name = "users";
    $record_id = $user_id;
    $description = $full_name . " logged out" . ($role !== '' ? " from " . ucfirst($role) . " account." : ".");

    $logSql = "INSERT INTO activity_logs
        (user_id, action, table_name, record_id, description)
        VALUES (?, ?, ?, ?, ?)";

    $logStmt = $conn->prepare($logSql);
    $logStmt->bind_param(
        "issis",
        $user_id,
        $action,
        $table_name,
        $record_id,
        $description
    );
    $logStmt->execute();
}

/* Destroy session after logging */
$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
?>