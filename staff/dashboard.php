<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit;
}

$pageTitle = "Staff Dashboard";

ob_start();
?>

<div class="grid md:grid-cols-4 gap-6">

    <div class="bg-white shadow rounded-xl p-5">
        <h3 class="text-gray-500">Books</h3>
        <p class="text-4xl font-bold">0</p>
    </div>

    <div class="bg-white shadow rounded-xl p-5">
        <h3 class="text-gray-500">Members</h3>
        <p class="text-4xl font-bold">0</p>
    </div>

    <div class="bg-white shadow rounded-xl p-5">
        <h3 class="text-gray-500">Issued</h3>
        <p class="text-4xl font-bold">0</p>
    </div>

    <div class="bg-white shadow rounded-xl p-5">
        <h3 class="text-gray-500">Returns</h3>
        <p class="text-4xl font-bold">0</p>
    </div>

</div>

<div class="mt-8 bg-white rounded-xl shadow p-6">
    <h2 class="text-xl font-bold mb-2">
        Welcome
    </h2>

    <p>
        Hello
        <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong>,
        welcome to University Library Management System.
    </p>
</div>

<?php

$content = ob_get_clean();

include("../layouts/main_layout.php");