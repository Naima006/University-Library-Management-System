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
$namePattern = '/^[A-Za-z0-9 &, -]+$/';

function importFail(string $message): void
{
    $_SESSION['category_import_report'] = [
        'imported' => 0,
        'skipped' => 0,
        'errors' => [
            ['row' => '-', 'name' => '-', 'reason' => $message],
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

if (!in_array('category_name', $headers, true)) {
    fclose($handle);
    importFail('Missing required column: category_name. Download the template and keep the header row unchanged.');
}

$columnIndex = array_flip($headers);

$existing = [];
$existingResult = $conn->query("SELECT category_name FROM categories");
if ($existingResult) {
    while ($row = $existingResult->fetch_assoc()) {
        $existing[strtolower(trim($row['category_name']))] = true;
    }
}

$insertStmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
if (!$insertStmt) {
    fclose($handle);
    importFail('Unable to prepare the import query. Please try again.');
}

$imported = 0;
$skipped = 0;
$errors = [];
$seen = [];
$rowNumber = 1;

while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
    $rowNumber++;

    if ($rowNumber - 1 > $maxRows) {
        $errors[] = [
            'row' => $rowNumber,
            'name' => '-',
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

    $name = trim((string) ($row[$columnIndex['category_name']] ?? ''));
    $key = strtolower($name);

    $fail = function (string $reason) use (&$errors, &$skipped, $rowNumber, $name) {
        $errors[] = [
            'row' => $rowNumber,
            'name' => $name !== '' ? $name : '-',
            'reason' => $reason,
        ];
        $skipped++;
    };

    if ($name === '') {
        $fail('Category name is required.');
        continue;
    }

    if (strlen($name) < 2 || strlen($name) > 100 || !preg_match($namePattern, $name)) {
        $fail('Use 2–100 characters: letters, numbers, spaces, ampersands, commas, and hyphens only.');
        continue;
    }

    if (isset($seen[$key])) {
        $fail('Duplicate category in this CSV (also on row ' . $seen[$key] . ').');
        continue;
    }

    if (isset($existing[$key])) {
        $fail('A category with this name already exists.');
        continue;
    }

    $insertStmt->bind_param("s", $name);
    if (!$insertStmt->execute()) {
        $fail('Database insert failed for this row.');
        continue;
    }

    $seen[$key] = $rowNumber;
    $existing[$key] = true;
    $imported++;
}

fclose($handle);

if ($imported > 0) {
    addActivityLog(
        $conn,
        (int) $_SESSION['user_id'],
        "Imported Categories",
        "categories",
        0,
        "Imported {$imported} categor" . ($imported === 1 ? "y" : "ies") . " from CSV"
            . ($skipped > 0 ? " ({$skipped} row(s) skipped)." : ".")
    );
}

$_SESSION['category_import_report'] = [
    'imported' => $imported,
    'skipped' => $skipped,
    'errors' => $errors,
    'fatal' => false,
];

header("Location: index.php");
exit;
