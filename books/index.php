<?php

session_start();
include("../config/db.php");

$import_report = $_SESSION['book_import_report'] ?? null;
unset($_SESSION['book_import_report']);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$search = trim($_GET['search'] ?? '');

$allowed_limits = [10, 25, 50, 100];

$records_per_page = isset($_GET['limit'])
    ? (int)$_GET['limit']
    : 10;

if (!in_array($records_per_page, $allowed_limits)) {
    $records_per_page = 10;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $records_per_page;

/* ================= MAIN QUERY ================= */

$sql = "
SELECT b.*, c.category_name
FROM books b
LEFT JOIN categories c ON b.category_id = c.category_id
";

if (!empty($search)) {
    $sql .= "
    WHERE b.title LIKE ?
    OR b.author_name LIKE ?
    OR b.isbn LIKE ?
    OR c.category_name LIKE ?
    ";
}

$sql .= " ORDER BY b.book_id DESC LIMIT ?, ?";

$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $searchParam = "%{$search}%";

    $stmt->bind_param(
        "ssssii",
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam,
        $offset,
        $records_per_page
    );
} else {
    $stmt->bind_param("ii", $offset, $records_per_page);
}

$stmt->execute();
$result = $stmt->get_result();

/* ================= COUNT QUERY ================= */

$countSql = "
SELECT COUNT(*) AS total
FROM books b
LEFT JOIN categories c ON b.category_id = c.category_id
";

if (!empty($search)) {
    $countSql .= "
    WHERE b.title LIKE ?
    OR b.author_name LIKE ?
    OR b.isbn LIKE ?
    OR c.category_name LIKE ?
    ";
}

$countStmt = $conn->prepare($countSql);

if (!empty($search)) {
    $searchParam = "%{$search}%";

    $countStmt->bind_param(
        "ssss",
        $searchParam,
        $searchParam,
        $searchParam,
        $searchParam
    );
}

$countStmt->execute();

$total_records = $countStmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

$pageTitle = "Books";

