<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? "ULMS";
$content = $content ?? "";

$baseUrl = "/university-library-management-system";

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function activeSidebarLink($path, $currentPath) {
    return str_contains($currentPath, $path)
        ? "bg-blue-700 text-white shadow"
        : "text-slate-200 hover:bg-blue-700";
}
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

<div class="min-h-screen bg-slate-900 lg:flex">

    <!-- Mobile overlay -->
    <div id="overlay"
         class="fixed inset-0 bg-black/50 hidden lg:hidden z-40"
         onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="w-64 bg-slate-900 text-white fixed lg:absolute lg:left-0 lg:top-0 lg:bottom-0
            left-0 top-0 h-full min-h-screen
            transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50">

        <div class="p-5 border-b border-slate-700 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">
                    📚 ULMS
                </h1>
                <p class="text-sm text-slate-400">
                    Library Management
                </p>
            </div>

            <!-- Close button (mobile only) -->
            <button
                type="button"
                class="lg:hidden text-white text-xl"
                onclick="toggleSidebar()"
                aria-label="Close navigation menu">

                <i class="fas fa-times"></i>
            </button>
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
                    <a href="<?= $baseUrl ?>/<?= htmlspecialchars($_SESSION['role'] ?? '') ?>/dashboard.php"
                    class="sidebar-link block p-3 rounded-lg <?= activeSidebarLink('/' . ($_SESSION['role'] ?? '') . '/dashboard.php', $currentPath) ?>">
                        <i class="fas fa-chart-line mr-2"></i>
                        Dashboard
                    </a>
                </li>

                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <li>
                        <a href="<?= $baseUrl ?>/admin/users/index.php"
                        class="sidebar-link block p-3 rounded-lg <?= activeSidebarLink('/admin/users/', $currentPath) ?>">
                            <i class="fas fa-users-cog mr-2"></i>
                            User Management
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="<?= $baseUrl ?>/books/index.php"
                    class="sidebar-link block p-3 rounded-lg <?= activeSidebarLink('/books/', $currentPath) ?>">
                        <i class="fas fa-book mr-2"></i>
                        Books
                    </a>
                </li>

                <li>
                    <a href="<?= $baseUrl ?>/categories/index.php"
                    class="sidebar-link block p-3 rounded-lg <?= activeSidebarLink('/categories/', $currentPath) ?>">
                        <i class="fas fa-tags mr-2"></i>
                        Categories
                    </a>
                </li>

                <li>
                    <a href="<?= $baseUrl ?>/members/index.php"
                    class="sidebar-link block p-3 rounded-lg <?= activeSidebarLink('/members/', $currentPath) ?>">
                        <i class="fas fa-user-graduate mr-2"></i>
                        Members
                    </a>
                </li>

                <li>
                    <a href="<?= $baseUrl ?>/issues/index.php"
                    class="sidebar-link block p-3 rounded-lg <?= activeSidebarLink('/issues/', $currentPath) ?>">
                        <i class="fas fa-exchange-alt mr-2"></i>
                        Book Issues
                    </a>
                </li>

                <li>
                    <a href="<?= $baseUrl ?>/reports/index.php"
                    class="sidebar-link block p-3 rounded-lg <?= activeSidebarLink('/reports/', $currentPath) ?>">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Reports
                    </a>
                </li>

                <li>
                    <a href="<?= $baseUrl ?>/activity_logs/index.php"
                    class="sidebar-link block p-3 rounded-lg <?= activeSidebarLink('/activity_logs/', $currentPath) ?>">
                        <i class="fas fa-history mr-2"></i>
                        Activity Logs
                    </a>
                </li>

                <li>
                    <a href="<?= $baseUrl ?>/auth/logout.php"
                    class="sidebar-link block p-3 rounded-lg <?= activeSidebarLink('/auth/logout.php', $currentPath) ?>">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </a>
                </li>

            </ul>

        </div>

    </aside>

    <!-- Main Content -->
    <main class="w-full lg:ml-64 bg-slate-50 min-h-screen">

        <!-- Topbar -->
        <div class="bg-white shadow p-4 lg:p-5 flex justify-between items-center gap-3">

            <!-- Mobile menu button -->
            <button
                type="button"
                class="lg:hidden text-slate-800 text-xl shrink-0"
                onclick="toggleSidebar()"
                aria-label="Open navigation menu">

                <i class="fas fa-bars"></i>
            </button>

            <h2 class="text-lg sm:text-xl lg:text-2xl font-bold text-slate-800 truncate">
                <?= $pageTitle ?>
            </h2>

            <div class="text-sm text-gray-600 hidden sm:block whitespace-nowrap">
                <?= date("d M Y") ?>
            </div>

        </div>

        <div class="p-4 lg:p-6">
            <?= $content ?>
        </div>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>

</body>
</html>