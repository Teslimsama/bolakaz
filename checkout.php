<?php include 'includes/session.php';
    
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Title Page-->
    <title>Bolakaz</title>
    <!-- favicon  -->
    <link rel="apple-touch-icon" sizes="180x180" href="favicomatic/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicomatic/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicomatic/favicon-16x16.png">
    <link rel="manifest" href="favicomatic/site.webmanifest">
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

    <script src="https://js.paystack.co/v1/inline.js"></script>

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <?php
    include "includes/header.php"
    ?>
    <?php include 'includes/navbar.php'; ?>
    <!-- Footer End -->

    <!-- Navbar End -->


    <!-- Page Header Start -->
    <div class="container-fluid bg-secondary mb-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 300px">
            <h1 class="font-weight-semi-bold text-uppercase mb-3">Checkout</h1>
            <div class="d-inline-flex">
                <p class="m-0"><a href="">Home</a></p>
                <p class="m-0 px-2">-</p>
                <p class="m-0">Checkout</p>
            </div>
        </div>
    </div>
    <!-- Page Header End -->

    <form id="payForm">
        <!-- Checkout Start -->
        <div class="container-fluid pt-5">
            <div class="row px-xl-5">
                <div class="col-lg-8">
                    <div class="mb-4">
                        <h4 class="font-weight-semi-bold mb-4">Billing Address</h4>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>First Name</label>
                                <input class="form-control" id="first-name" type="text" value="<?php echo $user['firstname'] ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Last Name</label>
                                <input class="form-control" id="last-name" type="text" value="<?php echo $user['lastname'] ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>E-mail</label>
                                <input class="form-control" id="email-address" type="text" value="<?php echo $user['email'] ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Mobile No</label>
                                <input class="form-control" type="text" id="phone" value="<?php echo (!empty($user['phone'])) ?  $user['phone'] : 'Moblie No'; ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Address Line 1</label>
                                <input class="form-control" type="text" value="<?php echo (!empty($user['address'])) ?  $user['address'] : '123 Street'; ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Address Line 2</label>
                                <input class="form-control" type="text" value="123 Street">
                            </div>
                            <!-- <div class="col-md-6 form-group">
                            <label>Country</label>
                            <select class="custom-select">
                                <option selected>United States</option>
                                <option>Afghanistan</option>
                                <option>Albania</option>
                                <option>Algeria</option>
                            </select>
                        </div> -->
                            <div class="col-md-6 form-group">
                                <label>City</label>
                                <input class="form-control" type="text" value="New York">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>State</label>
                                <input class="form-control" type="text" value="New York">
                            </div>
                            <div class="col-md-12 form-group">
                                <label>ZIP Code</label>
                                <input class="form-control" type="text" value="123">
                            </div>
                            <div class="col-md-12 form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="newaccount">
                                    <label class="custom-control-label" for="newaccount">Create an account</label>
                                </div>
                            </div>
                            <div class="col-md-12 form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="shipto">
                                    <label class="custom-control-label" for="shipto" data-toggle="collapse" data-target="#shipping-address">Ship to different address</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="collapse mb-4" id="shipping-address">
                        <h4 class="font-weight-semi-bold mb-4">Shipping Address</h4>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>First Name</label>
                                <input class="form-control" type="text" value="John">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Last Name</label>
                                <input class="form-control" type="text" value="Doe">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>E-mail</label>
                                <input class="form-control" type="text" value="example@email.com">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Mobile No</label>
                                <input class="form-control" type="text" value="+123 456 789">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Address Line 1</label>
                                <input class="form-control" type="text" value="123 Street">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Address Line 2</label>
                                <input class="form-control" type="text" value="123 Street">
                            </div>
                            <!-- <div class="col-md-6 form-group">
                            <label>Country</label>
                            <select class="custom-select">
                                <option selected>United States</option>
                                <option>Afghanistan</option>
                                <option>Albania</option>
                                <option>Algeria</option>
                            </select>
                        </div> -->
                            <div class="col-md-6 form-group">
                                <label>City</label>
                                <input class="form-control" type="text" value="New York">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>State</label>
                                <input class="form-control" type="text" value="New York">
                            </div>
                            <div class="col-md-12 form-group">
                                <label>ZIP Code</label>
                                <input class="form-control" type="text" value="123">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-secondary mb-5">
                        <div class="card-header bg-secondary border-0">
                            <h4 class="font-weight-semi-bold m-0">Order Total</h4>
                        </div>
                        <div class="card-body">
                            <h5 class='font-weight-medium mb-3'>Products</h5>


                            <div id="order"></div>
                        </div>

                    </div>
                    <div class="card border-secondary mb-5">
                        <div class="card-header bg-secondary border-0">
                            <h4 class="font-weight-semi-bold m-0">Payment</h4>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-radio">
                                    <input type="radio" class="custom-control-input" name="payment" onclick="paystack()" id="paypal">
                                    <label class="custom-control-label" for="paypal">Paystack</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-radio">
                                    <input type="radio" class="custom-control-input" name="payment" onclick="flutterwave()" id="directcheck">
                                    <label class="custom-control-label" for="directcheck">Flutterwave</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-radio">
                                    <input type="radio" class="custom-control-input" name="payment" onclick="bank_transfer()" id="banktransfer">
                                    <label class="custom-control-label" for="banktransfer">Bank Transfer</label>
                                </div>
                            </div>
                        </div>
                        <div id="paystack" class="card-footer border-secondary bg-transparent">
                            <button onclick="payWithPaystack()" type="submit" class="btn btn-lg btn-block btn-primary font-weight-bold my-3 py-3">Place Order</button>
                        </div>
                        <div style="display: none;" id="flutterwave" class="card-footer border-secondary bg-transparent">
                            <button onclick="payNow()" type="submit" class="btn btn-lg btn-block btn-primary font-weight-bold my-3 py-3">Place Order</button>
                        </div>
                        <div style="display: none;" id="bank_transfer" class="card-footer border-secondary bg-transparent">
                            <button type="submit" class="btn btn-lg btn-block btn-primary font-weight-bold my-3 py-3">Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Checkout End -->
    </form>

    <!-- <script>
        const paymentForm = document.getElementById('payForm');
        paymentForm.addEventListener("submit", payWithPaystack, false);

        function payWithPaystack(e) {
            e.preventDefault();

            let handler = PaystackPop.setup({
                key: 'pk_test_3d44964799de7e2a5abdbf2eef2fbe6852e60833', // Replace with your public key
                email: document.getElementById("email-address").value,
                amount: document.getElementById("amount").value * 100,
                firstname: document.getElementById("first-name").value,
                lastname: document.getElementById("last-name").value,
                phone: document.getElementById("phone").value,
                ref: 'unibooks' + Math.floor((Math.random() * 1000000000) + 1), // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
                // label: "Optional string that replaces customer email"
                onClose: function() {
                    // window.location
                    alert('Failed Transaction.');
                },
                callback: function(response) {
                    let message = 'Payment complete! Your Reference Number: ' + response.reference + ' Thank you!';
                    alert(message);

                    window.location = "http://localhost/my_project/transact_verify?reference=" + response.reference;

                }
            });

            handler.openIframe();
        }
    </script> -->
    <!-- Footer Start -->
    <script src="js/checkout.js"></script>

    <?php
    include "includes/footer.php"
    ?>
    <!-- Footer End -->
    <script>
        let div = document.getElementById('paystack');
        let display = 0;
        let div2 = document.getElementById('flutterwave');
        let div3 = document.getElementById('bank_transfer');

        function paystack() {

            div.style.display = 'block';
            div2.style.display = 'none';
            div3.style.display = 'none';


        }

        function flutterwave() {

            div.style.display = 'none';
            div2.style.display = 'block';
            div3.style.display = 'none';

        }

        function bank_transfer() {

            div3.style.display = 'block';
            div2.style.display = 'none';
            div.style.display = 'none';

        }
    </script>

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
                url: 'checkout_details.php',
                dataType: 'json',
                success: function(response) {
                    $('#order').html(response);
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