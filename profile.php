<?php include 'session.php'; ?>
<?php require_once __DIR__ . '/lib/offline_statement.php'; ?>
<?php
if (!isset($_SESSION['user'])) {
    header('location: index');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Profile"; include "head.php"; ?>
</head>

<body>

    <?php
    include "header.php"; ?>
    <?php include 'navbar.php'; ?>
    <!-- Page Header Start -->
    <div class="container-fluid bg-secondary mb-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 300px">
            <h1 class="font-weight-semi-bold text-uppercase mb-3">Profile</h1>
            <div class="d-inline-flex">
                <p class="m-0"><a href="index">Home</a></p>
                <p class="m-0 px-2">-</p>
                <p class="m-0">Profile</p>
            </div>
        </div>
    </div>
    <!-- Page Header End -->
    <div class="container-fluid pt-5">
        <div class="card card-body mx-3 mx-md-4 mt-n6 shadow-sm vw-80">
            <div class="row gx-4 mb-2">
                <div class="col-auto ">
                    <div class="" style=" width: 110px !important; height: 210px !important;">
                        <img src="<?php echo e(app_image_url($user['photo'] ?? '')); ?>" style=" border-radius: 8px; object-fit: cover; height: 100%;" width="100%" onerror="this.onerror=null;this.src='<?php echo e(app_placeholder_image()); ?>';">
                    </div>

                </div>
                <div class="col-sm-9">
                    <div class="float-end">
                        <span class="">
                            <a href="#edit" class="btn btn-primary btn-flat btn-sm" data-toggle="modal"><i class="fa fa-edit"></i> Edit</a>
                        </span>
                    </div>
                    <ul class="list-group">

                        <li class="list-group-item border-0 ps-0 pt-0 text-sm"><strong class="text-dark">Full Name:</strong> &nbsp; <?php echo e(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')); ?>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Mobile:</strong> &nbsp; <?php echo e($user['phone'] ?? ''); ?></li>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Email:</strong> &nbsp; <?php echo e($user['email'] ?? ''); ?></li>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Address:</strong> &nbsp; <?php echo e(!empty($user['address']) ? $user['address'] : 'N/a'); ?></li>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Member since:</strong> &nbsp;<?php echo e(date('M d, Y', strtotime($user['created_on']))); ?></li>

                </div>
            </div>
        </div>
        <div id="trans" class="box box-solid shadow-sm mt-4 card">
            <div class="box-header with-border card-header">
                <h4 class="box-title"><i class="fa fa-calendar"></i> <b>Transaction History</b></h4>
            </div>
            <div class="box-body table-responsive card-body">
                <table class="table table-bordered" id="example1">
                    <thead>
                        <th class="hidden"></th>
                        <th>Date</th>
                        <th>Transaction#</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Full Details</th>
                    </thead>
                    <tbody>
                        <?php
                        $conn = $pdo->open();

                        try {
                            $stmt = $conn->prepare("SELECT sales.*,
                                    (SELECT COALESCE(SUM(d.quantity * p.price), 0)
                                        FROM details d
                                        LEFT JOIN products p ON p.id = d.product_id
                                        WHERE d.sales_id = sales.id) AS order_total,
                                    (SELECT COALESCE(SUM(op.amount), 0)
                                        FROM offline_payments op
                                        WHERE op.sales_id = sales.id) AS amount_paid
                                FROM sales
                                WHERE user_id=:user_id
                                ORDER BY id DESC");
                            $stmt->execute(['user_id' => $user['id']]);
                            foreach ($stmt as $row) {
                                $isOffline = ((int)($row['is_offline'] ?? 0) === 1);
                                $total = (float)($row['order_total'] ?? 0);
                                $amountPaid = (float)($row['amount_paid'] ?? 0);
                                $balance = $isOffline ? max(0, $total - $amountPaid) : 0;
                                $statusText = trim((string)($row['Status'] ?? 'Pending'));
                                if ($isOffline) {
                                    $statusText = app_statement_status_label($row['payment_status'] ?? '');
                                }

                                $actions = "<button class='btn btn-sm btn-flat btn-primary transact' data-id='" . (int)$row['id'] . "'><i class='fa fa-search'></i> View</button>";
                                if ($isOffline) {
                                    $actions .= " <a class='btn btn-sm btn-flat btn-default' href='offline_statement?id=" . (int)$row['id'] . "' target='_blank' rel='noopener'><i class='fa fa-file-text-o'></i> Statement</a>";
                                }

                                echo "
	        									<tr>
	        										<td class='hidden'></td>
	        										<td>" . date('M d, Y', strtotime($row['sales_date'])) . "</td>
	        										<td>" . e($row['tx_ref']) . "</td>
	        										<td>" . e($statusText) . "</td>
	        										<td>" . app_money($total) . "</td>
	        										<td>" . ($isOffline ? app_money($balance) : '&mdash;') . "</td>
	        										<td>" . $actions . "</td>
	        									</tr>
	        								";
                            }
                        } catch (PDOException $e) {
                            echo "There is some problem in connection: " . $e->getMessage();
                        }

                        $pdo->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer Start -->

    <?php
    include "footer.php"; ?>
    <?php include 'profile_modal.php'; ?>

    <!-- Footer End -->
    <script>
        $(function() {
            $(document).on('click', '.transact', function(e) {
                e.preventDefault();
                $('#transaction').modal('show');
                var id = $(this).data('id');
                $.ajax({
                    type: 'POST',
                    url: 'transaction',
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#date').html(response.date);
                        $('#transid').html(response.transaction);
                        $('#detail').prepend(response.list);
                        $('#total').html(response.total);
                    }
                });
            });

            $("#transaction").on("hidden.bs.modal", function() {
                $('.prepend_items').remove();
            });
        });
    </script>

    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <!-- JavaScript Bundle with Popper -->

    <!-- Contact Javascript File -->

    <!-- Template Javascript -->
</body>

</html>
