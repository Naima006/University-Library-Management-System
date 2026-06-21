<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - ULMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-blue-900 text-white flex flex-col">

        <div class="p-5 text-xl font-bold border-b border-blue-700">
            📚 ULMS Admin
        </div>

        <div class="p-4 text-sm border-b border-blue-700">
            <div class="font-semibold">
                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
            </div>
            <div class="text-blue-200 text-xs">
                <?php echo htmlspecialchars($_SESSION['email']); ?>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-2 text-sm">
            <a href="dashboard.php" class="block p-2 rounded bg-blue-700">Dashboard</a>

            <a href="users/index.php" class="block p-2 rounded hover:bg-blue-700">
                User Management
            </a>

            <a href="#" class="block p-2 rounded hover:bg-blue-700">Books</a>
            <a href="#" class="block p-2 rounded hover:bg-blue-700">Members</a>
            <a href="#" class="block p-2 rounded hover:bg-blue-700">Categories</a>
            <a href="#" class="block p-2 rounded hover:bg-blue-700">Issue Books</a>
            <a href="#" class="block p-2 rounded hover:bg-blue-700">Reports</a>
            <a href="#" class="block p-2 rounded hover:bg-blue-700">Activity Logs</a>
        </nav>

        <div class="p-4 border-t border-blue-700">
            <a href="../auth/logout.php"
               class="block text-center bg-red-500 hover:bg-red-600 p-2 rounded">
                Logout
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-6">

        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            Admin Dashboard
        </h1>

        <!-- CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-white p-5 rounded-lg shadow">
                <h2 class="text-gray-500 text-sm">Total Books</h2>
                <p class="text-2xl font-bold">0</p>
            </div>

            <div class="bg-white p-5 rounded-lg shadow">
                <h2 class="text-gray-500 text-sm">Total Members</h2>
                <p class="text-2xl font-bold">0</p>
            </div>

            <div class="bg-white p-5 rounded-lg shadow">
                <h2 class="text-gray-500 text-sm">Issued Books</h2>
                <p class="text-2xl font-bold">0</p>
            </div>

        </div>

        <!-- QUICK INFO -->
        <div class="mt-8 bg-white p-5 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-3">System Overview</h2>
            <p class="text-gray-600 text-sm">
                Welcome to the University Library Management System.
                Use the sidebar to manage books, members, issue/return operations and reports.
            </p>
        </div>

    </main>

</div>

</body>
</html>