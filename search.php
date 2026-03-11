<?php include 'session.php'; ?>
<?php
require_once __DIR__ . '/lib/catalog_v2.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedKeyword = trim((string)($_POST['keyword'] ?? $_POST['search'] ?? ''));
    header('location: search?q=' . urlencode($postedKeyword));
    exit();
}

$keyword = trim((string)($_GET['q'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Search"; include "head.php"; ?>
</head>

<body>
    <?php include "header.php"; ?>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid bg-secondary mb-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 300px">
            <h1 class="font-weight-semi-bold text-uppercase mb-3">Our Shop</h1>
            <div class="d-inline-flex">
                <p class="m-0"><a href="index">Home</a></p>
                <p class="m-0 px-2">-</p>
                <p class="m-0">Search</p>
            </div>
        </div>
    </div>

    <div class="container-fluid pt-5">
        <form method="get" action="search" id="search_form">
            <div class="row px-xl-5">
                <div class="col-lg-12 col-md-12">
                    <div class="row pb-3">
                        <div class="col-12 pb-1">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="q" id="search" placeholder="Search by name or description" value="<?php echo e($keyword); ?>">
                                    <button type="submit" class="input-group-text bg-transparent text-primary border" aria-label="Search">
                                            <i class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class='row g-3'>
                            <?php
                            $conn = $pdo->open();

                            if ($keyword === '') {
                                echo '<h1 class="page-header px-3">Enter a keyword to search products.</h1>';
                            } else {
                                if (catalog_v2_ready($conn)) {
                                    $stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM products_v2 WHERE status = 'active' AND (name LIKE :keyword OR description LIKE :keyword)");
                                } else {
                                    $stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM products WHERE product_status = '1' AND (name LIKE :keyword OR description LIKE :keyword)");
                                }
                                $stmt->execute(['keyword' => '%' . $keyword . '%']);
                                $row = $stmt->fetch();

                                if ((int)$row['numrows'] < 1) {
                                    echo '<h1 class="page-header px-3">No results found for <i>' . e($keyword) . '</i></h1>';
                                } else {
                                    echo '<h1 class="page-header px-3">Search results for <i>' . e($keyword) . '</i></h1>';
                                    try {
                                        if (catalog_v2_ready($conn)) {
                                            $stmt = $conn->prepare("SELECT id, slug, name, base_price AS price, main_image AS photo FROM products_v2 WHERE status = 'active' AND (name LIKE :keyword OR description LIKE :keyword) ORDER BY id DESC");
                                        } else {
                                            $stmt = $conn->prepare("SELECT * FROM products WHERE product_status = '1' AND (name LIKE :keyword OR description LIKE :keyword) ORDER BY id DESC");
                                        }
                                        $stmt->execute(['keyword' => '%' . $keyword . '%']);

                                        foreach ($stmt as $row) {
                                            $name = (string)($row['name'] ?? '');
                                            $highlighted = preg_filter('/' . preg_quote($keyword, '/') . '/i', '<b>$0</b>', e($name));
                                            if ($highlighted === null) {
                                                $highlighted = e($name);
                                            }
                                            $image = app_image_url($row['photo'] ?? '');

                                            echo "
                                            <div class='col-lg-4 col-md-6 col-sm-12 pb-1'>
                                                <div class='card product-item border-0 mb-4'>
                                                    <div class='card-header product-img position-relative overflow-hidden bg-transparent border p-0'>
                                                        <div class='sf-media sf-media-product'>
                                                            <img loading='lazy' class='img-fluid w-100' src='" . e($image) . "' alt='" . e($name) . "' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">
                                                        </div>
                                                    </div>
                                                    <div class='card-body border-left border-right text-center p-0 pt-4 pb-3'>
                                                        <h6 class='text-truncate mb-3'>" . $highlighted . "</h6>
                                                        <div class='d-flex justify-content-center'>
                                                            <h6>" . app_money($row['price']) . "</h6>
                                                        </div>
                                                    </div>
                                                    <div class='card-footer d-flex justify-content-center bg-light border'>
                                                        <a href='detail.php?product=" . e((string)$row['slug']) . "' class='btn btn-sm text-dark p-0'><i class='fas fa-eye text-primary mr-1'></i>View Detail</a>
                                                    </div>
                                                </div>
                                            </div>
                                            ";
                                        }
                                    } catch (PDOException $e) {
                                        echo "There is some problem in connection: " . e($e->getMessage());
                                    }
                                }
                            }

                            $pdo->close();
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php include "footer.php"; ?>

    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>
</body>

</html>
