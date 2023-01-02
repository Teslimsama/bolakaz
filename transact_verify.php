<?php
//  $customerid = $_SESSION['id'] ;
include_once 'includes/session.php';

$ref = $_GET['reference'];
if ($ref == "") {
  header("location:javascript://history.go(-1)");
}
?>
    <?php
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($ref),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer sk_test_b6e69229a47fa4f88ca61ebe3c855cf9d4014ebb",
        "Cache-Control: no-cache",
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
      echo $response;
      $result = json_decode($response);
    }
    if ($result->data->status == 'success') {
      $status = $result->data->status;
      $payid = $result->data->reference;
      $amount = $result->data->amount;
      // $lname = $result->data->customer->last_name;
      // $fname = $result->data->customer->first_name;
      // $fullname = $fname . ' ' . $lname;
      // $Cus_email = $result->data->customer->email;
      date_default_timezone_set('Africa/lagos');
      
      $date = date("Y-m-d H:i:s");

      $conn = $pdo->open();

      try {

        $stmt = $conn->prepare("INSERT INTO sales (user_id, tx_ref, sales_date) VALUES (:user_id, :tx_ref, :sales_date)");
        $stmt->execute(['user_id' => $user['id'], 'tx_ref' => $payid, 'sales_date' => $date]);
        $salesid = $conn->lastInsertId();

        try {
          $stmt = $conn->prepare("SELECT * FROM cart LEFT JOIN products ON products.id=cart.product_id WHERE user_id=:user_id");
          $stmt->execute(['user_id' => $user['id']]);

          foreach ($stmt as $row) {
            $stmt = $conn->prepare("INSERT INTO details (sales_id, product_id, quantity) VALUES (:sales_id, :product_id, :quantity)");
            $stmt->execute(['sales_id' => $salesid, 'product_id' => $row['product_id'], 'quantity' => $row['quantity']]);
          }

          $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=:user_id");
          $stmt->execute(['user_id' => $user['id']]);
          header('location: profile#trans');

          exit;
          $_SESSION['success'] = 'Transaction successful. Thank you.';
        } catch (PDOException $e) {
          $_SESSION['error'] = $e->getMessage();
        }
      } catch (PDOException $e) {
        $_SESSION['error'] = $e->getMessage();
      }

      $pdo->close();
    }else {
      header("location:checkout#payment");
    }
    ?>
