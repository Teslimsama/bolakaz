<?php

require_once __DIR__ . '/product_payload.php';
require_once __DIR__ . '/catalog_v2.php';
require_once __DIR__ . '/sync.php';
require_once __DIR__ . '/../admin/slugify.php';

if (!function_exists('product_import_session_key')) {
    function product_import_session_key(): string
    {
        return 'product_csv_import';
    }
}

if (!function_exists('product_import_fields')) {
    function product_import_fields(): array
    {
        return [
            'slug' => ['label' => 'Slug', 'required' => true, 'guesses' => ['slug', 'product slug']],
            'name' => ['label' => 'Product Name', 'required' => true, 'guesses' => ['name', 'product name']],
            'category_slug' => ['label' => 'Category Slug', 'required' => true, 'guesses' => ['category_slug', 'category slug', 'category']],
            'price' => ['label' => 'Price', 'required' => true, 'guesses' => ['price', 'amount', 'selling price']],
            'qty' => ['label' => 'Quantity', 'required' => true, 'guesses' => ['qty', 'quantity', 'stock', 'stock qty']],
            'product_status' => ['label' => 'Product Status', 'required' => true, 'guesses' => ['product_status', 'product status', 'status']],
            'description' => ['label' => 'Description', 'required_on_create' => true, 'guesses' => ['description', 'product description']],
            'material' => ['label' => 'Material', 'required_on_create' => true, 'guesses' => ['material', 'materials']],
            'subcategory_slug' => ['label' => 'Subcategory Slug', 'guesses' => ['subcategory_slug', 'subcategory slug', 'subcategory']],
            'brand' => ['label' => 'Brand', 'guesses' => ['brand']],
            'color' => ['label' => 'Color', 'guesses' => ['color', 'colour']],
            'size' => ['label' => 'Size', 'guesses' => ['size']],
            'spec_fit' => ['label' => 'Spec: Fit', 'guesses' => ['spec_fit', 'fit']],
            'spec_care_instructions' => ['label' => 'Spec: Care Instructions', 'guesses' => ['spec_care_instructions', 'care instructions', 'care']],
            'spec_composition' => ['label' => 'Spec: Composition', 'guesses' => ['spec_composition', 'composition']],
            'spec_dimensions' => ['label' => 'Spec: Dimensions', 'guesses' => ['spec_dimensions', 'dimensions']],
            'spec_shipping_class' => ['label' => 'Spec: Shipping Class', 'guesses' => ['spec_shipping_class', 'shipping class']],
            'spec_origin' => ['label' => 'Spec: Origin', 'guesses' => ['spec_origin', 'origin']],
        ];
    }
}

if (!function_exists('product_import_template_csv')) {
    function product_import_template_csv(?PDO $conn = null): string
    {
        $headers = [
            'slug',
            'name',
            'category_slug',
            'subcategory_slug',
            'price',
            'qty',
            'brand',
            'description',
            'material',
            'color',
            'size',
            'product_status',
            'spec_fit',
            'spec_care_instructions',
            'spec_composition',
            'spec_dimensions',
            'spec_shipping_class',
            'spec_origin',
        ];

        $rows = $conn ? product_import_template_rows($conn) : [];
        if (empty($rows)) {
            $rows = [
                [
                    'sample-product-one',
                    'Sample Product One',
                    'replace-with-existing-category-slug',
                    '',
                    '18500',
                    '12',
                    'Bolakaz',
                    'Replace this row with your real product data.',
                    'cotton',
                    'black',
                    'M',
                    'active',
                    'Regular fit',
                    'Hand wash',
                    '100% cotton',
                    '',
                    'light',
                    'Nigeria',
                ],
            ];
        }

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new RuntimeException('Unable to build the CSV template.');
        }

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }
}

