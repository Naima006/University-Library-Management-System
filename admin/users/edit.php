<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=user_not_found");
    exit;
}

$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: index.php?error=user_not_found");
    exit;
}

$pageTitle = "Edit User";
ob_start();
?>

<div class="max-w-xl mx-auto bg-white p-6 rounded-xl shadow">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Edit User</h1>
        <p class="text-sm text-gray-500 mt-1">
            Update user information or reset the user's password.
        </p>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">

            <?php if ($_GET['error'] === 'missing_fields'): ?>
                Full name and email are required.

            <?php elseif ($_GET['error'] === 'invalid_email'): ?>
                Please enter a valid email address.

            <?php elseif ($_GET['error'] === 'invalid_phone'): ?>
                Please enter a valid Bangladeshi phone number.

            <?php elseif ($_GET['error'] === 'password_mismatch'): ?>
                New password and confirm password do not match.

            <?php elseif ($_GET['error'] === 'password_too_short'): ?>
                Password must contain at least 6 characters.

            <?php elseif ($_GET['error'] === 'email_exists'): ?>
                This email address is already being used by another user.

            <?php else: ?>
                Something went wrong. Please try again.
            <?php endif; ?>

        </div>
    <?php endif; ?>

    <form method="POST" action="update.php">

        <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">

        <div class="mb-4">
            <label class="block mb-2 font-medium text-slate-700">
                Full Name
            </label>

            <input
                type="text"
                name="full_name"
                required
                value="<?= htmlspecialchars($user['full_name']) ?>"
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
            >
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-medium text-slate-700">
                Email
            </label>

            <input
                type="email"
                name="email"
                required
                value="<?= htmlspecialchars($user['email']) ?>"
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
            >
        </div>

        <div class="mb-5">
            <label class="block mb-2 font-medium text-slate-700">
                Phone
            </label>

            <input
                type="text"
                name="phone"
                value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                placeholder="01XXXXXXXXX"
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
            >
        </div>

        <div class="border-t border-gray-200 pt-5 mt-5">
            <h2 class="text-lg font-semibold text-slate-800">
                Reset Password
            </h2>

            <p class="text-sm text-gray-500 mt-1 mb-4">
                Leave these fields empty if you do not want to change the password.
            </p>

            <div class="mb-4">
                <label class="block mb-2 font-medium text-slate-700">
                    New Password
                </label>

                <input
                    type="password"
                    name="new_password"
                    minlength="6"
                    placeholder="Minimum 6 characters"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
            </div>

            <div class="mb-5">
                <label class="block mb-2 font-medium text-slate-700">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    minlength="6"
                    placeholder="Re-enter the new password"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
            <a href="index.php"
                class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 text-center">
                Cancel
            </a>

            <button type="submit"
                class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-1"></i>
                Update User
            </button>
        </div>

    </form>

</div>

<?php
$content = ob_get_clean();
include("../../layouts/main_layout.php");
?>