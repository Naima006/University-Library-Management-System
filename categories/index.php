<?php
session_start();

include("../config/db.php");

/* Admin and staff can manage categories */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$search = trim($_GET['search'] ?? '');

$allowed_limits = [10, 25, 50, 100];
$records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

if (!in_array($records_per_page, $allowed_limits)) {
    $records_per_page = 10;
}

$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($current_page < 1) {
    $current_page = 1;
}

/* Count categories */
$count_sql = "
    SELECT COUNT(*) AS total
    FROM categories
    WHERE category_name LIKE ?
";

$count_stmt = $conn->prepare($count_sql);
$search_param = "%" . $search . "%";
$count_stmt->bind_param("s", $search_param);
$count_stmt->execute();

$total_records = (int)$count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = max(1, (int)ceil($total_records / $records_per_page));

if ($current_page > $total_pages) {
    $current_page = $total_pages;
}

$offset = ($current_page - 1) * $records_per_page;

/* Get categories with number of books */
$sql = "
    SELECT
        c.category_id,
        c.category_name,
        COUNT(b.book_id) AS total_books
    FROM categories c
    LEFT JOIN books b ON c.category_id = b.category_id
    WHERE c.category_name LIKE ?
    GROUP BY c.category_id, c.category_name
    ORDER BY c.category_name ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $search_param, $records_per_page, $offset);
$stmt->execute();
$categories = $stmt->get_result();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$success_messages = [
    'created' => 'Category added successfully.',
    'updated' => 'Category updated successfully.',
    'deleted' => 'Category deleted successfully.'
];

$error_messages = [
    'required' => 'Category name is required.',
    'invalid_name' => 'Category name may contain letters, numbers, spaces, ampersands, commas, and hyphens only.',
    'duplicate' => 'A category with this name already exists.',
    'not_found' => 'Category not found.',
    'in_use' => 'This category cannot be deleted because one or more books are assigned to it.',
    'delete_failed' => 'Category could not be deleted. Please try again.',
    'update_failed' => 'Category could not be updated. Please try again.',
    'create_failed' => 'Category could not be added. Please try again.'
];

$pageTitle = "Categories";

ob_start();
?>

<div class="max-w-7xl mx-auto">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Categories</h1>
            <p class="text-sm text-gray-500">
                Organize books by academic department, subject, or course category.
            </p>
        </div>

        <a href="create.php"
           class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium">
            <i class="fas fa-folder-plus mr-2"></i>
            Add Category
        </a>
    </div>

    <?php if (isset($success_messages[$success])): ?>
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            <i class="fas fa-circle-check mr-1"></i>
            <?= htmlspecialchars($success_messages[$success]) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_messages[$error])): ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i class="fas fa-circle-exclamation mr-1"></i>
            <?= htmlspecialchars($error_messages[$error]) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow p-4 mb-5">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search category name..."
                class="md:col-span-2 border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
            >

            <select
                name="limit"
                class="border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
            >
                <?php foreach ($allowed_limits as $limit): ?>
                    <option value="<?= $limit ?>" <?= $records_per_page === $limit ? 'selected' : '' ?>>
                        <?= $limit ?> per page
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg">
                    <i class="fas fa-search mr-1"></i>
                    Search
                </button>

                <a href="index.php"
                   class="border border-gray-300 hover:bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-gray-600">
                    <tr>
                        <th class="text-left px-5 py-4">Category ID</th>
                        <th class="text-left px-5 py-4">Category Name</th>
                        <th class="text-center px-5 py-4">Books Assigned</th>
                        <th class="text-center px-5 py-4">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4 text-gray-500">
                                    #<?= (int)$category['category_id'] ?>
                                </td>

                                <td class="px-5 py-4 font-medium text-slate-800">
                                    <i class="fas fa-tag text-blue-500 mr-2"></i>
                                    <?= htmlspecialchars($category['category_name']) ?>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">
                                        <?= (int)$category['total_books'] ?>
                                        <?= (int)$category['total_books'] === 1 ? ' Book' : ' Books' ?>
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="edit.php?id=<?= (int)$category['category_id'] ?>"
                                           class="text-blue-600 hover:text-blue-800 text-xl"
                                           title="Edit Category">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>

                                        <button
                                            type="button"
                                            onclick="confirmDelete(<?= (int)$category['category_id'] ?>, <?= (int)$category['total_books'] ?>)"
                                            class="text-red-600 hover:text-red-800 text-xl"
                                            title="Delete Category"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-gray-500">
                                <i class="fas fa-tags text-3xl text-gray-300 mb-3 block"></i>
                                No categories found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <p class="text-sm text-gray-500">
            Showing page <?= $current_page ?> of <?= $total_pages ?> · <?= $total_records ?> total categories
        </p>

        <?php if ($total_pages > 1): ?>
            <div class="flex flex-wrap items-center gap-2">
                <?php
                $base_query = http_build_query([
                    'search' => $search,
                    'limit' => $records_per_page
                ]);
                ?>

                <?php if ($current_page > 1): ?>
                    <a href="?<?= $base_query ?>&page=<?= $current_page - 1 ?>"
                       class="border border-gray-300 px-3 py-2 rounded-lg text-sm hover:bg-gray-100">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </a>
                <?php endif; ?>

                <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                    <a href="?<?= $base_query ?>&page=<?= $page ?>"
                       class="px-3 py-2 rounded-lg text-sm <?= $page === $current_page
                           ? 'bg-blue-600 text-white'
                           : 'border border-gray-300 text-gray-700 hover:bg-gray-100' ?>">
                        <?= $page ?>
                    </a>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?<?= $base_query ?>&page=<?= $current_page + 1 ?>"
                       class="border border-gray-300 px-3 py-2 rounded-lg text-sm hover:bg-gray-100">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function confirmDelete(categoryId, totalBooks) {
    if (totalBooks > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Category Is In Use',
            text: 'This category has ' + totalBooks + ' assigned book(s), so it cannot be deleted.',
            confirmButtonColor: '#2563eb'
        });
        return;
    }

    Swal.fire({
        title: 'Delete Category?',
        text: 'This category will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + categoryId;
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>