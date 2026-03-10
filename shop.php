<?php
include 'session.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Shop"; include "head.php"; ?>
    <link href="css/jquery-ui.css?v=<?php echo file_exists(__DIR__ . '/css/jquery-ui.css') ? filemtime(__DIR__ . '/css/jquery-ui.css') : time(); ?>" rel="stylesheet">
    <link href="css/shop-filters.css?v=<?php echo file_exists(__DIR__ . '/css/shop-filters.css') ? filemtime(__DIR__ . '/css/shop-filters.css') : time(); ?>" rel="stylesheet">
</head>

<body>

    <?php
    include "header.php"; ?>
    <?php include 'navbar.php'; ?>


    <input type="hidden" value="">
    <!-- Page Header Start -->
    <div class="container-fluid bg-secondary mb-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 300px">
            <h1 class="font-weight-semi-bold text-uppercase mb-3">Our Shop</h1>
            <div class="d-inline-flex">
                <p class="m-0"><a href="index">Home</a></p>
                <p class="m-0 px-2">-</p>
                <p class="m-0">Shop</p>
            </div>
        </div>
    </div>
    <!-- Page Header End -->


    <!-- Shop Start -->
    <div class="container-fluid pt-5">
        <form method="post" id="search_form">
            <div class="row px-xl-5">
                <!-- Shop Sidebar Start -->
                <div class="col-lg-3 col-md-12 shop-filter-sidebar">
                    <!-- Price Start -->
                    <div class="border-bottom mb-4 pb-4">
                        <h5 class="font-weight-semi-bold mb-4">Filter by price</h5>
                        <div class="custom-control custom-checkbox justify-content-between mb-3">
                            <input type="hidden" id="hidden_minimum_price" value="500" />
                            <input type="hidden" id="hidden_maximum_price" value="65000" />
                            <input type="hidden" id="cat" value="<?php echo e(isset($_GET['category']) ? (string)$_GET['category'] : '0'); ?>">
                            <p id="price_show"><?php echo app_money(500); ?> - <?php echo app_money(65000); ?></p>
                            <div id="price_range"></div>
                        </div>
                    </div>
                    <!-- Price End -->

                    <!--Category Start -->
                    <div class="border-bottom mb-4 pb-4">
                        <h5 class="font-weight-semi-bold mb-4">Filter by category</h5>
                        <?php
                        $n = 1;
                        $query = "SELECT p.category_name AS category_slug, c.name AS category_name, COUNT(*) AS total
                                  FROM products p
                                  LEFT JOIN category c ON c.cat_slug = p.category_name
                                  WHERE p.product_status = '1' AND p.category_name <> ''
                                  GROUP BY p.category_name, c.name
                                  ORDER BY c.name ASC, p.category_name ASC";
                        $statement = $conn->prepare($query);
                        $statement->execute();
                        $result = $statement->fetchAll();

                        foreach ($result as $row) {
                            $slug = (string)($row['category_slug'] ?? '');
                            $label = trim((string)($row['category_name'] ?? ''));
                            if ($label === '') {
                                $label = ucwords(str_replace(['_', '-'], ' ', $slug));
                            }
                        ?>
                            <div class="custom-control custom-checkbox checkbox d-flex align-items-center justify-content-between mb-3 sf-filter-option">
                                <input type="checkbox" class="custom-control-input common_selector category" value="<?php echo e($slug); ?>" id="<?php echo 'cat-' . $n ?>">
                                <label class="custom-control-label" for="<?php echo 'cat-' . $n ?>"><?php echo e(ucwords($label)); ?></label>
                                <span class="text-dark badge border font-weight-normal sf-filter-count">
                                    <?php echo (int)($row['total'] ?? 0); ?>
                                </span>
                            </div>
                        <?php
                            $n++;
                        }

                        ?>
                    </div>
                    <!-- Category End -->

                    <!--Brand Start -->
                    <div class="border-bottom mb-4 pb-4">
                        <h5 class="font-weight-semi-bold mb-4">Filter by brand</h5>
                        <?php
                        $n = 1;
                        $query = "SELECT DISTINCT(brand) FROM products WHERE product_status = '1' AND brand <> '' ORDER BY brand DESC";
                        $statement = $conn->prepare($query);
                        $statement->execute();
                        $result = $statement->fetchAll();
                        foreach ($result as $row) {
                        ?>
                            <div class="custom-control custom-checkbox checkbox d-flex align-items-center justify-content-between mb-3 sf-filter-option">
                                <input type="checkbox" class="custom-control-input common_selector brand" value="<?php echo e((string)$row['brand']); ?>" id="<?php echo 'brand-' . $n ?>">
                                <label class="custom-control-label" for="<?php echo 'brand-' . $n ?>"><?php echo e(ucwords(str_replace(['_', '-'], ' ', (string)$row['brand']))); ?></label>
                                <span class="text-dark badge border font-weight-normal sf-filter-count">
                                    <?php $name = (string)$row['brand'];
                                    $sql = "SELECT COUNT(*) AS total FROM products WHERE product_status = '1' AND brand = :name";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute(['name' => $name]);
                                    echo (int)($stmt->fetch()['total'] ?? 0);
                                    ?>
                                </span>
                            </div>
                        <?php
                            $n++;
                        }

                        ?>
                    </div>
                    <!-- Brand End -->

                    <!--Material Start -->
                    <!-- <div class="border-bottom mb-4 pb-4">
                        <h5 class="font-weight-semi-bold mb-4">Filter by material</h5>
                        <?php
                        $n = 1;
                        $query = "SELECT DISTINCT(material) FROM products GROUP BY material DESC";
                        $statement = $conn->prepare($query);
                        $statement->execute();
                        $result = $statement->fetchAll();
                        foreach ($result as $row) {
                        ?>
                            <div class="custom-control custom-checkbox checkbox d-flex align-items-center justify-content-between mb-3">
                                <input type="checkbox" class="custom-control-input common_selector material" value="<?php echo $row['material']; ?>" id="<?php echo 'mat-' . $n ?>">
                                <label class=" custom-control-label" for="<?php echo 'mat-' . $n ?>"><?php echo ucwords($row['material']); ?></label>
                                <span class="text-dark badge border font-weight-normal">
                                    <?php $name = $row['material'];
                                    $sql = "SELECT * FROM products WHERE material='$name'";

                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute();
                                    echo $stmt->rowCount();
                                    ?>
                                </span>
                            </div>
                        <?php
                            $n++;
                        }

                        ?>
                    </div> -->
                    <!-- Material End -->

                    <!--Color Start -->
                    <!-- <div class="border-bottom mb-4 pb-4">
                        <h5 class="font-weight-semi-bold mb-4">Filter by color</h5>
                        <?php
                        $n = 1;
                        $query = "SELECT DISTINCT(color) FROM products GROUP BY color DESC";
                        $statement = $conn->prepare($query);
                        $statement->execute();
                        $result = $statement->fetchAll();
                        foreach ($result as $row) {
                        ?>
                            <div class="custom-control custom-checkbox checkbox d-flex align-items-center justify-content-between mb-3">
                                <input type="checkbox" class="custom-control-input common_selector color" value="<?php echo $row['color']; ?>" id="<?php echo 'col-' . $n ?>">
                                <label class=" custom-control-label" for="<?php echo 'col-' . $n ?>"><?php echo ucwords($row['color']); ?></label>
                                <span class="text-dark badge border font-weight-normal">
                                    <?php $name = $row['color'];
                                    $sql = "SELECT * FROM products WHERE color='$name'";

                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute();
                                    echo $stmt->rowCount();
                                    ?>
                                </span>
                            </div>
                        <?php
                            $n++;
                        }

                        ?>
                    </div> -->
                    <!-- Color End -->

                    <!-- Size Start -->
                    <!-- <div class="mb-5">
                        <h5 class="font-weight-semi-bold mb-4">Filter by size</h5>
                        <?php
                        $n = 1;
                        $query = "SELECT DISTINCT(size) FROM products GROUP BY size DESC";
                        $statement = $conn->prepare($query);
                        $statement->execute();
                        $result = $statement->fetchAll();
                        foreach ($result as $row) {
                        ?>
                            <div class="custom-control custom-checkbox checkbox d-flex align-items-center justify-content-between mb-3">
                                <input type="checkbox" class="custom-control-input common_selector size" value="<?php echo $row['size']; ?>" id="<?php echo 'size-' . $n ?>">
                                <label class="custom-control-label" for="<?php echo 'size-' . $n ?>"><?php echo $row['size']; ?> </label>
                                <span class="text-dark badge border font-weight-normal">
                                    <?php $name = $row['size'];
                                    $sql = "SELECT * FROM products WHERE size='$name'";

                                    $stmt = $conn->prepare($sql);
                                    $stmt->execute();
                                    echo $stmt->rowCount();
                                    ?>
                                </span>
                            </div>
                        <?php
                            $n++;
                        }
                        ?>
                    </div> -->
                    <!-- Size End -->

                </div>
                <!-- Shop Sidebar End -->


                <!-- Shop Product Start -->
                <div class="col-lg-9 col-md-12">
                    <div class="row pb-3">
                        <div class="col-12 pb-1">
                            <div class="d-flex align-items-center justify-content-between mb-4">

                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" class="form-control" id="search" placeholder="Search by name">
                                    <div class="input-group-append">
                                        <span class="input-group-text bg-transparent text-primary">
                                            <i class="fa fa-search"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="dropdown ml-4">
                                    <button class="btn border dropdown-toggle" type="button" id="triggerId" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Sort by
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="triggerId">
                                        <label class="dropdown-item"><input type="radio" <?php if (isset($_POST['sorting']) && ($_POST['sorting'] == 'newest' || $_POST['sorting'] == '')) {
                                                                                                echo "checked";
                                                                                            } ?> name="sorting" class="custom-control-input common_selector sorting" value="newest">Latest</label>

                                        <label class="dropdown-item"><input type="radio" <?php if (isset($_POST['sorting']) && ($_POST['sorting'] == 'most_viewed' || $_POST['sorting'] == '')) {
                                                                                                echo "checked";
                                                                                            } ?> name="sorting" class="custom-control-input common_selector sorting" value="most_viewed">
                                            Popularity</label>

                                        <!-- <label class="dropdown-item"><input type="radio"<?php if (isset($_POST['sorting']) && ($_POST['sorting'] == 'best' || $_POST['sorting'] == '')) {
                                                                                                    echo "checked";
                                                                                                } ?>  name="sorting" class="custom-control-input common_selector"value="high">
                                            Best Rating</label> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=" row filter_data">

                        </div>



        </form>
    </div>
    </div>
    <!-- Shop Product End -->
    </div>
    </div>
    <!-- Shop End -->

    <!-- Footer Start -->


    <?php
    include "footer.php"
    ?>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="js/jquery-ui.js"></script>
    <script src="js/filter.js"></script>
    <!-- JavaScript Bundle with Popper -->

    <!-- Contact Javascript File -->

    <!-- Template Javascript -->
</body>

</html>