if (!function_exists('product_import_template_rows')) {
    function product_import_template_rows(PDO $conn): array
    {
        $parents = [];
        $childrenByParent = [];

        $stmt = $conn->query("SELECT id, name, cat_slug, is_parent, parent_id FROM category ORDER BY is_parent DESC, id ASC");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $slug = trim((string) ($row['cat_slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            if ((int) ($row['is_parent'] ?? 0) === 1) {
                $parents[] = [
                    'id' => (int) $row['id'],
                    'name' => trim(html_entity_decode((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8')),
                    'slug' => $slug,
                ];
                continue;
            }

            $parentId = (int) ($row['parent_id'] ?? 0);
            if ($parentId > 0) {
                $childrenByParent[$parentId][] = [
                    'id' => (int) $row['id'],
                    'name' => trim(html_entity_decode((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8')),
                    'slug' => $slug,
                ];
            }
        }

        $rows = [];
        foreach (array_slice($parents, 0, 3) as $index => $parent) {
            $child = $childrenByParent[$parent['id']][0] ?? null;
            $nameSeed = trim($parent['name']) !== '' ? $parent['name'] : ('Category ' . ($index + 1));
            $sampleName = 'Sample ' . $nameSeed . ' Product';
            $sampleSlug = slugify($parent['slug'] . '-sample-' . ($index + 1));

            $rows[] = [
                $sampleSlug !== '' ? $sampleSlug : ('sample-product-' . ($index + 1)),
                $sampleName,
                $parent['slug'],
                $child['slug'] ?? '',
                (string) (15000 + ($index * 2500)),
                (string) (10 + $index),
                'Bolakaz',
                'Replace this sample row with your real product data.',
                'cotton',
                ['black', 'beige', 'blue'][$index] ?? 'black',
                ['M', 'L', '8-10'][$index] ?? 'M',
                'active',
                ['Regular fit', 'Relaxed fit', 'Comfort fit'][$index] ?? 'Regular fit',
                'Hand wash',
                '100% cotton',
                '',
                'light',
                'Nigeria',
            ];
        }

        return $rows;
    }
}

if (!function_exists('product_import_temp_dir')) {
    function product_import_temp_dir(): string
    {
        $directory = dirname(__DIR__) . '/storage/tmp/product-import';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return $directory;
    }
}

if (!function_exists('product_import_clear_state')) {
    function product_import_clear_state(): void
    {
        $state = $_SESSION[product_import_session_key()] ?? [];
        if (!empty($state['upload']['path']) && is_file((string) $state['upload']['path'])) {
            @unlink((string) $state['upload']['path']);
        }
        unset($_SESSION[product_import_session_key()]);
    }
}

if (!function_exists('product_import_state')) {
    function product_import_state(): array
    {
        return (isset($_SESSION[product_import_session_key()]) && is_array($_SESSION[product_import_session_key()]))
            ? $_SESSION[product_import_session_key()]
            : [];
    }
}

if (!function_exists('product_import_store_state')) {
    function product_import_store_state(array $state): void
    {
        $_SESSION[product_import_session_key()] = $state;
    }
}

if (!function_exists('product_import_read_csv')) {
    function product_import_read_csv(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('The uploaded CSV file could not be found.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the uploaded CSV file.');
        }

        $headers = [];
        $rows = [];
        try {
            $firstRow = fgetcsv($handle);
            if (!is_array($firstRow) || empty($firstRow)) {
                throw new RuntimeException('The CSV file must include a header row.');
            }

            $headers = array_map(static fn($value): string => trim((string) $value), $firstRow);

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === false) {
                    continue;
                }
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}

if (!function_exists('product_import_normalize_header')) {
    function product_import_normalize_header(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', ' ', $header ?? '');
        return trim((string) preg_replace('/\s+/', ' ', $header));
    }
}

if (!function_exists('product_import_guess_mapping')) {
    function product_import_guess_mapping(array $headers): array
    {
        $mapping = [];
        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $normalizedHeaders[$index] = product_import_normalize_header((string) $header);
        }

        foreach (product_import_fields() as $field => $meta) {
            $guesses = array_map('product_import_normalize_header', (array) ($meta['guesses'] ?? []));
            foreach ($normalizedHeaders as $index => $normalizedHeader) {
                if (in_array($normalizedHeader, $guesses, true)) {
                    $mapping[$field] = $index;
                    break;
                }
            }
        }

        return $mapping;
    }
}

if (!function_exists('product_import_store_upload')) {
    function product_import_store_upload(array $file): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
            throw new RuntimeException('Choose a CSV file to upload.');
        }

        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'txt'], true)) {
            throw new RuntimeException('Upload a valid CSV file.');
        }

        $token = bin2hex(random_bytes(12));
        $path = product_import_temp_dir() . '/upload-' . $token . '.csv';
        if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
            throw new RuntimeException('Unable to store the uploaded CSV file.');
        }

        $parsed = product_import_read_csv($path);
        $headers = $parsed['headers'];
        if (empty($headers)) {
            @unlink($path);
            throw new RuntimeException('The CSV file must include a header row.');
        }

        return [
            'token' => $token,
            'path' => $path,
            'original_name' => (string) ($file['name'] ?? 'upload.csv'),
            'headers' => $headers,
            'row_count' => count($parsed['rows']),
            'guess_mapping' => product_import_guess_mapping($headers),
        ];
    }
}

