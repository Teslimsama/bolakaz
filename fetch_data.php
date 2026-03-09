<?php
include 'session.php';

if (!isset($_POST['action'])) {
    exit;
}

$conn = $pdo->open();

$cat = isset($_POST['cat']) ? trim((string)$_POST['cat']) : '0';
$limit = 12;
$page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
$start = ($page - 1) * $limit;

$where = ["product_status = '1'"];
$params = [];

if ($cat !== '0' && $cat !== '') {
    $where[] = "category_name = :cat";
    $params['cat'] = $cat;
}

if (!empty($_POST['minimum_price']) && !empty($_POST['maximum_price'])) {
    $min = (float)$_POST['minimum_price'];
    $max = (float)$_POST['maximum_price'];
    if ($min <= $max) {
        $where[] = "price BETWEEN :min_price AND :max_price";
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

$appendInFilter('brand', 'brand');
$appendInFilter('category_name', 'category');
$appendInFilter('color', 'color');
$appendInFilter('material', 'material');
$appendInFilter('size', 'size');

$allowedSorts = [
    'newest' => 'id DESC',
    'most_viewed' => 'counter DESC',
];
$sorting = isset($_POST['sorting']) ? (string)$_POST['sorting'] : 'newest';
$orderBy = $allowedSorts[$sorting] ?? $allowedSorts['newest'];

$whereSql = ' WHERE ' . implode(' AND ', $where);
$baseSql = " FROM products{$whereSql}";

$countStmt = $conn->prepare("SELECT COUNT(*) AS total{$baseSql}");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT *{$baseSql} ORDER BY {$orderBy} LIMIT :start, :limit";
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
        <div class="col-lg-4 col-md-6 col-sm-12 pb-1">
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
                    <a href="detail.php?product=' . $slug . '" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                </div>
            </div>
        </div>';
    }
} else {
    $output = '<h3 class="text-center my-5">No Data Found</h3>';
}

$totalPages = max(1, (int)ceil($totalProducts / $limit));
$pagination = '<ul class="pagination justify-content-center mb-3">';

if ($page > 1) {
    $prevPage = $page - 1;
    $pagination .= "<li class='page-item'><a class='page-link' href='#' data-page='{$prevPage}'><span aria-hidden='true'>&laquo;</span><span class='sr-only'>Previous</span></a></li>";
} else {
    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'><span aria-hidden='true'>&laquo;</span><span class='sr-only'>Previous</span></a></li>";
}

for ($i = 1; $i <= $totalPages; $i++) {
    $active = ($i === $page) ? 'active' : '';
    $pagination .= "<li class='page-item {$active}'><a class='page-link' href='#' data-page='{$i}'>{$i}</a></li>";
}

if ($page < $totalPages) {
    $nextPage = $page + 1;
    $pagination .= "<li class='page-item'><a class='page-link' href='#' data-page='{$nextPage}'><span aria-hidden='true'>&raquo;</span><span class='sr-only'>Next</span></a></li>";
} else {
    $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'><span aria-hidden='true'>&raquo;</span><span class='sr-only'>Next</span></a></li>";
}

$pagination .= '</ul>';

echo $output . $pagination;
