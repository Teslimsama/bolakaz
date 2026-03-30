<?php
include 'session.php';
include_once '../includes/conn.php';
require_once __DIR__ . '/../lib/product_csv_import.php';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="bolakaz-product-import-template.csv"');

$conn = $pdo->open();
try {
    echo product_import_template_csv($conn);
} finally {
    $pdo->close();
}
exit;
