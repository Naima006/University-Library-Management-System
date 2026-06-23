<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$booksCount = 0;
$membersCount = 0;
$issuedCount = 0;
$staffCount = 0;

$booksResult = $conn->query("SELECT COUNT(*) AS total FROM books");
if ($booksResult) {
    $booksCount = $booksResult->fetch_assoc()['total'];
}

$membersResult = $conn->query(
    "SELECT COUNT(*) AS total
    FROM members
    WHERE is_active = 1 AND is_deleted = 0"
);

if ($membersResult) {
    $membersCount = $membersResult->fetch_assoc()['total'];
}

$issuedResult = $conn->query("SELECT COUNT(*) AS total FROM book_issues WHERE status = 'issued'");
if ($issuedResult) {
    $issuedCount = $issuedResult->fetch_assoc()['total'];
}

$staffResult = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'staff' AND is_active = 1");
if ($staffResult) {
    $staffCount = $staffResult->fetch_assoc()['total'];
}

$pageTitle = "Admin Dashboard";

ob_start();
?>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">

    <div class="bg-white shadow rounded-xl p-5">
        <p class="text-sm text-gray-500">Total Books</p>
        <p class="mt-2 text-4xl font-bold text-slate-800">
            <?= $booksCount ?>
        </p>
        <p class="mt-2 text-xs text-gray-400">Books in the library catalog</p>
    </div>

    <div class="bg-white shadow rounded-xl p-5">
        <p class="text-sm text-gray-500">Active Members</p>
        <p class="mt-2 text-4xl font-bold text-slate-800">
            <?= $membersCount ?>
        </p>
        <p class="mt-2 text-xs text-gray-400">Members currently active</p>
    </div>

    <div class="bg-white shadow rounded-xl p-5">
        <p class="text-sm text-gray-500">Issued Books</p>
        <p class="mt-2 text-4xl font-bold text-slate-800">
            <?= $issuedCount ?>
        </p>
        <p class="mt-2 text-xs text-gray-400">Books not yet returned</p>
    </div>

    <div class="bg-white shadow rounded-xl p-5">
        <p class="text-sm text-gray-500">Active Staff</p>
        <p class="mt-2 text-4xl font-bold text-slate-800">
            <?= $staffCount ?>
        </p>
        <p class="mt-2 text-xs text-gray-400">Staff accounts currently active</p>
    </div>

</div>

<div class="mt-8 bg-white rounded-xl shadow p-6">
    <h2 class="text-xl font-bold text-slate-800">Welcome back</h2>

    <p class="mt-2 text-gray-600">
        Hello <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong>.
        Use the navigation menu to manage staff accounts, books, members, categories, and library operations.
    </p>
</div>

<?php
$content = ob_get_clean();

include("../layouts/main_layout.php");
?>