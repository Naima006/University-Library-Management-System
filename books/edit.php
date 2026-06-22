<?php
session_start();
include("../config/db.php");

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM books WHERE book_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

$pageTitle = "Edit Book";

ob_start();
?>

<div class="max-w-3xl mx-auto">

    <div class="bg-white rounded-xl shadow p-5">

        <div class="border-b pb-3 mb-4">
            <h1 class="text-2xl font-bold">Edit Book</h1>
            <p class="text-gray-500 text-sm">Update book details</p>
        </div>

        <form action="update.php" method="POST">

            <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">

            <div class="grid gap-3">

                <input class="border p-2.5 rounded-lg w-full"
                       name="title"
                       value="<?= htmlspecialchars($book['title']) ?>">

                <input class="border p-2.5 rounded-lg w-full"
                       name="author_name"
                       value="<?= htmlspecialchars($book['author_name']) ?>">

                <input class="border p-2.5 rounded-lg w-full"
                       name="isbn"
                       value="<?= htmlspecialchars($book['isbn']) ?>">

                <input class="border p-2.5 rounded-lg w-full"
                       name="published_year"
                       value="<?= $book['published_year'] ?>">

            </div>

            <div class="grid grid-cols-2 gap-3 mt-3">

                <input class="border p-2.5 rounded-lg"
                       name="total_copies"
                       value="<?= $book['total_copies'] ?>">

                <input class="border p-2.5 rounded-lg"
                       name="available_copies"
                       value="<?= $book['available_copies'] ?>">

            </div>

            <div class="mt-3">

                <select name="category_id"
                        class="border p-2.5 rounded-lg w-full">

                    <?php
                    $cats = $conn->query("SELECT * FROM categories");
                    while ($cat = $cats->fetch_assoc()):
                    ?>

                        <option value="<?= $cat['category_id'] ?>"
                            <?= $cat['category_id'] == $book['category_id'] ? 'selected' : '' ?>>

                            <?= $cat['category_name'] ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="flex justify-end gap-2 mt-5 border-t pt-4">

                <a href="index.php"
                   class="px-4 py-2 border rounded-lg">
                    Cancel
                </a>

                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                    Update
                </button>

            </div>

        </form>

    </div>

</div>

<?php
$content = ob_get_clean();
include("../layouts/main_layout.php");
?>