<?php
include 'session.php';
include 'Rating.php';
require_once __DIR__ . '/lib/product_payload.php';
require_once __DIR__ . '/lib/catalog_v2.php';
$rating = new Rating();
?>
<?php
$conn = $pdo->open();

$slug = trim((string)($_GET['product'] ?? ''));
if ($slug === '') {
    $pdo->close();
    header('location: shop');
    exit();
}

try {
    $v2Product = catalog_v2_get_product_by_slug($conn, $slug);
    if ($v2Product) {
        $legacyMapStmt = $conn->prepare("SELECT legacy_product_id FROM product_legacy_map WHERE product_v2_id = :product_v2_id LIMIT 1");
        $legacyMapStmt->execute(['product_v2_id' => (int)$v2Product['id']]);
        $legacyProductId = (int)$legacyMapStmt->fetchColumn();
        $product = [
            'prodid' => ($legacyProductId > 0 ? $legacyProductId : (int)$v2Product['id']),
            'product_v2_id' => (int)$v2Product['id'],
            'legacy_product_id' => ($legacyProductId > 0 ? $legacyProductId : null),
            'prodname' => (string)$v2Product['name'],
            'catname' => (string)($v2Product['catname'] ?? ''),
            'subcatname' => (string)($v2Product['subcatname'] ?? ''),
            'price' => (float)($v2Product['base_price'] ?? 0),
            'description' => (string)$v2Product['description'],
            'brand' => (string)($v2Product['brand'] ?? ''),
            'qty' => 0,
            'date_view' => date('Y-m-d'),
            'photo' => (string)($v2Product['main_image'] ?? ''),
            'additional_info' => (string)($v2Product['specs_json'] ?? ''),
            'variants' => $v2Product['variants'],
            'is_v2' => 1,
        ];
        foreach ($product['variants'] as $variantRow) {
            if (((string)($variantRow['status'] ?? 'active')) === 'active') {
                $product['qty'] += max(0, (int)($variantRow['stock_qty'] ?? 0));
            }
        }
    } else {
        $stmt = $conn->prepare("SELECT *, products.name AS prodname, category.name AS catname, sub_category.name AS subcatname, products.id AS prodid FROM products LEFT JOIN category ON category.id=products.category_id LEFT JOIN 
        category AS sub_category ON sub_category.id = products.subcategory_id WHERE slug = :slug AND products.product_status = 1");
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            $product['is_v2'] = 0;
            $product['variants'] = [];
            $product['product_v2_id'] = null;
            $product['legacy_product_id'] = (int)($product['prodid'] ?? 0);
        }
    }
} catch (PDOException $e) {
    error_log('detail.php product fetch error: ' . $e->getMessage());
    $product = false;
}

if (!$product) {
    $pdo->close();
    header('location: shop');
    exit();
}

//page view
$now = date('Y-m-d');
$legacyPageViewId = (int)($product['legacy_product_id'] ?? 0);
if ($legacyPageViewId > 0) {
    if ($product['date_view'] == $now) {
        $stmt = $conn->prepare("UPDATE products SET counter=counter+1 WHERE id=:id");
        $stmt->execute(['id' => $legacyPageViewId]);
    } else {
        $stmt = $conn->prepare("UPDATE products SET counter=1, date_view=:now WHERE id=:id");
        $stmt->execute(['id' => $legacyPageViewId, 'now' => $now]);
    }
}

?>
<?php
$sizeOptions = product_csv_to_array($product['size'] ?? '');
$colorOptions = product_csv_to_array($product['color'] ?? '');
$materialOptions = product_csv_to_array($product['material'] ?? '', 80);
$additionalInfo = product_decode_specs($product['additional_info'] ?? '');

