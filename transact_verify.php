<?php
//  $customerid = $_SESSION['id'] ;
include_once 'session.php';

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
        "Authorization: Bearer sk_test_5ed56c4ca780722ce08e4cdbdd05cc4338fe9ac7",
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

      $date = date("Y-m-d ");

      $conn = $pdo->open();

      try {

        $stmt = $conn->prepare("INSERT INTO sales (user_id, tx_ref, Status, sales_date) VALUES (:user_id, :tx_ref, :Status, :sales_date)");
        $stmt->execute(['user_id' => $user['id'], 'tx_ref' => $payid, 'Status'=>$status, 'sales_date' => $date]);
        $salesid = $conn->lastInsertId();

        try {
          $stmt = $conn->prepare("SELECT * FROM cart LEFT JOIN products ON products.id=cart.product_id WHERE user_id=:user_id");
          $stmt->execute(['user_id' => $user['id']]);

          foreach ($stmt as $row) {
            $stmt = $conn->prepare("INSERT INTO details (sales_id, product_id, quantity) VALUES (:sales_id, :product_id, :quantity)");
            $stmt->execute(['sales_id' => $salesid, 'product_id' => $row['product_id'], 'quantity' => $row['quantity']]);
            $subtraction_value = $row['quantity'];
            $current_value= $row['qty'];
            $new_value = $current_value - $subtraction_value;
            $id= $row['product_id'];
            $sql = "UPDATE products SET qty = '$new_value' WHERE id = $id";
            $sqll= $conn->prepare($sql);
            $sqll->execute(['qty'=>$new_value]);
           


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
    } else {
      header("location:checkout#payment");
      echo $result;
    }
    ?>
