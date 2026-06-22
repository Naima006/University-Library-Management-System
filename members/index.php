<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$sql = "SELECT * FROM members WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (
        student_id LIKE ?
        OR first_name LIKE ?
        OR last_name LIKE ?
        OR department LIKE ?
        OR email LIKE ?
    )";

    $searchValue = "%$search%";
    $params = [$searchValue, $searchValue, $searchValue, $searchValue, $searchValue];
    $types = "sssss";
}

if ($statusFilter === 'active') {
    $sql .= " AND is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $sql .= " AND is_active = 0";
}

$sql .= " ORDER BY member_id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$members = $stmt->get_result();

$pageTitle = "Member Management";

ob_start();
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Manage Members</h1>
        <p class="text-sm text-gray-500">
            Add, search, update, and manage library members.
        </p>
    </div>

    <a href="create.php"
       class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium">
        <i class="fas fa-user-plus mr-2"></i>
        Add Member
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        <?php
        $messages = [
            'created' => 'Member added successfully.',
            'updated' => 'Member information updated successfully.',
            'deactivated' => 'Member has been marked as inactive.',
            'activated' => 'Member has been reactivated successfully.'
        ];

        echo htmlspecialchars($messages[$_GET['success']] ?? 'Operation completed successfully.');
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?php
        $errors = [
            'duplicate_student_id' => 'This student ID already exists.',
            'invalid_request' => 'Invalid request.',
            'cannot_activate' => 'Only an admin can reactivate a member.'
        ];

        echo htmlspecialchars($errors[$_GET['error']] ?? 'Something went wrong. Please try again.');
        ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow p-4 mb-5">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Student ID, name, department, email..."
            class="md:col-span-2 border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
        >

        <select name="status"
                class="border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Members</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
        </select>

        <div class="flex gap-2">
            <button type="submit"
                    class="flex-1 bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg">
                <i class="fas fa-search mr-1"></i> Search
            </button>

            <a href="index.php"
               class="border border-gray-300 hover:bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-gray-600">
            <tr>
                <th class="text-left px-5 py-4">Student ID</th>
                <th class="text-left px-5 py-4">Member</th>
                <th class="text-left px-5 py-4">Department</th>
                <th class="text-left px-5 py-4">Contact</th>
                <th class="text-left px-5 py-4">Status</th>
                <th class="text-center px-5 py-4">Actions</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200">
            <?php if ($members->num_rows > 0): ?>
                <?php while ($member = $members->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">
                            <?= htmlspecialchars($member['student_id']) ?>
                        </td>

                        <td class="px-5 py-4">
                            <p class="font-medium text-slate-800">
                                <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?= htmlspecialchars($member['email'] ?: 'No email provided') ?>
                            </p>
                        </td>

                        <td class="px-5 py-4 text-gray-600">
                            <?= htmlspecialchars($member['department'] ?: '-') ?>
                        </td>

                        <td class="px-5 py-4 text-gray-600">
                            <?= htmlspecialchars($member['phone'] ?: '-') ?>
                        </td>

                        <td class="px-5 py-4">
                            <?php if ($member['is_active']): ?>
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
                            <div class="flex items-center justify-center gap-2">

                                <a href="edit.php?id=<?= $member['member_id'] ?>"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-100 text-blue-700 hover:bg-blue-200">
                                    <i class="fas fa-pen mr-1"></i> Edit
                                </a>

                                <?php if ($member['is_active']): ?>
                                    <a href="delete.php?id=<?= $member['member_id'] ?>"
                                       onclick="return confirm('Mark this member as inactive? This will not permanently delete their record.');"
                                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-red-100 text-red-700 hover:bg-red-200">
                                        <i class="fas fa-user-slash mr-1"></i> Deactivate
                                    </a>
                                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                                    <a href="toggle_status.php?id=<?= $member['member_id'] ?>"
                                       onclick="return confirm('Reactivate this member?');"
                                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-green-100 text-green-700 hover:bg-green-200">
                                        <i class="fas fa-user-check mr-1"></i> Reactivate
                                    </a>
                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-gray-500">
                        No members found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>