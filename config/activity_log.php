<?php

function addActivityLog($conn, $user_id, $action, $table_name, $record_id, $description)
{
    $user_id = (int) $user_id;
    $record_id = (int) $record_id;

    $sql = "INSERT INTO activity_logs
            (user_id, action, table_name, record_id, description)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "issis",
        $user_id,
        $action,
        $table_name,
        $record_id,
        $description
    );

    return $stmt->execute();
}
?>