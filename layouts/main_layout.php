<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? "ULMS";
$content = $content ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            background: #f8fafc;
        }

        .sidebar-link {
            transition: all .3s ease;
        }

        .sidebar-link:hover {
            background: #1e40af;
        }
    </style>
</head>

<body>

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white fixed left-0 top-0 h-screen">

        <div class="p-5 border-b border-slate-700">
            <h1 class="text-2xl font-bold">
                📚 ULMS
            </h1>
            <p class="text-sm text-slate-400">
                Library Management
            </p>
        </div>

        <div class="p-4">

            <div class="mb-5">
                <p class="text-sm text-slate-400">
                    Logged In As
                </p>

                <h3 class="font-semibold">
                    <?= htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                </h3>

                <span class="text-xs bg-blue-600 px-2 py-1 rounded">
                    <?= strtoupper($_SESSION['role'] ?? ''); ?>
                </span>
            </div>

            <ul class="space-y-2">

                <li>
                    <a href="dashboard.php"
                       class="sidebar-link block p-3 rounded-lg">
                        <i class="fas fa-chart-line mr-2"></i>
                        Dashboard
                    </a>
                </li>

                <?php if ($_SESSION['role'] == 'admin'): ?>

                    <li>
                        <a href="../admin/users/index.php"
                           class="sidebar-link block p-3 rounded-lg">
                            <i class="fas fa-users-cog mr-2"></i>
                            Staff Management
                        </a>
                    </li>

                <?php endif; ?>

                <li>
                    <a href="../books/index.php"
                       class="sidebar-link block p-3 rounded-lg">
                        <i class="fas fa-book mr-2"></i>
                        Books
                    </a>
                </li>

                <li>
                    <a href="../categories/index.php"
                       class="sidebar-link block p-3 rounded-lg">
                        <i class="fas fa-tags mr-2"></i>
                        Categories
                    </a>
                </li>

                <li>
                    <a href="../members/index.php"
                       class="sidebar-link block p-3 rounded-lg">
                        <i class="fas fa-user-graduate mr-2"></i>
                        Members
                    </a>
                </li>

                <li>
                    <a href="../issues/index.php"
                       class="sidebar-link block p-3 rounded-lg">
                        <i class="fas fa-exchange-alt mr-2"></i>
                        Book Issues
                    </a>
                </li>

                <li>
                    <a href="../reports/index.php"
                       class="sidebar-link block p-3 rounded-lg">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Reports
                    </a>
                </li>

                <li>
                    <a href="../activity_logs/index.php"
                       class="sidebar-link block p-3 rounded-lg">
                        <i class="fas fa-history mr-2"></i>
                        Activity Logs
                    </a>
                </li>

                <li>
                    <a href="../auth/logout.php"
                       class="block bg-red-600 hover:bg-red-700 p-3 rounded-lg mt-6">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </a>
                </li>

            </ul>

        </div>

    </aside>

    <!-- Main Content -->
    <main class="ml-64 w-full">

        <!-- Topbar -->
        <div class="bg-white shadow p-5 flex justify-between items-center">

            <h2 class="text-2xl font-bold text-slate-800">
                <?= $pageTitle ?>
            </h2>

            <div>
                <?= date("d M Y") ?>
            </div>

        </div>

        <div class="p-6">
            <?= $content ?>
        </div>

    </main>

</div>

</body>
</html>