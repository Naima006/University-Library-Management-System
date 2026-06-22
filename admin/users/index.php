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

$pageTitle = "User Management";

ob_start();
?>

<!-- HEADER -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Manage User Accounts</h1>
        <p class="text-sm text-gray-500">
            Create, edit, activate and deactivate staff accounts.
        </p>
    </div>

    <a href="create.php"
       class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium">
        <i class="fas fa-user-plus mr-2"></i>
        Add Staff Account
    </a>
</div>

<!-- SUCCESS MESSAGE -->
<?php if (isset($_GET["success"])): ?>
    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        <?php
            if ($_GET["success"] === "staff_created") echo "Staff account created successfully.";
            if ($_GET["success"] === "user_updated") echo "User updated successfully.";
            if ($_GET["success"] === "user_deactivated") echo "User deactivated successfully.";
            if ($_GET["success"] === "user_activated") echo "User activated successfully.";
        ?>
    </div>
<?php endif; ?>

<!-- SEARCH -->
<div class="bg-white rounded-xl shadow p-4 mb-5">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <input
            type="text"
            name="search"
            value="<?php echo htmlspecialchars($search); ?>"
            placeholder="Search by name or email..."
            class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
        >

        <button type="submit"
                class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-lg">
            <i class="fas fa-search mr-1"></i>
            Search
        </button>

        <a href="index.php"
           class="border border-gray-300 hover:bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg text-center">
            Reset
        </a>
    </form>
</div>

<!-- TABLE -->
<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-gray-600">
            <tr>
                <th class="text-left px-5 py-4">ID</th>
                <th class="text-left px-5 py-4">Full Name</th>
                <th class="text-left px-5 py-4">Email</th>
                <th class="text-left px-5 py-4">Phone</th>
                <th class="text-left px-5 py-4">Role</th>
                <th class="text-left px-5 py-4">Status</th>
                <th class="text-left px-5 py-4">Actions</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200">
            <?php if ($users && $users->num_rows > 0): ?>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50">

                        <td class="px-5 py-4 text-gray-600">
                            <?php echo $user['user_id']; ?>
                        </td>

                        <td class="px-5 py-4 font-medium text-slate-800">
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

                        <!-- ACTIONS -->
                        <td class="px-5 py-4 space-x-2">

                            <!-- EDIT -->
                            <a href="edit.php?id=<?= $user['user_id'] ?>"
                               class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                                <i class="fas fa-pen mr-1"></i> Edit
                            </a>

                            <?php if ($user['is_active']): ?>

                                <!-- DEACTIVATE -->
                                <a href="deactivate.php?id=<?= $user['user_id'] ?>"
                                   onclick="return confirm('Deactivate this user?')"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                                    <i class="fas fa-user-slash mr-1"></i> Deactivate
                                </a>

                            <?php else: ?>

                                <!-- ACTIVATE -->
                                <a href="activate.php?id=<?= $user['user_id'] ?>"
                                    onclick="return confirm('Reactivate this member?');"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-green-100 text-green-700 hover:bg-green-200">
                                    <i class="fas fa-user-check mr-1"></i> Activate
                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-gray-500">
                        No users found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include("../../layouts/main_layout.php");
?>