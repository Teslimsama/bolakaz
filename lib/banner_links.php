<?php

if (!function_exists('banner_normalize_slug')) {
    function banner_normalize_slug(string $value): string
    {
        return trim((string)$value);
    }
}

if (!function_exists('banner_parse_destination')) {
    function banner_parse_destination(string $link): array
    {
        $trimmed = trim($link);
        if ($trimmed === '' || strtolower($trimmed) === 'shop') {
            return [
                'type' => 'shop',
                'product_slug' => '',
                'category_slug' => '',
                'resolved_link' => 'shop',
            ];
        }

        $parts = parse_url($trimmed);
        if (!is_array($parts)) {
            return [
                'type' => 'unknown',
                'product_slug' => '',
                'category_slug' => '',
                'resolved_link' => 'shop',
            ];
        }

        $path = trim((string)($parts['path'] ?? ''));
        $query = [];
        if (isset($parts['query'])) {
            parse_str((string)$parts['query'], $query);
        }

        if (strcasecmp($path, 'detail.php') === 0 && !empty($query['product'])) {
            $slug = banner_normalize_slug((string)$query['product']);
            if ($slug !== '') {
                return [
                    'type' => 'product',
                    'product_slug' => $slug,
                    'category_slug' => '',
                    'resolved_link' => 'detail?product=' . rawurlencode($slug),
                ];
            }
        }

        if (strcasecmp($path, 'shop.php') === 0 && !empty($query['category'])) {
            $slug = banner_normalize_slug((string)$query['category']);
            if ($slug !== '') {
                return [
                    'type' => 'category',
                    'product_slug' => '',
                    'category_slug' => $slug,
                    'resolved_link' => 'shop?category=' . rawurlencode($slug),
                ];
            }
        }

        return [
            'type' => 'unknown',
            'product_slug' => '',
            'category_slug' => '',
            'resolved_link' => 'shop',
        ];
    }
}

if (!function_exists('banner_resolve_storefront_link')) {
    function banner_resolve_storefront_link(string $link): string
    {
        $parsed = banner_parse_destination($link);
        return (string)($parsed['resolved_link'] ?? 'shop');
    }
}

if (!function_exists('banner_get_product_name_by_slug')) {
    function banner_get_product_name_by_slug(PDO $conn, string $slug): string
    {
        static $cache = [];
        $key = strtolower($slug);
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $stmt = $conn->prepare("SELECT name FROM products WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $name = (string)$stmt->fetchColumn();
        $cache[$key] = $name;
        return $name;
    }
}

if (!function_exists('banner_get_category_name_by_slug')) {
    function banner_get_category_name_by_slug(PDO $conn, string $slug): string
    {
        static $cache = [];
        $key = strtolower($slug);
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $stmt = $conn->prepare("SELECT name FROM category WHERE cat_slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $name = (string)$stmt->fetchColumn();
        $cache[$key] = $name;
        return $name;
    }
}

if (!function_exists('banner_destination_meta')) {
    function banner_destination_meta(PDO $conn, string $link): array
    {
        $parsed = banner_parse_destination($link);

        if ($parsed['type'] === 'product') {
            $name = banner_get_product_name_by_slug($conn, $parsed['product_slug']);
            if ($name !== '') {
                return [
                    'type' => 'product',
                    'product_slug' => $parsed['product_slug'],
                    'category_slug' => '',
                    'resolved_link' => $parsed['resolved_link'],
                    'display' => 'Product: ' . $name,
                    'is_fallback' => false,
                ];
            }
        }

        if ($parsed['type'] === 'category') {
            $name = banner_get_category_name_by_slug($conn, $parsed['category_slug']);
            if ($name !== '') {
                return [
                    'type' => 'category',
                    'product_slug' => '',
                    'category_slug' => $parsed['category_slug'],
                    'resolved_link' => $parsed['resolved_link'],
                    'display' => 'Category: ' . $name,
                    'is_fallback' => false,
                ];
            }
        }

        return [
            'type' => 'unknown',
            'product_slug' => '',
            'category_slug' => '',
            'resolved_link' => 'shop',
            'display' => 'Fallback: Shop',
            'is_fallback' => true,
        ];
    }
}

if (!function_exists('banner_build_link_from_request')) {
    function banner_build_link_from_request(PDO $conn, array $request, string &$error = ''): ?string
    {
        $type = strtolower(trim((string)($request['destination_type'] ?? '')));
        if ($type === 'product') {
            $slug = banner_normalize_slug((string)($request['product_slug'] ?? ''));
            if ($slug === '') {
                $error = 'Please select a product destination.';
                return null;
            }

            $stmt = $conn->prepare("SELECT slug FROM products WHERE slug = :slug AND product_status = 1 LIMIT 1");
            $stmt->execute(['slug' => $slug]);
            $validSlug = (string)$stmt->fetchColumn();
            if ($validSlug === '') {
                $error = 'Selected product is not available.';
                return null;
            }

            return 'detail?product=' . rawurlencode($validSlug);
        }

        if ($type === 'category') {
            $slug = banner_normalize_slug((string)($request['category_slug'] ?? ''));
            if ($slug === '') {
                $error = 'Please select a category destination.';
                return null;
            }

            $stmt = $conn->prepare("SELECT cat_slug FROM category WHERE cat_slug = :slug AND status = :status LIMIT 1");
            $stmt->execute([
                'slug' => $slug,
                'status' => 'active',
            ]);
            $validSlug = (string)$stmt->fetchColumn();
            if ($validSlug === '') {
                $error = 'Selected category is not available.';
                return null;
            }

            return 'shop?category=' . rawurlencode($validSlug);
        }

        $error = 'Please choose a valid destination type.';
        return null;
    }
}
