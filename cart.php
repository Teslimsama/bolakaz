<?php
// session_start();
// require_once('CreateDb.php');

require_once('component.php');


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
    include "includes/header.php"
    ?>


    <!-- Navbar End -->


    <!-- Page Header Start -->
    <div class="container-fluid bg-secondary mb-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 300px">
            <h1 class="font-weight-semi-bold text-uppercase mb-3">Shopping Cart</h1>
            <div class="d-inline-flex">
                <p class="m-0"><a href="">Home</a></p>
                <p class="m-0 px-2">-</p>
                <p class="m-0">Shopping Cart</p>
            </div>
        </div>
    </div>
    <!-- Page Header End -->


    <!-- Cart Start -->
    <div class="container-fluid pt-5">
        <div class="row px-xl-5">
            <div class="col-lg-8 table-responsive mb-5">
                <table class="table table-bordered text-center mb-0">
                    <thead class="bg-secondary text-dark">
                        <tr>
                            <th>Products</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Remove</th>
                        </tr>
                    </thead>

                    <?php include 'includes/scripts.php'; ?>
                    <tbody class="align-middle">
                        <script>
                            var total = 0;
                            $(function() {
                                $(document).on('click', '.cart_delete', function(e) {
                                    e.preventDefault();
                                    var id = $(this).data('id');
                                    $.ajax({
                                        type: 'POST',
                                        url: 'cart_delete.php',
                                        data: {
                                            id: id
                                        },
                                        dataType: 'json',
                                        success: function(response) {
                                            if (!response.error) {
                                                getDetails();
                                                getCart();
                                                getTotal();
                                            }
                                        }
                                    });
                                });

                                $(document).on('click', '.minus', function(e) {
                                    e.preventDefault();
                                    var id = $(this).data('id');
                                    var qty = $('#qty_' + id).val();
                                    if (qty > 1) {
                                        qty--;
                                    }
                                    $('#qty_' + id).val(qty);
                                    $.ajax({
                                        type: 'POST',
                                        url: 'cart_update.php',
                                        data: {
                                            id: id,
                                            qty: qty,
                                        },
                                        dataType: 'json',
                                        success: function(response) {
                                            if (!response.error) {
                                                getDetails();
                                                getCart();
                                                getTotal();
                                            }
                                        }
                                    });
                                });

                                $(document).on('click', '.add', function(e) {
                                    e.preventDefault();
                                    var id = $(this).data('id');
                                    var qty = $('#qty_' + id).val();
                                    qty++;
                                    $('#qty_' + id).val(qty);
                                    $.ajax({
                                        type: 'POST',
                                        url: 'cart_update.php',
                                        data: {
                                            id: id,
                                            qty: qty,
                                        },
                                        dataType: 'json',
                                        success: function(response) {
                                            if (!response.error) {
                                                getDetails();
                                                getCart();
                                                getTotal();
                                            }
                                        }
                                    });
                                });

                                getDetails();
                                getTotal();

                            });

                            function getDetails() {
                                $.ajax({
                                    type: 'POST',
                                    url: 'cart_details.php',
                                    dataType: 'json',
                                    success: function(response) {
                                        $('#tbody').html(response);
                                        getCart();
                                    }
                                });
                            }

                            function getTotal() {
                                $.ajax({
                                    type: 'POST',
                                    url: 'cart_total.php',
                                    dataType: 'json',
                                    success: function(response) {
                                        total = response;
                                    }
                                });
                            }
                        </script>
                    </tbody>
                </table>
            </div>
            <div class="col-lg-4">
                <form class="mb-5" action="">
                    <div class="input-group">
                        <input type="text" class="form-control p-4" placeholder="Coupon Code">
                        <div class="input-group-append">
                            <button class="btn btn-primary">Apply Coupon</button>
                        </div>
                    </div>
                </form>
                <form action="checkout" method="post">
                    <div class="card border-secondary mb-5">
                        <div class="card-header bg-secondary border-0">
                            <h4 class="font-weight-semi-bold m-0">Cart Summary</h4>


                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3 pt-1">
                                <h6 class="font-weight-medium">Subtotal</h6>
                                <h6 class="font-weight-medium"><?php "&#36;" . ".number_format($total, 2)." ?></h6>

                            </div>
                            <div class="d-flex justify-content-between">
                                <h6 class="font-weight-medium">Shipping</h6>
                                <h6 class="font-weight-medium">$10</h6>
                            </div>
                        </div>
                        <div class="card-footer border-secondary bg-transparent">
                            <div class="d-flex justify-content-between mt-2">
                                <h5 class="font-weight-bold">Total</h5>
                                <h5 class="font-weight-bold"><?php "&#36;" . ".number_format($total, 2)." ?></h5>
                            </div>
                            <button type="submit" class="btn btn-block btn-primary my-3 py-3">Proceed To Checkout</button>
                        </div>
                    </div>
            </div>
            </form>
        </div>
    </div>
    <!-- Cart End -->


    <!-- Footer Start -->

    <?php
    include "includes/footer.php"
    ?>
    <!-- Footer End -->



    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Contact Javascript File -->
    <script src="mail/jqBootstrapValidation.min.js"></script>
    <script src="mail/contact.js"></script>
    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>