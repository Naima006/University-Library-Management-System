<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../config/db.php");

/*
|--------------------------------------------------------------------------
| GLOBAL REPORT DASHBOARD (NO ROLE DIFFERENCE)
|--------------------------------------------------------------------------
| Admin and Staff now see EXACT SAME DATA
*/

$totalBooks = $conn->query("
SELECT COUNT(*) total FROM books
")->fetch_assoc()['total'];

$totalMembers = $conn->query("
SELECT COUNT(*) total FROM members
")->fetch_assoc()['total'];

$totalIssues = $conn->query("
SELECT COUNT(*) total FROM book_issues
")->fetch_assoc()['total'];

$totalFine = $conn->query("
SELECT COALESCE(SUM(fine_amount),0) total FROM book_issues
")->fetch_assoc()['total'];

$issuedBooks = $conn->query("
SELECT COUNT(*) total FROM book_issues WHERE status='issued'
")->fetch_assoc()['total'];

$returnedBooks = $conn->query("
SELECT COUNT(*) total FROM book_issues WHERE status='returned'
")->fetch_assoc()['total'];

$overdueBooks = $conn->query("
SELECT COUNT(*) total FROM book_issues WHERE status='overdue'
")->fetch_assoc()['total'];

/*
|--------------------------------------------------------------------------
| MOST BORROWED BOOKS
|--------------------------------------------------------------------------
*/

$popularBooks = $conn->query("
SELECT
    b.title,
    COUNT(*) AS borrow_count
FROM book_issues bi
JOIN books b ON bi.book_id = b.book_id
GROUP BY bi.book_id
ORDER BY borrow_count DESC
LIMIT 10
");

/*
|--------------------------------------------------------------------------
| RECENT TRANSACTIONS
|--------------------------------------------------------------------------
*/

$recentSql = "
SELECT
    bi.*,
    b.title,
    CONCAT(m.first_name,' ',m.last_name) AS member_name
FROM book_issues bi
JOIN books b ON bi.book_id = b.book_id
JOIN members m ON bi.member_id = m.member_id
ORDER BY bi.issue_id DESC
LIMIT 10
";

$recentTransactions = $conn->query($recentSql);

$pageTitle = "Reports Dashboard";

ob_start();
?>

<div id="printArea" class="max-w-7xl mx-auto p-6">

    <div class="print-header hidden mb-8 pb-4 border-b-2 border-slate-800">
        <div class="text-center">
            <h1 class="text-3xl font-bold uppercase tracking-wider text-slate-900">University Library System</h1>
            <div class="flex justify-between items-center mt-6 text-xs text-gray-500 font-medium">
                <p><strong>Report Title:</strong> Library Analytics & Reports Dashboard</p>
                <p><strong>Generated On:</strong> <?= date('d M Y') ?></p>
            </div>
        </div>
    </div>

    <div class="flex justify-between items-center mb-6 no-print">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Reports Dashboard</h1>
            <p class="text-gray-500">Library analytics and statistics</p>
        </div>
        <div>
            <button
                onclick="printLibraryReport()"
                type="button"
                class="bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-lg shadow flex items-center gap-2">
                <i class="fas fa-print"></i>
                Print Report
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6 print-stats-four">

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-sm text-gray-500">Books</p>
            <h2 class="text-3xl font-bold text-blue-600"><?= $totalBooks ?></h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-sm text-gray-500">Members</p>
            <h2 class="text-3xl font-bold text-green-600"><?= $totalMembers ?></h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-sm text-gray-500">Issues</p>
            <h2 class="text-3xl font-bold text-orange-600"><?= $totalIssues ?></h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-sm text-gray-500">Fine Amount</p>
            <h2 class="text-3xl font-bold text-red-600">৳<?= number_format($totalFine,2) ?></h2>
        </div>

    </div>

    <div class="grid md:grid-cols-3 gap-5 mb-6 print-stats-three">

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-gray-500">Active Issues</p>
            <h2 class="text-3xl font-bold text-blue-600"><?= $issuedBooks ?></h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-gray-500">Returned Books</p>
            <h2 class="text-3xl font-bold text-green-600"><?= $returnedBooks ?></h2>
        </div>

        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-gray-500">Overdue Books</p>
            <h2 class="text-3xl font-bold text-red-600"><?= $overdueBooks ?></h2>
        </div>

    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden mb-6 print-table-container">

        <div class="px-6 py-4 border-b print-section-title">
            <h3 class="font-semibold text-slate-800">Recent Transactions</h3>
        </div>

        <div class="overflow-x-auto print-overflow-override">

            <table class="w-full print-table">

                <thead class="bg-slate-800 text-white print-thead">
                    <tr>
                        <th class="px-4 py-3 text-left col-recent-book">Book</th>
                        <th class="px-4 py-3 text-left col-recent-member">Member</th>
                        <th class="px-4 py-3 text-center col-recent-date">Issue Date</th>
                        <th class="px-4 py-3 text-center col-recent-status">Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while($row = $recentTransactions->fetch_assoc()): ?>

                        <?php
                        $badge =
                            $row['status'] === 'returned'
                            ? 'bg-green-100 text-green-700'
                            : ($row['status'] === 'overdue'
                            ? 'bg-red-100 text-red-700'
                            : 'bg-blue-100 text-blue-700');
                        ?>

                        <tr class="border-b hover:bg-slate-50 print-row">
                            <td class="px-4 py-3 align-top break-words line-clamp-override">
                                <?= htmlspecialchars($row['title']) ?>
                            </td>
                            <td class="px-4 py-3 align-top break-words">
                                <?= htmlspecialchars($row['member_name']) ?>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap align-top">
                                <?= date('d M Y', strtotime($row['issue_date'])) ?>
                            </td>
                            <td class="px-4 py-3 text-center align-top">
                                <span class="<?= $badge ?> px-2 py-0.5 rounded-full text-xs whitespace-nowrap print-status-badge">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden mb-6 print-table-container page-break-before-auto">

        <div class="px-6 py-4 border-b print-section-title">
            <h3 class="font-semibold text-slate-800">Most Borrowed Books</h3>
        </div>

        <div class="overflow-x-auto print-overflow-override">

            <table class="w-full print-table">

                <thead class="bg-slate-800 text-white print-thead">
                    <tr>
                        <th class="px-4 py-3 text-left col-popular-book">Book Title</th>
                        <th class="px-4 py-3 text-center col-popular-count">Borrow Count</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while($book = $popularBooks->fetch_assoc()): ?>

                        <tr class="border-b hover:bg-slate-50 print-row">
                            <td class="px-4 py-3 align-top break-words line-clamp-override">
                                <?= htmlspecialchars($book['title']) ?>
                            </td>
                            <td class="px-4 py-3 text-center align-top font-semibold">
                                <?= $book['borrow_count'] ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    #printArea,
    #printArea * {
        visibility: visible;
    }

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

    .print-section-title {
        background-color: #f8fafc !important;
        border-bottom: 1px solid #cbd5e1 !important;
        padding: 0.75rem 1rem !important;
    }

    .print-section-title h3 {
        color: #0f172a !important;
        font-size: 1rem !important;
    }

    /* Print Grid Framework Setup for Metric Blocks */
    .print-stats-four, .print-stats-three {
        display: grid !important;
        gap: 1rem !important;
        margin-bottom: 1.5rem !important;
    }

    .print-stats-four {
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }

    .print-stats-three {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }

    .print-stats-four div, .print-stats-three div {
        border: 1px solid #e2e8f0 !important;
        padding: 0.875rem !important;
        border-radius: 0.375rem !important;
        background: #fff !important;
    }

    .print-stats-four p, .print-stats-three p {
        color: #475569 !important;
        font-size: 0.85rem !important;
    }

    .print-stats-four h2, .print-stats-three h2 {
        font-size: 1.5rem !important;
        margin-top: 0.25rem !important;
        color: #000 !important;
    }

    /* Table Wrapping & Container Rules */
    .print-table-container {
        border: 1px solid #94a3b8 !important;
        border-radius: 0.375rem !important;
        box-shadow: none !important;
        background: transparent !important;
        page-break-inside: avoid !important;
    }

    .print-overflow-override {
        overflow: visible !important;
    }

    .print-table {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }

    /* Strict Proportional Width Allocations */
    .col-recent-book   { width: 45%; }
    .col-recent-member { width: 25%; }
    .col-recent-date   { width: 15%; }
    .col-recent-status { width: 15%; }

    .col-popular-book  { width: 80%; }
    .col-popular-count { width: 20%; }

    /* High-contrast accessible headers */
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
        padding: 0.5rem 0.5rem !important;
    }

    .print-row {
        border-bottom: 1px solid #cbd5e1 !important;
        page-break-inside: avoid !important;
    }

    .print-row td {
        padding: 0.5rem 0.5rem !important;
        font-size: 0.75rem !important;
        color: #000 !important;
        line-height: 1.3 !important;
    }

    .line-clamp-override {
        display: block !important;
        overflow: visible !important;
        white-space: normal !important;
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
function printLibraryReport() {
    document.title = "Library_Management_System_Report_" + "<?= date('d_M_Y') ?>";
    window.print();
}
</script>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>