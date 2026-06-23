<?php
session_start();
include("../config/db.php");

/* Admin and staff can view activity logs */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

/* Search and filter values */
$search = trim($_GET['search'] ?? '');
$action_filter = trim($_GET['action'] ?? '');
$role_filter = trim($_GET['role'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

/* Pagination */
$per_page = 10;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($current_page < 1) {
    $current_page = 1;
}

$offset = ($current_page - 1) * $per_page;

/*
    activity_logs.user_id = users.user_id
    This lets us show which logged-in admin/staff performed each operation.
*/
$where_conditions = [];
$params = [];
$types = "";

/* Search by user name, email, action, table name, or description */
if ($search !== '') {
    $where_conditions[] = "(
        u.full_name LIKE ?
        OR u.email LIKE ?
        OR al.action LIKE ?
        OR al.table_name LIKE ?
        OR al.description LIKE ?
    )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sssss";
}

/* Filter by action */
if ($action_filter !== '') {
    $where_conditions[] = "al.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}

/* Filter by role */
if ($role_filter !== '') {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

/* Filter by starting date */
if ($date_from !== '') {
    $where_conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

/* Filter by ending date */
if ($date_to !== '') {
    $where_conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_sql = "";

if (!empty($where_conditions)) {
    $where_sql = " WHERE " . implode(" AND ", $where_conditions);
}

/* Get total records for pagination */
$count_sql = "
    SELECT COUNT(*) AS total_logs
    FROM activity_logs al
    INNER JOIN users u ON al.user_id = u.user_id
    $where_sql
";

$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_logs = (int) $count_result->fetch_assoc()['total_logs'];

$total_pages = max(1, (int) ceil($total_logs / $per_page));

/* Prevent invalid page numbers */
if ($current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $per_page;
}

/* Get activity logs */
$logs_sql = "
    SELECT
        al.log_id,
        al.action,
        al.table_name,
        al.record_id,
        al.description,
        al.created_at,
        u.full_name,
        u.email,
        u.role
    FROM activity_logs al
    INNER JOIN users u ON al.user_id = u.user_id
    $where_sql
    ORDER BY al.created_at DESC, al.log_id DESC
    LIMIT ? OFFSET ?
";

$logs_stmt = $conn->prepare($logs_sql);

$log_params = $params;
$log_types = $types . "ii";

$log_params[] = $per_page;
$log_params[] = $offset;

$logs_stmt->bind_param($log_types, ...$log_params);
$logs_stmt->execute();
$logs = $logs_stmt->get_result();

/* Get action names for the action filter dropdown */
$actions_sql = "SELECT DISTINCT action FROM activity_logs ORDER BY action ASC";
$actions_result = $conn->query($actions_sql);

/* Preserve filters while changing pages */
$query_params = [
    'search' => $search,
    'action' => $action_filter,
    'role' => $role_filter,
    'date_from' => $date_from,
    'date_to' => $date_to
];

$page_query = http_build_query(array_filter(
    $query_params,
    fn($value) => $value !== ''
));

$pageTitle = "Activity Logs";

ob_start();
?>

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-slate-800">Activity Logs</h1>
        <p class="text-sm text-gray-500">
            View the history of actions performed by administrators and staff.
        </p>
    </div>

    <div class="inline-flex items-center bg-slate-100 text-slate-700 px-4 py-2.5 rounded-lg text-sm font-medium">
        <i class="fas fa-history mr-2"></i>
        Total Logs: <?= $total_logs ?>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-4 mb-5">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">

        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Search user or action..."
            class="xl:col-span-2 border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
        >

        <select name="action"
            class="border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">All Actions</option>

            <?php if ($actions_result): ?>
                <?php while ($action_row = $actions_result->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($action_row['action']) ?>"
                        <?= $action_filter === $action_row['action'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($action_row['action']) ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>

        <select name="role"
            class="border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">All Roles</option>
            <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="staff" <?= $role_filter === 'staff' ? 'selected' : '' ?>>Staff</option>
        </select>

        <input
            type="date"
            name="date_from"
            value="<?= htmlspecialchars($date_from) ?>"
            class="border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
        >

        <input
            type="date"
            name="date_to"
            value="<?= htmlspecialchars($date_to) ?>"
            class="border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
        >

        <button type="submit"
            class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-lg">
            <i class="fas fa-search mr-1"></i> Filter
        </button>

        <a href="index.php"
            class="border border-gray-300 hover:bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg text-center">
            Reset
        </a>
    </form>
</div>

<div class="bg-white rounded-xl shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-gray-600">
            <tr>
                <th class="text-left px-5 py-4">Log ID</th>
                <th class="text-left px-5 py-4">Performed By</th>
                <th class="text-left px-5 py-4">Role</th>
                <th class="text-left px-5 py-4">Action</th>
                <th class="text-left px-5 py-4">Module</th>
                <th class="text-left px-5 py-4">Record ID</th>
                <th class="text-left px-5 py-4">Description</th>
                <th class="text-left px-5 py-4">Date & Time</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200">
            <?php if ($logs && $logs->num_rows > 0): ?>
                <?php while ($log = $logs->fetch_assoc()): ?>
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-5 py-4 text-gray-500">
                            #<?= $log['log_id'] ?>
                        </td>

                        <td class="px-5 py-4">
                            <p class="font-medium text-slate-800">
                                <?= htmlspecialchars($log['full_name']) ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?= htmlspecialchars($log['email']) ?>
                            </p>
                        </td>

                        <td class="px-5 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $log['role'] === 'admin'
                                ? 'bg-purple-100 text-purple-700'
                                : 'bg-blue-100 text-blue-700' ?>">
                                <?= ucfirst(htmlspecialchars($log['role'])) ?>
                            </span>
                        </td>

                        <td class="px-5 py-4 font-medium text-slate-700">
                            <?= htmlspecialchars($log['action']) ?>
                        </td>

                        <td class="px-5 py-4 text-gray-600">
                            <?= htmlspecialchars($log['table_name'] ?? '-') ?>
                        </td>

                        <td class="px-5 py-4 text-gray-600">
                            <?= $log['record_id'] ? '#' . $log['record_id'] : '-' ?>
                        </td>

                        <td class="px-5 py-4 text-gray-600 w-72 min-w-[18rem]">
                            <?php
                                $description = $log['description'] ?? '-';
                                $descriptionLimit = 50;
                                $isLongDescription = strlen($description) > $descriptionLimit;
                                $shortDescription = $isLongDescription
                                    ? substr($description, 0, $descriptionLimit) . '...'
                                    : $description;
                            ?>

                            <div>
                                <span id="short-description-<?= $log['log_id'] ?>">
                                    <?= htmlspecialchars($shortDescription) ?>
                                </span>

                                <?php if ($isLongDescription): ?>
                                    <span id="full-description-<?= $log['log_id'] ?>" class="hidden">
                                        <?= htmlspecialchars($description) ?>
                                    </span>

                                    <button
                                        type="button"
                                        id="read-more-btn-<?= $log['log_id'] ?>"
                                        onclick="toggleDescription(<?= $log['log_id'] ?>)"
                                        class="mt-2 text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                        Read more
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="px-5 py-4 text-gray-600 whitespace-nowrap">
                            <?= date("d M Y, h:i A", strtotime($log['created_at'])) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="px-5 py-12 text-center text-gray-500">
                        <i class="fas fa-history text-3xl text-gray-300 mb-3 block"></i>
                        No activity logs found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($total_pages > 1): ?>
    <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-gray-500">
            Page <?= $current_page ?> of <?= $total_pages ?>
        </p>

        <div class="flex items-center gap-2">
            <?php if ($current_page > 1): ?>
                <a href="?<?= $page_query ?><?= $page_query !== '' ? '&' : '' ?>page=<?= $current_page - 1 ?>"
                    class="border border-gray-300 px-3 py-2 rounded-lg text-sm hover:bg-gray-100">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>

            <?php
            $max_visible = 5; // how many page numbers to show

            $start = max(1, $current_page - floor($max_visible / 2));
            $end = $start + $max_visible - 1;

            if ($end > $total_pages) {
                $end = $total_pages;
                $start = max(1, $end - $max_visible + 1);
            }
            ?>

            <?php if ($start > 1): ?>
                <a href="?<?= $page_query ?>&page=1"
                    class="px-3 py-2 border rounded-lg text-sm hover:bg-gray-100">
                    1
                </a>

                <?php if ($start > 2): ?>
                    <span class="px-2 text-gray-400">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($page = $start; $page <= $end; $page++): ?>
                <a href="?<?= $page_query ?>&page=<?= $page ?>"
                    class="px-3 py-2 rounded-lg text-sm <?= $page === $current_page
                        ? 'bg-blue-600 text-white'
                        : 'border border-gray-300 text-gray-700 hover:bg-gray-100' ?>">
                    <?= $page ?>
                </a>
            <?php endfor; ?>

            <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?>
                    <span class="px-2 text-gray-400">...</span>
                <?php endif; ?>

                <a href="?<?= $page_query ?>&page=<?= $total_pages ?>"
                    class="px-3 py-2 border rounded-lg text-sm hover:bg-gray-100">
                    <?= $total_pages ?>
                </a>
            <?php endif; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?<?= $page_query ?><?= $page_query !== '' ? '&' : '' ?>page=<?= $current_page + 1 ?>"
                    class="border border-gray-300 px-3 py-2 rounded-lg text-sm hover:bg-gray-100">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleDescription(logId) {
    const shortDescription = document.getElementById('short-description-' + logId);
    const fullDescription = document.getElementById('full-description-' + logId);
    const button = document.getElementById('read-more-btn-' + logId);

    if (fullDescription.classList.contains('hidden')) {
        shortDescription.classList.add('hidden');
        fullDescription.classList.remove('hidden');
        button.textContent = 'Show less';
    } else {
        fullDescription.classList.add('hidden');
        shortDescription.classList.remove('hidden');
        button.textContent = 'Read more';
    }
}
</script>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>