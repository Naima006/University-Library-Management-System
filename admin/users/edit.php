<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$id = (int) $_GET['id'];

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

<form method="POST" action="update.php">

    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">

    <label>Full Name</label>
    <input type="text" name="full_name"
           value="<?= htmlspecialchars($user['full_name']) ?>"
           class="w-full border p-2 mb-3">

    <label>Email</label>
    <input type="email" name="email"
           value="<?= htmlspecialchars($user['email']) ?>"
           class="w-full border p-2 mb-3">

    <label>Phone</label>
    <input type="text" name="phone"
           value="<?= htmlspecialchars($user['phone']) ?>"
           class="w-full border p-2 mb-3">

    <div class="flex justify-end gap-3 pt-2">
        <a href="index.php"
        class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
            Cancel
        </a>

        <button type="submit"
                class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-save mr-1"></i> Update User
        </button>
    </div>
    

</form>

</div>

<?php
$content = ob_get_clean();
include("../../layouts/main_layout.php");
?>