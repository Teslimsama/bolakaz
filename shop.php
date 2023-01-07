<?php
include 'includes/session.php';

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

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/jquery-ui.css" rel="stylesheet">
</head>

<body>

    <?php
    include "includes/header.php"; ?>
    <?php include 'includes/navbar.php'; ?>


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
                <div class="col-lg-3 col-md-12">
                    <!-- Price Start -->
                    <div class="border-bottom mb-4 pb-4">
                        <h5 class="font-weight-semi-bold mb-4">Filter by price</h5>
                        <div class="custom-control custom-checkbox justify-content-between mb-3">
                            <input type="hidden" id="hidden_minimum_price" value="0" />
                            <input type="hidden" id="hidden_maximum_price" value="65000" />
                            <input type="hidden" id="cat" value="<?php if (isset($_GET['category'])) {
                                echo $_GET['category'];
                            }else {
                                echo "0";
                            } ?>">
                            <p id="price_show">1000 - 65000</p>
                            <div id="price_range"></div>
                        </div>
                    </div>
                    <!-- Price End -->

                    <!--Category Start -->
                    <div class="border-bottom mb-4 pb-4">
                        <h5 class="font-weight-semi-bold mb-4">Filter by category</h5>
                        <?php
                        $n = 1;
                        $query = "SELECT DISTINCT(category_name) FROM products GROUP BY category_name DESC";
                        $statement = $conn->prepare($query);
                        $statement->execute();
                        $result = $statement->fetchAll();

                        foreach ($result as $row) {
                        ?>
                            <div class="custom-control custom-checkbox checkbox d-flex align-items-center justify-content-between mb-3">
                                <input type="checkbox" class="custom-control-input common_selector category" value="<?php echo $row['category_name']; ?>" id="<?php echo 'cat-' . $n ?>">
                                <label class=" custom-control-label" for="<?php echo 'cat-' . $n ?>"><?php echo ucwords($row['category_name']); ?></label>
                                <span class="text-dark badge border font-weight-normal">
                                    <?php $name = $row['category_name'];
                                    $sql = "SELECT * FROM products WHERE category_name='$name'";

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
                    </div>
                    <!-- Category End -->

                    <!--Brand Start -->
                    <div class="border-bottom mb-4 pb-4">
                        <h5 class="font-weight-semi-bold mb-4">Filter by brand</h5>
                        <?php
                        $n = 1;
                        $query = "SELECT DISTINCT(brand) FROM products GROUP BY brand DESC";
                        $statement = $conn->prepare($query);
                        $statement->execute();
                        $result = $statement->fetchAll();
                        foreach ($result as $row) {
                        ?>
                            <div class="custom-control custom-checkbox checkbox d-flex align-items-center justify-content-between mb-3">
                                <input type="checkbox" class="custom-control-input common_selector brand" value="<?php echo $row['brand']; ?>" id="<?php echo 'brand-' . $n ?>">
                                <label class=" custom-control-label" for="<?php echo 'brand-' . $n ?>"><?php echo ucwords($row['brand']); ?></label>
                                <span class="text-dark badge border font-weight-normal">
                                    <?php $name = $row['brand'];
                                    $sql = "SELECT * FROM products WHERE brand='$name'";

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
                    </div>
                    <!-- Brand End -->

                    <!--Material Start -->
                    <div class="border-bottom mb-4 pb-4">
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
                    </div>
                    <!-- Material End -->

                    <!--Color Start -->
                    <div class="border-bottom mb-4 pb-4">
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
                    </div>
                    <!-- Color End -->

                    <!-- Size Start -->
                    <div class="mb-5">
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
                    </div>
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
    include "includes/footer.php"
    ?>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="js/jquery-1.10.2.min.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/filter.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <!-- JavaScript Bundle with Popper -->
    <script src="js/bootstrap.min.js"></script>

    <!-- Contact Javascript File -->
    <script src="mail/jqBootstrapValidation.min.js"></script>
    <script src="mail/contact.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>