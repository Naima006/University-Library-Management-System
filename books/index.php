<?php

session_start();
include("../config/db.php");

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

$pageTitle = "Books Modules";

ob_start();
?>

<div class="max-w-7xl mx-auto p-6">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">

        <div>
            <h1 class="text-3xl font-bold text-slate-800">Books Modules</h1>
            <p class="text-gray-500">Manage all library books</p>
        </div>

        <a href="create.php"
           class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg shadow flex items-center gap-2">

            <i class="fas fa-plus"></i>
            Add Book
        </a>

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
<!-- DELETE -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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