<?php
include 'session.php';
require_once __DIR__ . '/../lib/offline_statement.php';
require_once __DIR__ . '/../lib/sync.php';
require_once __DIR__ . '/../lib/customer_accounts.php';
require_once __DIR__ . '/../lib/sales_snapshot.php';

if(isset($_POST['add'])){
    $user_id = (int)$_POST['user_id'];
    $sales_date = $_POST['sales_date'];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : NULL;
    $initial_payment = (float)($_POST['initial_payment'] ?? 0);
    $payment_method = $_POST['payment_method'];
    $customer_name = trim((string)($_POST['customer_name'] ?? ''));
    $customer_phone = app_statement_sanitize_phone_snapshot($_POST['customer_phone'] ?? '');
    
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
        $detailIds = [];
        $initialPaymentId = 0;

        if ($user_id > 0) {
            $userStmt = $conn->prepare("SELECT firstname, lastname, phone FROM users WHERE id = :id LIMIT 1");
            $userStmt->execute(['id' => $user_id]);
            $userRow = $userStmt->fetch();

            if (!$userRow) {
                throw new RuntimeException('Selected customer could not be found.');
            }

            if ($customer_name === '') {
                $customer_name = trim((string)$userRow['firstname'].' '.(string)$userRow['lastname']);
            }
            if ($customer_phone === '') {
                $customer_phone = app_statement_sanitize_phone_snapshot($userRow['phone'] ?? '');
            }
        }

        if ($customer_name === '') {
            throw new RuntimeException('Customer name is required for offline sales.');
        }

        if ($user_id <= 0) {
            $customer = app_customer_create_incomplete_profile($conn, [
                'full_name' => $customer_name,
                'phone' => $customer_phone,
            ]);
            $user_id = (int) $customer['id'];
            sync_enqueue_or_fail($conn, 'users', $user_id);
        }

        $statementShareToken = app_statement_generate_unique_token($conn);

        $stmt = $conn->prepare("INSERT INTO sales (user_id, is_offline, sales_date, due_date, tx_ref, Status, payment_status, customer_name, statement_share_token, phone) VALUES (:user_id, 1, :sales_date, :due_date, :tx_ref, :status, :pstatus, :customer_name, :statement_share_token, :phone)");
        $stmt->execute([
            'user_id'=>$user_id,
            'sales_date'=>$sales_date,
            'due_date'=>$due_date,
            'tx_ref'=>$tx_ref,
            'status'=>'success',
            'pstatus'=>'unpaid',
            'customer_name' => $customer_name,
            'statement_share_token' => $statementShareToken,
            'phone' => ($customer_phone !== '' ? $customer_phone : null),
        ]);
        $sales_id = $conn->lastInsertId();

        $total_amount = 0;
        for($i=0; $i<count($products); $i++){
            $p_id = (int)$products[$i];
            $qty = (int)$qtys[$i];

            if($p_id <= 0 || $qty <= 0) continue;

            $stmt = $conn->prepare("SELECT name, slug, price FROM products WHERE id=:id");
            $stmt->execute(['id'=>$p_id]);
            $prow = $stmt->fetch();
            if (!$prow) {
                continue;
            }
            $price = $prow['price'];
            $total_amount += ($price * $qty);

            $detailIds[] = app_sales_insert_detail_row(
                $conn,
                (int) $sales_id,
                $p_id,
                $qty,
                (float) $price,
                (string) ($prow['name'] ?? ''),
                (string) ($prow['slug'] ?? '')
            );
        }

        if($initial_payment > 0){
            $stmt = $conn->prepare("INSERT INTO offline_payments (sales_id, amount, payment_method, payment_date, note) VALUES (:sales_id, :amount, :method, :date, :note)");
            $stmt->execute(['sales_id'=>$sales_id, 'amount'=>$initial_payment, 'method'=>$payment_method, 'date'=>$sales_date, 'note'=>'Initial payment']);
            $initialPaymentId = (int) $conn->lastInsertId();

            $pstatus = ($initial_payment >= $total_amount) ? 'paid' : 'partial';
            $stmt = $conn->prepare("UPDATE sales SET payment_status=:pstatus WHERE id=:id");
            $stmt->execute(['pstatus'=>$pstatus, 'id'=>$sales_id]);
        }

        sync_enqueue_or_fail($conn, 'sales', (int) $sales_id);
        foreach ($detailIds as $detailId) {
            sync_enqueue_or_fail($conn, 'details', $detailId);
        }
        if ($initialPaymentId > 0) {
            sync_enqueue_or_fail($conn, 'offline_payments', $initialPaymentId);
        }

        $conn->commit();
        $_SESSION['success'] = 'Offline sale added successfully.';
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
    $_SESSION['error'] = 'Fill up the form first.';
}

header('location: offline_sales.php');
?>
