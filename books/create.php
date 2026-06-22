<?php
session_start();
include("../config/db.php");

$pageTitle = "Add Book";

ob_start();
?>

<div class="max-w-3xl mx-auto">

    <div class="bg-white rounded-xl shadow p-5">

        <div class="border-b pb-3 mb-4">
            <h1 class="text-2xl font-bold text-slate-800">Add Book</h1>
            <p class="text-gray-500 text-sm">Enter new book details</p>
        </div>

        <form action="save.php" method="POST">

            <div class="grid gap-3">

                <div>
                    <label class="text-sm text-gray-600">Title</label>
                    <input type="text" name="title" required
                           class="w-full border rounded-lg p-2.5">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Author</label>
                    <input type="text" name="author_name" required
                           class="w-full border rounded-lg p-2.5">
                </div>

                <div>
                    <label class="text-sm text-gray-600">ISBN</label>
                    <input type="text" name="isbn" maxlength="20" required
                           class="w-full border rounded-lg p-2.5">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Published Year</label>
                    <input type="number" name="published_year"
                           class="w-full border rounded-lg p-2.5">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-gray-600">Total Copies</label>
                        <input type="number" name="total_copies" required
                               class="w-full border rounded-lg p-2.5">
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Available</label>
                        <input type="number" name="available_copies" required
                               class="w-full border rounded-lg p-2.5">
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-600">Category</label>
                    <select name="category_id" required
                            class="w-full border rounded-lg p-2.5">

                        <?php
                        $cats = $conn->query("SELECT * FROM categories ORDER BY category_name");
                        while ($cat = $cats->fetch_assoc()):
                        ?>
                            <option value="<?= $cat['category_id'] ?>">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </option>
                        <?php endwhile; ?>

                    </select>
                </div>

            </div>

            <div class="flex justify-end gap-2 mt-5 border-t pt-4">

                <a href="index.php"
                   class="px-4 py-2 border rounded-lg text-gray-600">
                    Cancel
                </a>

                <button type="submit"
                        class="px-4 py-2 bg-slate-800 text-white rounded-lg">
                    Save
                </button>

            </div>

        </form>

    </div>
</div>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>