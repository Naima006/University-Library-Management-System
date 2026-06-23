<?php
session_start();

include("../config/db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id <= 0) {
    header("Location: index.php?error=not_found");
    exit;
}

$stmt = $conn->prepare("
    SELECT category_id, category_name
    FROM categories
    WHERE category_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $category_id);
$stmt->execute();

$category = $stmt->get_result()->fetch_assoc();

if (!$category) {
    header("Location: index.php?error=not_found");
    exit;
}

$error = $_GET['error'] ?? '';

$error_messages = [
    'required' => 'Category name is required.',
    'invalid_name' => 'Category name may contain letters, numbers, spaces, ampersands, commas, and hyphens only.',
    'duplicate' => 'A category with this name already exists.',
    'update_failed' => 'Category could not be updated. Please try again.'
];

$pageTitle = "Edit Category";

ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Edit Category</h1>
            <p class="text-sm text-gray-500">
                Update the category name used for organizing books.
            </p>
        </div>

        <a href="index.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            <i class="fas fa-arrow-left mr-1"></i>
            Back to Categories
        </a>
    </div>

    <div class="bg-white rounded-xl shadow p-6 md:p-8">

        <?php if (isset($error_messages[$error])): ?>
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="fas fa-circle-exclamation mr-1"></i>
                <?= htmlspecialchars($error_messages[$error]) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="update.php" class="space-y-5" autocomplete="off">
            <input type="hidden" name="category_id" value="<?= (int)$category['category_id'] ?>">

            <div>
                <label for="category_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Category Name <span class="text-red-500">*</span>
                </label>

                <input
                    id="category_name"
                    type="text"
                    name="category_name"
                    required
                    minlength="2"
                    maxlength="100"
                    value="<?= htmlspecialchars($category['category_name']) ?>"
                    title="Use letters, numbers, spaces, ampersands, commas, and hyphens only."
                    oninput="this.value = this.value.replace(/[^A-Za-z0-9 &, -]/g, '')"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="index.php"
                   class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                    Cancel
                </a>

                <button type="submit"
                        class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-1"></i>
                    Update Category
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>