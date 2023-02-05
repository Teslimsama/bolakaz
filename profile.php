<?php include 'session.php'; ?>
<?php
if (!isset($_SESSION['user'])) {
    header('location: index.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Title Page-->
    <title>Bolakaz</title>

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- favicon  -->
    <link rel="apple-touch-icon" sizes="180x180" href="favicomatic/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicomatic/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicomatic/favicon-16x16.png">
    <link rel="manifest" href="favicomatic/site.webmanifest">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/e9de02addb.js" crossorigin="anonymous"></script>
    <!-- CSS only -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">


    <!-- Libraries Stylesheet -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
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
                        <img src="<?php echo (!empty($user['photo'])) ? 'images/' . $user['photo'] : 'images/profile.jpg'; ?>" style=" border-radius: 8px;" width="100%">
                    </div>

                </div>
                <div class="col-sm-9">
                    <div class="float-end">
                        <span class="">
                            <a href="#edit" class="btn btn-primary btn-flat btn-sm" data-toggle="modal"><i class="fa fa-edit"></i> Edit</a>
                        </span>
                    </div>
                    <ul class="list-group">

                        <li class="list-group-item border-0 ps-0 pt-0 text-sm"><strong class="text-dark">Full Name:</strong> &nbsp; <?php echo $user['firstname'] . ' ' . $user['lastname']; ?>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Mobile:</strong> &nbsp; <?php echo $user['phone']; ?></li>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Email:</strong> &nbsp; <?php echo $user['email']; ?></li>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Address:</strong> &nbsp; <?php echo (!empty($user['address'])) ? $user['address'] : 'N/a'; ?></li>
                        <li class="list-group-item border-0 ps-0 text-sm"><strong class="text-dark">Member since:</strong> &nbsp;<?php echo date('M d, Y', strtotime($user['created_on'])); ?></li>

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
                        <th>Amount</th>
                        <th>Full Details</th>
                    </thead>
                    <tbody>
                        <?php
                        $conn = $pdo->open();

                        try {
                            $stmt = $conn->prepare("SELECT * FROM sales WHERE user_id=:user_id ORDER BY id DESC");
                            $stmt->execute(['user_id' => $user['id']]);
                            foreach ($stmt as $row) {
                                $stmt2 = $conn->prepare("SELECT * FROM details LEFT JOIN products ON products.id=details.product_id WHERE sales_id=:id");
                                $stmt2->execute(['id' => $row['id']]);
                                $total = 0;
                                foreach ($stmt2 as $row2) {
                                    $subtotal = $row2['price'] * $row2['quantity'];
                                    $total += $subtotal;
                                }
                                echo "
	        									<tr>
	        										<td class='hidden'></td>
	        										<td>" . date('M d, Y', strtotime($row['sales_date'])) . "</td>
	        										<td>" . $row['tx_ref'] . "</td>
	        										<td>" . $row['status'] . "</td>
	        										<td>&#36;" . number_format($total, 2) . "</td>
	        										<td><button class='btn btn-sm btn-flat btn-primary transact' data-id='" . $row['id'] . "'><i class='fa fa-search'></i> View</button></td>
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
                    url: 'transaction.php',
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
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>

    <!-- Contact Javascript File -->
    <script src="mail/jqBootstrapValidation.min.js"></script>
    <script src="mail/contact.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>