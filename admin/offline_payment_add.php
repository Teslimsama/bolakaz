<?php
include 'session.php';
require_once __DIR__ . '/../lib/sync.php';

if(isset($_POST['add_payment'])){
    $sales_id = (int)$_POST['sales_id'];
    $amount = (float)$_POST['amount'];
    $method = $_POST['payment_method'];
    $date = $_POST['payment_date'];

    if($sales_id <= 0 || $amount <= 0){
        $_SESSION['error'] = 'Invalid payment details.';
        header('location: offline_sales.php');
        exit();
    }

    $conn = $pdo->open();

    try{
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO offline_payments (sales_id, amount, payment_method, payment_date) VALUES (:sales_id, :amount, :method, :date)");
        $stmt->execute(['sales_id'=>$sales_id, 'amount'=>$amount, 'method'=>$method, 'date'=>$date]);
        $paymentId = (int) $conn->lastInsertId();

        // Recalculate status
        $stmt = $conn->prepare("SELECT SUM(details.quantity * products.price) AS total FROM details LEFT JOIN products ON products.id=details.product_id WHERE details.sales_id=:id");
        $stmt->execute(['id'=>$sales_id]);
        $total = $stmt->fetch()['total'];

        $stmt = $conn->prepare("SELECT SUM(amount) AS paid FROM offline_payments WHERE sales_id=:id");
        $stmt->execute(['id'=>$sales_id]);
        $paid = $stmt->fetch()['paid'];

        $pstatus = ($paid >= $total) ? 'paid' : 'partial';
        $stmt = $conn->prepare("UPDATE sales SET payment_status=:pstatus WHERE id=:id");
        $stmt->execute(['pstatus'=>$pstatus, 'id'=>$sales_id]);

        sync_enqueue_or_fail($conn, 'sales', $sales_id);
        sync_enqueue_or_fail($conn, 'offline_payments', $paymentId);

        $conn->commit();
        $_SESSION['success'] = 'Payment added successfully.';
    }
    catch(Throwable $e){
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }

    $pdo->close();
}
else{
    $_SESSION['error'] = 'Fill up the payment form first.';
}

header('location: offline_sales.php');
?>
