<?php
include 'session.php';
require_once __DIR__ . '/lib/catalog_v2.php';

if (!isset($_POST['action'])) {
    exit;
}

$conn = $pdo->open();

$cat = isset($_POST['cat']) ? trim((string)$_POST['cat']) : '0';
$limit = 12;
$page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
$start = ($page - 1) * $limit;

$isV2 = catalog_v2_ready($conn);
$where = [$isV2 ? "p.status = 'active'" : "product_status = '1'"];
$params = [];

if ($cat !== '0' && $cat !== '') {
    $where[] = $isV2 ? "p.slug IS NOT NULL AND p.category_id IN (SELECT id FROM category WHERE cat_slug = :cat)" : "category_name = :cat";
    $params['cat'] = $cat;
}

if (isset($_POST['search'])) {
    $search = trim((string)$_POST['search']);
    if ($search !== '') {
        $where[] = $isV2 ? "(p.name LIKE :search OR p.description LIKE :search)" : "(name LIKE :search OR description LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
}

if (!empty($_POST['minimum_price']) && !empty($_POST['maximum_price'])) {
    $min = (float)$_POST['minimum_price'];
    $max = (float)$_POST['maximum_price'];
    if ($min <= $max) {
        $where[] = $isV2 ? "COALESCE(vp_min.min_price, p.base_price, 0) BETWEEN :min_price AND :max_price" : "price BETWEEN :min_price AND :max_price";
        $params['min_price'] = $min;
        $params['max_price'] = $max;
    }
}

$appendInFilter = function (string $column, string $inputKey) use (&$where, &$params): void {
    if (!isset($_POST[$inputKey]) || !is_array($_POST[$inputKey]) || empty($_POST[$inputKey])) {
        return;
    }

    $values = array_values(array_filter($_POST[$inputKey], static function ($value) {
        return is_string($value) && trim($value) !== '';
    }));

    if (empty($values)) {
        return;
    }

    $placeholders = [];
    foreach ($values as $index => $value) {
        $key = "{$inputKey}_{$index}";
        $placeholders[] = ':' . $key;
        $params[$key] = trim($value);
    }

    $where[] = sprintf('%s IN (%s)', $column, implode(', ', $placeholders));
};

$appendInFilter($isV2 ? 'p.brand' : 'brand', 'brand');
$appendInFilter($isV2 ? 'c.cat_slug' : 'category_name', 'category');
if (!$isV2) {
    $appendInFilter('color', 'color');
    $appendInFilter('material', 'material');
    $appendInFilter('size', 'size');
}

if ($isV2) {
    $appendAttributeFilter = function (string $attributeCode, string $inputKey) use (&$where, &$params): void {
        if (!isset($_POST[$inputKey]) || !is_array($_POST[$inputKey]) || empty($_POST[$inputKey])) {
            return;
        }

        $values = array_values(array_filter($_POST[$inputKey], static function ($value) {
            return is_string($value) && trim($value) !== '';
        }));
        if (empty($values)) {
            return;
        }

        $placeholders = [];
        foreach ($values as $index => $value) {
            $key = "{$inputKey}_{$index}";
            $placeholders[] = ':' . $key;
            $params[$key] = catalog_v2_normalize_value(trim($value));
        }

        $where[] = "EXISTS (
            SELECT 1
            FROM product_variants pvf
            INNER JOIN variant_option_values vovf ON vovf.variant_id = pvf.id
            INNER JOIN attributes af ON af.id = vovf.attribute_id
            INNER JOIN attribute_values avf ON avf.id = vovf.attribute_value_id
            WHERE pvf.product_id = p.id
              AND pvf.status = 'active'
              AND af.code = :attr_code_{$inputKey}
              AND avf.normalized_value IN (" . implode(', ', $placeholders) . ")
        )";
        $params["attr_code_{$inputKey}"] = $attributeCode;
    };

    $appendAttributeFilter('size', 'size');
    $appendAttributeFilter('color', 'color');
    $appendAttributeFilter('material', 'material');
}

