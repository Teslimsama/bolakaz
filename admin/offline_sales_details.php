<?php
include 'session.php';

if(isset($_POST['id'])){
    $id = $_POST['id'];
    $conn = $pdo->open();
    
    $output = array('customer'=>'', 'date'=>'', 'status'=>'', 'items'=>'', 'total'=>'');
    
    try{
        $stmt = $conn->prepare("SELECT sales.*, users.firstname, users.lastname FROM sales LEFT JOIN users ON users.id=sales.user_id WHERE sales.id=:id");
        $stmt->execute(['id'=>$id]);
        $row = $stmt->fetch();
        
        $output['customer'] = e($row['firstname'].' '.$row['lastname']);
        if(empty(trim($output['customer']))) $output['customer'] = 'Guest';
        $output['date'] = date('M d, Y', strtotime($row['sales_date']));
        
        if($row['payment_status'] == 'paid') $output['status'] = '<span class="label label-success">Paid</span>';
        elseif($row['payment_status'] == 'partial') $output['status'] = '<span class="label label-warning">Partial</span>';
        else $output['status'] = '<span class="label label-danger">Unpaid</span>';
        
        $stmt = $conn->prepare("SELECT details.*, products.name, products.price FROM details LEFT JOIN products ON products.id=details.product_id WHERE details.sales_id=:id");
        $stmt->execute(['id'=>$id]);
        
        $total = 0;
        $items = '';
        foreach($stmt as $it){
            $sub = $it['price'] * $it['quantity'];
            $total += $sub;
            $items .= "<tr>
                <td>".e($it['name'])."</td>
                <td>".app_money($it['price'])."</td>
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
