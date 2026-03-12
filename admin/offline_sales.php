<?php include 'session.php'; ?>
<?php include 'header.php'; ?>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

  <?php include 'navbar.php'; ?>
  <?php include 'menubar.php'; ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Offline Sales History
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Offline Sales</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <?php
        if(isset($_SESSION['error'])){
          echo "
            <div class='alert alert-danger alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-warning'></i> Error!</h4>
              ".$_SESSION['error']."
            </div>
          ";
          unset($_SESSION['error']);
        }
        if(isset($_SESSION['success'])){
          echo "
            <div class='alert alert-success alert-dismissible'>
              <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
              <h4><i class='icon fa fa-check'></i> Success!</h4>
              ".$_SESSION['success']."
            </div>
          ";
          unset($_SESSION['success']);
        }
      ?>
      <div class="row">
        <div class="col-xs-12">
          <div class="box">
            <div class="box-header with-border">
              <a href="#add_offline" data-toggle="modal" class="btn btn-primary btn-sm btn-flat"><i class="fa fa-plus"></i> New Offline Sale</a>
            </div>
            <div class="box-body table-responsive">
              <table id="example1" class="table table-bordered">
                <thead>
                  <th>Date</th>
                  <th>Customer</th>
                  <th>Progress</th>
                  <th>Total</th>
                  <th>Paid</th>
                  <th>Balance</th>
                  <th>Status</th>
                  <th>Tools</th>
                </thead>
                <tbody>
                  <?php
                    $conn = $pdo->open();

                    try{
                      $stmt = $conn->prepare("SELECT 
                          sales.id AS salesid, 
                          sales.sales_date, 
                          sales.due_date,
                          sales.payment_status,
                          users.firstname, 
                          users.lastname, 
                          (SELECT SUM(details.quantity * products.price) FROM details LEFT JOIN products ON products.id=details.product_id WHERE details.sales_id=sales.id) AS total_amount,
                          (SELECT COALESCE(SUM(offline_payments.amount), 0) FROM offline_payments WHERE offline_payments.sales_id=sales.id) AS amount_paid
                        FROM sales 
                        LEFT JOIN users ON users.id=sales.user_id 
                        WHERE sales.is_offline = 1
                        ORDER BY sales.sales_date DESC");
                      $stmt->execute();
                      foreach($stmt as $row){
                        $total = (float)$row['total_amount'];
                        $paid = (float)$row['amount_paid'];
                        $balance = $total - $paid;
                        $percent = ($total > 0) ? round(($paid / $total) * 100) : 0;
                        
                        $status_label = '';
                        if($row['payment_status'] == 'paid') $status_label = '<span class="label label-success">Paid</span>';
                        elseif($row['payment_status'] == 'partial') $status_label = '<span class="label label-warning">Partial</span>';
                        else $status_label = '<span class="label label-danger">Unpaid</span>';

                        $name = e($row['firstname'].' '.$row['lastname']);
                        if(empty(trim($name))) $name = 'Guest';

                        echo "
                          <tr>
                            <td>".date('M d, Y', strtotime($row['sales_date']))."</td>
                            <td>".$name."</td>
                            <td>
                              <div class='progress progress-xs'>
                                <div class='progress-bar progress-bar-primary' style='width: ".$percent."%'></div>
                              </div>
                              <small>".$percent."% Complete</small>
                            </td>
                            <td>".app_money($total)."</td>
                            <td>".app_money($paid)."</td>
                            <td>".app_money($balance)."</td>
                            <td>".$status_label."</td>
                            <td>
                              <button class='btn btn-info btn-sm view_details btn-flat' data-id='".$row['salesid']."'><i class='fa fa-search'></i> Details</button>
                              <button class='btn btn-success btn-sm manage_payments btn-flat' data-id='".$row['salesid']."'><i class='fa fa-money'></i> Payments</button>
                            </td>
                          </tr>
                        ";
                      }
                    }
                    catch(PDOException $e){
                      echo $e->getMessage();
                    }

                    $pdo->close();
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
     
  </div>
  <?php include 'footer.php'; ?>

</div>
<!-- ./wrapper -->

<?php include 'scripts.php'; ?>
<?php include 'offline_sales_modal.php'; ?>
<script>
$(function(){
  $(document).on('click', '.manage_payments', function(e){
    e.preventDefault();
    $('#manage_payments_modal').modal('show');
    var id = $(this).data('id');
    getPaymentRow(id);
  });

  $(document).on('click', '.view_details', function(e){
    e.preventDefault();
    $('#offline_details_modal').modal('show');
    var id = $(this).data('id');
    getOfflineSaleRow(id);
  });
});

function getPaymentRow(id){
  $.ajax({
    type: 'POST',
    url: 'offline_payments_row.php',
    data: {id:id},
    dataType: 'json',
    success: function(response){
      $('.sales_id').val(response.id);
      $('#payment_history').html(response.history);
      $('#total_sale_amount').html(response.total_formatted);
      $('#total_paid_amount').html(response.paid_formatted);
      $('#remaining_balance').html(response.balance_formatted);
    }
  });
}

function getOfflineSaleRow(id){
  $.ajax({
    type: 'POST',
    url: 'offline_sales_details.php',
    data: {id:id},
    dataType: 'json',
    success: function(response){
      $('#detail_customer').html(response.customer);
      $('#detail_date').html(response.date);
      $('#detail_status').html(response.status);
      $('#detail_items').html(response.items);
      $('#detail_total').html(response.total);
    }
  });
}
</script>
</body>
</html>
