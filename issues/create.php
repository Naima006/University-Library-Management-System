<?php

session_start();
include("../config/db.php");

/*
|--------------------------------------------------------------------------
| BOOKS
|--------------------------------------------------------------------------
*/

$books = $conn->query("
SELECT *
FROM books
WHERE available_copies > 0
ORDER BY title ASC
");

/*
|--------------------------------------------------------------------------
| MEMBERS
|--------------------------------------------------------------------------
*/

$members = $conn->query("
SELECT *
FROM members
WHERE is_active = 1
ORDER BY first_name ASC
");

$pageTitle = "Issue Book";

ob_start();
?>

<div class="max-w-4xl mx-auto">

<div class="bg-white rounded-xl shadow p-6">

    <div class="mb-6">

        <h1 class="text-3xl font-bold text-slate-800">
            Issue Book
        </h1>

        <p class="text-gray-500">
            Assign a book to a library member
        </p>

    </div>

    <?php if(isset($_GET['error']) && $_GET['error']=='nostock'): ?>

        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
            No copies available for this book.
        </div>

    <?php endif; ?>

    <form action="store.php" method="POST">

        <!-- BOOK -->

        <div class="mb-5">

            <label class="block mb-2 font-medium text-slate-700">
                Select Book
            </label>

            <select
                name="book_id"
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-3">

                <option value="">
                    Choose Book
                </option>

                <?php while($book = $books->fetch_assoc()): ?>

                    <option value="<?= $book['book_id'] ?>">

                        <?= htmlspecialchars($book['title']) ?>
                        (<?= $book['available_copies'] ?> Available)

                    </option>

                <?php endwhile; ?>

            </select>

        </div>

        <!-- MEMBER -->

        <div class="mb-5">

            <label class="block mb-2 font-medium text-slate-700">
                Select Member
            </label>

            <select
                name="member_id"
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-3">

                <option value="">
                    Choose Member
                </option>

                <?php while($member = $members->fetch_assoc()): ?>

                    <option value="<?= $member['member_id'] ?>">

                        <?= htmlspecialchars(
                            $member['first_name'].' '.$member['last_name']
                        ) ?>

                        (<?= htmlspecialchars($member['student_id']) ?>)

                    </option>

                <?php endwhile; ?>

            </select>

        </div>

        <!-- ISSUE DATE -->

        <div class="mb-5">

            <label class="block mb-2 font-medium text-slate-700">
                Issue Date
            </label>

            <input
                type="date"
                name="issue_date"
                required
                value="<?= date('Y-m-d') ?>"
                class="w-full border border-gray-300 rounded-lg px-4 py-3">

        </div>

        <!-- DUE DATE -->

        <div class="mb-6">

            <label class="block mb-2 font-medium text-slate-700">
                Due Date
            </label>

            <input
                type="date"
                name="due_date"
                required
                value="<?= date('Y-m-d', strtotime('+14 days')) ?>"
                class="w-full border border-gray-300 rounded-lg px-4 py-3">

        </div>

        <!-- BUTTONS -->

        <div class="flex gap-3">

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg">

                <i class="fas fa-save mr-2"></i>
                Issue Book

            </button>

            <a href="index.php"
               class="bg-slate-300 hover:bg-slate-400 text-slate-800 px-6 py-3 rounded-lg">

                Cancel

            </a>

        </div>

    </form>

</div>

</div>

<?php

$content = ob_get_clean();

include("../layouts/main_layout.php");
?>
