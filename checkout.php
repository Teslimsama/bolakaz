<?php include 'session.php';
// Fetch shipping options from the database
$conn = $pdo->open();
$stmt = $conn->prepare("SELECT * FROM shippings WHERE status = :status");
$stmt->execute(['status' => 'active']);
$shipping_options = $stmt->fetchAll();
$pdo->close();

$checkoutUser = [
    'id' => isset($user['id']) ? (int)$user['id'] : 0,
    'firstname' => isset($user['firstname']) ? (string)$user['firstname'] : '',
    'lastname' => isset($user['lastname']) ? (string)$user['lastname'] : '',
    'email' => isset($user['email']) ? (string)$user['email'] : '',
    'phone' => isset($user['phone']) ? (string)$user['phone'] : '',
    'address' => isset($user['address']) ? (string)$user['address'] : '',
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Checkout"; include "head.php"; ?>
</head>

<body>
    <?php
    include "header.php"
    ?>
    <?php include 'navbar.php'; ?>
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
        <?php
        if (isset($_SESSION['error'])) {
            echo "
                        <div class='alert alert-danger alert-dismissible'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            " . $_SESSION['error'] . "
                        </div>
                    ";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "
                        <div class='alert alert-success alert-dismissible'>
                            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                            " . $_SESSION['success'] . "
                        </div>
                    ";
            unset($_SESSION['success']);
        }
        ?>
        <div class="message" id="message" ></div>
        <div class="container-fluid pt-5">
            <div class="row px-xl-5">
                <div class="col-lg-8">
                    <div class="mb-4">
                        <h4 class="font-weight-semi-bold mb-4">Billing Address</h4>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>First Name</label>
                                <input class="form-control" id="first-name" type="text" value="<?php echo e($checkoutUser['firstname']); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Last Name</label>
                                <input class="form-control" id="last-name" type="text" value="<?php echo e($checkoutUser['lastname']); ?>">
                                <input class="form-control" id="id" type="hidden" value="<?php echo $checkoutUser['id']; ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>E-mail</label>
                                <input class="form-control" id="email-address" name="email-address" type="text" value="<?php echo e($checkoutUser['email']); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Mobile No</label>
                                <input class="form-control" type="text" name="phone" id="phone" value="<?php echo e($checkoutUser['phone'] !== '' ? $checkoutUser['phone'] : 'Mobile No'); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Address Line 1</label>
                                <input class="form-control" type="text" name="address1" id="address1" value="<?php echo e($checkoutUser['address'] !== '' ? $checkoutUser['address'] : '123 Street'); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Address Line 2</label>
                                <input class="form-control" type="text" id="address2" name="address2">
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
                                <select id="shipping" name="shipping" class="form-control" required>
                                    <option value="">Select Shipping Option</option>
                                    <?php foreach ($shipping_options as $option) : ?>
                                        <option value="<?php echo (int)$option['id']; ?>"><?php echo e($option['type']); ?></option>

                                    <?php endforeach; ?>

                                </select>

                            </div>
                            <!-- <div class="col-md-12 form-group">
                                <label>ZIP Code</label>
                                <input class="form-control" type="text" value="123">
                            </div> -->
                            <div class="col-md-12 form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="newaccount" onclick="redirect()">
                                    <label class="custom-control-label" for="newaccount">Create an account</label>
                                </div>
                            </div>
                            <!-- <div class="col-md-12 form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="shipto">
                                    <label class="custom-control-label" for="shipto" data-toggle="collapse" data-target="#shipping-address">Ship to different address</label>
                                </div>
                            </div> -->
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
                <div id="payment" class="col-lg-4">
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
                                    <input type="radio" checked class="custom-control-input" name="payment" onclick="paystack()" id="paypal">
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
                            <button onclick="payWithPaystack(event)" type="button" class="btn btn-lg btn-block btn-primary font-weight-bold my-3 py-3">Place Order</button>
                        </div>
                        <div style="display: none;" id="flutterwave" class="card-footer border-secondary bg-transparent">
                            <button onclick="payNow(event)" type="button" class="btn btn-lg btn-block btn-primary font-weight-bold my-3 py-3">Place Order</button>
                        </div>
                        <div style="display: none;" id="bank_transfer" class="card-footer border-secondary bg-transparent">
                            <button type="button" class="btn btn-lg btn-block btn-primary font-weight-bold my-3 py-3" data-bs-toggle="modal" data-bs-target="#staticBackdrop">Place Order</button>
                            <?php
                            include "pay_modal.php"
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Checkout End -->
    </form>

    <!-- Footer Start -->
    <script src="js/checkout.js"></script>

    <?php
    include "footer.php"
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

        function redirect() {
            window.location.href = "https://bolakaz.unibooks.com.ng/signup";
        }
    </script>

    <script>
        $(document).ready(function() {
            $('#shipping').change(function() {
                var shippingId = $(this).val();
                $.ajax({
                    type: 'POST',
                    url: 'shipping.php',
                    data: {
                        shipping_id: shippingId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.success) {
                            if (window.appNotify) {
                                window.appNotify({
                                    message: response.message || 'Shipping option updated.',
                                    type: 'success',
                                    title: 'Shipping Updated'
                                });
                            }
                            getDetails()
                        } else if (window.appNotify) {
                            window.appNotify({
                                message: (response && response.message) || 'Invalid shipping option.',
                                type: 'error',
                                title: 'Shipping Failed'
                            });
                        }
                    },
                    error: function() {
                        if (window.appNotify) {
                            window.appNotify({
                                message: 'Unable to update shipping right now.',
                                type: 'error',
                                title: 'Shipping Failed'
                            });
                        }
                    }
                });
            });
        });
        $(document).ready(function() {
            $('#submitFormButton').click(function() {
                // Collect the form data
                var formData = $('#payForm').serialize();

                // Send the form data to the server using AJAX
                $.ajax({
                    type: 'POST',
                    url: 'bank_transfer.php',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        var data = response || {};
                        if (data.success) {
                            if (window.appNotify) {
                                window.appNotify({
                                    message: data.message || 'Bank transfer request received.',
                                    type: 'success',
                                    title: 'Order Submitted',
                                    delay: 3500
                                });
                            }
                            setTimeout(function() {
                                window.location.href = 'profile#trans';
                            }, 700);
                        } else {
                            $('#message').html('<p style="color: red;">' + data.message + '</p>');
                            if (window.appNotify) {
                                window.appNotify({
                                    message: data.message || 'Unable to process bank transfer.',
                                    type: 'error',
                                    title: 'Bank Transfer Failed'
                                });
                            }
                        }
                    },
                    error: function() {
                        $('#message').html('<p style="color: red;">An error occurred. Please try again later.</p>');
                        if (window.appNotify) {
                            window.appNotify({
                                message: 'An error occurred. Please try again later.',
                                type: 'error',
                                title: 'Request Failed'
                            });
                        }
                    }
                });

                // Close the modal (Bootstrap 5 + jQuery fallback).
                var modalEl = document.getElementById('staticBackdrop');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    var modalInstance = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                    modalInstance.hide();
                } else if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
                    window.jQuery('#staticBackdrop').modal('hide');
                }
            });
        });
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

    <!-- Contact Javascript File -->
    <!-- JavaScript Bundle with Popper -->
    <!-- Template Javascript -->
</body>

</html>
