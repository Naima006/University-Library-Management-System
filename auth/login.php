<?php
session_start();
include("../config/db.php");

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $sql = "SELECT * FROM users WHERE email='$email' AND role='$role' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if ($user['is_active'] == 0) {
            $error_message = "Account disabled!";
        }  else if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];

                /* Activity Log: successful login */
                $log_user_id = (int) $user['user_id'];
                $action = "User Login";
                $table_name = "users";
                $record_id = (int) $user['user_id'];
                $description = $user['full_name'] . " logged in as " . ucfirst($user['role']) . ".";

                $logSql = "INSERT INTO activity_logs
                    (user_id, action, table_name, record_id, description)
                    VALUES (?, ?, ?, ?, ?)";

                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param(
                    "issis",
                    $log_user_id,
                    $action,
                    $table_name,
                    $record_id,
                    $description
                );
                $logStmt->execute();

            if ($user['role'] == 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../staff/dashboard.php");
            }
            exit;
        } else {
            $error_message = "Wrong password!";
        }
    } else {
        $error_message = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - ULMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

<div class="bg-white shadow-lg rounded-xl w-full max-w-md p-8">

    <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">
        University Library Login
    </h2>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-lg mb-4 text-sm text-center">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="space-y-4">
        <div>
            <label class="block text-gray-600">Email</label>
            <input type="email" name="email" required
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
        </div>

        <div>
            <label class="block text-gray-600">Password</label>
            <input type="password" name="password" required
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
        </div>

        <div>
            <label class="block text-gray-600">Role</label>
            <select name="role" class="w-full px-4 py-2 border rounded-lg">
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
            </select>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
            Login
        </button>
    </form>
</div>

</body>
</html>