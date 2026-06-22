<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

$book_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($book_id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();

$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    header("Location: index.php?error=not_found");
    exit;
}

$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");

$pageTitle = "Edit Book";

ob_start();
?>

<div class="max-w-3xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Edit Book</h1>
            <p class="text-sm text-gray-500">
                Update the selected book information.
            </p>
        </div>

        <a href="index.php"
           class="text-sm font-medium text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left mr-1"></i>
            Back to Books
        </a>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_isbn'): ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            ISBN must contain exactly 13 digits.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate_isbn'): ?>
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            Another book already uses this ISBN.
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow p-6 md:p-8">

        <form action="update.php" method="POST" class="space-y-5">

            <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Book Title <span class="text-red-500">*</span>
                </label>

                <input
                    id="title"
                    type="text"
                    name="title"
                    required
                    minlength="2"
                    maxlength="255"
                    value="<?= htmlspecialchars($book['title']) ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
            </div>

            <div>
                <label for="author_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Author Name <span class="text-red-500">*</span>
                </label>

                <input
                    id="author_name"
                    type="text"
                    name="author_name"
                    required
                    minlength="2"
                    maxlength="255"
                    value="<?= htmlspecialchars($book['author_name']) ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
            </div>

            <div>
                <label for="isbn" class="block text-sm font-medium text-gray-700 mb-1">
                    ISBN-13 <span class="text-red-500">*</span>
                </label>

                <input
                    id="isbn"
                    type="text"
                    name="isbn"
                    required
                    maxlength="13"
                    inputmode="numeric"
                    pattern="\d{13}"
                    title="ISBN must contain exactly 13 digits."
                    value="<?= htmlspecialchars($book['isbn']) ?>"
                    oninput="this.value = this.value.replace(/\D/g, '').slice(0, 13)"
                    placeholder="Example: 9781234567890"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
            </div>

            <div>
                <label for="published_year" class="block text-sm font-medium text-gray-700 mb-1">
                    Published Year
                </label>

                <input
                    id="published_year"
                    type="number"
                    name="published_year"
                    min="1000"
                    max="<?= date('Y') ?>"
                    value="<?= htmlspecialchars($book['published_year'] ?? '') ?>"
                    placeholder="Example: 2024"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label for="total_copies" class="block text-sm font-medium text-gray-700 mb-1">
                        Total Copies <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="total_copies"
                        type="number"
                        name="total_copies"
                        required
                        min="1"
                        value="<?= $book['total_copies'] ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    >
                </div>

                <div>
                    <label for="available_copies" class="block text-sm font-medium text-gray-700 mb-1">
                        Available Copies <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="available_copies"
                        type="number"
                        name="available_copies"
                        required
                        min="0"
                        max="<?= $book['total_copies'] ?>"
                        value="<?= $book['available_copies'] ?>"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    >
                </div>

            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Category <span class="text-red-500">*</span>
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >
                    <option value="">Select a category</option>

                    <?php while ($category = $categories->fetch_assoc()): ?>
                        <option
                            value="<?= $category['category_id'] ?>"
                            <?= $category['category_id'] == $book['category_id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($category['category_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <a href="index.php"
                   class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                    Cancel
                </a>

                <button type="submit"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <i class="fas fa-save mr-1"></i>
                    Update Book
                </button>
            </div>

        </form>

    </div>

</div>

<script>
const totalCopies = document.getElementById('total_copies');
const availableCopies = document.getElementById('available_copies');

totalCopies.addEventListener('input', function () {
    availableCopies.max = this.value;

    if (parseInt(availableCopies.value || 0) > parseInt(this.value || 0)) {
        availableCopies.value = this.value;
    }
});
</script>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>