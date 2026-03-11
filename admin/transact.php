<?php
include 'session.php';

header('Content-Type: application/json; charset=UTF-8');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['list' => '', 'date' => '', 'transaction' => '', 'total' => app_money(0)]);
    exit;
}

$conn = $pdo->open();
$output = ['list' => '', 'date' => '', 'transaction' => '', 'total' => app_money(0)];

try {
    $stmt = $conn->prepare("SELECT details.quantity, products.name, products.price, sales.tx_ref, sales.sales_date
        FROM details
        LEFT JOIN products ON products.id = details.product_id
        LEFT JOIN sales ON sales.id = details.sales_id
        WHERE details.sales_id = :id");
    $stmt->execute(['id' => $id]);

    $total = 0.0;
    foreach ($stmt as $row) {
        $output['transaction'] = e((string)($row['tx_ref'] ?? ''));
        $output['date'] = !empty($row['sales_date']) ? date('M d, Y', strtotime((string)$row['sales_date'])) : '';

        $price = (float)($row['price'] ?? 0);
        $qty = (int)($row['quantity'] ?? 0);
        $subtotal = $price * $qty;
        $total += $subtotal;

        $output['list'] .= "
            <tr class='prepend_items'>
                <td>" . e((string)($row['name'] ?? '')) . "</td>
                <td>" . app_money($price) . "</td>
                <td>" . $qty . "</td>
                <td>" . app_money($subtotal) . "</td>
            </tr>
        ";
    }

    $output['total'] = '<b>' . app_money($total) . '</b>';
} catch (Throwable $e) {
    $output['list'] = "<tr class='prepend_items'><td colspan='4'>Unable to load transaction details.</td></tr>";
} finally {
    $pdo->close();
}

echo json_encode($output);