ob_start();
?>

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

        <div>
            <h1 class="text-xl font-bold text-slate-800">Manage Books</h1>
            <p class="text-sm text-gray-500">Add, search, update, and track library books.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <button type="button"
                    onclick="openImportModal()"
                    class="bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 px-5 py-3 rounded-lg shadow-sm flex items-center justify-center gap-2">
                <i class="fas fa-file-csv text-emerald-600"></i>
                Import Books
            </button>

           <a href="create.php"
            class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 font-medium text-white
                    bg-gradient-to-r from-cyan-500 to-blue-600
                    shadow-md shadow-cyan-500/25
                    hover:from-cyan-400 hover:to-blue-500
                    transition">
                <i class="fas fa-plus"></i>
                Add New Book
            </a>
        </div>

    </div>

    <!-- FILTER + SEARCH PANEL -->
    <div class="bg-white rounded-xl shadow p-4 mb-5">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <!-- SEARCH -->
            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Title, Author, ISBN, Category..."
                class="md:col-span-2 border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
            >

            <!-- LIMIT -->
            <select name="limit"
                    class="border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400">

                <?php foreach ([10,25,50,100] as $limit): ?>
                    <option value="<?= $limit ?>"
                        <?= $records_per_page == $limit ? 'selected' : '' ?>>
                        <?= $limit ?> per page
                    </option>
                <?php endforeach; ?>

            </select>

            <!-- BUTTONS -->
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
    <!-- TOAST -->
    <?php if (isset($_GET['success'])): ?>

        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">

            <?php
            $messages = [
                'created' => 'Book added successfully.',
                'updated' => 'Book updated successfully.',
                'deleted' => 'Book deleted successfully.'
            ];

            $key = $_GET['success'];

            echo htmlspecialchars($messages[$key] ?? 'Operation completed successfully.');
            ?>

        </div>

    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <?php
            $errors = [
                'book_currently_issued' => 'This book cannot be deleted because it is currently issued or overdue.',
                'book_not_found' => 'Book not found.',
                'delete_failed' => 'Book could not be deleted.',
                'invalid_data' => 'Invalid book information submitted.'
            ];

            echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred.');
            ?>
        </div>
    <?php endif; ?>

    <?php if (is_array($import_report)): ?>
        <?php
            $importedCount = (int) ($import_report['imported'] ?? 0);
            $skippedCount = (int) ($import_report['skipped'] ?? 0);
            $importErrors = $import_report['errors'] ?? [];
            $isFatal = !empty($import_report['fatal']);
        ?>
        <div class="mb-5 rounded-lg border px-4 py-3 text-sm <?= $importedCount > 0 ? 'border-green-200 bg-green-50 text-green-800' : 'border-amber-200 bg-amber-50 text-amber-800' ?>">
            <?php if ($isFatal): ?>
                <p class="font-semibold">Import could not be completed.</p>
            <?php else: ?>
                <p class="font-semibold">
                    Import finished: <?= $importedCount ?> book(s) added
                    <?php if ($skippedCount > 0): ?>
                        , <?= $skippedCount ?> row(s) skipped
                    <?php endif; ?>.
                </p>
            <?php endif; ?>

            <?php if (!empty($importErrors)): ?>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-xs bg-white/70 rounded-md overflow-hidden">
                        <thead class="bg-white/80">
                            <tr>
                                <th class="text-left px-3 py-2">Row</th>
                                <th class="text-left px-3 py-2">ISBN</th>
                                <th class="text-left px-3 py-2">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($importErrors, 0, 20) as $importError): ?>
                                <tr class="border-t border-black/5">
                                    <td class="px-3 py-2"><?= htmlspecialchars((string) $importError['row']) ?></td>
                                    <td class="px-3 py-2"><?= htmlspecialchars((string) $importError['isbn']) ?></td>
                                    <td class="px-3 py-2"><?= htmlspecialchars((string) $importError['reason']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($importErrors) > 20): ?>
                        <p class="mt-2">Showing the first 20 issues. Fix these rows and import the remaining records.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>


    <!-- TABLE -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left">Title</th>
                        <th class="px-6 py-4 text-left">Author</th>
                        <th class="px-6 py-4 text-left">ISBN</th>
                        <th class="px-6 py-4 text-left">Category</th>
                        <th class="px-6 py-4 text-center">Copies</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($book = $result->fetch_assoc()): ?>

                        <tr class="hover:bg-slate-50 transition">

                            <td class="px-6 py-4 font-medium">
                                <?= htmlspecialchars($book['title']) ?>
                            </td>

                            <td class="px-6 py-4">
                                <?= htmlspecialchars($book['author_name']) ?>
                            </td>

                            <td class="px-6 py-4">
                                <?= htmlspecialchars($book['isbn']) ?>
                            </td>

                            <td class="px-6 py-4">
                                <?= htmlspecialchars($book['category_name']) ?>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">
                                    <?= $book['available_copies'] ?>/<?= $book['total_copies'] ?>
                                </span>
                            </td>

                            <td class="px-6 py-4">

                                <div class="flex justify-center items-center gap-4">

                                    <a href="edit.php?id=<?= $book['book_id'] ?>"
                                       class="text-blue-600 hover:text-blue-800 text-xl">
                                        <i class="fas fa-pen-to-square"></i>
                                    </a>

                                    <a href="#"
                                       onclick="deleteBook(<?= $book['book_id'] ?>)"
                                       class="text-red-600 hover:text-red-800 text-xl">
                                        <i class="fas fa-trash"></i>
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-500">
                            No books found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>
    </div>

    <!-- PAGINATION -->
    <div class="flex justify-between items-center mt-5">

        <div class="text-gray-600">
            Page <strong><?= $page ?></strong> of <strong><?= $total_pages ?></strong>
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
<!-- IMPORT MODAL -->
<div id="importModal"
     class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-slate-900/50" onclick="closeImportModal()"></div>

    <div class="relative z-10 min-h-full flex items-start sm:items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-slate-200">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Import Books from CSV</h2>
                    <p class="text-sm text-slate-500 mt-1">Add many titles at once without using the single-book form.</p>
                </div>
                <button type="button"
                        onclick="closeImportModal()"
                        class="text-slate-400 hover:text-slate-700 text-xl"
                        aria-label="Close import dialog">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="import.php" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-4">
                <ol class="text-sm text-slate-600 space-y-2 list-decimal list-inside">
                    <li>Create the categories in <strong>Categories</strong> first. Names in the CSV must match exactly.</li>
                    <li>Download the template and keep the header row unchanged.</li>
                    <li>In Excel, set the ISBN column to <strong>Text</strong> so 13-digit values are not converted to scientific notation.</li>
                    <li>Upload the saved <code>.csv</code> file. Valid rows are imported; invalid rows are skipped with reasons.</li>
                </ol>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Required columns</p>
                    <p class="text-sm text-slate-700 font-mono break-all">
                        title, author_name, isbn, published_year, total_copies, available_copies, category_name
                    </p>
                    <ul class="mt-3 text-sm text-slate-600 space-y-1">
                        <li>ISBN: 13 digits. Hyphens are removed automatically.</li>
                        <li>published_year: optional 4-digit year.</li>
                        <li>available_copies: optional. If blank, it is set equal to total_copies.</li>
                        <li>Maximum 300 data rows and 1 MB per upload.</li>
                    </ul>
                </div>

                <a href="import_template.csv"
                   class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-800">
                    <i class="fas fa-download"></i>
                    Download CSV template
                </a>

                <div>
                    <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-1">
                        CSV file <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="csv_file"
                        type="file"
                        name="csv_file"
                        accept=".csv,text/csv"
                        required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-white"
                    >
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-slate-200">
                    <button type="button"
                            onclick="closeImportModal()"
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg">
                        <i class="fas fa-file-import mr-1"></i>
                        Import Books
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DELETE -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeImportModal();
    }
});

function deleteBook(id) {
    Swal.fire({
        title: 'Delete Book?',
        html: "This permanently removes the book from the system.<br><br><span class='text-sm text-amber-600 font-medium'>⚠️ Note: Books currently issued to members cannot be deleted.</span>",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + id;
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include(__DIR__ . "/../layouts/main_layout.php");
?>