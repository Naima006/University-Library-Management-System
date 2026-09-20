<?php

session_start();
include("../config/db.php");
include("../config/activity_log.php");

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$maxRows = 300;
$maxBytes = 1024 * 1024;
$currentYear = (int) date('Y');
$requiredHeaders = [
    'title',
    'author_name',
    'isbn',
    'published_year',
    'total_copies',
    'available_copies',
    'category_name',
];

function importFail(string $message): void
{
    $_SESSION['book_import_report'] = [
        'imported' => 0,
        'skipped' => 0,
        'errors' => [
            ['row' => '-', 'isbn' => '-', 'reason' => $message],
        ],
        'fatal' => true,
    ];
    header("Location: index.php");
    exit;
}

function normalizeHeader(string $value): string
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    return strtolower(trim($value));
}

function normalizeIsbn(string $isbn): string
{
    return preg_replace('/\D+/', '', $isbn);
}

function detectDelimiter(string $headerLine): string
{
    $comma = substr_count($headerLine, ',');
    $semicolon = substr_count($headerLine, ';');
    return $semicolon > $comma ? ';' : ',';
}

if (!isset($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    importFail('Please choose a CSV file to import.');
}

$file = $_FILES['csv_file'];

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    importFail('The CSV file could not be uploaded. Please try again.');
}

if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxBytes) {
    importFail('The CSV file must be between 1 byte and 1 MB.');
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($extension !== 'csv') {
    importFail('Only .csv files are accepted.');
}

$handle = fopen($file['tmp_name'], 'r');
if ($handle === false) {
    importFail('Unable to read the uploaded CSV file.');
}

$firstLine = fgets($handle);
if ($firstLine === false) {
    fclose($handle);
    importFail('The CSV file is empty.');
}

$delimiter = detectDelimiter($firstLine);
rewind($handle);

$headerRow = fgetcsv($handle, 0, $delimiter);
if ($headerRow === false) {
    fclose($handle);
    importFail('The CSV header row could not be read.');
}

$headers = array_map('normalizeHeader', $headerRow);

foreach ($requiredHeaders as $required) {
    if (!in_array($required, $headers, true)) {
        fclose($handle);
        importFail('Missing required column: ' . $required . '. Download the template and keep the header row unchanged.');
    }
}

$columnIndex = array_flip($headers);

$categories = [];
$categoryResult = $conn->query("SELECT category_id, category_name FROM categories");
if ($categoryResult) {
    while ($category = $categoryResult->fetch_assoc()) {
        $categories[strtolower(trim($category['category_name']))] = (int) $category['category_id'];
    }
}

if (empty($categories)) {
    fclose($handle);
    importFail('Create at least one category before importing books.');
}

$insertStmt = $conn->prepare("
    INSERT INTO books
    (title, author_name, isbn, published_year, total_copies, available_copies, category_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$isbnCheckStmt = $conn->prepare("SELECT book_id FROM books WHERE isbn = ? LIMIT 1");

if (!$insertStmt || !$isbnCheckStmt) {
    fclose($handle);
    importFail('Unable to prepare the import query. Please try again.');
}

$imported = 0;
$skipped = 0;
$errors = [];
$seenIsbn = [];
$rowNumber = 1;

while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
    $rowNumber++;

    if ($rowNumber - 1 > $maxRows) {
        $errors[] = [
            'row' => $rowNumber,
            'isbn' => '-',
            'reason' => 'Import stopped. Maximum ' . $maxRows . ' data rows allowed per file.',
        ];
        $skipped++;
        break;
    }

    $isEmpty = true;
    foreach ($row as $cell) {
        if (trim((string) $cell) !== '') {
            $isEmpty = false;
            break;
        }
    }
    if ($isEmpty) {
        continue;
    }

    $title = trim((string) ($row[$columnIndex['title']] ?? ''));
    $author = trim((string) ($row[$columnIndex['author_name']] ?? ''));
    $isbnRaw = trim((string) ($row[$columnIndex['isbn']] ?? ''));
    $isbn = normalizeIsbn($isbnRaw);
    $yearRaw = trim((string) ($row[$columnIndex['published_year']] ?? ''));
    $totalRaw = trim((string) ($row[$columnIndex['total_copies']] ?? ''));
    $availableRaw = trim((string) ($row[$columnIndex['available_copies']] ?? ''));
    $categoryName = trim((string) ($row[$columnIndex['category_name']] ?? ''));

    $fail = function (string $reason) use (&$errors, &$skipped, $rowNumber, $isbn) {
        $errors[] = [
            'row' => $rowNumber,
            'isbn' => $isbn !== '' ? $isbn : '-',
            'reason' => $reason,
        ];
        $skipped++;
    };

    if ($title === '' || $author === '' || $isbnRaw === '' || $totalRaw === '' || $categoryName === '') {
        $fail('Title, author, ISBN, total copies, and category are required.');
        continue;
    }

    if (strlen($title) < 2 || strlen($author) < 2) {
        $fail('Title and author must be at least 2 characters.');
        continue;
    }

    if (!preg_match('/^\d{13}$/', $isbn)) {
        $fail('ISBN must contain exactly 13 digits. Format the Excel column as Text.');
        continue;
    }

    if (isset($seenIsbn[$isbn])) {
        $fail('Duplicate ISBN in this CSV (also on row ' . $seenIsbn[$isbn] . ').');
        continue;
    }

    $isbnCheckStmt->bind_param("s", $isbn);
    $isbnCheckStmt->execute();
    if ($isbnCheckStmt->get_result()->num_rows > 0) {
        $fail('A book with this ISBN already exists in the library.');
        continue;
    }

    $year = null;
    if ($yearRaw !== '') {
        if (!preg_match('/^\d{4}$/', $yearRaw) || (int) $yearRaw < 1000 || (int) $yearRaw > $currentYear) {
            $fail('Published year must be a 4-digit year between 1000 and ' . $currentYear . '.');
            continue;
        }
        $year = (int) $yearRaw;
    }

    if (!preg_match('/^\d+$/', $totalRaw) || (int) $totalRaw < 1) {
        $fail('Total copies must be a whole number of at least 1.');
        continue;
    }
    $total = (int) $totalRaw;

    if ($availableRaw === '') {
        $available = $total;
    } else {
        if (!preg_match('/^\d+$/', $availableRaw)) {
            $fail('Available copies must be a whole number.');
            continue;
        }
        $available = (int) $availableRaw;
        if ($available < 0 || $available > $total) {
            $fail('Available copies cannot be greater than total copies.');
            continue;
        }
    }

    $categoryKey = strtolower($categoryName);
    if (!isset($categories[$categoryKey])) {
        $fail('Category "' . $categoryName . '" was not found. Create it first under Categories.');
        continue;
    }
    $categoryId = $categories[$categoryKey];

    $insertStmt->bind_param(
        "sssiiii",
        $title,
        $author,
        $isbn,
        $year,
        $total,
        $available,
        $categoryId
    );

    if (!$insertStmt->execute()) {
        $fail('Database insert failed for this row.');
        continue;
    }

    $seenIsbn[$isbn] = $rowNumber;
    $imported++;
}

fclose($handle);

if ($imported > 0) {
    addActivityLog(
        $conn,
        (int) $_SESSION['user_id'],
        "Imported Books",
        "books",
        0,
        "Imported {$imported} book(s) from CSV" . ($skipped > 0 ? " ({$skipped} row(s) skipped)." : ".")
    );
}

$_SESSION['book_import_report'] = [
    'imported' => $imported,
    'skipped' => $skipped,
    'errors' => $errors,
    'fatal' => false,
];

header("Location: index.php");
exit;