$allowedSorts = [
    'newest' => 'id DESC',
    'most_viewed' => 'counter DESC',
];
$sorting = isset($_POST['sorting']) ? (string)$_POST['sorting'] : 'newest';
$orderBy = $allowedSorts[$sorting] ?? $allowedSorts['newest'];

$whereSql = ' WHERE ' . implode(' AND ', $where);
if ($isV2) {
    $orderByV2 = ($sorting === 'most_viewed') ? 'p.id DESC' : 'p.id DESC';
    $baseSql = " FROM products_v2 p
        LEFT JOIN category c ON c.id = p.category_id
        LEFT JOIN (
            SELECT product_id, MIN(price) AS min_price
            FROM product_variants
            WHERE status = 'active'
            GROUP BY product_id
        ) vp_min ON vp_min.product_id = p.id{$whereSql}";
} else {
    $orderByV2 = '';
    $baseSql = " FROM products{$whereSql}";
}

$countStmt = $conn->prepare("SELECT COUNT(*) AS total{$baseSql}");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = $isV2
    ? "SELECT p.id, p.slug, p.name, p.main_image AS photo, COALESCE(vp_min.min_price, p.base_price, 0) AS price{$baseSql} ORDER BY {$orderByV2} LIMIT :start, :limit"
    : "SELECT *{$baseSql} ORDER BY {$orderBy} LIMIT :start, :limit";
$stmt = $conn->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, (string)$value, PDO::PARAM_STR);
}
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = '';
if (!empty($result)) {
    foreach ($result as $row) {
        $image = app_image_url($row['photo'] ?? '');
        $name = e($row['name'] ?? 'Product');
        $slug = rawurlencode((string)($row['slug'] ?? ''));
        $price = (float)($row['price'] ?? 0);
        $discount = $price * 1.15;

        $output .= '
        <div class="col-lg-4 col-md-6 col-6 pb-1">
            <div class="card product-item border-0 mb-4">
                <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                    <div class="sf-media sf-media-product">
                        <img loading="lazy" class="img-fluid w-100" src="' . e($image) . '" alt="' . $name . '" onerror="this.onerror=null;this.src=\'' . e(app_placeholder_image()) . '\';">
                    </div>
                </div>
                <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                    <h6 class="text-truncate mb-3">' . $name . '</h6>
                    <div class="d-flex justify-content-center">
                        <h6>' . app_money($price) . '</h6>
                        <h6 class="text-muted ml-2"><del>' . app_money($discount) . '</del></h6>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-center bg-light border">
                    <a href="detail?product=' . $slug . '" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                </div>
            </div>
        </div>';
    }
} else {
    $output = '<h3 class="text-center my-5">No Data Found</h3>';
}

$totalPages = max(1, (int)ceil($totalProducts / $limit));
$pagination = '<div class="col-12"><ul class="pagination justify-content-center mb-3">';

if ($page > 1) {
    $prevPage = $page - 1;
    $pagination .= "<li class='page-item'><button type='button' class='page-link' data-page='{$prevPage}' aria-label='Previous'><span aria-hidden='true'>&laquo;</span></button></li>";
} else {
    $pagination .= "<li class='page-item disabled'><button type='button' class='page-link' disabled aria-label='Previous'><span aria-hidden='true'>&laquo;</span></button></li>";
}

for ($i = 1; $i <= $totalPages; $i++) {
    $active = ($i === $page) ? 'active' : '';
    $pagination .= "<li class='page-item {$active}'><button type='button' class='page-link' data-page='{$i}'>{$i}</button></li>";
}

if ($page < $totalPages) {
    $nextPage = $page + 1;
    $pagination .= "<li class='page-item'><button type='button' class='page-link' data-page='{$nextPage}' aria-label='Next'><span aria-hidden='true'>&raquo;</span></button></li>";
} else {
    $pagination .= "<li class='page-item disabled'><button type='button' class='page-link' disabled aria-label='Next'><span aria-hidden='true'>&raquo;</span></button></li>";
}

$pagination .= '</ul></div>';

echo $output . $pagination;