if (!function_exists('product_import_extract_cell')) {
    function product_import_extract_cell(array $row, ?int $index): string
    {
        if ($index === null || $index < 0) {
            return '';
        }

        return trim((string) ($row[$index] ?? ''));
    }
}

if (!function_exists('product_import_parse_status')) {
    function product_import_parse_status(string $value): ?int
    {
        $value = strtolower(trim($value));
        if ($value === 'active') {
            return 1;
        }
        if ($value === 'inactive') {
            return 0;
        }

        return null;
    }
}

if (!function_exists('product_import_build_reference_maps')) {
    function product_import_build_reference_maps(PDO $conn): array
    {
        $categories = [];
        $stmt = $conn->query("SELECT id, name, cat_slug, is_parent, parent_id, status FROM category ORDER BY id ASC");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $categories[strtolower(trim((string) ($row['cat_slug'] ?? '')))] = $row;
        }

        $products = [];
        $stmt = $conn->query("SELECT * FROM products ORDER BY id ASC");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $products[strtolower(trim((string) ($row['slug'] ?? '')))] = $row;
        }

        return ['categories' => $categories, 'products' => $products];
    }
}

if (!function_exists('product_import_normalize_multi_value')) {
    function product_import_normalize_multi_value(string $value, int $maxLength = 60): string
    {
        if ($value === '') {
            return '';
        }

        return product_values_to_csv(product_normalize_values(array_map('trim', explode(',', $value)), $maxLength));
    }
}

if (!function_exists('product_import_build_specs_payload')) {
    function product_import_build_specs_payload(array $mappedValues, ?array $existing): ?string
    {
        $specKeys = [
            'spec_fit' => 'fit',
            'spec_care_instructions' => 'care_instructions',
            'spec_composition' => 'composition',
            'spec_dimensions' => 'dimensions',
            'spec_shipping_class' => 'shipping_class',
            'spec_origin' => 'origin',
        ];

        $existingSpecs = product_decode_specs((string) ($existing['additional_info'] ?? ''));
        $specs = $existingSpecs;
        foreach ($specKeys as $field => $specKey) {
            if (!array_key_exists($field, $mappedValues)) {
                continue;
            }

            $value = trim((string) $mappedValues[$field]);
            if ($value === '') {
                unset($specs[$specKey]);
            } else {
                $specs[$specKey] = $value;
            }
        }

        $encoded = product_encode_specs($specs);
        return $encoded !== '' ? $encoded : null;
    }
}

if (!function_exists('product_import_error_csv')) {
    function product_import_error_csv(array $errorRows): string
    {
        if (empty($errorRows)) {
            return '';
        }

        $lines = ["row_number,slug,name,action,message"];
        foreach ($errorRows as $row) {
            $lines[] = '"' . implode('","', array_map(static function ($value): string {
                return str_replace('"', '""', (string) $value);
            }, [
                $row['row_number'] ?? '',
                $row['slug'] ?? '',
                $row['name'] ?? '',
                $row['action'] ?? '',
                $row['message'] ?? '',
            ])) . '"';
        }

        return implode("\r\n", $lines);
    }
}

