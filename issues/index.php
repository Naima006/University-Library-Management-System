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

<div id="printArea" class="max-w-7xl mx-auto p-6">

    <div class="print-header hidden mb-8 pb-4 border-b-2 border-slate-800">
        <div class="text-center">
            <h1 class="text-3xl font-bold uppercase tracking-wider text-slate-900">University Library System</h1>
            <div class="flex justify-between items-center mt-6 text-xs text-gray-500 font-medium">
                <p><strong>Report Title:</strong> Book Issues Inventory Log</p>
                <p><strong>Generated On:</strong> <?= date('d M Y') ?></p>
            </div>
        </div>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6 no-print">

        <div>
            <h1 class="text-3xl font-bold text-slate-800">Book Issues</h1>
            <p class="text-gray-500">Manage all issued books</p>
        </div>

        <div class="flex gap-3 no-print">

            <button
                onclick="printIssuesReport()"
                type="button"
                class="bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-lg shadow flex items-center gap-2">

                <i class="fas fa-print"></i>
                Print PDF

            </button>

            <a href="create.php"
               class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg shadow flex items-center gap-2">

                <i class="fas fa-plus"></i>
                Issue Book

            </a>

        </div>

    </div>

    <div class="grid md:grid-cols-3 gap-5 mb-6 print-stats">

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-gray-500">Active Issues</p>
            <h2 class="text-3xl font-bold text-blue-600"><?= $totalIssued ?></h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-gray-500">Returned</p>
            <h2 class="text-3xl font-bold text-green-600"><?= $totalReturned ?></h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-gray-500">Overdue</p>
            <h2 class="text-3xl font-bold text-red-600"><?= $totalOverdue ?></h2>
        </div>

    </div>

    <div class="bg-white rounded-xl shadow p-4 mb-5 no-print">

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

    <div class="bg-white rounded-xl shadow overflow-hidden print-table-container">

        <div class="overflow-x-auto print-overflow-override">

            <table class="min-w-full print-table">

                <thead class="bg-slate-800 text-white print-thead">
                    <tr>
                        <th class="px-4 py-3 text-left col-book">Book</th>
                        <th class="px-4 py-3 text-left col-member">Member</th>
                        <th class="px-4 py-3 text-left col-staff">Issued By</th>
                        <th class="px-4 py-3 text-center col-date">Issue Date</th>
                        <th class="px-4 py-3 text-center col-date">Due Date</th>
                        <th class="px-4 py-3 text-center col-date">Return Date</th>
                        <th class="px-4 py-3 text-right col-fine">Fine</th>
                        <th class="px-4 py-3 text-center col-status">Status</th>
                        <th class="px-6 py-4 text-center no-print">Actions</th>
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

                        <tr class="border-b hover:bg-slate-50 print-row">

                            <td class="px-4 py-3 align-top">
                                <div class="font-medium text-slate-800 break-words line-clamp-override">
                                    <?= htmlspecialchars($row['title']) ?>
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="break-words">
                                    <?= htmlspecialchars($row['member_name']) ?>
                                </div>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <div class="break-words">
                                    <?= htmlspecialchars($row['staff_name']) ?>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-center whitespace-nowrap align-top">
                                <?= date('d M Y', strtotime($row['issue_date'])) ?>
                            </td>

                            <td class="px-4 py-3 text-center whitespace-nowrap align-top">
                                <?= date('d M Y', strtotime($row['due_date'])) ?>
                            </td>

                            <td class="px-4 py-3 text-center whitespace-nowrap align-top">
                                <?php if(!empty($row['return_date'])): ?>
                                    <?= date('d M Y', strtotime($row['return_date'])) ?>
                                <?php else: ?>
                                    <span class="text-gray-400 print-dash">-</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap font-medium align-top">
                                <?php if($row['fine_amount'] > 0): ?>
                                    <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs print-fine">
                                        ৳<?= number_format($row['fine_amount'], 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-500 print-fine-zero">
                                        ৳0.00
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-center align-top">
                                <span class="<?= $badge ?> px-2 py-0.5 rounded-full text-xs whitespace-nowrap print-status-badge">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>

                            <td class="px-6 py-4 no-print">
                                <div class="flex justify-center items-center">
                                    <?php if ($row['status'] !== 'returned'): ?>
                                        <a href="return.php?id=<?= $row['issue_id'] ?>"
                                           title="Return Book"
                                           class="text-green-600 hover:text-green-800 text-xl">
                                            <i class="fas fa-rotate-left"></i>
                                        </a>
                                    <?php else: ?>
                                        <span title="Book Returned"
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

    <div class="flex justify-between mt-5 no-print">

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

<style>
@media print {
    /* Hide scrollbars and unneeded components globally */
    body * {
        visibility: hidden;
    }

    #printArea,
    #printArea * {
        visibility: visible;
    }

    /* Standard A4 full page alignment setup */
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0 !important;
        margin: 0 !important;
    }

    .no-print {
        display: none !important;
    }

    .print-header {
        display: block !important;
    }

    /* Stats container row alignment configuration */
    .print-stats {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 1.25rem !important;
        margin-bottom: 1.5rem !important;
    }

    .print-stats div {
        border: 1px solid #e2e8f0 !important;
        padding: 1rem !important;
        border-radius: 0.375rem !important;
        background: #fff !important;
    }

    .print-stats p {
        color: #475569 !important;
        font-size: 0.875rem !important;
    }

    .print-stats h2 {
        font-size: 1.5rem !important;
        margin-top: 0.25rem !important;
        color: #000 !important;
    }

    /* Complete correction of table overflow and widths */
    .print-table-container {
        border: 1px solid #94a3b8 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
    }

    .print-overflow-override {
        overflow: visible !important;
    }

    .print-table {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }

    /* Proportional Column Allocation for exact fitting on A4 layout */
    .col-book   { width: 26%; }
    .col-member { width: 16%; }
    .col-staff  { width: 12%; }
    .col-date   { width: 11%; }
    .col-fine   { width: 12%; }
    .col-status { width: 12%; }

    /* Elegant high contrast headers without ink drain background */
    .print-thead {
        background: #f1f5f9 !important;
    }

    .print-thead th {
        color: #0f172a !important;
        font-weight: 700 !important;
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        border-bottom: 2px solid #0f172a !important;
        padding: 0.5rem 0.375rem !important;
    }

    /* Table Cells standard crisp look */
    .print-row {
        border-bottom: 1px solid #cbd5e1 !important;
        page-break-inside: avoid !important;
    }

    .print-row td {
        padding: 0.5rem 0.375rem !important;
        font-size: 0.75rem !important;
        color: #000 !important;
        line-height: 1.25 !important;
    }

    .line-clamp-override {
        display: block !important;
        overflow: visible !important;
        white-space: normal !important;
    }

    /* Plain typography representations for elements inside cells */
    .print-dash {
        color: #94a3b8 !important;
    }

    .print-fine {
        color: #b91c1c !important;
        background: transparent !important;
        font-weight: 600 !important;
        padding: 0 !important;
    }

    .print-fine-zero {
        color: #64748b !important;
    }

    .print-status-badge {
        background: transparent !important;
        color: #000 !important;
        font-weight: 600 !important;
        padding: 0 !important;
        text-transform: uppercase !important;
        font-size: 0.7rem !important;
    }
}
</style>

<script>
function printIssuesReport() {
    document.title = "Book_Issues_Report_" + "<?= date('d_M_Y') ?>";
    window.print();
}
</script>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>
