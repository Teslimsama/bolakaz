<?php
include 'session.php';
include 'Rating.php';
$rating = new Rating();
?>
<?php
$conn = $pdo->open();

$slug = $_GET['product'];

try {

    $stmt = $conn->prepare("SELECT *, products.name AS prodname, category.name AS catname, sub_category.name AS subcatname, products.id AS prodid FROM products LEFT JOIN category ON category.id=products.category_id LEFT JOIN 
    category AS sub_category ON sub_category.id = products.subcategory_id  WHERE slug = :slug");
    $stmt->execute(['slug' => $slug]);
    $product = $stmt->fetch();
} catch (PDOException $e) {
    echo "There is some problem in connection: " . $e->getMessage();
}

//page view
$now = date('Y-m-d');
if ($product['date_view'] == $now) {
    $stmt = $conn->prepare("UPDATE products SET counter=counter+1 WHERE id=:id");
    $stmt->execute(['id' => $product['prodid']]);
} else {
    $stmt = $conn->prepare("UPDATE products SET counter=1, date_view=:now WHERE id=:id");
    $stmt->execute(['id' => $product['prodid'], 'now' => $now]);
}

?>
<?php
$itemRating = $rating->getItemRating($product['prodid']);
$ratingNumber = 0;
$count = 0;
$fiveStarRating = 0;
$fourStarRating = 0;
$threeStarRating = 0;
$twoStarRating = 0;
$oneStarRating = 0;
foreach ($itemRating as $rate) {
    $ratingNumber += $rate['ratingNumber'];
    $count += 1;
    if ($rate['ratingNumber'] == 5) {
        $fiveStarRating += 1;
    } else if ($rate['ratingNumber'] == 4) {
        $fourStarRating += 1;
    } else if ($rate['ratingNumber'] == 3) {
        $threeStarRating += 1;
    } else if ($rate['ratingNumber'] == 2) {
        $twoStarRating += 1;
    } else if ($rate['ratingNumber'] == 1) {
        $oneStarRating += 1;
    }
}
$average = 0;
if ($ratingNumber && $count) {
    $average = $ratingNumber / $count;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Product Detail"; include "head.php"; ?>
</head>

<body>
    <!-- Topbar Start -->
    <?php include 'header.php'; ?>

    <?php include 'navbar.php'; ?>
    <!-- Navbar End -->


    <!-- Page Header Start -->
    <div class="container-fluid bg-secondary mb-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 300px">
            <h1 class="font-weight-semi-bold text-uppercase mb-3">Shop Detail</h1>
            <div class="d-inline-flex">
                <p class="m-0"><a href="">Home</a></p>
                <p class="m-0 px-2">-</p>
                <p class="m-0">Shop Detail</p>
            </div>
        </div>
    </div>
    <!-- Page Header End -->


    <!-- Shop Detail Start -->
    <div class="container-fluid py-5">
        <div class="alert" id="callout" style="display:none">
            <button type="button" class="close"><span aria-hidden="true">&times;</span></button>
            <span class="message"></span>
        </div>
        <div class="row px-xl-5">
            <div class="col-lg-5 pb-5">
                <div id="product-carousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner border">
                        <?php
                        // Fetch product images from the database
                        $proid = $product['prodid'];
                        $sql = 'SELECT * FROM gallery_images WHERE gallery_id = :gallery_id';
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':gallery_id', $proid);
                        $stmt->execute();
                        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Check if images exist
                        if (!empty($images)) {
                            $activeClass = 'active'; // Add "active" class to the first image
                            foreach ($images as $image) {
                                $imagePath = app_image_url($image['file_name'] ?? '');
                                echo '<div class="carousel-item ' . $activeClass . '">';
                                echo '<div class="sf-media sf-media-detail">';
                                echo '<img loading="lazy" class="w-100 h-100" src="' . e($imagePath) . '" alt="Product Image" onerror="this.onerror=null;this.src=\'' . e(app_placeholder_image()) . '\';">';
                                echo '</div>';
                                echo '</div>';
                                $activeClass = ''; // Remove "active" class for subsequent items
                            }
                        } else {
                            // If no images are available, display a placeholder
                            echo '<div class="carousel-item active">';
                            echo '<div class="sf-media sf-media-detail">';
                            echo '<img class="w-100 h-100" src="' . e(app_placeholder_image()) . '" alt="No Image Available">';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <a class="carousel-control-prev" href="#product-carousel" data-bs-slide="prev">
                        <i class="fa fa-2x fa-angle-left text-dark"></i>
                    </a>
                    <a class="carousel-control-next" href="#product-carousel" data-bs-slide="next">
                        <i class="fa fa-2x fa-angle-right text-dark"></i>
                    </a>
                </div>
            </div>


            <div class="col-lg-7 pb-5">
                <h3 class="font-weight-semi-bold"><?php echo $product['prodname']; ?></h3>
                <div class="d-flex mb-3">
                    <div class="mr-2">
                        <?php
                        $averageRating = round($average, 0);
                        for ($i = 1; $i <= 5; $i++) {
                            $ratingClass = "btn-default btn-grey";
                            if ($i <= $averageRating) {
                                $ratingClass = "text-primary";
                            }
                        ?>
                            <small class="fas fa-star <?php echo $ratingClass; ?> "></small>

                        <?php } ?>
                        <?php printf('%.1f', $average); ?>
                    </div>
                </div>
                <h3 class="font-weight-semi-bold mb-4"><?php echo app_money($product['price']); ?></h3>
                <p class="mb-4"><?php echo $product['description']; ?></p>
                <form id="productForm">
                    <!-- Display Sizes -->
                    <?php if (!empty($product['size'])) { ?>
                        <div class="d-flex mb-3">
                            <p class="text-dark font-weight-medium mb-0 mr-3">Sizes:</p>
                            <?php
                            $n = 1;
                            $sizes = explode(',', $product['size']);
                            foreach ($sizes as $size) {
                            ?>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input" value="<?php echo htmlspecialchars($size); ?>" id="<?php echo 'size-' . $n; ?>" name="size">
                                    <label class="custom-control-label" for="<?php echo 'size-' . $n; ?>"><?php echo htmlspecialchars($size); ?></label>
                                </div>
                            <?php
                                $n++;
                            } // Close the foreach for sizes
                            ?>
                        </div>
                    <?php } ?>

                    <!-- Display Colors -->
                    <?php if (!empty($product['color'])) { ?>
                        <div class="d-flex mb-4">
                            <p class="text-dark font-weight-medium mb-0 mr-3">Colors:</p>
                            <?php
                            $colors = explode(',', $product['color']);
                            $n = 1;
                            foreach ($colors as $color) {
                            ?>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input" value="<?php echo htmlspecialchars($color); ?>" id="<?php echo 'color-' . $n; ?>" name="color">
                                    <label class="custom-control-label" for="<?php echo 'color-' . $n; ?>"><?php echo htmlspecialchars($color); ?></label>
                                </div>
                            <?php
                                $n++;
                            } // Close the foreach for colors
                            ?>
                        </div>
                    <?php } ?>




                    <div class="d-flex align-items-center mb-4 pt-2">
                        <div class="input-group quantity mr-3" style="width: 130px;">
                            <div class="input-group-btn">
                                <button id="minus" type="button" class="btn btn-primary btn-minus">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </div>
                            <input type="text" name="quantity" id="quantity" class="form-control bg-secondary text-center" value="1">

                            <div class="input-group-btn">
                                <button id="add" type="button" class="btn btn-primary btn-plus">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div> <input type="hidden" value="<?php echo $product['prodid']; ?>" name="id">
                        <?php echo (!empty($user['id'])) ? '<button type="submit" class="btn btn-primary px-3"><i class="fa fa-shopping-cart mr-1"></i> Add To Cart </button>'  : '<a href="signin" class="btn btn-primary px-3"><i class="fa fa-shopping-cart mr-1"></i> Add To Cart</a>'; ?>
                    </div>
                </form>
                <div class="d-flex mb-3">
                    <p class="text-dark font-weight-medium mb-0 mr-3">Caterory:</p>
                    <?php echo e(ucwords((string)($product['category_name'] ?? ''))); ?>
                </div>
                <div class="d-flex mb-3">
                    <p class="text-dark font-weight-medium mb-0 mr-3">Sub Caterory:</p>
                    <?php echo e(ucwords((string)($product['subcatname'] ?? 'N/A'))); ?>
                </div>
                <div class="d-flex pt-2">
                    <p class="text-dark font-weight-medium mb-0 mr-2">Share on:</p>
                    <div class="d-inline-flex">
                        <a class="text-dark px-2" href="https://www.facebook.com/sharer/sharer.php?u=https://bolakaz.unibooks.com.ng/detail.php?product=<?php echo $slug; ?>">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a class="text-dark px-2" href="https://twitter.com/intent/tweet?url=https://bolakaz.unibooks.com.ng/detail.php?product=<?php echo $slug; ?>&text=">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a class="text-dark px-2" href="https://www.linkedin.com/shareArticle?mini=true&url=https://bolakaz.unibooks.com.ng/detail.php?product=<?php echo $slug; ?>">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a class="text-dark px-2" href="https://pinterest.com/pin/create/button/?url=https://bolakaz.unibooks.com.ng/detail.php?product=<?php echo $slug; ?>&media=&description=">
                            <i class="fab fa-pinterest"></i>
                        </a>
                        <a class="text-dark px-2" href="https://wa.me/?text=https://bolakaz.unibooks.com.ng/detail.php?product=<?php echo $slug; ?>&media=&description=">
                            <i class="fab fa-pinterest"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row px-xl-5">
            <div class="col">
                <div class="nav nav-tabs justify-content-center border-secondary mb-4">
                    <a class="nav-item nav-link active" data-bs-toggle="tab" href="#tab-pane-1">Description</a>
                    <a class="nav-item nav-link" data-bs-toggle="tab" href="#tab-pane-2">Information</a>
                    <a class="nav-item nav-link" id="total_review" data-bs-toggle="tab" href="#tab-pane-3">Reviews</a>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-pane-1">
                        <h4 class="mb-3">Product Description</h4>
                        <p><?php echo $product['description']; ?></p>
                    </div>
                    <div class="tab-pane fade" id="tab-pane-2">
                        <h4 class="mb-3">Additional Information</h4>
                        <p>Eos no lorem eirmod diam diam, eos elitr et gubergren diam sea. Consetetur vero aliquyam invidunt duo dolores et duo sit. Vero diam ea vero et dolore rebum, dolor rebum eirmod consetetur invidunt sed sed et, lorem duo et eos elitr, sadipscing kasd ipsum rebum diam. Dolore diam stet rebum sed tempor kasd eirmod. Takimata kasd ipsum accusam sadipscing, eos dolores sit no ut diam consetetur duo justo est, sit sanctus diam tempor aliquyam eirmod nonumy rebum dolor accusam, ipsum kasd eos consetetur at sit rebum, diam kasd invidunt tempor lorem, ipsum lorem elitr sanctus eirmod takimata dolor ea invidunt.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item px-0">
                                        Sit erat duo lorem duo ea consetetur, et eirmod takimata.
                                    </li>
                                    <li class="list-group-item px-0">
                                        Amet kasd gubergren sit sanctus et lorem eos sadipscing at.
                                    </li>
                                    <li class="list-group-item px-0">
                                        Duo amet accusam eirmod nonumy stet et et stet eirmod.
                                    </li>
                                    <li class="list-group-item px-0">
                                        Takimata ea clita labore amet ipsum erat justo voluptua. Nonumy.
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item px-0">
                                        Sit erat duo lorem duo ea consetetur, et eirmod takimata.
                                    </li>
                                    <li class="list-group-item px-0">
                                        Amet kasd gubergren sit sanctus et lorem eos sadipscing at.
                                    </li>
                                    <li class="list-group-item px-0">
                                        Duo amet accusam eirmod nonumy stet et et stet eirmod.
                                    </li>
                                    <li class="list-group-item px-0">
                                        Takimata ea clita labore amet ipsum erat justo voluptua. Nonumy.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-pane-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="mb-4">Reviews for "<?php echo $product['prodname']; ?> "</h4>
                                <?php
                                $itemRating = $rating->getItemRating($product['prodid']);
                                foreach ($itemRating as $rating) {
                                    $date = date_create($rating['created']);
                                    $reviewDate = date_format($date, "M d, Y");
                                    $profilePic = "profile.png";
                                    if ($rating['photo']) {
                                        $profilePic = $rating['photo'];
                                    }
                                ?>
                                    <div class="media mb-4">
                                        <img src="<?php echo e(app_image_url($rating['photo'] ?? '')); ?>" alt="Image" class="img-fluid rounded mr-3 mt-1" style="width: 45px; height: 45px; object-fit: cover;" onerror="this.onerror=null;this.src='<?php echo e(app_placeholder_image()); ?>';">
                                        <div class="media-body">
                                            <h6><?php echo ucwords($rating['firstname'] . " " . $rating['lastname']); ?><small> - <i><?php echo $reviewDate; ?></i></small></h6>
                                            <div class="mb-2">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    $ratingClass = "btn-default btn-grey";
                                                    if ($i <= $rating['ratingNumber']) {
                                                        $ratingClass = "text-primary";
                                                    }
                                                ?>
                                                    <i class="fas fa-star <?php echo $ratingClass; ?>"></i>
                                                <?php } ?>
                                            </div>
                                            <p><?php echo $rating['comments']; ?></p>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <form id="ratingForm" method="POST">
                                    <h4 class="mb-4">Leave a review</h4>
                                    <small>Your email address will not be published. Required fields are marked *</small>
                                    <div class="d-flex my-3">
                                        <p class="mb-0 mr-2">Your Rating * :</p>
                                        <div class="text-primary">
                                            <button type="button" class="btn btn-primary btn-sm rateButton" aria-label="Left Align">
                                                <span class="fa fa-star star-light" aria-hidden="true"></span>
                                            </button>
                                            <button type="button" class="btn btn-default btn-grey btn-sm rateButton" aria-label="Left Align">
                                                <span class="fa fa-star star-light" aria-hidden="true"></span>
                                            </button>
                                            <button type="button" class="btn btn-default btn-grey btn-sm rateButton" aria-label="Left Align">
                                                <span class="fa fa-star star-light" aria-hidden="true"></span>
                                            </button>
                                            <button type="button" class="btn btn-default btn-grey btn-sm rateButton" aria-label="Left Align">
                                                <span class="fa fa-star star-light" aria-hidden="true"></span>
                                            </button>
                                            <button type="button" class="btn btn-default btn-grey btn-sm rateButton" aria-label="Left Align">
                                                <span class="fa fa-star star-light" aria-hidden="true"></span>
                                            </button>

                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="message">Your Review *</label>
                                        <textarea id="comment" name="comment" cols="30" rows="5" class="form-control" required></textarea>
                                    </div>

                                    <input type="hidden" value="<?php echo $product['prodid']; ?>" id="itemid" name="itemid">
                                    <input type="hidden" class="form-control" id="rating" name="rating" value="1">

                                    <input type="hidden" name="action" value="saveRating">
                                    <div class="form-group">
                                        <label for="name">Title *</label>
                                        <input type="text" class="form-control" name="title" id="title" required>
                                    </div>
                                    <!-- use for later -->
                                    <div class="form-group">
                                        <label for="email">Your Email *</label>
                                        <input type="email" class="form-control" id="email" required>
                                    </div>
                                    <div class="form-group mb-0">
                                        <?php echo (!empty($user['id'])) ? '<button type="submit" id="saveReview" class="btn btn-primary px-3">Leave Your Review</button>'  : '<a href="signin" class="btn btn-primary px-3"><i class="Leave Your Review</a>'; ?>

                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Shop Detail End -->


    <!-- Products Start -->
    <!-- <div class="container-fluid py-5">
        <div class="text-center mb-4">
            <h2 class="section-title px-5"><span class="px-2">You May Also Like</span></h2>
        </div>
        <div class="row px-xl-5">
            <div class="col">
                <div class="owl-carousel related-carousel">
                    <div class="card product-item border-0">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <img class="img-fluid w-100" src="img/product-1.jpg" alt="">
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3">Colorful Stylish Shirt</h6>
                            <div class="d-flex justify-content-center">
                                <h6>$123.00</h6>
                                <h6 class="text-muted ml-2"><del>$123.00</del></h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between bg-light border">
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-shopping-cart text-primary mr-1"></i>Add To Cart</a>
                        </div>
                    </div>
                    <div class="card product-item border-0">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <img class="img-fluid w-100" src="img/product-2.jpg" alt="">
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3">Colorful Stylish Shirt</h6>
                            <div class="d-flex justify-content-center">
                                <h6>$123.00</h6>
                                <h6 class="text-muted ml-2"><del>$123.00</del></h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between bg-light border">
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-shopping-cart text-primary mr-1"></i>Add To Cart</a>
                        </div>
                    </div>
                    <div class="card product-item border-0">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <img class="img-fluid w-100" src="img/product-3.jpg" alt="">
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3">Colorful Stylish Shirt</h6>
                            <div class="d-flex justify-content-center">
                                <h6>$123.00</h6>
                                <h6 class="text-muted ml-2"><del>$123.00</del></h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between bg-light border">
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-shopping-cart text-primary mr-1"></i>Add To Cart</a>
                        </div>
                    </div>
                    <div class="card product-item border-0">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <img class="img-fluid w-100" src="img/product-4.jpg" alt="">
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3">Colorful Stylish Shirt</h6>
                            <div class="d-flex justify-content-center">
                                <h6>$123.00</h6>
                                <h6 class="text-muted ml-2"><del>$123.00</del></h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between bg-light border">
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-shopping-cart text-primary mr-1"></i>Add To Cart</a>
                        </div>
                    </div>
                    <div class="card product-item border-0">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <img class="img-fluid w-100" src="img/product-5.jpg" alt="">
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3">Colorful Stylish Shirt</h6>
                            <div class="d-flex justify-content-center">
                                <h6>$123.00</h6>
                                <h6 class="text-muted ml-2"><del>$123.00</del></h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between bg-light border">
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                            <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-shopping-cart text-primary mr-1"></i>Add To Cart</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
    <!-- Products End -->


    <!-- Footer Start -->

    <?php
    include 'footer.php';
    ?>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <!-- JavaScript Bundle with Popper -->
    <!-- Contact Javascript File -->
    <script src="js/rating.js"></script>

    <script>
        $(function() {
            $('#add').click(function(e) {
                e.preventDefault();
                var quantity = $('#quantity').val();
                quantity++;
                $('#quantity').val(quantity);
            });
            $('#minus').click(function(e) {
                e.preventDefault();
                var quantity = $('#quantity').val();
                if (quantity > 1) {
                    quantity--;
                }
                $('#quantity').val(quantity);
            });

        });
    </script>


    <!-- Template Javascript -->
</body>

</html>