if (!function_exists('product_import_build_preview')) {
    function product_import_build_preview(PDO $conn, string $path, array $mapping): array
    {
        $parsed = product_import_read_csv($path);
        $rows = $parsed['rows'];
        $references = product_import_build_reference_maps($conn);
        $categoryMap = $references['categories'];
        $productMap = $references['products'];

        $slugCounts = [];
        foreach ($rows as $row) {
            $slugIndex = array_key_exists('slug', $mapping) ? (int) $mapping['slug'] : null;
            $slug = slugify(product_import_extract_cell($row, $slugIndex));
            if ($slug === '') {
                continue;
            }
            $slugCounts[$slug] = (int) ($slugCounts[$slug] ?? 0) + 1;
        }

        $previewRows = [];
        $validRows = [];
        $errorRows = [];
        $summary = [
            'create' => 0,
            'update' => 0,
            'failed' => 0,
            'valid' => 0,
            'total' => count($rows),
        ];

        foreach ($rows as $offset => $row) {
            $lineNumber = $offset + 2;
            $mappedValues = [];
            foreach ($mapping as $field => $index) {
                if ($index === '' || $index === null) {
                    continue;
                }
                $mappedValues[$field] = product_import_extract_cell($row, (int) $index);
            }

            $errors = [];
            $slug = slugify((string) ($mappedValues['slug'] ?? ''));
            $name = trim((string) ($mappedValues['name'] ?? ''));
            $categorySlug = strtolower(trim((string) ($mappedValues['category_slug'] ?? '')));
            $subcategorySlug = strtolower(trim((string) ($mappedValues['subcategory_slug'] ?? '')));
            $priceRaw = trim((string) ($mappedValues['price'] ?? ''));
            $qtyRaw = trim((string) ($mappedValues['qty'] ?? ''));
            $statusRaw = trim((string) ($mappedValues['product_status'] ?? ''));

            if ($slug === '') {
                $errors[] = 'Slug is required.';
            } elseif (($slugCounts[$slug] ?? 0) > 1) {
                $errors[] = 'Slug must be unique within the CSV.';
            }

            $existing = ($slug !== '' && isset($productMap[$slug])) ? $productMap[$slug] : null;
            $action = $existing ? 'update' : 'create';

            if ($name === '') {
                $errors[] = 'Product name is required.';
            }
            if ($categorySlug === '') {
                $errors[] = 'Category slug is required.';
            }
            $category = ($categorySlug !== '' && isset($categoryMap[$categorySlug])) ? $categoryMap[$categorySlug] : null;
            if (!$category) {
                $errors[] = 'Category slug could not be found.';
            } elseif ((int) ($category['is_parent'] ?? 0) !== 1) {
                $errors[] = 'Category slug must belong to a parent category.';
            }

            $subcategory = null;
            if ($subcategorySlug !== '') {
                $subcategory = $categoryMap[$subcategorySlug] ?? null;
                if (!$subcategory) {
                    $errors[] = 'Subcategory slug could not be found.';
                } elseif ((int) ($subcategory['parent_id'] ?? 0) !== (int) ($category['id'] ?? 0)) {
                    $errors[] = 'Subcategory slug does not belong to the selected category.';
                }
            }

            if ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0) {
                $errors[] = 'Price must be a number greater than or equal to zero.';
            }
            if ($qtyRaw === '' || filter_var($qtyRaw, FILTER_VALIDATE_INT) === false || (int) $qtyRaw < 0) {
                $errors[] = 'Quantity must be a whole number greater than or equal to zero.';
            }

            $productStatus = product_import_parse_status($statusRaw);
            if ($productStatus === null) {
                $errors[] = 'Product status must be active or inactive.';
            }

            $descriptionMapped = array_key_exists('description', $mappedValues);
            $materialMapped = array_key_exists('material', $mappedValues);
            $subcategoryMapped = array_key_exists('subcategory_slug', $mappedValues);
            $descriptionValue = trim((string) ($mappedValues['description'] ?? ''));
            $materialValue = trim((string) ($mappedValues['material'] ?? ''));
            $materialCsv = product_import_normalize_multi_value($materialValue, 80);

            if ($action === 'create' && $descriptionValue === '') {
                $errors[] = 'Description is required for new products.';
            }
            if ($action === 'create' && $materialCsv === '') {
                $errors[] = 'Material is required for new products.';
            }

            $brandMapped = array_key_exists('brand', $mappedValues);
            $colorMapped = array_key_exists('color', $mappedValues);
            $sizeMapped = array_key_exists('size', $mappedValues);

            $finalValues = [
                'row_number' => $lineNumber,
                'slug' => $slug,
                'name' => $name,
                'category_id' => (int) ($category['id'] ?? 0),
                'subcategory_id' => $subcategoryMapped
                    ? (int) ($subcategory['id'] ?? 0)
                    : (int) ($existing['subcategory_id'] ?? 0),
                'category_name' => (string) ($category['cat_slug'] ?? ''),
                'price' => (float) $priceRaw,
                'qty' => (int) $qtyRaw,
                'product_status' => (int) $productStatus,
                'description' => $action === 'create'
                    ? $descriptionValue
                    : ($descriptionMapped && $descriptionValue !== '' ? $descriptionValue : (string) ($existing['description'] ?? '')),
                'material' => $action === 'create'
                    ? $materialCsv
                    : ($materialMapped && $materialCsv !== '' ? $materialCsv : (string) ($existing['material'] ?? '')),
                'brand' => $brandMapped ? trim((string) ($mappedValues['brand'] ?? '')) : (string) ($existing['brand'] ?? ''),
                'color' => $colorMapped ? product_import_normalize_multi_value((string) ($mappedValues['color'] ?? '')) : (string) ($existing['color'] ?? ''),
                'size' => $sizeMapped ? product_import_normalize_multi_value((string) ($mappedValues['size'] ?? '')) : (string) ($existing['size'] ?? ''),
                'additional_info' => product_import_build_specs_payload($mappedValues, $existing),
                'existing_id' => (int) ($existing['id'] ?? 0),
                'photo' => (string) ($existing['photo'] ?? ''),
            ];

            if ($action === 'update') {
                if ($descriptionMapped && $descriptionValue === '') {
                    $finalValues['description'] = (string) ($existing['description'] ?? '');
                }
                if ($materialMapped && $materialCsv === '') {
                    $finalValues['material'] = (string) ($existing['material'] ?? '');
                }
            }

            if ($finalValues['category_id'] <= 0) {
                $errors[] = 'A valid category is required.';
            }
            if ($finalValues['description'] === '') {
                $errors[] = 'Description could not be resolved.';
            }
            if ($finalValues['material'] === '') {
                $errors[] = 'Material could not be resolved.';
            }

            $message = implode(' ', $errors);
            $previewRows[] = [
                'row_number' => $lineNumber,
                'action' => $action,
                'status' => empty($errors) ? ucfirst($action) : 'Failed',
                'slug' => $slug,
                'name' => $name,
                'category_slug' => $categorySlug,
                'price' => $priceRaw,
                'qty' => $qtyRaw,
                'message' => $message,
            ];

            if (empty($errors)) {
                $summary[$action]++;
                $summary['valid']++;
                $validRows[] = $finalValues;
            } else {
                $summary['failed']++;
                $errorRows[] = [
                    'row_number' => $lineNumber,
                    'slug' => $slug,
                    'name' => $name,
                    'action' => $action,
                    'message' => $message,
                ];
            }
        }

        return [
            'mapping' => $mapping,
            'rows' => $previewRows,
            'valid_rows' => $validRows,
            'summary' => $summary,
            'error_rows' => $errorRows,
        ];
    }
}

