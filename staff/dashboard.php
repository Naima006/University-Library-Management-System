<?php

session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../auth/login.php");
    exit;
}

/* ================= DASHBOARD COUNTS ================= */

$booksCount = 0;
$availableBooksCount = 0;
$membersCount = 0;
$issuedCount = 0;
$returnedCount = 0;

/* Total book titles */
$booksResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM books
");

if ($booksResult) {
    $booksCount = (int) $booksResult->fetch_assoc()['total'];
}

/* Total available copies */
$availableBooksResult = $conn->query("
    SELECT COALESCE(SUM(available_copies), 0) AS total
    FROM books
");

if ($availableBooksResult) {
    $availableBooksCount = (int) $availableBooksResult->fetch_assoc()['total'];
}

/* Active members */
$membersResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM members
    WHERE is_active = 1
    AND is_deleted = 0
");

if ($membersResult) {
    $membersCount = (int) $membersResult->fetch_assoc()['total'];
}

/* Currently issued books */
$issuedResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM book_issues
    WHERE status = 'issued'
");

if ($issuedResult) {
    $issuedCount = (int) $issuedResult->fetch_assoc()['total'];
}

/* Returned books */
$returnResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM book_issues
    WHERE status = 'returned'
");

if ($returnResult) {
    $returnedCount = (int) $returnResult->fetch_assoc()['total'];
}

/* Recent issue records */
$recentIssues = $conn->query("
    SELECT
        bi.issue_id,
        bi.issue_date,
        bi.due_date,
        bi.status,
        b.title,
        m.first_name,
        m.last_name,
        m.student_id
    FROM book_issues bi
    INNER JOIN books b ON bi.book_id = b.book_id
    INNER JOIN members m ON bi.member_id = m.member_id
    ORDER BY bi.issue_id DESC
    LIMIT 5
");

/* Overdue books */
$overdueBooksCount = 0;

$overdueResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM book_issues
    WHERE status = 'issued'
    AND due_date < CURDATE()
");

if ($overdueResult) {
    $overdueBooksCount = (int) $overdueResult->fetch_assoc()['total'];
}

/* Book availability percentage */
$totalBookCopies = $availableBooksCount + $issuedCount;

$availabilityPercentage = 0;

if ($totalBookCopies > 0) {
    $availabilityPercentage = round(($availableBooksCount / $totalBookCopies) * 100);
}

$pageTitle = "Staff Dashboard";

ob_start();
?>

<div class="space-y-6">

    <!-- WELCOME BANNER -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-cyan-800 to-blue-700 p-6 sm:p-8 text-white shadow-lg">
        <div class="relative z-10">
            <p class="text-sm text-cyan-100">Library Operations Panel</p>

            <h1 class="mt-2 text-2xl sm:text-3xl font-bold">
                Welcome back, <?= htmlspecialchars($_SESSION['full_name']); ?>!
            </h1>

            <p class="mt-2 max-w-2xl text-sm sm:text-base text-cyan-100">
                Manage daily book circulation, member records, and library activities efficiently.
            </p>

            <div class="mt-5 flex flex-wrap gap-3">
                <a href="../issues/create.php"
                   class="inline-flex items-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                    <i class="fas fa-plus mr-2"></i>
                    Issue Book
                </a>

                <a href="../issues/index.php"
                   class="inline-flex items-center rounded-lg border border-cyan-200/50 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/20">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Manage Issues
                </a>
            </div>
        </div>

        <i class="fas fa-book-reader absolute -right-5 -bottom-8 text-[170px] text-white/10"></i>
    </div>

    <!-- STATISTIC CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

        <div class="rounded-2xl border border-blue-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Books</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800"><?= $booksCount ?></p>
                    <p class="mt-2 text-xs text-gray-400">Titles in the catalog</p>
                </div>

                <div class="rounded-xl bg-blue-100 p-3 text-blue-600">
                    <i class="fas fa-book text-xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active Members</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800"><?= $membersCount ?></p>
                    <p class="mt-2 text-xs text-gray-400">Registered active members</p>
                </div>

                <div class="rounded-xl bg-emerald-100 p-3 text-emerald-600">
                    <i class="fas fa-user-graduate text-xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Issued Books</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800"><?= $issuedCount ?></p>
                    <p class="mt-2 text-xs text-gray-400">Books currently borrowed</p>
                </div>

                <div class="rounded-xl bg-amber-100 p-3 text-amber-600">
                    <i class="fas fa-book-reader text-xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Overdue Books</p>
                    <p class="mt-2 text-3xl font-bold text-slate-800"><?= $overdueBooksCount ?></p>
                    <p class="mt-2 text-xs text-gray-400">Books past their due date</p>
                </div>

                <div class="rounded-xl bg-rose-100 p-3 text-rose-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- CHARTS -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 rounded-2xl bg-white p-5 sm:p-6 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Book Circulation Summary</h2>
                    <p class="text-sm text-gray-500">Overview of current library transactions</p>
                </div>

                <div class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
                    Current Data
                </div>
            </div>

            <div class="mt-5 h-72">
                <canvas id="staffCirculationChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 sm:p-6 shadow-sm border border-gray-100">
            <h2 class="text-lg font-bold text-slate-800">Book Availability</h2>
            <p class="text-sm text-gray-500">Available copies and issued books</p>

            <div class="mt-5 h-52">
                <canvas id="staffAvailabilityChart"></canvas>
            </div>

            <div class="mt-5">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Available copies</span>
                    <span class="font-semibold text-slate-800"><?= $availabilityPercentage ?>%</span>
                </div>

                <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-200">
                    <div class="h-full rounded-full bg-blue-600"
                         style="width: <?= $availabilityPercentage ?>%"></div>
                </div>

                <p class="mt-3 text-xs text-gray-500">
                    <?= $availableBooksCount ?> copies are currently available.
                </p>
            </div>
        </div>

    </div>

    <!-- RECENT ACTIVITY + QUICK ACTIONS -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <div class="xl:col-span-2 rounded-2xl bg-white shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-5 sm:p-6 border-b border-gray-100">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Recent Book Issues</h2>
                    <p class="text-sm text-gray-500">Latest book borrowing records</p>
                </div>

                <a href="../issues/index.php"
                   class="text-sm font-medium text-blue-600 hover:text-blue-800">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Book</th>
                            <th class="px-5 py-3">Member</th>
                            <th class="px-5 py-3">Due Date</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <?php if ($recentIssues && $recentIssues->num_rows > 0): ?>
                            <?php while ($issue = $recentIssues->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4 font-medium text-slate-700">
                                        <?= htmlspecialchars($issue['title']) ?>
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="font-medium text-slate-700">
                                            <?= htmlspecialchars($issue['first_name'] . ' ' . $issue['last_name']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?= htmlspecialchars($issue['student_id']) ?>
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-gray-600 whitespace-nowrap">
                                        <?= date("d M Y", strtotime($issue['due_date'])) ?>
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $issue['status'] === 'issued'
                                            ? 'bg-amber-100 text-amber-700'
                                            : 'bg-emerald-100 text-emerald-700' ?>">
                                            <?= ucfirst(htmlspecialchars($issue['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-gray-500">
                                    <i class="fas fa-book-open text-3xl text-gray-300 mb-3 block"></i>
                                    No book issue records found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 sm:p-6 shadow-sm border border-gray-100">
            <h2 class="text-lg font-bold text-slate-800">Quick Actions</h2>
            <p class="mt-1 text-sm text-gray-500">Frequently used tasks</p>

            <div class="mt-5 space-y-3">
                <a href="../issues/create.php"
                   class="flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50 p-4 text-blue-700 hover:bg-blue-100">
                    <span class="flex items-center font-medium">
                        <i class="fas fa-plus-circle mr-3"></i>
                        Issue a Book
                    </span>
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>

                <a href="../issues/index.php"
                   class="flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50 p-4 text-amber-700 hover:bg-amber-100">
                    <span class="flex items-center font-medium">
                        <i class="fas fa-exchange-alt mr-3"></i>
                        Manage Issues
                    </span>
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>

                <a href="../members/index.php"
                   class="flex items-center justify-between rounded-xl border border-purple-100 bg-purple-50 p-4 text-purple-700 hover:bg-purple-100">
                    <span class="flex items-center font-medium">
                        <i class="fas fa-user-graduate mr-3"></i>
                        Manage Members
                    </span>
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>

                <a href="../activity_logs/index.php"
                   class="flex items-center justify-between rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-emerald-700 hover:bg-emerald-100">
                    <span class="flex items-center font-medium">
                        <i class="fas fa-history mr-3"></i>
                        Activity Logs
                    </span>
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const staffCirculationChart = document.getElementById('staffCirculationChart');

new Chart(staffCirculationChart, {
    type: 'bar',
    data: {
        labels: ['Currently Issued', 'Returned Books', 'Overdue Books'],
        datasets: [{
            label: 'Books',
            data: [
                <?= $issuedCount ?>,
                <?= $returnedCount ?>,
                <?= $overdueBooksCount ?>
            ],
            backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                },
                grid: {
                    color: '#e5e7eb'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

const staffAvailabilityChart = document.getElementById('staffAvailabilityChart');

new Chart(staffAvailabilityChart, {
    type: 'doughnut',
    data: {
        labels: ['Available Copies', 'Issued Books'],
        datasets: [{
            data: [<?= $availableBooksCount ?>, <?= $issuedCount ?>],
            backgroundColor: ['#2563eb', '#f59e0b'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 16
                }
            }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>