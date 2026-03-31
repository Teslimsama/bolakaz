<?php

require_once __DIR__ . '/product_sku.php';

if (!function_exists('product_label_barcode_data_uri')) {
    function product_label_barcode_data_uri(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        require_once dirname(__DIR__) . '/tcpdf/tcpdf_barcodes_1d.php';

        $barcode = new TCPDFBarcode($value, 'C128');
        $svg = $barcode->getBarcodeSVGcode(1.15, 34, 'black');
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
