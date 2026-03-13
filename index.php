<?php
include 'session.php';
require_once __DIR__ . '/lib/banner_links.php';

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
    <?php $pageTitle = "Bolakaz | Home"; include "head.php"; ?>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'navbar.php'; ?>

    <section class="container-fluid py-4 py-lg-5">
        <div class="row px-xl-5">
            <div class="col-12">
                <div id="header-carousel" class="carousel slide rounded-4 overflow-hidden shadow-sm" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php
                        $first = true;
                        foreach ($carouselItems as $item) {
                            $activeClass = $first ? ' active' : '';
                            $first = false;
                            echo "
                            <div class='carousel-item$activeClass'>
                                <div class='sf-media sf-media-hero'>
                                    <img class='img-fluid w-100 h-100' src='" . e(app_image_url($item['image_path'] ?? '')) . "' alt='Banner' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">
                                </div>
                                <div class='carousel-caption d-flex flex-column align-items-center justify-content-center'>
                                    <div class='p-3 text-center' style='max-width: 780px;'>
                                        <p class='text-uppercase mb-2'>" . htmlspecialchars($item['caption_text'], ENT_QUOTES, 'UTF-8') . "</p>
                                        <h2 class='display-4 text-white mb-4'>" . htmlspecialchars($item['caption_heading'], ENT_QUOTES, 'UTF-8') . "</h2>
                                        <a href='" . e(banner_resolve_storefront_link((string)($item['link'] ?? ''))) . "' class='btn btn-primary px-4 py-2'>Shop Collection</a>
                                    </div>
                                </div>
                            </div>";
                        }
                        ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Start -->
    <div class="container-fluid pt-5">
        <div class="row px-xl-5 pb-3">
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <i class="feature-icon fa fa-check text-primary m-0 me-3" aria-hidden="true"></i>
                    <h5 class="font-weight-semi-bold m-0">Quality Product</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <i class="feature-icon fa fa-truck text-primary m-0 me-3" aria-hidden="true"></i>
                    <h5 class="font-weight-semi-bold m-0">Free Shipping</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <i class="feature-icon fa fa-refresh text-primary m-0 me-3" aria-hidden="true"></i>
                    <h5 class="font-weight-semi-bold m-0">14-Day Return</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <i class="feature-icon fa fa-headphones text-primary m-0 me-3" aria-hidden="true"></i>
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
                $stmt = $conn->prepare("SELECT * FROM category WHERE is_parent = 1 AND status = :status");
                $stmt->execute(['status' => 'active']);
                foreach ($stmt as $row) {
                    echo "
    <div class='col-lg-3 col-md-4 col-sm-6 pb-1'>
        <div class='cat-item d-flex flex-column border mb-4' style='padding: 30px;'>
            <p class='text-right'>" . e(ucwords((string)$row['name'])) . "</p>
            <a href='shop?category=" . $row['cat_slug'] . "' class='cat-img position-relative overflow-hidden mb-3'>
                <div class='sf-media sf-media-category'>
                  <img class='img-fluid category-image' src='" . e(app_image_url($row['cat_image'] ?? '')) . "' alt='" . e(ucwords((string)$row['name'])) . "' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">
                </div>
            </a>
            <h5 class='font-weight-semi-bold m-0'> " . e(ucwords((string)$row['name'])) . "</h5>
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
                        <div class="sf-media sf-media-hero">
                            <img src="<?php echo e(app_image_url($offer['image_path'] ?? '')); ?>" alt="<?php echo e($offer['collection'] ?? 'Collection'); ?>" onerror="this.onerror=null;this.src='<?php echo e(app_placeholder_image()); ?>';">
                        </div>
                        <div class="position-relative" style="z-index: 1;">
                            <h5 class="text-uppercase text-primary mb-3"><?php echo e($offer['discount']); ?>% off the all order</h5>
                            <h1 class="mb-4 font-weight-semi-bold"><?php echo e(ucwords((string)$offer['collection'])); ?></h1>
                            <a href="shop?category=<?php echo e($offer['link']); ?>" class="btn btn-outline-primary py-md-2 px-md-3">Shop Now</a>
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
            $now = date('m');
            $conn = $pdo->open();

            $stmt = $conn->prepare("SELECT * FROM products WHERE date_view=:now ORDER BY counter DESC LIMIT 12");
            $stmt->execute(['now' => $now]);
            foreach ($stmt as $row) {

                $discount = $row['price'] * 15;
                echo '
                <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                    <div class="card product-item border-0 mb-4">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <div class="sf-media sf-media-product"><img class="img-fluid w-100" src="' . e(app_image_url($row['photo'] ?? '')) . '" alt="' . e($row['name']) . '" onerror="this.onerror=null;this.src=\'' . e(app_placeholder_image()) . '\';"></div>
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3">' . e($row['name']) . '</h6>
                            <div class="d-flex justify-content-center">
                            <h6>' . app_money($row['price']) . '</h6>
                            <h6 class="text-muted ml-2"><del>' . app_money($discount) . '</del></h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-center bg-light border">
                            <a href="detail?product=' . e($row['slug']) . '" class="btn btn-sm text-dark p-0">
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
            $stmt = $conn->prepare("SELECT * FROM products WHERE product_status = 1 ORDER BY id DESC LIMIT 12");
            $stmt->execute();
            $result = $stmt->fetchAll();
            foreach ($result as $row) {
                $discount = $row['price'] * 15;
                echo '
                <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                    <div class="card product-item border-0 mb-4">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <div class="sf-media sf-media-product"><img class="img-fluid w-100" src="' . e(app_image_url($row['photo'] ?? '')) . '" alt="' . e($row['name']) . '" onerror="this.onerror=null;this.src=\'' . e(app_placeholder_image()) . '\';"></div>
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3">' . e($row['name']) . '</h6>
                            <div class="d-flex justify-content-center">
                                <h6>' . app_money($row['price']) . '</h6>
                            <h6 class="text-muted ml-2"><del>' . app_money($discount) . '</del></h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-center bg-light border">
                            <a href="detail?product=' . e($row['slug']) . '" class="btn btn-sm text-dark p-0">
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

    <!-- JavaScript Bundle with Popper -->
    <!-- Template Javascript -->
</body>

</html>
