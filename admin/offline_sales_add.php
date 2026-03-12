<?php
include 'session.php';

if(isset($_POST['add'])){
    $user_id = (int)$_POST['user_id'];
    $sales_date = $_POST['sales_date'];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : NULL;
    $initial_payment = (float)($_POST['initial_payment'] ?? 0);
    $payment_method = $_POST['payment_method'];
    
    $products = $_POST['products'];
    $qtys = $_POST['qty'];

    if(empty($products)){
        $_SESSION['error'] = 'No products selected.';
        header('location: offline_sales.php');
        exit();
    }

    $conn = $pdo->open();
    $tx_ref = 'OFFLINE-'.strtoupper(bin2hex(random_bytes(4)));

    try{
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO sales (user_id, is_offline, sales_date, due_date, tx_ref, Status, payment_status) VALUES (:user_id, 1, :sales_date, :due_date, :tx_ref, :status, :pstatus)");
        $stmt->execute(['user_id'=>$user_id, 'sales_date'=>$sales_date, 'due_date'=>$due_date, 'tx_ref'=>$tx_ref, 'status'=>'success', 'pstatus'=>'unpaid']);
        $sales_id = $conn->lastInsertId();

        $total_amount = 0;
        for($i=0; $i<count($products); $i++){
            $p_id = (int)$products[$i];
            $qty = (int)$qtys[$i];

            if($p_id <= 0 || $qty <= 0) continue;

            $stmt = $conn->prepare("SELECT price FROM products WHERE id=:id");
            $stmt->execute(['id'=>$p_id]);
            $prow = $stmt->fetch();
            $price = $prow['price'];
            $total_amount += ($price * $qty);

            $stmt = $conn->prepare("INSERT INTO details (sales_id, product_id, quantity) VALUES (:sales_id, :product_id, :quantity)");
            $stmt->execute(['sales_id'=>$sales_id, 'product_id'=>$p_id, 'quantity'=>$qty]);
        }

        if($initial_payment > 0){
            $stmt = $conn->prepare("INSERT INTO offline_payments (sales_id, amount, payment_method, payment_date, note) VALUES (:sales_id, :amount, :method, :date, :note)");
            $stmt->execute(['sales_id'=>$sales_id, 'amount'=>$initial_payment, 'method'=>$payment_method, 'date'=>$sales_date, 'note'=>'Initial payment']);

            $pstatus = ($initial_payment >= $total_amount) ? 'paid' : 'partial';
            $stmt = $conn->prepare("UPDATE sales SET payment_status=:pstatus WHERE id=:id");
            $stmt->execute(['pstatus'=>$pstatus, 'id'=>$sales_id]);
        }

        $conn->commit();
        $_SESSION['success'] = 'Offline sale added successfully.';
    }
    catch(PDOException $e){
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }

    $pdo->close();
}
else{
    $_SESSION['error'] = 'Fill up the form first.';
}

header('location: offline_sales.php');
?>
