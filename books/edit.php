<?php

session_start();
include("../config/db.php");

$id = intval($_GET['id']);

$stmt = $conn->prepare("
SELECT *
FROM books
WHERE book_id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows == 0)
{
    die("Book not found");
}

$book = $result->fetch_assoc();

$categories = $conn->query("
SELECT *
FROM categories
ORDER BY category_name
");

?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Book</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100">

<div class="max-w-3xl mx-auto mt-10">

    <div class="bg-white shadow rounded-xl p-6">

        <h1 class="text-3xl font-bold mb-6">
            Edit Book
        </h1>

        <form action="update.php" method="POST">

            <input
                type="hidden"
                name="book_id"
                value="<?= $book['book_id'] ?>">

            <div class="mb-4">

                <label>Title</label>

                <input
                    type="text"
                    name="title"
                    value="<?= htmlspecialchars($book['title']) ?>"
                    class="w-full border p-3 rounded-lg"
                    required>

            </div>

            <div class="mb-4">

                <label>Author</label>

                <input
                    type="text"
                    name="author_name"
                    value="<?= htmlspecialchars($book['author_name']) ?>"
                    class="w-full border p-3 rounded-lg"
                    required>

            </div>

            <div class="mb-4">

                <label>ISBN</label>

                <input
                    type="text"
                    name="isbn"
                    value="<?= htmlspecialchars($book['isbn']) ?>"
                    class="w-full border p-3 rounded-lg"
                    required>

            </div>

            <div class="mb-4">

                <label>Published Year</label>

                <input
                    type="number"
                    name="published_year"
                    value="<?= $book['published_year'] ?>"
                    class="w-full border p-3 rounded-lg">

            </div>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label>Total Copies</label>

                    <input
                        type="number"
                        name="total_copies"
                        value="<?= $book['total_copies'] ?>"
                        class="w-full border p-3 rounded-lg">
                </div>

                <div>
                    <label>Available Copies</label>

                    <input
                        type="number"
                        name="available_copies"
                        value="<?= $book['available_copies'] ?>"
                        class="w-full border p-3 rounded-lg">
                </div>

            </div>

            <div class="mt-4">

                <label>Category</label>

                <select
                    name="category_id"
                    class="w-full border p-3 rounded-lg">

                    <?php while($cat = $categories->fetch_assoc()): ?>

                        <option
                            value="<?= $cat['category_id'] ?>"
                            <?= $cat['category_id'] == $book['category_id'] ? 'selected' : '' ?>>

                            <?= htmlspecialchars($cat['category_name']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <button
                class="mt-6 bg-blue-600 text-white px-5 py-3 rounded-lg">

                Update Book

            </button>

        </form>

    </div>

</div>

</body>
</html>