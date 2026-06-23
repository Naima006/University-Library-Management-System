<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

/*
|--------------------------------------------------------------------------
| AUTO OVERDUE UPDATE
|--------------------------------------------------------------------------
*/

$conn->query("
    UPDATE book_issues
    SET
        status = 'overdue',
        fine_amount =
        DATEDIFF(CURDATE(), due_date) * 10
    WHERE due_date < CURDATE()
    AND return_date IS NULL
");

/*
|--------------------------------------------------------------------------
| SEARCH + FILTERS
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$allowed_limits = [10, 25, 50, 100];

$records_per_page = isset($_GET['limit'])
    ? (int)$_GET['limit']
    : 10;

if (!in_array($records_per_page, $allowed_limits)) {
    $records_per_page = 10;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $records_per_page;

$where = [];
$params = [];
$types = '';

if (($_SESSION['role'] ?? '') === 'staff') {
    $where[] = "bi.issued_by = ?";
    $params[] = $_SESSION['user_id'];
    $types .= "i";
}

if (!empty($search)) {
    $where[] = "(
        b.title LIKE ?
        OR CONCAT(m.first_name,' ',m.last_name) LIKE ?
        OR u.full_name LIKE ?
    )";

    $searchParam = "%{$search}%";

    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;

    $types .= "sss";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalIssued = $conn->query("
SELECT COUNT(*) total FROM book_issues WHERE status='issued'
")->fetch_assoc()['total'];

$totalReturned = $conn->query("
SELECT COUNT(*) total FROM book_issues WHERE status='returned'
")->fetch_assoc()['total'];

$totalOverdue = $conn->query("
SELECT COUNT(*) total FROM book_issues WHERE status='overdue'
")->fetch_assoc()['total'];

/*
|--------------------------------------------------------------------------
| MAIN QUERY
|--------------------------------------------------------------------------
*/

$sql = "
SELECT
    bi.*,
    b.title,
    CONCAT(m.first_name,' ',m.last_name) AS member_name,
    u.full_name AS staff_name
FROM book_issues bi
JOIN books b ON bi.book_id = b.book_id
JOIN members m ON bi.member_id = m.member_id
JOIN users u ON bi.issued_by = u.user_id
{$whereSql}
ORDER BY bi.issue_id DESC
LIMIT ?, ?
";

$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| COUNT (pagination)
|--------------------------------------------------------------------------
*/

$countSql = "
SELECT COUNT(*) as total
FROM book_issues bi
JOIN books b ON bi.book_id = b.book_id
JOIN members m ON bi.member_id = m.member_id
JOIN users u ON bi.issued_by = u.user_id
{$whereSql}
";

$countStmt = $conn->prepare($countSql);

if (!empty($where)) {
    $countStmt->bind_param(substr($types, 0, -2), ...array_slice($params, 0, -2));
}

$countStmt->execute();
$total_records = $countStmt->get_result()->fetch_assoc()['total'];

$total_pages = ceil($total_records / $records_per_page);

$pageTitle = "Book Issues";

ob_start();
?>

<div class="max-w-7xl mx-auto p-6">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">

        <div>
            <h1 class="text-3xl font-bold text-slate-800">Book Issues</h1>
            <p class="text-gray-500">Manage all issued books</p>
        </div>

        <a href="create.php"
           class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg shadow flex items-center gap-2">

            <i class="fas fa-plus"></i>
            Issue Book
        </a>

    </div>

    <!-- STATS -->
    <div class="grid md:grid-cols-3 gap-5 mb-6">

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500">Active Issues</p>
            <h2 class="text-3xl font-bold text-blue-600"><?= $totalIssued ?></h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500">Returned</p>
            <h2 class="text-3xl font-bold text-green-600"><?= $totalReturned ?></h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <p class="text-gray-500">Overdue</p>
            <h2 class="text-3xl font-bold text-red-600"><?= $totalOverdue ?></h2>
        </div>

    </div>

    <!-- SEARCH + FILTER (BOOK STYLE) -->
    <div class="bg-white rounded-xl shadow p-4 mb-5">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Book, Member, Staff..."
                class="md:col-span-2 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-400"
            >

            <select name="limit"
                    class="border border-gray-300 rounded-lg px-4 py-2.5">

                <?php foreach ([10,25,50,100] as $limit): ?>
                    <option value="<?= $limit ?>"
                        <?= $records_per_page == $limit ? 'selected' : '' ?>>
                        <?= $limit ?> per page
                    </option>
                <?php endforeach; ?>

            </select>

            <div class="flex gap-2">

                <button class="flex-1 bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg flex items-center justify-center gap-2">

    <i class="fas fa-search"></i>
    <span>Search</span>

</button>

                <a href="index.php"
                   class="border px-4 py-2.5 rounded-lg text-center">
                    Reset
                </a>

            </div>

        </form>

    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-800 text-white">
<tr>
    <th class="px-6 py-4 text-left">Book</th>
    <th class="px-6 py-4 text-left">Member</th>
    <th class="px-6 py-4 text-left">Issued By</th>
    <th class="px-6 py-4 text-center">Issue Date</th>
    <th class="px-6 py-4 text-center">Due Date</th>
    <th class="px-6 py-4 text-center">Return Date</th>
    <th class="px-6 py-4 text-center">Fine</th>
    <th class="px-6 py-4 text-center">Status</th>
    <th class="px-6 py-4 text-center">Actions</th>
</tr>
</thead>

                <tbody>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <?php
                        $badge = match ($row['status']) {
                            'issued' => 'bg-blue-100 text-blue-700',
                            'returned' => 'bg-green-100 text-green-700',
                            default => 'bg-red-100 text-red-700'
                        };
                        ?>

                        <tr class="border-b hover:bg-slate-50">

                            <td class="px-5 py-4 align-top">

    <div class="font-medium text-slate-800 break-words">

        <?= htmlspecialchars($row['title']) ?>

    </div>

</td>
                            <td class="px-5 py-4 align-top">

    <div class="break-words">

        <?= htmlspecialchars($row['member_name']) ?>

    </div>

</td>
                            <td class="px-6 py-4 align-top">

    <div class="break-words">

        <?= htmlspecialchars($row['staff_name']) ?>

    </div>

</td>

                        <td class="px-6 py-4 text-center whitespace-nowrap">
    <?= date('d M Y', strtotime($row['issue_date'])) ?>
</td>

<td class="px-6 py-4 text-center whitespace-nowrap">
    <?= date('d M Y', strtotime($row['due_date'])) ?>
</td>

<td class="px-6 py-4 text-center whitespace-nowrap">

    <?php if(!empty($row['return_date'])): ?>

        <?= date('d M Y', strtotime($row['return_date'])) ?>

    <?php else: ?>

        <span class="text-gray-400">-</span>

    <?php endif; ?>

</td>

<td class="px-6 py-4 text-center whitespace-nowrap">

    <?php if($row['fine_amount'] > 0): ?>

        <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm">

            ৳<?= number_format($row['fine_amount'], 2) ?>

        </span>

    <?php else: ?>

        <span class="text-gray-500">

            ৳0.00

        </span>

    <?php endif; ?>

</td>

                        <td class="px-6 py-4 text-center">

    <span class="<?= $badge ?> px-3 py-1 rounded-full text-sm whitespace-nowrap">

        <?= ucfirst($row['status']) ?>

    </span>

</td>

                            <td class="px-6 py-4">

    <div class="flex justify-center items-center">

        <?php if ($row['status'] !== 'returned'): ?>

            <a href="return.php?id=<?= $row['issue_id'] ?>"
               title="Return Book"
               class="text-green-600 hover:text-green-800 text-xl">

                <i class="fas fa-rotate-left"></i>

            </a>

        <?php else: ?>

            <span
                title="Book Returned"
                class="text-gray-400 text-xl">

                <i class="fas fa-check-circle"></i>

            </span>

        <?php endif; ?>

    </div>

</td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>
    </div>

    <!-- PAGINATION -->
    <div class="flex justify-between mt-5">

        <div>
            Page <?= $page ?> of <?= $total_pages ?>
        </div>

        <div class="flex gap-2">

            <?php if ($page > 1): ?>
                <a class="px-3 py-1 bg-gray-200 rounded"
                   href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&limit=<?= $records_per_page ?>">
                    Prev
                </a>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
                <a class="px-3 py-1 bg-gray-200 rounded"
                   href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&limit=<?= $records_per_page ?>">
                    Next
                </a>
            <?php endif; ?>

        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>