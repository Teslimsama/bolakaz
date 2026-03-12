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
        'offline_collected' => 0.0,
        'offline_pending' => 0.0,
    ];

    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $monthlyOnlineRevenue = array_fill(0, 12, 0.0);
    $monthlyOfflineRevenue = array_fill(0, 12, 0.0);
    $monthlyOrders = array_fill(0, 12, 0);

    // Filtered Online Revenue (is_offline = 0)
    $onlineRevenueStmt = $conn->prepare("SELECT COALESCE(SUM(d.quantity * p.price), 0) FROM details d INNER JOIN products p ON p.id = d.product_id INNER JOIN sales s ON s.id = d.sales_id WHERE s.is_offline = 0 AND s.sales_date BETWEEN :start AND :end");
    $onlineRevenueStmt->execute(['start' => $startDate, 'end' => $endDate]);
    $onlineTotal = (float)$onlineRevenueStmt->fetchColumn();

    // Filtered Offline Revenue (Sum of offline_payments in range)
    $offlineCollectedStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM offline_payments WHERE payment_date BETWEEN :start AND :end");
    $offlineCollectedStmt->execute(['start' => $startDate, 'end' => $endDate]);
    $metrics['offline_collected'] = (float)$offlineCollectedStmt->fetchColumn();

    $metrics['total_revenue'] = $onlineTotal + $metrics['offline_collected'];

    // Filtered Offline Pending (Volume in range - Collections in range?) 
    // Actually Pending should probably be total debt remaining for sales in that period.
    $offlineVolumeStmt = $conn->prepare("SELECT COALESCE(SUM(d.quantity * p.price), 0) FROM details d INNER JOIN products p ON p.id = d.product_id INNER JOIN sales s ON s.id = d.sales_id WHERE s.is_offline = 1 AND s.sales_date BETWEEN :start AND :end");
    $offlineVolumeStmt->execute(['start' => $startDate, 'end' => $endDate]);
    $offlineVolume = (float)$offlineVolumeStmt->fetchColumn();
    
    // We already have collections in range, but debt is usually all-time for those sales.
    // Simplifying: Show total outstanding balance for ALL matching offline sales in this period.
    $metrics['offline_pending'] = max(0, $offlineVolume - $metrics['offline_collected']);

    $today = date('Y-m-d');
    // Revenue Today (Online + Offline collected today - ignores range filter as it is a fixed KPI)
    $onlineTodayStmt = $conn->prepare("SELECT COALESCE(SUM(d.quantity * p.price), 0) FROM details d INNER JOIN products p ON p.id = d.product_id INNER JOIN sales s ON s.id = d.sales_id WHERE s.sales_date = :sd AND s.is_offline = 0");
    $onlineTodayStmt->execute(['sd' => $today]);
    $onlineToday = (float)$onlineTodayStmt->fetchColumn();

    $offlineTodayStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM offline_payments WHERE payment_date = :pd");
    $offlineTodayStmt->execute(['pd' => $today]);
    $offlineToday = (float)$offlineTodayStmt->fetchColumn();
    $metrics['revenue_today'] = $onlineToday + $offlineToday;

    $ordersStmt = $conn->prepare("SELECT COUNT(*) FROM sales WHERE sales_date BETWEEN :start AND :end");
    $ordersStmt->execute(['start' => $startDate, 'end' => $endDate]);
    $metrics['total_orders'] = (int)$ordersStmt->fetchColumn();

    $productsStmt = $conn->query("SELECT COUNT(*) FROM products");
    $metrics['total_products'] = (int)$productsStmt->fetchColumn();

    $usersStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE created_on BETWEEN :start AND :end");
    $usersStmt->execute(['start' => $startDate, 'end' => $endDate]);
    $metrics['total_users'] = (int)$usersStmt->fetchColumn();

    $lowStockStmt = $conn->query("SELECT COUNT(*) FROM products WHERE qty <= 5 AND (product_status = 1 OR product_status IS NULL)");
    $metrics['low_stock_count'] = (int)$lowStockStmt->fetchColumn();

    // Monthly Online Revenue (Still uses YEAR for the trend chart)
    $monthlyOnlineStmt = $conn->prepare("SELECT MONTH(s.sales_date) AS month_num, COALESCE(SUM(d.quantity * p.price), 0) AS revenue FROM sales s INNER JOIN details d ON d.sales_id = s.id INNER JOIN products p ON p.id = d.product_id WHERE YEAR(s.sales_date) = :year AND s.is_offline = 0 GROUP BY MONTH(s.sales_date)");
    $monthlyOnlineStmt->execute(['year' => $year]);
    foreach ($monthlyOnlineStmt as $row) {
        $monthIdx = (int)$row['month_num'] - 1;
        if ($monthIdx >= 0 && $monthIdx < 12) $monthlyOnlineRevenue[$monthIdx] = round((float)$row['revenue'], 2);
    }

    // Monthly Offline Revenue
    $monthlyOfflineStmt = $conn->prepare("SELECT MONTH(payment_date) AS month_num, SUM(amount) AS revenue FROM offline_payments WHERE YEAR(payment_date) = :year GROUP BY MONTH(payment_date)");
    $monthlyOfflineStmt->execute(['year' => $year]);
    foreach ($monthlyOfflineStmt as $row) {
        $monthIdx = (int)$row['month_num'] - 1;
        if ($monthIdx >= 0 && $monthIdx < 12) $monthlyOfflineRevenue[$monthIdx] = round((float)$row['revenue'], 2);
    }

    $monthlyOrdersStmt = $conn->prepare("SELECT MONTH(sales_date) AS month_num, COUNT(*) AS order_count FROM sales WHERE YEAR(sales_date) = :year GROUP BY MONTH(sales_date)");
    $monthlyOrdersStmt->execute(['year' => $year]);
    foreach ($monthlyOrdersStmt as $row) {
        $monthIdx = (int)$row['month_num'] - 1;
        if ($monthIdx >= 0 && $monthIdx < 12) $monthlyOrders[$monthIdx] = (int)$row['order_count'];
    }

    $topProductsStmt = $conn->prepare("SELECT p.name, COALESCE(SUM(d.quantity * p.price), 0) AS revenue FROM details d INNER JOIN sales s ON s.id = d.sales_id INNER JOIN products p ON p.id = d.product_id WHERE s.sales_date BETWEEN :start_date AND :end_date GROUP BY p.id, p.name ORDER BY revenue DESC LIMIT 10");
    $topProductsStmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $topProducts = [];
    foreach ($topProductsStmt as $row) {
        $topProducts[] = ['name' => (string)$row['name'], 'revenue' => round((float)$row['revenue'], 2)];
    }

    $categorySalesStmt = $conn->prepare("SELECT COALESCE(c.name, 'Uncategorized') AS category_name, COALESCE(SUM(d.quantity * p.price), 0) AS revenue FROM details d INNER JOIN sales s ON s.id = d.sales_id INNER JOIN products p ON p.id = d.product_id LEFT JOIN category c ON c.id = p.category_id WHERE s.sales_date BETWEEN :start_date AND :end_date GROUP BY c.name ORDER BY revenue DESC");
    $categorySalesStmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $categorySales = [];
    foreach ($categorySalesStmt as $row) {
        $categorySales[] = ['category' => (string)$row['category_name'], 'revenue' => round((float)$row['revenue'], 2)];
    }

    echo json_encode([
        'success' => true,
        'filters' => ['year' => $year, 'start_date' => $startDate, 'end_date' => $endDate],
        'cards' => $metrics,
        'monthly_revenue' => [
            'labels' => $months,
            'series_online' => $monthlyOnlineRevenue,
            'series_offline' => $monthlyOfflineRevenue,
        ],
        'monthly_orders' => ['labels' => $months, 'series' => $monthlyOrders],
        'top_products' => $topProducts,
        'category_sales' => $categorySales,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $pdo->close();
}

