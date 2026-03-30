<?php
include 'session.php';
require_once __DIR__ . '/../lib/offline_statement.php';
require_once __DIR__ . '/../lib/sales_snapshot.php';

if(isset($_POST['id'])){
    $id = $_POST['id'];
    $conn = $pdo->open();
    
    $output = array('customer'=>'', 'date'=>'', 'status'=>'', 'items'=>'', 'total'=>'');
    
    try{
        $stmt = $conn->prepare("SELECT sales.*, users.firstname, users.lastname FROM sales LEFT JOIN users ON users.id=sales.user_id WHERE sales.id=:id");
        $stmt->execute(['id'=>$id]);
        $row = $stmt->fetch();
        
        $output['customer'] = e(app_statement_customer_name_from_row((array) $row));
        $output['date'] = date('M d, Y', strtotime($row['sales_date']));
        
        if($row['payment_status'] == 'paid') $output['status'] = '<span class="label label-success">Paid</span>';
        elseif($row['payment_status'] == 'partial') $output['status'] = '<span class="label label-warning">Partial</span>';
        else $output['status'] = '<span class="label label-danger">Unpaid</span>';
        
        $priceSql = app_sales_detail_price_sql($conn, 'details', 'products');
        $nameSql = app_sales_detail_name_sql($conn, 'details', 'products');
        $stmt = $conn->prepare("SELECT details.*, {$nameSql} AS item_name, {$priceSql} AS item_price FROM details LEFT JOIN products ON products.id=details.product_id WHERE details.sales_id=:id");
        $stmt->execute(['id'=>$id]);
        
        $total = 0;
        $items = '';
        foreach($stmt as $it){
            $sub = ((float) $it['item_price']) * ((int) $it['quantity']);
            $total += $sub;
            $items .= "<tr>
                <td>".e((string) ($it['item_name'] ?? 'Item'))."</td>
                <td>".app_money((float) ($it['item_price'] ?? 0))."</td>
                <td>".$it['quantity']."</td>
                <td>".app_money($sub)."</td>
            </tr>";
        }
        
        $output['items'] = $items;
        $output['total'] = app_money($total);
    }
    catch(PDOException $e){
        $output['items'] = 'Error: '.$e->getMessage();
    }
    
    $pdo->close();
    echo json_encode($output);
}
?>
