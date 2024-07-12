<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
// session_start();
include_once 'session.php';

// Validate and sanitize input
$ref = filter_input(INPUT_GET, 'reference', FILTER_SANITIZE_SPECIAL_CHARS);
if (empty($ref)) {
  header("location: checkout#payment");
  exit;
}

$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($ref),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_HTTPHEADER => array(
    "Authorization: Bearer " . $_ENV['PAYSTACK_SECRET_KEY'], // Use environment variable
    "Cache-Control: no-cache",
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
// print_r($response);
if ($err) {
  echo "cURL Error #:" . $err;
} else {
  $result = json_decode($response);
  // echo ($result);
  // print_r($result);
  if ($result->data->status == 'success') {
    $status = $result->data->status;
    $email = $result->data->customer->email;
    $phone=$result->data->metadata->customer->phone;
    $payid = $result->data->reference;
    $amount = $result->data->amount;
    $address1 = $result->data->metadata->custom_fields[0]->address1;
    $address2=$result->data->metadata->custom_fields[0]->address2;
    $id=$result->data->metadata->custom_fields[0]->id;
    // echo $address1;
    date_default_timezone_set('Africa/Lagos');
    $date = date("Y-m-d");

    $coupon_id = isset($_SESSION['coupon']) ? $_SESSION['coupon']['id'] : 0;
    $shipping_id = isset($_SESSION['shipping']['shipping_price']) ? $_SESSION['shipping']['shipping_id'] : 0;
    try {
      $conn = $pdo->open();
      $stmt = $conn->prepare("INSERT INTO sales (user_id, tx_ref, Status, shipping_id, coupon_id, address_1, address_2, phone, email,  sales_date) VALUES (:user_id, :tx_ref, :status, :shipping_id, :coupon_id, :address_1, :address_2, :phone, :email, :sales_date)");
      $stmt->execute(['user_id' => $user['id'], 'tx_ref' => $payid, 'status' => $status, 'shipping_id' => $shipping_id, 'coupon_id' => $coupon_id, 'address_1' => $address1, 'address_2' => $address2, 'phone' => $phone, 'email' => $email, 'sales_date' => $date]);
      $salesid = $conn->lastInsertId();

      $stmt = $conn->prepare("SELECT * FROM cart LEFT JOIN products ON products.id=cart.product_id WHERE user_id=:user_id");
      $stmt->execute(['user_id' => $user['id']]);

      foreach ($stmt as $row) {
        $stmt = $conn->prepare("INSERT INTO details (sales_id, product_id, quantity) VALUES (:sales_id, :product_id, :quantity)");
        $stmt->execute(['sales_id' => $salesid, 'product_id' => $row['product_id'], 'quantity' => $row['quantity']]);

        $new_value = $row['qty'] - $row['quantity'];
        $stmt = $conn->prepare("UPDATE products SET qty = :new_value WHERE id = :id");
        $stmt->execute(['new_value' => $new_value, 'id' => $row['product_id']]);
      }

      $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=:user_id");
      $stmt->execute(['user_id' => $user['id']]);

      $_SESSION['success'] = 'Transaction successful. Thank you.';
      header("location: profile#trans");
      exit;
    } catch (PDOException $e) {
      $_SESSION['error'] = $e->getMessage();
    }

    $pdo->close();
  } else {
    $_SESSION['error'] = 'Transaction failed.';
    header("location: checkout#payment");
    exit;
  }
}
