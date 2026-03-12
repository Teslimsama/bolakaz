<?php

if (!function_exists('catalog_v2_is_enabled')) {
    function catalog_v2_is_enabled(): bool
    {
        $value = trim((string)($_ENV['CATALOG_V2_ENABLED'] ?? getenv('CATALOG_V2_ENABLED') ?? '0'));
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('catalog_v2_table_exists')) {
    function catalog_v2_table_exists(PDO $conn, string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        $stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table LIMIT 1");
        $stmt->execute(['table' => $table]);
        $cache[$table] = (bool)$stmt->fetchColumn();
        return $cache[$table];
    }
}

if (!function_exists('catalog_v2_ready')) {
    function catalog_v2_ready(PDO $conn): bool
    {
        if (!catalog_v2_is_enabled()) {
            return false;
        }

        $required = [
            'products_v2',
            'attributes',
            'attribute_values',
            'product_variants',
            'variant_option_values',
            'product_legacy_map',
        ];

        foreach ($required as $table) {
            if (!catalog_v2_table_exists($conn, $table)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('catalog_v2_normalize_value')) {
    function catalog_v2_normalize_value(string $value): string
    {
        $text = trim($value);
        $text = preg_replace('/\s+/', ' ', $text ?? '');
        return mb_strtolower($text ?? '');
    }
}

if (!function_exists('catalog_v2_standardize_label')) {
    function catalog_v2_standardize_label(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value ?? '');
        if ($value === null) {
            return '';
        }
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}

if (!function_exists('catalog_v2_get_or_create_attribute')) {
    function catalog_v2_get_or_create_attribute(PDO $conn, string $code, string $label): int
    {
        $stmt = $conn->prepare("SELECT id FROM attributes WHERE code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        $id = (int)$stmt->fetchColumn();
        if ($id > 0) {
            return $id;
        }

        $insert = $conn->prepare("INSERT INTO attributes (code, label, status) VALUES (:code, :label, :status)");
        $insert->execute([
            'code' => $code,
            'label' => $label,
            'status' => 'active',
        ]);
        return (int)$conn->lastInsertId();
    }
}

if (!function_exists('catalog_v2_get_or_create_attribute_value')) {
    function catalog_v2_get_or_create_attribute_value(PDO $conn, int $attributeId, string $value): int
    {
        $clean = catalog_v2_standardize_label($value);
        if ($clean === '') {
            return 0;
        }
        $normalized = catalog_v2_normalize_value($clean);

        $stmt = $conn->prepare("SELECT id FROM attribute_values WHERE attribute_id = :attribute_id AND normalized_value = :normalized_value LIMIT 1");
        $stmt->execute([
            'attribute_id' => $attributeId,
            'normalized_value' => $normalized,
        ]);
        $id = (int)$stmt->fetchColumn();
        if ($id > 0) {
            return $id;
        }

        $insert = $conn->prepare("INSERT INTO attribute_values (attribute_id, value, normalized_value, sort_order) VALUES (:attribute_id, :value, :normalized_value, :sort_order)");
        $insert->execute([
            'attribute_id' => $attributeId,
            'value' => $clean,
            'normalized_value' => $normalized,
            'sort_order' => 0,
        ]);

        return (int)$conn->lastInsertId();
    }
}

if (!function_exists('catalog_v2_csv_to_values')) {
    function catalog_v2_csv_to_values($source): array
    {
        $parts = is_array($source) ? $source : explode(',', (string)$source);
        $result = [];
        $seen = [];
        foreach ($parts as $value) {
            $clean = catalog_v2_standardize_label((string)$value);
            if ($clean === '') {
                continue;
            }
            $key = catalog_v2_normalize_value($clean);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $clean;
        }
        return $result;
    }
}

if (!function_exists('catalog_v2_build_option_matrix')) {
    function catalog_v2_build_option_matrix(array $groups): array
    {
        $matrix = [[]];
        foreach ($groups as $group) {
            if (empty($group)) {
                continue;
            }
            $next = [];
            foreach ($matrix as $combination) {
                foreach ($group as $item) {
                    $row = $combination;
                    $row[] = $item;
                    $next[] = $row;
                }
            }
            $matrix = $next;
        }

        return empty($matrix) ? [[]] : $matrix;
    }
}

if (!function_exists('catalog_v2_generate_unique_sku')) {
    function catalog_v2_generate_unique_sku(PDO $conn, string $slugBase, string $seed): string
    {
        $slug = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($slugBase));
        $slug = trim((string)$slug, '-');
        if ($slug === '') {
            $slug = 'sku';
        }

        $try = 0;
        while ($try < 10) {
            $hash = substr(strtoupper(sha1($seed . '|' . $try . '|' . random_int(1000, 999999))), 0, 6);
            $sku = strtoupper($slug) . '-' . $hash;
            $stmt = $conn->prepare("SELECT id FROM product_variants WHERE sku = :sku LIMIT 1");
            $stmt->execute(['sku' => $sku]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $sku;
            }
            $try++;
        }

        return strtoupper($slug) . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}

if (!function_exists('catalog_v2_sync_product_from_legacy')) {
    function catalog_v2_sync_product_from_legacy(PDO $conn, array $legacyProduct): ?int
    {
        if (!catalog_v2_table_exists($conn, 'products_v2')) {
            return null;
        }

        $legacyId = (int)($legacyProduct['id'] ?? 0);
        if ($legacyId <= 0) {
            return null;
        }

        $status = ((int)($legacyProduct['product_status'] ?? 1) === 1) ? 'active' : 'inactive';
        $name = trim((string)($legacyProduct['name'] ?? ''));
        $slug = trim((string)($legacyProduct['slug'] ?? ''));
        if ($slug === '') {
            $slug = 'product-' . $legacyId;
        }
        $description = trim((string)($legacyProduct['description'] ?? ''));
        $brand = trim((string)($legacyProduct['brand'] ?? ''));
        $mainImage = trim((string)($legacyProduct['photo'] ?? ''));
        $basePrice = (float)($legacyProduct['price'] ?? 0);
        $stockQty = max(0, (int)($legacyProduct['qty'] ?? 0));
        $categoryId = (int)($legacyProduct['category_id'] ?? 0);
        $subcategoryId = (int)($legacyProduct['subcategory_id'] ?? 0);
        $specsJson = trim((string)($legacyProduct['additional_info'] ?? ''));

        $mapStmt = $conn->prepare("SELECT product_v2_id FROM product_legacy_map WHERE legacy_product_id = :legacy_product_id LIMIT 1");
        $mapStmt->execute(['legacy_product_id' => $legacyId]);
        $productV2Id = (int)$mapStmt->fetchColumn();

        if ($productV2Id > 0) {
            $slugCandidate = $slug;
            $suffix = 1;
            while (true) {
                $slugCheck = $conn->prepare("SELECT id FROM products_v2 WHERE slug = :slug AND id <> :id LIMIT 1");
                $slugCheck->execute(['slug' => $slugCandidate, 'id' => $productV2Id]);
                if (!(int)$slugCheck->fetchColumn()) {
                    break;
                }
                $slugCandidate = $slug . '-' . $suffix;
                $suffix++;
            }
            $slug = $slugCandidate;

            $update = $conn->prepare("UPDATE products_v2
                SET category_id = :category_id,
                    subcategory_id = :subcategory_id,
                    slug = :slug,
                    name = :name,
                    description = :description,
                    brand = :brand,
                    status = :status,
                    base_price = :base_price,
                    main_image = :main_image,
                    specs_json = :specs_json,
                    updated_at = NOW()
                WHERE id = :id");
            $update->execute([
                'category_id' => ($categoryId > 0 ? $categoryId : null),
                'subcategory_id' => ($subcategoryId > 0 ? $subcategoryId : null),
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'brand' => ($brand !== '' ? $brand : null),
                'status' => $status,
                'base_price' => $basePrice,
                'main_image' => ($mainImage !== '' ? $mainImage : null),
                'specs_json' => ($specsJson !== '' ? $specsJson : null),
                'id' => $productV2Id,
            ]);
        } else {
            $slugCandidate = $slug;
            $suffix = 1;
            while (true) {
                $slugCheck = $conn->prepare("SELECT id FROM products_v2 WHERE slug = :slug LIMIT 1");
                $slugCheck->execute(['slug' => $slugCandidate]);
                if (!(int)$slugCheck->fetchColumn()) {
                    break;
                }
                $slugCandidate = $slug . '-' . $suffix;
                $suffix++;
            }
            $slug = $slugCandidate;

            $insert = $conn->prepare("INSERT INTO products_v2
                (category_id, subcategory_id, slug, name, description, brand, status, base_price, main_image, specs_json, needs_variant_stock_review, created_at, updated_at)
                VALUES
                (:category_id, :subcategory_id, :slug, :name, :description, :brand, :status, :base_price, :main_image, :specs_json, :needs_variant_stock_review, NOW(), NOW())");
            $insert->execute([
                'category_id' => ($categoryId > 0 ? $categoryId : null),
                'subcategory_id' => ($subcategoryId > 0 ? $subcategoryId : null),
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'brand' => ($brand !== '' ? $brand : null),
                'status' => $status,
                'base_price' => $basePrice,
                'main_image' => ($mainImage !== '' ? $mainImage : null),
                'specs_json' => ($specsJson !== '' ? $specsJson : null),
                'needs_variant_stock_review' => 0,
            ]);
            $productV2Id = (int)$conn->lastInsertId();

            $mapInsert = $conn->prepare("INSERT INTO product_legacy_map (legacy_product_id, product_v2_id, created_at, updated_at)
                VALUES (:legacy_product_id, :product_v2_id, NOW(), NOW())");
            $mapInsert->execute([
                'legacy_product_id' => $legacyId,
                'product_v2_id' => $productV2Id,
            ]);
        }

        $sizeAttrId = catalog_v2_get_or_create_attribute($conn, 'size', 'Size');
        $colorAttrId = catalog_v2_get_or_create_attribute($conn, 'color', 'Color');
        $materialAttrId = catalog_v2_get_or_create_attribute($conn, 'material', 'Material');

        $sizes = catalog_v2_csv_to_values($legacyProduct['size'] ?? '');
        $colors = catalog_v2_csv_to_values($legacyProduct['color'] ?? '');
        $materials = catalog_v2_csv_to_values($legacyProduct['material'] ?? '');

        $sizeValues = [];
        foreach ($sizes as $size) {
            $valueId = catalog_v2_get_or_create_attribute_value($conn, $sizeAttrId, $size);
            if ($valueId > 0) {
                $sizeValues[] = ['attribute_id' => $sizeAttrId, 'attribute_value_id' => $valueId];
            }
        }

        $colorValues = [];
        foreach ($colors as $color) {
            $valueId = catalog_v2_get_or_create_attribute_value($conn, $colorAttrId, $color);
            if ($valueId > 0) {
                $colorValues[] = ['attribute_id' => $colorAttrId, 'attribute_value_id' => $valueId];
            }
        }

        $materialValues = [];
        foreach ($materials as $material) {
            $valueId = catalog_v2_get_or_create_attribute_value($conn, $materialAttrId, $material);
            if ($valueId > 0) {
                $materialValues[] = ['attribute_id' => $materialAttrId, 'attribute_value_id' => $valueId];
            }
        }

        $groups = [
            (!empty($sizeValues) ? $sizeValues : [['attribute_id' => $sizeAttrId, 'attribute_value_id' => 0]]),
            (!empty($colorValues) ? $colorValues : [['attribute_id' => $colorAttrId, 'attribute_value_id' => 0]]),
            (!empty($materialValues) ? $materialValues : [['attribute_id' => $materialAttrId, 'attribute_value_id' => 0]]),
        ];

        $combinations = catalog_v2_build_option_matrix($groups);
        $variantCount = count($combinations);
        $needsReview = ($variantCount > 1) ? 1 : 0;

        $cleanupVariantOption = $conn->prepare("DELETE vov FROM variant_option_values vov
            INNER JOIN product_variants pv ON pv.id = vov.variant_id
            WHERE pv.product_id = :product_id");
        $cleanupVariantOption->execute(['product_id' => $productV2Id]);

        $cleanupVariants = $conn->prepare("DELETE FROM product_variants WHERE product_id = :product_id");
        $cleanupVariants->execute(['product_id' => $productV2Id]);

        foreach ($combinations as $index => $combo) {
            $signatureParts = [];
            foreach ($combo as $entry) {
                $signatureParts[] = (int)$entry['attribute_id'] . ':' . (int)$entry['attribute_value_id'];
            }
            sort($signatureParts);
            $signature = implode('|', $signatureParts);
            if ($signature === '') {
                $signature = 'default';
            }

            $variantStock = ($index === 0) ? $stockQty : 0;
            if ($variantCount === 1) {
                $variantStock = $stockQty;
            }
            $sku = catalog_v2_generate_unique_sku($conn, $slug, $signature);

            $insertVariant = $conn->prepare("INSERT INTO product_variants
                (product_id, sku, price, stock_qty, image, status, option_signature, created_at, updated_at)
                VALUES
                (:product_id, :sku, :price, :stock_qty, :image, :status, :option_signature, NOW(), NOW())");
            $insertVariant->execute([
                'product_id' => $productV2Id,
                'sku' => $sku,
                'price' => $basePrice,
                'stock_qty' => $variantStock,
                'image' => ($mainImage !== '' ? $mainImage : null),
                'status' => $status,
                'option_signature' => $signature,
            ]);
            $variantId = (int)$conn->lastInsertId();

            foreach ($combo as $entry) {
                $attrValueId = (int)$entry['attribute_value_id'];
                if ($attrValueId <= 0) {
                    continue;
                }
                $insertPivot = $conn->prepare("INSERT INTO variant_option_values (variant_id, attribute_id, attribute_value_id)
                    VALUES (:variant_id, :attribute_id, :attribute_value_id)");
                $insertPivot->execute([
                    'variant_id' => $variantId,
                    'attribute_id' => (int)$entry['attribute_id'],
                    'attribute_value_id' => $attrValueId,
                ]);
            }
        }

        $updateProduct = $conn->prepare("UPDATE products_v2
            SET needs_variant_stock_review = :needs_variant_stock_review,
                updated_at = NOW()
            WHERE id = :id");
        $updateProduct->execute([
            'needs_variant_stock_review' => $needsReview,
            'id' => $productV2Id,
        ]);

        return $productV2Id;
    }
}

if (!function_exists('catalog_v2_find_variant_by_options')) {
    function catalog_v2_find_variant_by_options(PDO $conn, int $productV2Id, string $size, string $color): ?array
    {
        if ($productV2Id <= 0) {
            return null;
        }

        $sizeNorm = catalog_v2_normalize_value($size);
        $colorNorm = catalog_v2_normalize_value($color);

        $sql = "SELECT pv.id, pv.sku, pv.price, pv.stock_qty, pv.status
            FROM product_variants pv
            LEFT JOIN variant_option_values vov_size ON vov_size.variant_id = pv.id
            LEFT JOIN attributes a_size ON a_size.id = vov_size.attribute_id AND a_size.code = 'size'
            LEFT JOIN attribute_values av_size ON av_size.id = vov_size.attribute_value_id
            LEFT JOIN variant_option_values vov_color ON vov_color.variant_id = pv.id
            LEFT JOIN attributes a_color ON a_color.id = vov_color.attribute_id AND a_color.code = 'color'
            LEFT JOIN attribute_values av_color ON av_color.id = vov_color.attribute_value_id
            WHERE pv.product_id = :product_id
              AND pv.status = 'active'";

        $params = ['product_id' => $productV2Id];
        if ($sizeNorm !== '') {
            $sql .= " AND COALESCE(av_size.normalized_value, '') = :size_norm";
            $params['size_norm'] = $sizeNorm;
        }
        if ($colorNorm !== '') {
            $sql .= " AND COALESCE(av_color.normalized_value, '') = :color_norm";
            $params['color_norm'] = $colorNorm;
        }
        $sql .= " LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $variant = $stmt->fetch(PDO::FETCH_ASSOC);
        return $variant ?: null;
    }
}

if (!function_exists('catalog_v2_get_product_by_slug')) {
    function catalog_v2_get_product_by_slug(PDO $conn, string $slug): ?array
    {
        if (!catalog_v2_ready($conn)) {
            return null;
        }

        $stmt = $conn->prepare("SELECT p.*, c.name AS catname, sc.name AS subcatname
            FROM products_v2 p
            LEFT JOIN category c ON c.id = p.category_id
            LEFT JOIN category sc ON sc.id = p.subcategory_id
            WHERE p.slug = :slug
              AND p.status = 'active'
            LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            return null;
        }

        $variantStmt = $conn->prepare("SELECT pv.id, pv.sku, pv.price, pv.stock_qty, pv.image, pv.option_signature
            FROM product_variants pv
            WHERE pv.product_id = :product_id
              AND pv.status = 'active'
            ORDER BY pv.id ASC");
        $variantStmt->execute(['product_id' => (int)$product['id']]);
        $variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($variants as &$variant) {
            $optStmt = $conn->prepare("SELECT a.code, a.label, av.value
                FROM variant_option_values vov
                INNER JOIN attributes a ON a.id = vov.attribute_id
                INNER JOIN attribute_values av ON av.id = vov.attribute_value_id
                WHERE vov.variant_id = :variant_id");
            $optStmt->execute(['variant_id' => (int)$variant['id']]);
            $variant['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($variant);

        $product['variants'] = $variants;
        return $product;
    }
}
