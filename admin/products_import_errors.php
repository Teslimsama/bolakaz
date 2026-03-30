<?php
include 'session.php';
require_once __DIR__ . '/../lib/product_csv_import.php';

$state = product_import_state();
$csv = trim((string) ($state['latest_error_csv'] ?? ''));
if ($csv === '') {
    $_SESSION['error'] = 'No import error report is available right now.';
    header('location: products_import.php');
    exit;
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="bolakaz-product-import-errors.csv"');

echo $csv;
exit;
