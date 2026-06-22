<?php

session_start();

include("../config/db.php");

$categories =
$conn->query("
SELECT *
FROM categories
ORDER BY category_name
");

?>

<!DOCTYPE html>
<html>
<head>

<title>Add Book</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-slate-100">

<div class="max-w-3xl mx-auto p-8">

    <div class="bg-white shadow rounded-xl p-6">

        <h1 class="text-3xl font-bold mb-6">
            Add Book
        </h1>

        <form action="save.php" method="POST">

            <div class="mb-4">

                <label>
                    Title
                </label>

                <input
                type="text"
                name="title"
                required
                class="w-full border p-3 rounded-lg">

            </div>

            <div class="mb-4">

                <label>
                    Author
                </label>

                <input
                type="text"
                name="author_name"
                required
                class="w-full border p-3 rounded-lg">

            </div>

            <div class="mb-4">

                <label>
                    ISBN
                </label>

                <input
                type="text"
                name="isbn"
                maxlength="13"
                required
                class="w-full border p-3 rounded-lg">

            </div>

            <div class="mb-4">

                <label>
                    Published Year
                </label>

                <input
                type="number"
                name="published_year"
                class="w-full border p-3 rounded-lg">

            </div>

            <div class="grid grid-cols-2 gap-4">

                <div>

                    <label>
                        Total Copies
                    </label>

                    <input
                    type="number"
                    name="total_copies"
                    required
                    class="w-full border p-3 rounded-lg">

                </div>

                <div>

                    <label>
                        Available Copies
                    </label>

                    <input
                    type="number"
                    name="available_copies"
                    required
                    class="w-full border p-3 rounded-lg">

                </div>

            </div>

            <div class="mb-4">
    <label class="block mb-2 font-medium">
        Category
    </label>

    <select
        name="category_id"
        required
        class="w-full border p-3 rounded-lg">


        <?php
        $categories = $conn->query("
            SELECT *
            FROM categories
            ORDER BY category_name ASC
        ");

        while($cat = $categories->fetch_assoc()):
        ?>

            <option value="<?= $cat['category_id']; ?>">
                <?= htmlspecialchars($cat['category_name']); ?>
            </option>

        <?php endwhile; ?>

    </select>
</div>

            <div class="flex justify-end gap-4">

                <a
                href="index.php"
                class="px-4 py-2 border rounded-lg">
                    Cancel
                </a>

                <button
                type="submit"
                class="px-4 py-2 bg-slate-800 text-white rounded-lg">
                    Save
                </button>

            </div>

        </form>

</body>
</html>