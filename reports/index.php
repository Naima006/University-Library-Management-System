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

<div class="max-w-7xl mx-auto p-6">

<!-- HEADER -->
<div class="mb-6">
    <h1 class="text-3xl font-bold text-slate-800">Reports Dashboard</h1>
    <p class="text-gray-500">Library analytics and statistics</p>
</div>

<!-- SUMMARY CARDS -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">

    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Books</p>
        <h2 class="text-3xl font-bold text-blue-600"><?= $totalBooks ?></h2>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Members</p>
        <h2 class="text-3xl font-bold text-green-600"><?= $totalMembers ?></h2>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Issues</p>
        <h2 class="text-3xl font-bold text-orange-600"><?= $totalIssues ?></h2>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Fine Amount</p>
        <h2 class="text-3xl font-bold text-red-600">৳<?= number_format($totalFine,2) ?></h2>
    </div>

</div>

<!-- STATUS CARDS -->
<div class="grid md:grid-cols-3 gap-5 mb-6">

    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-gray-500">Active Issues</p>
        <h2 class="text-3xl font-bold text-blue-600"><?= $issuedBooks ?></h2>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-gray-500">Returned Books</p>
        <h2 class="text-3xl font-bold text-green-600"><?= $returnedBooks ?></h2>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-gray-500">Overdue Books</p>
        <h2 class="text-3xl font-bold text-red-600"><?= $overdueBooks ?></h2>
    </div>

</div>

<!-- RECENT TRANSACTIONS -->
<div class="bg-white rounded-xl shadow overflow-hidden mb-6">

    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold text-slate-800">Recent Transactions</h3>
    </div>

    <div class="overflow-x-auto">

        <table class="w-full">

            <thead class="bg-slate-800 text-white">
            <tr>
                <th class="px-6 py-4 text-left">Book</th>
                <th class="px-6 py-4 text-left">Member</th>
                <th class="px-6 py-4 text-center">Issue Date</th>
                <th class="px-6 py-4 text-center">Status</th>
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

                <tr class="border-b hover:bg-slate-50">

                    <td class="px-6 py-4"><?= htmlspecialchars($row['title']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($row['member_name']) ?></td>
                    <td class="px-6 py-4 text-center">
                        <?= date('d M Y', strtotime($row['issue_date'])) ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="<?= $badge ?> px-3 py-1 rounded-full text-sm">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>
</div>

<!-- MOST BORROWED BOOKS -->
<div class="bg-white rounded-xl shadow overflow-hidden mb-6">

    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold text-slate-800">Most Borrowed Books</h3>
    </div>

    <div class="overflow-x-auto">

        <table class="w-full">

            <thead class="bg-slate-800 text-white">
            <tr>
                <th class="px-6 py-4 text-left">Book Title</th>
                <th class="px-6 py-4 text-center">Borrow Count</th>
            </tr>
            </thead>

            <tbody>

            <?php while($book = $popularBooks->fetch_assoc()): ?>

                <tr class="border-b hover:bg-slate-50">
                    <td class="px-6 py-4"><?= htmlspecialchars($book['title']) ?></td>
                    <td class="px-6 py-4 text-center"><?= $book['borrow_count'] ?></td>
                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</div>

</div>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>