if (!function_exists('product_import_apply')) {
    function product_import_apply(PDO $conn, array $validRows): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($validRows as $index => $row) {
            try {
                $conn->beginTransaction();

                if ((int) ($row['existing_id'] ?? 0) > 0) {
                    $stmt = $conn->prepare(
                        "UPDATE products
                         SET category_id = :category_id,
                             subcategory_id = :subcategory_id,
                             category_name = :category_name,
                             name = :name,
                             description = :description,
                             additional_info = :additional_info,
                             slug = :slug,
                             price = :price,
                             color = :color,
                             size = :size,
                             brand = :brand,
                             material = :material,
                             qty = :qty,
                             product_status = :product_status
                         WHERE id = :id"
                    );
                    $stmt->execute([
                        'category_id' => (int) $row['category_id'],
                        'subcategory_id' => ((int) ($row['subcategory_id'] ?? 0) > 0 ? (int) $row['subcategory_id'] : null),
                        'category_name' => (string) $row['category_name'],
                        'name' => (string) $row['name'],
                        'description' => (string) $row['description'],
                        'additional_info' => $row['additional_info'],
                        'slug' => (string) $row['slug'],
                        'price' => (float) $row['price'],
                        'color' => (string) $row['color'],
                        'size' => (string) $row['size'],
                        'brand' => (string) $row['brand'],
                        'material' => (string) $row['material'],
                        'qty' => (int) $row['qty'],
                        'product_status' => (int) $row['product_status'],
                        'id' => (int) $row['existing_id'],
                    ]);
                    $productId = (int) $row['existing_id'];
                    $action = 'update';
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO products (category_id, subcategory_id, category_name, name, description, additional_info, slug, price, color, size, brand, material, qty, photo, product_status)
                         VALUES (:category_id, :subcategory_id, :category_name, :name, :description, :additional_info, :slug, :price, :color, :size, :brand, :material, :qty, :photo, :product_status)"
                    );
                    $stmt->execute([
                        'category_id' => (int) $row['category_id'],
                        'subcategory_id' => ((int) ($row['subcategory_id'] ?? 0) > 0 ? (int) $row['subcategory_id'] : null),
                        'category_name' => (string) $row['category_name'],
                        'name' => (string) $row['name'],
                        'description' => (string) $row['description'],
                        'additional_info' => $row['additional_info'],
                        'slug' => (string) $row['slug'],
                        'price' => (float) $row['price'],
                        'color' => (string) $row['color'],
                        'size' => (string) $row['size'],
                        'brand' => (string) $row['brand'],
                        'material' => (string) $row['material'],
                        'qty' => (int) $row['qty'],
                        'photo' => '',
                        'product_status' => (int) $row['product_status'],
                    ]);
                    $productId = (int) $conn->lastInsertId();
                    $action = 'create';
                }

                if (catalog_v2_table_exists($conn, 'products_v2')) {
                    catalog_v2_sync_product_from_legacy($conn, [
                        'id' => $productId,
                        'category_id' => (int) $row['category_id'],
                        'subcategory_id' => ((int) ($row['subcategory_id'] ?? 0) > 0 ? (int) $row['subcategory_id'] : null),
                        'name' => (string) $row['name'],
                        'description' => (string) $row['description'],
                        'additional_info' => $row['additional_info'],
                        'slug' => (string) $row['slug'],
                        'price' => (float) $row['price'],
                        'color' => (string) $row['color'],
                        'size' => (string) $row['size'],
                        'brand' => (string) $row['brand'],
                        'material' => (string) $row['material'],
                        'qty' => (int) $row['qty'],
                        'photo' => (string) ($row['photo'] ?? ''),
                        'product_status' => (int) $row['product_status'],
                    ]);
                }

                sync_enqueue_or_fail($conn, 'products', $productId);
                $conn->commit();
                if ($action === 'update') {
                    $summary['updated']++;
                } else {
                    $summary['created']++;
                }
            } catch (Throwable $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $summary['failed']++;
                $summary['errors'][] = [
                    'row_number' => (int) ($row['row_number'] ?? ($index + 1)),
                    'slug' => (string) ($row['slug'] ?? ''),
                    'name' => (string) ($row['name'] ?? ''),
                    'action' => ((int) ($row['existing_id'] ?? 0) > 0) ? 'update' : 'create',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $summary;
    }
}
