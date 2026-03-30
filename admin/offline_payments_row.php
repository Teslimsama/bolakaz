<?php
include 'session.php';
require_once __DIR__ . '/../lib/sales_snapshot.php';

if(isset($_POST['id'])){
    $id = $_POST['id'];
    $conn = $pdo->open();
    
    $output = array('id'=>$id, 'history'=>'', 'total'=>0, 'paid'=>0, 'balance'=>0);
    
    try{
        $sumSql = app_sales_detail_total_sum_sql($conn, 'details', 'products');
        $stmt = $conn->prepare("SELECT 
            (SELECT {$sumSql} FROM details LEFT JOIN products ON products.id=details.product_id WHERE details.sales_id=sales.id) AS total_amount
            FROM sales WHERE id=:id");
        $stmt->execute(['id'=>$id]);
        $row = $stmt->fetch();
        $total = (float)$row['total_amount'];
        
        $output['total_formatted'] = app_money($total);
        
        $stmt = $conn->prepare("SELECT * FROM offline_payments WHERE sales_id=:id ORDER BY payment_date DESC, id DESC");
        $stmt->execute(['id'=>$id]);
        
        $paid = 0;
        $history = '<table class="table table-bordered table-striped"><thead><th>Date</th><th>Method</th><th>Amount</th></thead><tbody>';
        foreach($stmt as $prow){
            $paid += (float)$prow['amount'];
            $history .= "<tr>
                <td>".date('M d, Y', strtotime($prow['payment_date']))."</td>
                <td>".e($prow['payment_method'])."</td>
                <td>".app_money($prow['amount'])."</td>
            </tr>";
        }
        $history .= '</tbody></table>';
        
        if($paid == 0) $history = '<p class="text-center">No payments recorded yet.</p>';
        
        $output['history'] = $history;
        $output['paid_formatted'] = app_money($paid);
        $output['balance_formatted'] = app_money($total - $paid);
    }
    catch(PDOException $e){
        $output['history'] = $e->getMessage();
    }
    
    $pdo->close();
    echo json_encode($output);
}
?>
