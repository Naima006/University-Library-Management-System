<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must contain at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Password and confirm password do not match.";
    } else {

        $check_sql = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error_message = "This email address is already registered.";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = "staff";
            $is_active = 1;

            $insert_sql = "INSERT INTO users
                (full_name, email, password, phone, role, is_active)
                VALUES (?, ?, ?, ?, ?, ?)";

            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param(
                "sssssi",
                $full_name,
                $email,
                $hashed_password,
                $phone,
                $role,
                $is_active
            );

            if ($insert_stmt->execute()) {

                $new_staff_id = $conn->insert_id;

                $admin_id = $_SESSION["user_id"];
                $action = "Created staff account";
                $table_name = "users";
                $description = "Created staff account for " . $full_name . " (" . $email . ")";

                $log_sql = "INSERT INTO activity_logs
                    (user_id, action, table_name, record_id, description)
                    VALUES (?, ?, ?, ?, ?)";

                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param(
                    "issis",
                    $admin_id,
                    $action,
                    $table_name,
                    $new_staff_id,
                    $description
                );
                $log_stmt->execute();

                header("Location: index.php?success=staff_created");
                exit;

            } else {
                $error_message = "Unable to create the staff account. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Staff Account - ULMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="min-h-screen flex items-center justify-center p-5">

    <div class="w-full max-w-2xl bg-white rounded-xl shadow p-7">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Add Staff Account</h1>
                <p class="text-sm text-gray-500">
                    Create a login account for a library staff member.
                </p>
            </div>

            <a href="index.php" class="text-sm text-blue-600 hover:text-blue-800">
                ← Back to Users
            </a>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input
                    type="text"
                    name="full_name"
                    required
                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                    placeholder="Enter staff full name"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        placeholder="staff@ulms.com"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input
                        type="text"
                        name="phone"
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                        placeholder="01XXXXXXXXX"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    >
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input
                        type="password"
                        name="password"
                        required
                        placeholder="Minimum 6 characters"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input
                        type="password"
                        name="confirm_password"
                        required
                        placeholder="Re-enter password"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    >
                </div>

            </div>

            <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-700">
                This account will be created with the <strong>Staff</strong> role.
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="index.php"
                   class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                    Cancel
                </a>

                <button type="submit"
                        class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Create Staff Account
                </button>
            </div>

        </form>

    </div>

</div>

</body>
</html>