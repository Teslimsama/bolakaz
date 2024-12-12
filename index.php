<?php
include 'session.php';

try {
    $stmt = $conn->prepare("SELECT * FROM banner");
    $stmt->execute();
    $carouselItems = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "There was an error fetching the carousel data: " . $e->getMessage();
}
$conn = $pdo->open();
// $stmt = $conn->prepare("SELECT * FROM ads");
$stmt = $conn->prepare("SELECT ads.*, category.cat_slug AS category_slug FROM ads JOIN category ON ads.collection = category.name");
$stmt->execute();
$offers = $stmt->fetchAll();
$pdo->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include 'head.php'; ?>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="favicomatic/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicomatic/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicomatic/favicon-16x16.png">
    <link rel="manifest" href="favicomatic/site.webmanifest">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Font Awesome Kit -->
    <script src="https://kit.fontawesome.com/e9de02addb.js" crossorigin="anonymous"></script>

    <!-- Owl Carousel -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Custom Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'header.php'; ?>

    <style>
        .category-image {
            height: 250px;
            object-fit: cover;
            width: 100%;
        }
    </style>

    <div class="container-fluid">
        <div class="row border-top px-xl-5">
            <div class="col-lg-3 d-none d-lg-block">
                <a class="btn shadow-none d-flex align-items-center justify-content-between bg-primary text-white w-100" data-bs-toggle="collapse" href="#navbar-vertical" style="height: 65px; margin-top: -1px; padding: 0 30px;">
                    <h6 class="m-0">Categories</h6>
                    <i class="fa fa-angle-down text-dark"></i>
                </a>
                <nav class="position-absolute navbar navbar-vertical navbar-light align-items-start p-0 border border-top-0 border-bottom-0 bg-light collapse show" style="width: 21.51%;" id="navbar-vertical">
                    <div class="navbar-nav w-100 overflow-hidden" style="height: 410px">
                        <?php
                        $conn = $pdo->open();
                        try {
                            $stmt = $conn->prepare("SELECT * FROM category");
                            $stmt->execute();
                            foreach ($stmt as $row) {
                                $caton = ucwords($row['name']);
                                echo "
                                    <a class='nav-item nav-link' href='shop.php?category=" . $row['cat_slug'] . "'>"  . htmlspecialchars_decode($caton) . "</a>
                                ";
                            }
                        } catch (PDOException $e) {
                            echo "There is some problem in connection: " . $e->getMessage();
                        }
                        $pdo->close();
                        ?>
                    </div>
                </nav>
            </div>
            <div class="col-lg-9">
                <nav class="navbar navbar-expand-lg bg-light navbar-light py-3 py-lg-0 px-0">
                    <a href="index" class="text-decoration-none d-block d-lg-none">
                        <h1 class="m-0 display-5 font-weight-semi-bold">
                            <span class="text-primary font-weight-bold border px-3 mr-1">B</span>Bolakaz.Ent
                        </h1>
                    </a>
                    <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                        <div class="navbar-nav mr-auto py-0">
                            <a href="index" class="nav-item nav-link active">Home</a>
                            <a href="shop" class="nav-item nav-link">Shop</a>
                            <!-- <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu rounded-0 m-0">
                                    <a href="cart" class="dropdown-item">Shopping Cart</a>
                                    <a href="checkout" class="dropdown-item">Checkout</a>
                                </div>
                            </div> -->
                            <a href="cart" class="nav-item nav-link">Shopping Cart</a>
                            <a href="checkout" class="nav-item nav-link">Checkout</a>
                            <a href="contact" class="nav-item nav-link">Contact</a>
                            <a href="profile" class="nav-item nav-link">Profile</a>
                        </div>
                        <div class="navbar-nav ml-auto py-0">
                            <?php
                            if (isset($_SESSION['user'])) {
                                echo "<a href='logout' class='nav-item nav-link'>Logout</a>";
                            } else {
                                echo "
                                    <a href='signin' class='nav-item nav-link'>Login</a>
                                    <a href='signup' class='nav-item nav-link'>Register</a>
                                ";
                            }
                            ?>
                        </div>
                    </div>
                </nav>

                <!-- Navbar End -->
                <div id="header-carousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php
                        $first = true;
                        foreach ($carouselItems as $item) {
                            $activeClass = $first ? ' active' : '';
                            $first = false;
                            echo "
            <div class='carousel-item$activeClass' style='height: 410px;'>
                <img class='img-fluid' src='images/" . htmlspecialchars($item['image_path']) . "' alt='Image'>
                <div class='carousel-caption d-flex flex-column align-items-center justify-content-center'>
                    <div class='p-3' style='max-width: 700px;'>
                        <h4 class='text-light text-uppercase font-weight-medium mb-3'>" . htmlspecialchars($item['caption_text']) . "</h4>
                        <h3 class='display-4 text-white font-weight-semi-bold mb-4'>" . htmlspecialchars($item['caption_heading']) . "</h3>
                        <a href='" . htmlspecialchars($item['link']) . "' class='btn btn-light py-2 px-3'>Shop Now</a>
                    </div>
                </div>
            </div>";
                        }
                        ?>
                    </div>
                    <a class="carousel-control-prev" href="#header-carousel" role="button" data-bs-slide="prev">
                        <div class="btn btn-dark" style="width: 45px; height: 45px;">
                            <span class="carousel-control-prev-icon mb-n2"></span>
                        </div>
                    </a>
                    <a class="carousel-control-next" href="#header-carousel" role="button" data-bs-slide="next">
                        <div class="btn btn-dark" style="width: 45px; height: 45px;">
                            <span class="carousel-control-next-icon mb-n2"></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Start -->
    <div class="container-fluid pt-5">
        <div class="row px-xl-5 pb-3">
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <h1 class="fa fa-check text-primary m-0 mr-3"></h1>
                    <h5 class="font-weight-semi-bold m-0">Quality Product</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <h1 class="fa fa-shipping-fast text-primary m-0 mr-2"></h1>
                    <h5 class="font-weight-semi-bold m-0">Free Shipping</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <h1 class="fas fa-exchange-alt text-primary m-0 mr-3"></h1>
                    <h5 class="font-weight-semi-bold m-0">14-Day Return</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <h1 class="fa fa-phone-volume text-primary m-0 mr-3"></h1>
                    <h5 class="font-weight-semi-bold m-0">24/7 Support</h5>
                </div>
            </div>
        </div>
    </div>
    <!-- Featured End -->

    <!-- Categories Start -->
    <div class="container-fluid pt-5">
        <div class="row px-xl-5 pb-3">
            <?php
            $conn = $pdo->open();
            try {
                $stmt = $conn->prepare("SELECT * FROM category WHERE is_parent = 1");
                $stmt->execute();
                foreach ($stmt as $row) {
                    echo "
    <div class='col-lg-3 col-md-4 col-sm-6 pb-1'>
        <div class='cat-item d-flex flex-column border mb-4' style='padding: 30px;'>
            <p class='text-right'>" . htmlspecialchars_decode(ucwords($row['name'])) . "</p>
            <a href='shop.php?category=" . $row['cat_slug'] . "' class='cat-img position-relative overflow-hidden mb-3'>
                <img class='img-fluid category-image' src='images/" . $row['cat_image'] . "' alt='" . htmlspecialchars_decode(ucwords($row['name'])) . "'>
            </a>
            <h5 class='font-weight-semi-bold m-0'> " . htmlspecialchars_decode(ucwords($row['name'])) . "</h5>
        </div>
    </div>
";
                }
            } catch (PDOException $e) {
                echo "There is some problem in connection: " . $e->getMessage();
            }
            $pdo->close();
            ?>
        </div>
    </div>
    <!-- Categories End -->
    <!-- Offer Start -->
    <!-- <div class="container-fluid offer pt-5">
        <div class="row px-xl-5">
            <div class="col-md-6 pb-4">
                <div class="position-relative bg-secondary text-center text-md-left text-white mb-2 py-5 px-5">
                    <img src="img/offer-1.png" alt="">
                    <div class="position-relative" style="z-index: 1;">
                        <h5 class="text-uppercase text-primary mb-3">20% off the all order</h5>
                        <h1 class="mb-4 font-weight-semi-bold">Spring Collection</h1>
                        <a href="shop" class="btn btn-outline-primary py-md-2 px-md-3">Shop Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 pb-4">
                <div class="position-relative bg-secondary text-center text-md-right text-white mb-2 py-5 px-5">
                    <img src="img/offer-1.png" alt="">
                    <div class="position-relative" style="z-index: 1;">
                        <h5 class="text-uppercase text-primary mb-3">20% off the all order</h5>
                        <h1 class="mb-4 font-weight-semi-bold">Spring Collection</h1>
                        <a href="shop" class="btn btn-outline-primary py-md-2 px-md-3">Shop Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 pb-4">
                <div class="position-relative bg-secondary text-center text-md-right text-white mb-2 py-5 px-5">
                    <img src="img/offer-1.png" alt="">
                    <div class="position-relative" style="z-index: 1;">
                        <h5 class="text-uppercase text-primary mb-3">20% off the all order</h5>
                        <h1 class="mb-4 font-weight-semi-bold">Spring Collection</h1>
                        <a href="shop" class="btn btn-outline-primary py-md-2 px-md-3">Shop Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 pb-4">
                <div class="position-relative bg-secondary text-center text-md-left text-white mb-2 py-5 px-5">
                    <img src="img/offer-2.png" alt="">
                    <div class="position-relative" style="z-index: 1;">
                        <h5 class="text-uppercase text-primary mb-3">20% off the all order</h5>
                        <h1 class="mb-4 font-weight-semi-bold">Winter Collection</h1>
                        <a href="shop" class="btn btn-outline-primary py-md-2 px-md-3">Shop Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
    <div class="container-fluid offer pt-5">
        <div class="row px-xl-5">
            <?php foreach ($offers as $offer) : ?>
                <div class="col-md-6 pb-4">
                    <div class="position-relative bg-secondary text-center <?php echo $offer['text_align']; ?> text-white mb-2 py-5 px-5">
                        <img src="images/<?php echo $offer['image_path']; ?>" alt="">
                        <div class="position-relative" style="z-index: 1;">
                            <h5 class="text-uppercase text-primary mb-3"><?php echo $offer['discount']; ?>% off the all order</h5>
                            <h1 class="mb-4 font-weight-semi-bold"><?php echo ucwords($offer['collection']); ?></h1>
                            <a href="shop.php?category=<?php echo $offer['link']; ?>" class="btn btn-outline-primary py-md-2 px-md-3">Shop Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Offer End -->

    <!-- Products Start -->
    <div class="container-fluid pt-5">
        <div class="text-center mb-4">
            <h2 class="section-title px-5"><span class="px-2">Trendy Products</span></h2>
        </div>
        <div class="row px-xl-5 pb-3">
            <?php
            $now = date('Y-m-d');
            $conn = $pdo->open();

            $stmt = $conn->prepare("SELECT * FROM products WHERE date_view=:now ORDER BY counter DESC LIMIT 10");
            $stmt->execute(['now' => $now]);
            foreach ($stmt as $row) {
                echo '
                <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                    <div class="card product-item border-0 mb-4">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <img class="img-fluid w-100" src="images/' . $row['photo'] . '" alt="">
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3">' . $row['name'] . '</h6>
                            <div class="d-flex justify-content-center">
                                <h6>₦' . $row['price'] . '</h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-center bg-light border">
                            <a href="detail.php?product=' . $row['slug'] . '" class="btn btn-sm text-dark p-0">
                                <i class="fas fa-eye text-primary mr-1"></i>View Detail
                            </a>
                        </div>
                    </div>
                </div>';
            }

            $pdo->close();
            ?>
        </div>
    </div>
    <!-- Products End -->

    <!-- Subscribe Start -->
    <div class="container-fluid bg-secondary my-5">
        <div class="row justify-content-md-center py-5 px-xl-5">
            <div class="col-md-6 col-12 py-5">
                <div class="text-center mb-2 pb-2">
                    <h2 class="section-title px-5 mb-3"><span class="bg-secondary px-2">Stay Updated</span></h2>
                    <p>Stay informed about our latest promotions, products and events by subscribing to our newsletter! It's easy to join, simply enter your email address in the sign-up box on our website, and we will make sure to add you to the list. Don't miss out on exclusive deals and updates, join our newsletter today!</p>
                </div>
                <form method="POST" action="newletter.php">
                    <div class="input-group">
                        <input type="email" class="form-control border-white p-4" name="email" placeholder="Email Goes Here">
                        <input type="hidden" class="form-control border-white p-4" value="nil" name="name">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary px-4">Subscribe</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Subscribe End -->

    <!-- Just Arrived Products Start -->
    <div class="container-fluid pt-5">
        <div class="text-center mb-4">
            <h2 class="section-title px-5"><span class="px-2">Just Arrived</span></h2>
        </div>
        <div class="row px-xl-5 pb-3">
            <?php
            $conn = $pdo->open();
            $stmt = $conn->prepare("SELECT * FROM products WHERE product_status = 1 ORDER BY id DESC LIMIT 8");
            $stmt->execute();
            $result = $stmt->fetchAll();
            foreach ($result as $row) {
                echo '
                <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                    <div class="card product-item border-0 mb-4">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <img class="img-fluid w-100" src="images/' . $row['photo'] . '" alt="">
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3">' . $row['name'] . '</h6>
                            <div class="d-flex justify-content-center">
                                <h6>₦' . $row['price'] . '</h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-center bg-light border">
                            <a href="detail.php?product=' . $row['slug'] . '" class="btn btn-sm text-dark p-0">
                                <i class="fas fa-eye text-primary mr-1"></i>View Detail
                            </a>
                        </div>
                    </div>
                </div>';
            }

            $pdo->close();
            ?>
        </div>
    </div>
    <!-- Just Arrived Products End -->
    <?php include 'footer.php'; ?>
    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>