<?php

session_start();
include("../config/db.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| BOOKS
|--------------------------------------------------------------------------
| Only books that currently have available copies can be issued.
*/

$books = $conn->query("
    SELECT book_id, title, author_name, isbn, available_copies
    FROM books
    WHERE available_copies > 0
    ORDER BY title ASC
");

/*
|--------------------------------------------------------------------------
| MEMBERS
|--------------------------------------------------------------------------
| Soft-deleted members must never appear in the issue form.
*/

$members = $conn->query("
    SELECT member_id, first_name, last_name, student_id
    FROM members
    WHERE is_active = 1
    AND is_deleted = 0
    ORDER BY first_name ASC, last_name ASC
");

$today = date('Y-m-d');
$default_due_date = date('Y-m-d', strtotime('+14 days'));

$pageTitle = "Issue Book";

ob_start();
?>

<div class="max-w-4xl mx-auto">

    <div class="bg-white rounded-xl shadow p-5 sm:p-6">

        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-800">
                Issue Book
            </h1>

            <p class="text-gray-500 mt-1">
                Assign an available book to an active library member.
            </p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">

                <?php if ($_GET['error'] === 'nostock'): ?>
                    No copies are currently available for this book.

                <?php elseif ($_GET['error'] === 'invalid_date'): ?>
                    Issue date cannot be before today.

                <?php elseif ($_GET['error'] === 'invalid_due_date'): ?>
                    Due date must be after the issue date.

                <?php elseif ($_GET['error'] === 'invalid_member'): ?>
                    Please select a valid active member.

                <?php elseif ($_GET['error'] === 'invalid_book'): ?>
                    Please select a valid available book.

                <?php else: ?>
                    Unable to issue the book. Please try again.
                <?php endif; ?>

            </div>
        <?php endif; ?>

        <form action="store.php" method="POST" id="issueBookForm">

            <!-- BOOK SEARCH -->

            <div class="mb-5 relative">

                <label for="book_search" class="block mb-2 font-medium text-slate-700">
                    Select Book
                </label>

                <input
                    type="text"
                    id="book_search"
                    placeholder="Search by book title or author..."
                    autocomplete="off"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >

                <i class="fas fa-search absolute right-4 top-12 text-gray-400"></i>

                <input type="hidden" name="book_id" id="book_id" required>

                <div
                    id="book_results"
                    class="hidden absolute z-30 mt-1 w-full max-h-60 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
                ></div>

                <p id="selected_book" class="mt-2 text-sm text-green-700 hidden"></p>

            </div>

            <!-- MEMBER SEARCH -->

            <div class="mb-5 relative">

                <label for="member_search" class="block mb-2 font-medium text-slate-700">
                    Select Member
                </label>

                <input
                    type="text"
                    id="member_search"
                    placeholder="Search by member name or student ID..."
                    autocomplete="off"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >

                <i class="fas fa-search absolute right-4 top-12 text-gray-400"></i>

                <input type="hidden" name="member_id" id="member_id" required>

                <div
                    id="member_results"
                    class="hidden absolute z-30 mt-1 w-full max-h-60 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
                ></div>

                <p id="selected_member" class="mt-2 text-sm text-green-700 hidden"></p>

            </div>

            <!-- ISSUE DATE -->

            <div class="mb-5">

                <label for="issue_date" class="block mb-2 font-medium text-slate-700">
                    Issue Date
                </label>

                <input
                    type="date"
                    id="issue_date"
                    name="issue_date"
                    required
                    min="<?= $today ?>"
                    value="<?= $today ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >

                <p class="mt-1 text-xs text-gray-500">
                    The issue date cannot be earlier than today.
                </p>

            </div>

            <!-- DUE DATE -->

            <div class="mb-6">

                <label for="due_date" class="block mb-2 font-medium text-slate-700">
                    Due Date
                </label>

                <input
                    type="date"
                    id="due_date"
                    name="due_date"
                    required
                    min="<?= $today ?>"
                    value="<?= $default_due_date ?>"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-400"
                >

                <p class="mt-1 text-xs text-gray-500">
                    The due date must be after the issue date.
                </p>

            </div>

            <!-- BUTTONS -->

            <div class="flex flex-col-reverse sm:flex-row gap-3">

                <a href="index.php"
                    class="bg-slate-200 hover:bg-slate-300 text-slate-800 px-6 py-3 rounded-lg text-center">
                    Cancel
                </a>

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg">

                    <i class="fas fa-save mr-2"></i>
                    Issue Book

                </button>

            </div>

        </form>

    </div>

</div>

<script>
const books = <?php
    $book_items = [];

    if ($books) {
        while ($book = $books->fetch_assoc()) {
            $book_items[] = [
                'id' => (int) $book['book_id'],
                'title' => $book['title'],
                'author' => $book['author_name'] ?? '',
                'available' => (int) $book['available_copies']
            ];
        }
    }

    echo json_encode(
        $book_items,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );
?>;

const members = <?php
    $member_items = [];

    if ($members) {
        while ($member = $members->fetch_assoc()) {
            $member_items[] = [
                'id' => (int) $member['member_id'],
                'name' => trim($member['first_name'] . ' ' . $member['last_name']),
                'student_id' => $member['student_id']
            ];
        }
    }

    echo json_encode(
        $member_items,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );
?>;

const bookSearch = document.getElementById('book_search');
const bookId = document.getElementById('book_id');
const bookResults = document.getElementById('book_results');
const selectedBook = document.getElementById('selected_book');

const memberSearch = document.getElementById('member_search');
const memberId = document.getElementById('member_id');
const memberResults = document.getElementById('member_results');
const selectedMember = document.getElementById('selected_member');

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
}

/* ---------------- BOOK SUGGESTIONS ---------------- */

function renderBookResults() {
    const keyword = bookSearch.value.trim().toLowerCase();

    const filteredBooks = books.filter(book => {
        return (
            book.title.toLowerCase().includes(keyword) ||
            book.author.toLowerCase().includes(keyword)
        );
    }).slice(0, 8);

    if (filteredBooks.length === 0) {
        bookResults.innerHTML = `
            <div class="px-4 py-3 text-sm text-gray-500">
                No available books found.
            </div>
        `;
        bookResults.classList.remove('hidden');
        return;
    }

    bookResults.innerHTML = filteredBooks.map(book => `
        <button
            type="button"
            data-book-id="${book.id}"
            class="book-option w-full text-left px-4 py-3 hover:bg-slate-50 border-b border-gray-100 last:border-b-0"
        >
            <p class="font-medium text-slate-800">${escapeHtml(book.title)}</p>
            <p class="text-xs text-gray-500">
                ${book.author ? escapeHtml(book.author) + ' · ' : ''}
                ${book.available} available
            </p>
        </button>
    `).join('');

    bookResults.classList.remove('hidden');

    document.querySelectorAll('.book-option').forEach(button => {
        button.addEventListener('click', function () {
            selectBook(Number(this.dataset.bookId));
        });
    });
}

function selectBook(id) {
    const book = books.find(item => item.id === id);

    if (!book) return;

    bookId.value = book.id;
    bookSearch.value = book.title;

    selectedBook.innerHTML = `
        <i class="fas fa-check-circle mr-1"></i>
        Selected: ${escapeHtml(book.title)} — ${book.available} available
    `;

    selectedBook.classList.remove('hidden');
    bookResults.classList.add('hidden');
}

/* ---------------- MEMBER SUGGESTIONS ---------------- */

function renderMemberResults() {
    const keyword = memberSearch.value.trim().toLowerCase();

    const filteredMembers = members.filter(member => {
        return (
            member.name.toLowerCase().includes(keyword) ||
            member.student_id.toLowerCase().includes(keyword)
        );
    }).slice(0, 8);

    if (filteredMembers.length === 0) {
        memberResults.innerHTML = `
            <div class="px-4 py-3 text-sm text-gray-500">
                No active members found.
            </div>
        `;
        memberResults.classList.remove('hidden');
        return;
    }

    memberResults.innerHTML = filteredMembers.map(member => `
        <button
            type="button"
            data-member-id="${member.id}"
            class="member-option w-full text-left px-4 py-3 hover:bg-slate-50 border-b border-gray-100 last:border-b-0"
        >
            <p class="font-medium text-slate-800">${escapeHtml(member.name)}</p>
            <p class="text-xs text-gray-500">
                Student ID: ${escapeHtml(member.student_id)}
            </p>
        </button>
    `).join('');

    memberResults.classList.remove('hidden');

    document.querySelectorAll('.member-option').forEach(button => {
        button.addEventListener('click', function () {
            selectMember(Number(this.dataset.memberId));
        });
    });
}

function selectMember(id) {
    const member = members.find(item => item.id === id);

    if (!member) return;

    memberId.value = member.id;
    memberSearch.value = member.name;

    selectedMember.innerHTML = `
        <i class="fas fa-check-circle mr-1"></i>
        Selected: ${escapeHtml(member.name)} (${escapeHtml(member.student_id)})
    `;

    selectedMember.classList.remove('hidden');
    memberResults.classList.add('hidden');
}

/* Reset selected IDs when the user edits text again */
bookSearch.addEventListener('input', function () {
    bookId.value = '';
    selectedBook.classList.add('hidden');
    renderBookResults();
});

memberSearch.addEventListener('input', function () {
    memberId.value = '';
    selectedMember.classList.add('hidden');
    renderMemberResults();
});

/* Show suggestions immediately when a user clicks a search field */
bookSearch.addEventListener('focus', renderBookResults);
memberSearch.addEventListener('focus', renderMemberResults);

/* ---------------- DATE VALIDATION ---------------- */

const issueDate = document.getElementById('issue_date');
const dueDate = document.getElementById('due_date');

function getNextDay(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    date.setDate(date.getDate() + 1);

    return date.toISOString().split('T')[0];
}

function updateDueDateMinimum() {
    const minimumDueDate = getNextDay(issueDate.value);

    dueDate.min = minimumDueDate;

    if (dueDate.value < minimumDueDate) {
        dueDate.value = minimumDueDate;
    }
}

issueDate.addEventListener('change', updateDueDateMinimum);

document.getElementById('issueBookForm').addEventListener('submit', function (event) {
    if (!bookId.value) {
        event.preventDefault();
        alert('Please select a book from the search suggestions.');
        bookSearch.focus();
        return;
    }

    if (!memberId.value) {
        event.preventDefault();
        alert('Please select a member from the search suggestions.');
        memberSearch.focus();
        return;
    }

    if (dueDate.value <= issueDate.value) {
        event.preventDefault();
        alert('Due date must be after the issue date.');
        dueDate.focus();
    }
});

/* Hide only when clicking outside each search area */
document.addEventListener('click', function (event) {
    if (!event.target.closest('#book_search') && !event.target.closest('#book_results')) {
        bookResults.classList.add('hidden');
    }

    if (!event.target.closest('#member_search') && !event.target.closest('#member_results')) {
        memberResults.classList.add('hidden');
    }
});

updateDueDateMinimum();
</script>

<?php

$content = ob_get_clean();

include("../layouts/main_layout.php");
?>