if (!empty($product['is_v2'])) {
    $sizeMap = [];
    $colorMap = [];
    $materialMap = [];
    foreach ($product['variants'] as $variant) {
        $variantOptions = $variant['options'] ?? [];
        foreach ($variantOptions as $opt) {
            $code = (string)($opt['code'] ?? '');
            $value = trim((string)($opt['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            if ($code === 'size') {
                $sizeMap[strtolower($value)] = $value;
            } elseif ($code === 'color') {
                $colorMap[strtolower($value)] = $value;
            } elseif ($code === 'material') {
                $materialMap[strtolower($value)] = $value;
            }
        }
    }
    $sizeOptions = array_values($sizeMap);
    $colorOptions = array_values($colorMap);
    $materialOptions = array_values($materialMap);
}

$reviewLegacyProductId = (int)($product['legacy_product_id'] ?? 0);
$reviewProductV2Id = (int)($product['product_v2_id'] ?? 0);
$itemRating = $rating->getItemRating($reviewLegacyProductId, $reviewProductV2Id > 0 ? $reviewProductV2Id : null);
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
                        // Fetch product gallery images from the database
                        $proid = $product['prodid'];
                        $sql = 'SELECT * FROM gallery_images WHERE gallery_id = :gallery_id';
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':gallery_id', $proid);
                        $stmt->execute();
                        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $renderedFirst = false;
                        $mainImage = app_image_url($product['photo'] ?? '');

                        // Always show main image first when available
                        if (!empty(trim((string)($product['photo'] ?? '')))) {
                            echo '<div class="carousel-item active">';
                            echo '<div class="sf-media sf-media-detail">';
                            echo '<img loading="lazy" class="w-100 h-100" src="' . e($mainImage) . '" alt="Product Main Image" onerror="this.onerror=null;this.src=\'' . e(app_placeholder_image()) . '\';">';
                            echo '</div>';
                            echo '</div>';
                            $renderedFirst = true;
                        }

                        // Then append gallery images
                        if (!empty($images)) {
                            $activeClass = 'active'; // Add "active" class to the first image
                            foreach ($images as $image) {
                                $imagePath = app_image_url($image['file_name'] ?? '');
                                if (!$renderedFirst) {
                                    echo '<div class="carousel-item ' . $activeClass . '">';
                                    $renderedFirst = true;
                                } else {
                                    echo '<div class="carousel-item">';
                                }
                                echo '<div class="sf-media sf-media-detail">';
                                echo '<img loading="lazy" class="w-100 h-100" src="' . e($imagePath) . '" alt="Product Image" onerror="this.onerror=null;this.src=\'' . e(app_placeholder_image()) . '\';">';
                                echo '</div>';
                                echo '</div>';
                                $activeClass = ''; // Remove "active" class for subsequent items
                            }
                        }

                        if (!$renderedFirst) {
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
                <h3 class="font-weight-semi-bold"><?php echo e($product['prodname']); ?></h3>
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
                    <?php if (!empty($product['is_v2']) && !empty($product['variants'])) { ?>
                        <div class="mb-3">
                            <p class="text-dark font-weight-medium mb-2">Variant:</p>
                            <select class="form-control" name="variant_id" id="variant_id" required>
                                <option value="">Select a variant</option>
                                <?php foreach ($product['variants'] as $variant) {
                                    $variantId = (int)($variant['id'] ?? 0);
                                    $variantPrice = (float)($variant['price'] ?? 0);
                                    $variantStock = max(0, (int)($variant['stock_qty'] ?? 0));
                                    $optionPieces = [];
                                    foreach (($variant['options'] ?? []) as $opt) {
                                        $label = trim((string)($opt['label'] ?? $opt['code'] ?? ''));
                                        $value = trim((string)($opt['value'] ?? ''));
                                        if ($label !== '' && $value !== '') {
                                            $optionPieces[] = $label . ': ' . $value;
                                        }
                                    }
                                    $variantLabel = !empty($optionPieces) ? implode(' | ', $optionPieces) : ('Variant #' . $variantId);
                                    $isDisabled = ($variantStock <= 0) ? 'disabled' : '';
                                    $stockText = ($variantStock > 0) ? 'In Stock' : 'Out of Stock';
                                ?>
                                    <option value="<?php echo $variantId; ?>" data-price="<?php echo e((string)$variantPrice); ?>" data-stock="<?php echo $variantStock; ?>" <?php echo $isDisabled; ?>>
                                        <?php echo e($variantLabel . ' - ' . app_money($variantPrice) . ' (' . $stockText . ')'); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    <?php } ?>
                    <!-- Display Sizes -->
                    <?php if (empty($product['is_v2']) && !empty($sizeOptions)) { ?>
                        <div class="d-flex mb-3">
                            <p class="text-dark font-weight-medium mb-0 mr-3">Sizes:</p>
                            <?php
                            $n = 1;
                            foreach ($sizeOptions as $size) {
                            ?>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input" value="<?php echo e($size); ?>" id="<?php echo 'size-' . $n; ?>" name="size">
                                    <label class="custom-control-label" for="<?php echo 'size-' . $n; ?>"><?php echo e($size); ?></label>
                                </div>
                            <?php
                                $n++;
                            } // Close the foreach for sizes
                            ?>
                        </div>
                    <?php } ?>

                    <!-- Display Colors -->
                    <?php if (empty($product['is_v2']) && !empty($colorOptions)) { ?>
                        <div class="d-flex mb-4">
                            <p class="text-dark font-weight-medium mb-0 mr-3">Colors:</p>
                            <?php
                            $n = 1;
                            foreach ($colorOptions as $color) {
                            ?>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input" value="<?php echo e($color); ?>" id="<?php echo 'color-' . $n; ?>" name="color">
                                    <label class="custom-control-label" for="<?php echo 'color-' . $n; ?>"><?php echo e($color); ?></label>
                                </div>
                            <?php
                                $n++;
                            } // Close the foreach for colors
                            ?>
                        </div>
                    <?php } ?>

                    <input type="hidden" name="catalog_mode" value="<?php echo !empty($product['is_v2']) ? 'v2' : 'legacy'; ?>">

                    <div class="d-flex align-items-center mb-4 pt-2">
                        <div class="input-group quantity mr-3" style="width: 130px;">
                            <div class="input-group-btn">
                                <button id="minus" type="button" class="btn btn-primary btn-minus">
                                    <i class="fa fa-minus"></i>
                                </button>
                            </div>
                            <input type="number" min="1" step="1" name="quantity" id="quantity" class="form-control bg-secondary text-center" value="1">

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
                    <?php echo e(ucwords((string)($product['catname'] ?? 'Uncategorized'))); ?>
                </div>
                <div class="d-flex mb-3">
                    <p class="text-dark font-weight-medium mb-0 mr-3">Sub Caterory:</p>
                    <?php echo e(ucwords((string)($product['subcatname'] ?? 'N/A'))); ?>
                </div>
                <?php if (!empty($product['brand'])) { ?>
                    <div class="d-flex mb-3">
                        <p class="text-dark font-weight-medium mb-0 mr-3">Brand:</p>
                        <?php echo e((string)$product['brand']); ?>
                    </div>
                <?php } ?>
                <?php if (!empty($materialOptions)) { ?>
                    <div class="d-flex mb-3">
                        <p class="text-dark font-weight-medium mb-0 mr-3">Material:</p>
                        <?php echo e(implode(', ', $materialOptions)); ?>
                    </div>
                <?php } ?>
                <div class="d-flex mb-3">
                    <p class="text-dark font-weight-medium mb-0 mr-3">Availability:</p>
                    <?php echo ((int)($product['qty'] ?? 0) > 0) ? 'In stock' : 'Out of stock'; ?>
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
                    <a class="nav-item nav-link active" data-toggle="tab" data-bs-toggle="tab" href="#tab-pane-1">Description</a>
                    <a class="nav-item nav-link" data-toggle="tab" data-bs-toggle="tab" href="#tab-pane-2">Information</a>
                    <a class="nav-item nav-link" id="total_review" data-toggle="tab" data-bs-toggle="tab" href="#tab-pane-3">Reviews</a>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-pane-1">
                        <h4 class="mb-3">Product Description</h4>
                        <p><?php echo $product['description']; ?></p>
                    </div>
                    <div class="tab-pane fade" id="tab-pane-2">
                        <h4 class="mb-3">Additional Information</h4>
                        <?php if (!empty($additionalInfo)) { ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <ul class="list-group list-group-flush">
                                        <?php
                                        $specLabels = [
                                            'fit' => 'Fit',
                                            'care_instructions' => 'Care Instructions',
                                            'composition' => 'Composition',
                                            'dimensions' => 'Dimensions',
                                            'shipping_class' => 'Shipping Class',
                                            'origin' => 'Origin',
                                        ];
                                        foreach ($specLabels as $specKey => $specLabel) {
                                            if (empty($additionalInfo[$specKey])) {
                                                continue;
                                            }
                                        ?>
                                            <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
                                                <strong><?php echo e($specLabel); ?></strong>
                                                <span class="text-muted"><?php echo e($additionalInfo[$specKey]); ?></span>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        <?php } else { ?>
                            <p class="text-muted mb-0">No additional product information has been provided yet.</p>
                        <?php } ?>
                    </div>
                    <div class="tab-pane fade" id="tab-pane-3">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="mb-4">Reviews for "<?php echo $product['prodname']; ?> "</h4>
                                <?php
                                $itemRating = $rating->getItemRating($reviewLegacyProductId, $reviewProductV2Id > 0 ? $reviewProductV2Id : null);
                                if (empty($itemRating)) {
                                    echo '<p class="text-muted">No reviews yet. Be the first to review this product.</p>';
                                }
                                foreach ($itemRating as $reviewRow) {
                                    $date = date_create($reviewRow['created']);
                                    $reviewDate = date_format($date, "M d, Y");
                                ?>
                                    <div class="media mb-4">
                                        <img src="<?php echo e(app_image_url($reviewRow['photo'] ?? '')); ?>" alt="Image" class="img-fluid rounded mr-3 mt-1" style="width: 45px; height: 45px; object-fit: cover;" onerror="this.onerror=null;this.src='<?php echo e(app_placeholder_image()); ?>';">
                                        <div class="media-body">
                                            <h6><?php echo e(ucwords(trim(($reviewRow['firstname'] ?? '') . " " . ($reviewRow['lastname'] ?? '')))); ?><small> - <i><?php echo e($reviewDate); ?></i></small></h6>
                                            <div class="mb-2">
                                                <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    $ratingClass = "btn-default btn-grey";
                                                    if ($i <= $reviewRow['ratingNumber']) {
                                                        $ratingClass = "text-primary";
                                                    }
                                                ?>
                                                    <i class="fas fa-star <?php echo $ratingClass; ?>"></i>
                                                <?php } ?>
                                            </div>
                                            <h6 class="mb-1"><?php echo e($reviewRow['title']); ?></h6>
                                            <p><?php echo e($reviewRow['comments']); ?></p>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($user['id'])) { ?>
                                    <form id="ratingForm" method="POST">
                                        <h4 class="mb-4">Leave a review</h4>
                                        <small>Required fields are marked *</small>
                                        <div id="reviewFeedback" class="alert d-none mt-3 mb-3" role="alert"></div>
                                        <div class="d-flex my-3">
                                            <p class="mb-0 mr-2">Your Rating * :</p>
                                            <div class="text-primary">
                                                <button type="button" class="btn btn-primary btn-sm rateButton" aria-label="1 star">
                                                    <span class="fa fa-star star-light" aria-hidden="true"></span>
                                                </button>
                                                <button type="button" class="btn btn-default btn-grey btn-sm rateButton" aria-label="2 stars">
                                                    <span class="fa fa-star star-light" aria-hidden="true"></span>
                                                </button>
                                                <button type="button" class="btn btn-default btn-grey btn-sm rateButton" aria-label="3 stars">
                                                    <span class="fa fa-star star-light" aria-hidden="true"></span>
                                                </button>
                                                <button type="button" class="btn btn-default btn-grey btn-sm rateButton" aria-label="4 stars">
                                                    <span class="fa fa-star star-light" aria-hidden="true"></span>
                                                </button>
                                                <button type="button" class="btn btn-default btn-grey btn-sm rateButton" aria-label="5 stars">
                                                    <span class="fa fa-star star-light" aria-hidden="true"></span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="title">Title *</label>
                                            <input type="text" class="form-control" name="title" id="title" maxlength="120" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="comment">Your Review *</label>
                                            <textarea id="comment" name="comment" cols="30" rows="5" class="form-control" maxlength="3000" required></textarea>
                                        </div>

                                        <input type="hidden" value="<?php echo $reviewLegacyProductId; ?>" id="itemid" name="itemid">
                                        <?php if ($reviewProductV2Id > 0) { ?>
                                            <input type="hidden" value="<?php echo $reviewProductV2Id; ?>" name="product_v2_id">
                                        <?php } ?>
                                        <input type="hidden" class="form-control" id="rating" name="rating" value="1">
                                        <input type="hidden" name="_csrf" value="<?php echo e(app_get_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="saveRating">

                                        <div class="form-group mb-0">
                                            <button type="submit" id="saveReview" class="btn btn-primary px-3">Leave Your Review</button>
                                        </div>
                                    </form>
                                <?php } else { ?>
                                    <div class="alert alert-info mb-0">Please <a href="signin">sign in</a> to leave a review.</div>
                                <?php } ?>
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

    <!-- Template Javascript -->
</body>

</html>
