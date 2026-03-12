<?php
include 'session.php';

$conn = $pdo->open();

if (isset($_GET['page'])) {
    $data = [];
    $limit = 8;
    $page = max(1, (int)$_GET['page']);
    $start = ($page - 1) * $limit;

    $where = [];
    $params = [];
    $searchQuery = [];

    if (!empty($_GET['category'])) {
        $slug = trim((string)$_GET['category']);
        $stmt = $conn->prepare('SELECT id FROM category WHERE cat_slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($cat['id'])) {
            $where[] = 'category_id = :category_id';
            $params['category_id'] = (int)$cat['id'];
        }
    }

    if (isset($_GET['gender_filter']) && $_GET['gender_filter'] !== '') {
        $gender = trim((string)$_GET['gender_filter']);
        $where[] = 'gender = :gender';
        $params['gender'] = $gender;
        $searchQuery[] = 'gender_filter=' . rawurlencode($gender);
    }

    $allowedPriceFilters = [
        'price < 1000',
        'price > 1000 AND price < 5000',
        'price > 5000 AND price < 10000',
        'price > 10000 AND price < 20000',
        'price > 20000',
    ];
    if (!empty($_GET['price_filter'])) {
        $priceFilter = strtoupper(trim((string)$_GET['price_filter']));
        $normalized = str_replace('&&', 'AND', $priceFilter);
        if (in_array($normalized, $allowedPriceFilters, true)) {
            $where[] = $normalized;
            $searchQuery[] = 'price_filter=' . rawurlencode((string)$_GET['price_filter']);
        }
    }

    if (!empty($_GET['color_filter'])) {
        $colorArray = array_values(array_filter(array_map('trim', explode(',', (string)$_GET['color_filter']))));
        if (!empty($colorArray)) {
            $placeholders = [];
            foreach ($colorArray as $index => $color) {
                $key = 'color_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $color;
            }
            $where[] = 'color IN (' . implode(', ', $placeholders) . ')';
            $searchQuery[] = 'color_filter=' . rawurlencode((string)$_GET['color_filter']);
        }
    }

    $whereSql = '';
    if (!empty($where)) {
        $whereSql = ' WHERE ' . implode(' AND ', $where);
    }

    $baseSql = " FROM products{$whereSql}";
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total{$baseSql}");
    $countStmt->execute($params);
    $totalData = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $listSql = "SELECT id, name, price, photo, color{$baseSql} ORDER BY id ASC LIMIT :start, :limit";
    $listStmt = $conn->prepare($listSql);
    foreach ($params as $key => $value) {
        $listStmt->bindValue(':' . $key, $value);
    }
    $listStmt->bindValue(':start', $start, PDO::PARAM_INT);
    $listStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $listStmt->execute();
    $result = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as $row) {
        $data[] = [
            'catid' => (int)$row['id'],
            'price' => (float)$row['price'],
            'name' => $row['name'],
            'photo' => $row['photo'],
        ];
    }

    $paginationHtml = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-3">';
    $totalLinks = (int)ceil($totalData / $limit);
    $searchQueryString = !empty($searchQuery) ? '&' . implode('&', $searchQuery) : '';
    $pageLink = '';
    $previousLink = '';
    $nextLink = '';
    $pageArray = [];

    if ($totalLinks > 0) {
        if ($totalLinks > 4) {
            if ($page < 5) {
                for ($count = 1; $count <= 5; $count++) {
                    $pageArray[] = $count;
                }
                $pageArray[] = '...';
                $pageArray[] = $totalLinks;
            } else {
                $endLimit = $totalLinks - 5;
                if ($page > $endLimit) {
                    $pageArray[] = 1;
                    $pageArray[] = '...';
                    for ($count = $endLimit; $count <= $totalLinks; $count++) {
                        $pageArray[] = $count;
                    }
                } else {
                    $pageArray[] = 1;
                    $pageArray[] = '...';
                    for ($count = $page - 1; $count <= $page + 1; $count++) {
                        $pageArray[] = $count;
                    }
                    $pageArray[] = '...';
                    $pageArray[] = $totalLinks;
                }
            }
        } else {
            for ($count = 1; $count <= $totalLinks; $count++) {
                $pageArray[] = $count;
            }
        }

        for ($count = 0; $count < count($pageArray); $count++) {
            if ($page === $pageArray[$count]) {
                $pageLink .= '<li class="page-item active"><a class="page-link" href="#">' . $pageArray[$count] . '</a></li>';
                $previousId = $pageArray[$count] - 1;
                $nextId = $pageArray[$count] + 1;

                if ($previousId > 0) {
                    $previousLink = "<li class='page-item'><a class='page-link' href='javascript:load_product(" . $previousId . ",`" . $searchQueryString . "`)' aria-label='Previous'><span aria-hidden='true'>&laquo;</span><span class='sr-only'>Previous</span></a></li>";
                } else {
                    $previousLink = "<li class='page-item disabled'><a class='page-link' href='#'><span aria-hidden='true'>&laquo;</span><span class='sr-only'>Previous</span></a></li>";
                }

                if ($nextId >= $totalLinks) {
                    $nextLink = "<li class='page-item disabled'><a class='page-link' href='#'><span aria-hidden='true'>&raquo;</span><span class='sr-only'>Next</span></a></li>";
                } else {
                    $nextLink = "<li class='page-item'><a class='page-link' href='javascript:load_product(" . $nextId . ",`" . $searchQueryString . "`)'><span aria-hidden='true'>&raquo;</span><span class='sr-only'>Next</span></a></li>";
                }
            } else {
                if ($pageArray[$count] === '...') {
                    $pageLink .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                } else {
                    $pageLink .= '<li class="page-item"><a class="page-link" href="javascript:load_product(' . $pageArray[$count] . ', `' . $searchQueryString . '`)">' . $pageArray[$count] . '</a></li>';
                }
            }
        }
    }

    $paginationHtml .= $previousLink . $pageLink . $nextLink . '</ul></nav>';

    echo json_encode([
        'data' => $data,
        'pagination' => $paginationHtml,
        'total_data' => $totalData,
    ]);
}

if (isset($_GET['action'])) {
    $data = [];

    $query = "SELECT gender, COUNT(id) AS Total FROM products GROUP BY gender";
    foreach ($conn->query($query) as $row) {
        $data['gender'][] = [
            'name' => $row['gender'],
            'total' => $row['Total'],
        ];
    }

    $query = "SELECT color, COUNT(id) AS Total FROM products GROUP BY color";
    foreach ($conn->query($query) as $row) {
        $data['color'][] = [
            'name' => $row['color'],
            'total' => $row['Total'],
        ];
    }

    $query = "SELECT size, COUNT(id) AS Total FROM products GROUP BY size";
    foreach ($conn->query($query) as $row) {
        $data['size'][] = [
            'name' => $row['size'],
            'total' => $row['Total'],
        ];
    }

    $priceRange = [
        'price < 1000' => 'Under 1000',
        'price > 1000 AND price < 5000' => '1000 - 5000',
        'price > 5000 AND price < 10000' => '5000 - 10000',
        'price > 10000 AND price < 20000' => '10000 - 20000',
        'price > 20000' => 'Over 20000',
    ];

    foreach ($priceRange as $condition => $label) {
        $rangeQuery = "SELECT COUNT(id) AS Total FROM products WHERE {$condition}";
        $subData = ['name' => $label, 'total' => 0, 'condition' => $condition];
        foreach ($conn->query($rangeQuery) as $subRow) {
            $subData['total'] = (int)$subRow['Total'];
        }
        $data['price'][] = $subData;
    }

    echo json_encode($data);
}

