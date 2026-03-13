<?php

require_once __DIR__ . '/CreateDb.php';
require_once __DIR__ . '/lib/catalog_v2.php';
require_once __DIR__ . '/lib/seo.php';

if (!function_exists('sitemap_xml_escape')) {
    function sitemap_xml_escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}

if (!function_exists('sitemap_table_has_column')) {
    function sitemap_table_has_column(PDO $conn, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . ':' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $stmt = $conn->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column LIMIT 1');
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);
        $cache[$key] = (bool)$stmt->fetchColumn();
        return $cache[$key];
    }
}

if (!function_exists('sitemap_format_lastmod')) {
    function sitemap_format_lastmod($value): ?string
    {
        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }

        $timestamp = strtotime($text);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('c', $timestamp);
    }
}

if (!function_exists('sitemap_add_entry')) {
    function sitemap_add_entry(array &$entries, array &$seen, string $loc, ?string $lastmod = null, string $changefreq = 'weekly', string $priority = '0.7'): void
    {
        $key = strtolower($loc);
        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $entries[] = [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}

header('Content-Type: application/xml; charset=UTF-8');

$entries = [];
$seen = [];

$staticPages = [
    ['path' => '', 'changefreq' => 'daily', 'priority' => '1.0'],
    ['path' => 'shop', 'changefreq' => 'daily', 'priority' => '0.9'],
    ['path' => 'contact', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['path' => 'shipping', 'changefreq' => 'monthly', 'priority' => '0.5'],
    ['path' => 'privacy_policy', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ['path' => 'terms_and_condition', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ['path' => 'return_and_refund_policy', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ['path' => 'cookie_policy', 'changefreq' => 'yearly', 'priority' => '0.3'],
    ['path' => 'disclaimer', 'changefreq' => 'yearly', 'priority' => '0.3'],
];

foreach ($staticPages as $page) {
    $path = (string)$page['path'];
    if ($path !== '' && !is_file(__DIR__ . '/' . $path . '.php')) {
        continue;
    }

    sitemap_add_entry(
        $entries,
        $seen,
        app_absolute_url($path),
        null,
        (string)$page['changefreq'],
        (string)$page['priority']
    );
}

try {
    $categoryStmt = $conn->prepare("SELECT cat_slug FROM category WHERE status = :status AND cat_slug <> '' ORDER BY name ASC");
    $categoryStmt->execute(['status' => 'active']);
    foreach ($categoryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $categorySlug = trim((string)($row['cat_slug'] ?? ''));
        if ($categorySlug === '') {
            continue;
        }

        sitemap_add_entry(
            $entries,
            $seen,
            app_absolute_url('shop', ['category' => $categorySlug]),
            null,
            'weekly',
            '0.8'
        );
    }

    $knownProductSlugs = [];

    if (catalog_v2_table_exists($conn, 'products_v2')) {
        $v2LastmodColumn = sitemap_table_has_column($conn, 'products_v2', 'updated_at') ? 'updated_at' : (sitemap_table_has_column($conn, 'products_v2', 'created_at') ? 'created_at' : '');
        $v2Sql = "SELECT slug" . ($v2LastmodColumn !== '' ? ", {$v2LastmodColumn} AS lastmod" : '') . " FROM products_v2 WHERE status = 'active' AND slug <> '' ORDER BY id DESC";
        foreach ($conn->query($v2Sql) as $row) {
            $slug = trim((string)($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $knownProductSlugs[strtolower($slug)] = true;
            sitemap_add_entry(
                $entries,
                $seen,
                app_absolute_url('detail', ['product' => $slug]),
                sitemap_format_lastmod($row['lastmod'] ?? null),
                'weekly',
                '0.9'
            );
        }
    }

    if (catalog_v2_table_exists($conn, 'products')) {
        $legacyLastmodColumn = sitemap_table_has_column($conn, 'products', 'updated_at') ? 'updated_at' : (sitemap_table_has_column($conn, 'products', 'date_view') ? 'date_view' : '');
        $legacySql = "SELECT slug" . ($legacyLastmodColumn !== '' ? ", {$legacyLastmodColumn} AS lastmod" : '') . " FROM products WHERE product_status = 1 AND slug <> '' ORDER BY id DESC";
        foreach ($conn->query($legacySql) as $row) {
            $slug = trim((string)($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            if (isset($knownProductSlugs[strtolower($slug)])) {
                continue;
            }

            sitemap_add_entry(
                $entries,
                $seen,
                app_absolute_url('detail', ['product' => $slug]),
                sitemap_format_lastmod($row['lastmod'] ?? null),
                'weekly',
                '0.9'
            );
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
foreach ($entries as $entry) {
    echo '  <url>' . PHP_EOL;
    echo '    <loc>' . sitemap_xml_escape($entry['loc']) . '</loc>' . PHP_EOL;
    if (!empty($entry['lastmod'])) {
        echo '    <lastmod>' . sitemap_xml_escape((string)$entry['lastmod']) . '</lastmod>' . PHP_EOL;
    }
    echo '    <changefreq>' . sitemap_xml_escape((string)$entry['changefreq']) . '</changefreq>' . PHP_EOL;
    echo '    <priority>' . sitemap_xml_escape((string)$entry['priority']) . '</priority>' . PHP_EOL;
    echo '  </url>' . PHP_EOL;
}
echo '</urlset>' . PHP_EOL;
