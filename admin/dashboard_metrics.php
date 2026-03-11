<?php
include 'session.php';

header('Content-Type: application/json; charset=UTF-8');

$year = (int)($_GET['year'] ?? date('Y'));
if ($year < 2015 || $year > 2100) {
    $year = (int)date('Y');
}

$startDate = trim((string)($_GET['start_date'] ?? ''));
$endDate = trim((string)($_GET['end_date'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = sprintf('%04d-01-01', $year);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = sprintf('%04d-12-31', $year);
}
if ($startDate > $endDate) {
    $tmp = $startDate;
    $startDate = $endDate;
    $endDate = $tmp;
}

$conn = $pdo->open();

try {
    $metrics = [
        'total_revenue' => 0.0,
        'revenue_today' => 0.0,
        'total_orders' => 0,
        'total_products' => 0,
        'total_users' => 0,
        'low_stock_count' => 0,
    ];

    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $monthlyRevenue = array_fill(0, 12, 0.0);
    $monthlyOrders = array_fill(0, 12, 0);

    $totalRevenueStmt = $conn->query("SELECT COALESCE(SUM(d.quantity * p.price), 0) FROM details d INNER JOIN products p ON p.id = d.product_id");
    $metrics['total_revenue'] = (float)$totalRevenueStmt->fetchColumn();

    $today = date('Y-m-d');
    $todayRevenueStmt = $conn->prepare("SELECT COALESCE(SUM(d.quantity * p.price), 0)
        FROM details d
        INNER JOIN products p ON p.id = d.product_id
        INNER JOIN sales s ON s.id = d.sales_id
        WHERE s.sales_date = :sales_date");
    $todayRevenueStmt->execute(['sales_date' => $today]);
    $metrics['revenue_today'] = (float)$todayRevenueStmt->fetchColumn();

    $ordersStmt = $conn->query("SELECT COUNT(*) FROM sales");
    $metrics['total_orders'] = (int)$ordersStmt->fetchColumn();

    $productsStmt = $conn->query("SELECT COUNT(*) FROM products");
    $metrics['total_products'] = (int)$productsStmt->fetchColumn();

    $usersStmt = $conn->query("SELECT COUNT(*) FROM users");
    $metrics['total_users'] = (int)$usersStmt->fetchColumn();

    $lowStockStmt = $conn->query("SELECT COUNT(*) FROM products WHERE qty <= 5 AND (product_status = 1 OR product_status IS NULL)");
    $metrics['low_stock_count'] = (int)$lowStockStmt->fetchColumn();

    $monthlyRevenueStmt = $conn->prepare("SELECT MONTH(s.sales_date) AS month_num, COALESCE(SUM(d.quantity * p.price), 0) AS revenue
        FROM sales s
        INNER JOIN details d ON d.sales_id = s.id
        INNER JOIN products p ON p.id = d.product_id
        WHERE YEAR(s.sales_date) = :year
        GROUP BY MONTH(s.sales_date)");
    $monthlyRevenueStmt->execute(['year' => $year]);
    foreach ($monthlyRevenueStmt as $row) {
        $monthIdx = (int)$row['month_num'] - 1;
        if ($monthIdx >= 0 && $monthIdx < 12) {
            $monthlyRevenue[$monthIdx] = round((float)$row['revenue'], 2);
        }
    }

    $monthlyOrdersStmt = $conn->prepare("SELECT MONTH(sales_date) AS month_num, COUNT(*) AS order_count
        FROM sales
        WHERE YEAR(sales_date) = :year
        GROUP BY MONTH(sales_date)");
    $monthlyOrdersStmt->execute(['year' => $year]);
    foreach ($monthlyOrdersStmt as $row) {
        $monthIdx = (int)$row['month_num'] - 1;
        if ($monthIdx >= 0 && $monthIdx < 12) {
            $monthlyOrders[$monthIdx] = (int)$row['order_count'];
        }
    }

    $topProductsStmt = $conn->prepare("SELECT p.name, COALESCE(SUM(d.quantity * p.price), 0) AS revenue
        FROM details d
        INNER JOIN sales s ON s.id = d.sales_id
        INNER JOIN products p ON p.id = d.product_id
        WHERE s.sales_date BETWEEN :start_date AND :end_date
        GROUP BY p.id, p.name
        ORDER BY revenue DESC
        LIMIT 10");
    $topProductsStmt->execute([
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
    $topProducts = [];
    foreach ($topProductsStmt as $row) {
        $topProducts[] = [
            'name' => (string)$row['name'],
            'revenue' => round((float)$row['revenue'], 2),
        ];
    }

    $categorySalesStmt = $conn->prepare("SELECT COALESCE(c.name, 'Uncategorized') AS category_name,
            COALESCE(SUM(d.quantity * p.price), 0) AS revenue
        FROM details d
        INNER JOIN sales s ON s.id = d.sales_id
        INNER JOIN products p ON p.id = d.product_id
        LEFT JOIN category c ON c.id = p.category_id
        WHERE s.sales_date BETWEEN :start_date AND :end_date
        GROUP BY c.name
        ORDER BY revenue DESC");
    $categorySalesStmt->execute([
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    $categorySales = [];
    foreach ($categorySalesStmt as $row) {
        $categorySales[] = [
            'category' => (string)$row['category_name'],
            'revenue' => round((float)$row['revenue'], 2),
        ];
    }

    echo json_encode([
        'success' => true,
        'filters' => [
            'year' => $year,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ],
        'cards' => $metrics,
        'monthly_revenue' => [
            'labels' => $months,
            'series' => $monthlyRevenue,
        ],
        'monthly_orders' => [
            'labels' => $months,
            'series' => $monthlyOrders,
        ],
        'top_products' => $topProducts,
        'category_sales' => $categorySales,
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load dashboard metrics right now.',
    ]);
} finally {
    $pdo->close();
}
