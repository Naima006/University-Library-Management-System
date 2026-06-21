<?php
session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $search_safe = $conn->real_escape_string($search);

    $sql = "SELECT user_id, full_name, email, phone, role, is_active
            FROM users
            WHERE full_name LIKE '%$search_safe%'
               OR email LIKE '%$search_safe%'
            ORDER BY user_id DESC";
} else {
    $sql = "SELECT user_id, full_name, email, phone, role, is_active
            FROM users
            ORDER BY user_id DESC";
}

$users = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - ULMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">

    <!-- Sidebar -->
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
            <a href="../dashboard.php" class="block p-2 rounded hover:bg-blue-700">
                Dashboard
            </a>

            <a href="index.php" class="block p-2 rounded bg-blue-700">
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
            <a href="../../auth/logout.php"
               class="block text-center bg-red-500 hover:bg-red-600 p-2 rounded">
                Logout
            </a>
        </div>

    </aside>

    <!-- Main content -->
    <main class="flex-1 p-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
                <p class="text-sm text-gray-500">
                    Create and manage staff accounts.
                </p>
            </div>

            <a href="create.php"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                + Add Staff Account
            </a>
        </div>

        <!-- Search -->
        <div class="bg-white rounded-lg shadow p-4 mb-5">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <input
                    type="text"
                    name="search"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search by name or email..."
                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >

                <button type="submit"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-lg">
                    Search
                </button>

                <a href="index.php"
                   class="border border-gray-300 hover:bg-gray-100 text-gray-700 px-5 py-2 rounded-lg text-center">
                    Reset
                </a>
            </form>
        </div>

        <!-- Users table -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">

            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-5 py-3">ID</th>
                        <th class="text-left px-5 py-3">Full Name</th>
                        <th class="text-left px-5 py-3">Email</th>
                        <th class="text-left px-5 py-3">Phone</th>
                        <th class="text-left px-5 py-3">Role</th>
                        <th class="text-left px-5 py-3">Status</th>
                        <th class="text-left px-5 py-3">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">

                    <?php if ($users && $users->num_rows > 0): ?>
                        <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <?php echo $user['user_id']; ?>
                                </td>

                                <td class="px-5 py-4 font-medium text-gray-800">
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </td>

                                <td class="px-5 py-4 text-gray-600">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>

                                <td class="px-5 py-4 text-gray-600">
                                    <?php echo htmlspecialchars($user['phone'] ?? '-'); ?>
                                </td>

                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium
                                        <?php echo $user['role'] === 'admin'
                                            ? 'bg-purple-100 text-purple-700'
                                            : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <?php if ($user['is_active']): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-5 py-4">
                                    <?php if ($user['role'] === 'staff'): ?>
                                        <span class="text-gray-400 text-xs">
                                            Actions coming next
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">
                                            Protected admin
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-500">
                                No users found.
                            </td>
                        </tr>
                    <?php endif; ?>

                </tbody>
            </table>

        </div>

    </main>

</div>

</body>
</html>