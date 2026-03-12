<?php include 'session.php'; ?>
<?php
require_once __DIR__ . '/lib/catalog_v2.php';
$userId = isset($user['id']) ? (int)$user['id'] : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Cart"; include "head.php"; ?>
</head>

<body>

    <?php
    include "header.php"
    ?>
    <?php include 'navbar.php'; ?>



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
                            <th width='65%'>Products</th>
                            <th width='20%'>Price</th>
                            <th>Quantity</th>
                            <th width='30%'>Subtotal</th>
                            <th>Remove</th>
                            <!-- <th>Total</th> -->
                        </tr>
                    </thead>


                    <tbody id="cart">
                    </tbody>
                </table>
            </div>
            <div class="col-lg-4">
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
                <form class="mb-5" method="POST" action="coupon.php">
                    <div class="input-group">
                        <input type="text" class="form-control p-4" name="coupon_code" placeholder="Coupon Code" required>
                        <!-- <input type="hidden" class="form-control p-4" name="user_id" value="<?php echo $user['id'] ?>" placeholder="Coupon Code" required> -->
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">Apply Coupon</button>
                        </div>
                    </div>
                </form>
                <form action="checkout.php" method="post">
                    <div id="checkout" class="card border-secondary mb-5">
                        <div class="card-header bg-secondary border-0">
                            <h4 class="font-weight-semi-bold m-0">Cart Summary</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3 pt-1">
                                <h6 class="font-weight-medium">Subtotal</h6>
                                <h6 class="font-weight-medium">
                                    <?php
                                        if ($userId > 0) {
                                            $conn = $pdo->open();
                                            $stmt = $conn->prepare("SELECT cart.quantity, cart.variant_id, products.price FROM cart LEFT JOIN products on products.id=cart.product_id WHERE user_id=:user_id");
                                            $stmt->execute(['user_id' => $userId]);

                                            $total = 0;
                                            foreach ($stmt as $row) {
                                                $linePrice = (float)($row['price'] ?? 0);
                                                if (catalog_v2_ready($conn) && (int)($row['variant_id'] ?? 0) > 0) {
                                                    $vpStmt = $conn->prepare("SELECT price FROM product_variants WHERE id = :id LIMIT 1");
                                                    $vpStmt->execute(['id' => (int)$row['variant_id']]);
                                                    $variantPrice = $vpStmt->fetchColumn();
                                                    if ($variantPrice !== false) {
                                                        $linePrice = (float)$variantPrice;
                                                    }
                                                }
                                                $subtotal = $linePrice * (int)$row['quantity'];
                                                $total += $subtotal;
                                            }

                                            $pdo->close();
                                            echo app_money($total);
                                        } else {
                                            echo app_money(0);
                                        }
                                        ?>
                                </h6>
                            </div>
                            <div class='d-flex justify-content-between'>
                                <h6 class='font-weight-medium'>Discount</h6>
                                <h6 class='font-weight-medium'>
                                     <?php echo app_money(isset($_SESSION['coupon']) ? (float)$_SESSION['coupon']['value'] : 0); ?>
                                </h6>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <h5 class="font-weight-bold">Total</h5>
                                <h5 class="font-weight-bold">
                                    <?php
                                        if ($userId > 0) {
                                            $conn = $pdo->open();
                                            $stmt = $conn->prepare("SELECT cart.quantity, cart.variant_id, products.price FROM cart LEFT JOIN products on products.id=cart.product_id WHERE user_id=:user_id");
                                            $stmt->execute(['user_id' => $userId]);

                                            $total = 0;
                                            foreach ($stmt as $row) {
                                                $linePrice = (float)($row['price'] ?? 0);
                                                if (catalog_v2_ready($conn) && (int)($row['variant_id'] ?? 0) > 0) {
                                                    $vpStmt = $conn->prepare("SELECT price FROM product_variants WHERE id = :id LIMIT 1");
                                                    $vpStmt->execute(['id' => (int)$row['variant_id']]);
                                                    $variantPrice = $vpStmt->fetchColumn();
                                                    if ($variantPrice !== false) {
                                                        $linePrice = (float)$variantPrice;
                                                    }
                                                }
                                                $subtotal = $linePrice * (int)$row['quantity'];
                                                $total += $subtotal;
                                            }

                                            $discount = isset($_SESSION['coupon']) ? $_SESSION['coupon']['value'] : 0;
                                            $total -= $discount;

                                            $pdo->close();
                                            echo app_money($total);
                                        } else {
                                            echo app_money(0);
                                        }
                                        ?>
                                </h5>
                            </div>
                            <?php
                            echo ($userId > 0) ?
                                '<a href="checkout.php" class="btn btn-block btn-primary my-3 py-3">Proceed To Checkout</a>' :
                                '<a href="signin.php" class="btn btn-primary px-3">Proceed To Checkout</a>';
                            ?>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- Cart End -->


    <!-- Footer Start -->

    <?php
    include "footer.php";
    ?>
    <!-- Footer End -->



    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>

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
                            window.location.reload();
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
                            window.location.reload();
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
                    $('#cart').html(response);
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

    <!-- JavaScript Libraries -->

    <!-- Contact Javascript File -->
    <!-- JavaScript Bundle with Popper -->
    <!-- Template Javascript -->
</body>

</html>
