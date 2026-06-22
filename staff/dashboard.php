<?php

session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit;
}

/* ================= COUNTS ================= */

$booksCount = 0;
$membersCount = 0;
$issuedCount = 0;
$returnedCount = 0;

$booksResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM books
");

if ($booksResult) {
    $booksCount = $booksResult->fetch_assoc()['total'];
}

$membersResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM members
    WHERE is_active = 1
");

if ($membersResult) {
    $membersCount = $membersResult->fetch_assoc()['total'];
}

$issuedResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM book_issues
    WHERE status = 'issued'
");

if ($issuedResult) {
    $issuedCount = $issuedResult->fetch_assoc()['total'];
}

$returnResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM book_issues
    WHERE status = 'returned'
");

if ($returnResult) {
    $returnedCount = $returnResult->fetch_assoc()['total'];
}

$pageTitle = "Staff Dashboard";

ob_start();
?>

<!-- DASHBOARD CARDS -->

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">

    <div class="bg-white shadow rounded-xl p-5">
        <p class="text-sm text-gray-500">Total Books</p>

        <p class="mt-2 text-4xl font-bold text-slate-800">
            <?= $booksCount ?>
        </p>

        <p class="mt-2 text-xs text-gray-400">
            Books available in catalog
        </p>
    </div>

    <div class="bg-white shadow rounded-xl p-5">
        <p class="text-sm text-gray-500">Active Members</p>

        <p class="mt-2 text-4xl font-bold text-slate-800">
            <?= $membersCount ?>
        </p>

        <p class="mt-2 text-xs text-gray-400">
            Registered active members
        </p>
    </div>

    <div class="bg-white shadow rounded-xl p-5">
        <p class="text-sm text-gray-500">Issued Books</p>

        <p class="mt-2 text-4xl font-bold text-slate-800">
            <?= $issuedCount ?>
        </p>

        <p class="mt-2 text-xs text-gray-400">
            Books currently issued
        </p>
    </div>

    <div class="bg-white shadow rounded-xl p-5">
        <p class="text-sm text-gray-500">Returned Books</p>

        <p class="mt-2 text-4xl font-bold text-slate-800">
            <?= $returnedCount ?>
        </p>

        <p class="mt-2 text-xs text-gray-400">
            Books successfully returned
        </p>
    </div>

</div>

<!-- WELCOME -->

<div class="mt-8 bg-white rounded-xl shadow p-6">

    <h2 class="text-xl font-bold text-slate-800">
        Welcome Back
    </h2>

    <p class="mt-2 text-gray-600">

        Hello
        <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong>.

        Use the navigation menu to manage books, members, book issues, returns and library activities.

    </p>

</div>

<?php

$content = ob_get_clean();

include("../layouts/main_layout.php");
?>