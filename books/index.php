<?php

session_start();
include("../config/db.php");
$search = trim($_GET['search'] ?? '');

$allowed_limits = [10, 25, 50, 100];

$records_per_page =
isset($_GET['limit'])
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


$sql = "
SELECT
    b.*,
    c.category_name
FROM books b
LEFT JOIN categories c
ON b.category_id = c.category_id
";

if (!empty($search)) {

    $sql .= "
    WHERE
        b.title LIKE ?
        OR b.author_name LIKE ?
        OR b.isbn LIKE ?
        OR c.category_name LIKE ?
    ";
}

$sql .= "
ORDER BY b.book_id DESC
LIMIT ?, ?
";

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

    $stmt->bind_param(
        "ii",
        $offset,
        $records_per_page
    );
}

$stmt->execute();
$result = $stmt->get_result();
$countSql = "
SELECT COUNT(*) AS total
FROM books b
LEFT JOIN categories c
ON b.category_id = c.category_id
";

if (!empty($search)) {

    $countSql .= "
    WHERE
        b.title LIKE ?
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

$total_records =
$countStmt
->get_result()
->fetch_assoc()['total'];

$total_pages =
ceil($total_records / $records_per_page);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Books Modules</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="bg-slate-100">

<div class="max-w-7xl mx-auto p-6">

    <!-- Header -->

    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">

        <div>
            <h1 class="text-3xl font-bold text-slate-800">
                Books Modules
            </h1>

            <p class="text-gray-500">
                Manage all library books
            </p>
        </div>

        <a href="create.php"
           class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg shadow flex items-center gap-2">

            <i class="fas fa-plus"></i>
            Add Book

        </a>

    </div>

    <!-- Search -->

    <form method="GET" class="mb-6">

        <div class="relative">

            <i class="fas fa-search absolute left-4 top-4 text-gray-400"></i>

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search by Title, Author, ISBN or Category..."
                class="w-full pl-12 pr-4 py-3 border rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

        </div>

    </form>
    <div class="flex justify-between items-center mb-4">

    <div>

        <form method="GET" class="flex items-center gap-2">

            <input
                type="hidden"
                name="search"
                value="<?= htmlspecialchars($search) ?>">

            <label class="text-gray-600">
                Show
            </label>

            <select
                name="limit"
                onchange="this.form.submit()"
                class="border rounded-lg px-3 py-2">

                <option value="10"
                    <?= $records_per_page == 10 ? 'selected' : '' ?>>
                    10
                </option>

                <option value="25"
                    <?= $records_per_page == 25 ? 'selected' : '' ?>>
                    25
                </option>

                <option value="50"
                    <?= $records_per_page == 50 ? 'selected' : '' ?>>
                    50
                </option>

                <option value="100"
                    <?= $records_per_page == 100 ? 'selected' : '' ?>>
                    100
                </option>

            </select>

            <span class="text-gray-600">
                entries
            </span>

        </form>

    </div>

</div>

    <!-- Table -->

    <div class="bg-white rounded-xl shadow overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-800 text-white">

                    <tr>

                        <th class="px-6 py-4 text-left">
                            Title
                        </th>

                        <th class="px-6 py-4 text-left">
                            Author
                        </th>

                        <th class="px-6 py-4 text-left">
                            ISBN
                        </th>

                        <th class="px-6 py-4 text-left">
                            Category
                        </th>

                        <th class="px-6 py-4 text-center">
                            Copies
                        </th>

                        <th class="px-6 py-4 text-center">
                            Actions
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-gray-200">

                <?php if($result->num_rows > 0): ?>

                    <?php while($book = $result->fetch_assoc()): ?>

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

                                <?= $book['available_copies'] ?>
                                /
                                <?= $book['total_copies'] ?>

                            </span>

                        </td>

                        <td class="px-6 py-4">

                            <div class="flex justify-center gap-4">

                                <!-- Edit -->

                                <a href="edit.php?id=<?= $book['book_id'] ?>"
                                   title="Edit Book"
                                   class="text-blue-600 hover:text-blue-800 text-xl">

                                    <i class="fas fa-pen-to-square"></i>

                                </a>

                                <!-- Delete -->

                                <a href="#"
                                   onclick="deleteBook(<?= $book['book_id'] ?>)"
                                   title="Delete Book"
                                   class="text-red-600 hover:text-red-800 text-xl">

                                    <i class="fas fa-trash"></i>

                                </a>

                            </div>

                        </td>

                    </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6"
                            class="text-center py-12 text-gray-500">

                            <i class="fas fa-book text-5xl mb-3 block"></i>

                            No books found.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

            <div class="flex justify-between items-center mt-4 text-gray-600">

<?php

$start_record =
$total_records > 0
? $offset + 1
: 0;

$end_record =
min(
    $offset + $records_per_page,
    $total_records
);

?>

<div>

Showing

<strong><?= $start_record ?></strong>

to

<strong><?= $end_record ?></strong>

of

<strong><?= $total_records ?></strong>

books

</div>

</div>


            <div class="flex justify-between items-center mt-6">

    <div class="text-gray-600">

        Showing Page

        <strong><?= $page ?></strong>

        of

        <strong><?= $total_pages ?></strong>

    </div>

    <div class="flex gap-2">

        <?php if($page > 1): ?>

            <a
           href="?search=<?= urlencode($search) ?>&limit=<?= $records_per_page ?>&page=<?= $page + 1 ?>"
            class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">

                Previous

            </a>

        <?php endif; ?>

        <?php for($i=1; $i <= $total_pages; $i++): ?>

            <a
            href="?search=<?= urlencode($search) ?>&limit=<?= $records_per_page ?>&page=<?= $i ?>"
            class="px-4 py-2 rounded-lg
            <?= ($page == $i)
                ? 'bg-blue-600 text-white'
                : 'bg-gray-200 hover:bg-gray-300'
            ?>">

                <?= $i ?>

            </a>

        <?php endfor; ?>

        <?php if($page < $total_pages): ?>

            <a
            href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>"
            class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">

                Next

            </a>

        <?php endif; ?>

    </div>

</div>

        </div>

    </div>

</div>

<script>

function deleteBook(id)
{
    Swal.fire({

        title: 'Delete Book?',
        text: 'This action cannot be undone.',

        icon: 'warning',

        showCancelButton: true,

        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',

        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'

    }).then((result) => {

        if(result.isConfirmed)
        {
            window.location =
                'delete.php?id=' + id;
        }

    });
}

</script>

</body>
</html>