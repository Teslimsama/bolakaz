<?php
//fetch_data.php
include 'session.php';

if (isset($_POST["action"])) {
    $conn = $pdo->open();
    $cat = $_POST["cat"];
    
    // Set default values for pagination
    $limit = 6; // Number of items per page
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1; // Current page number
    $start = ($page - 1) * $limit; // Offset for query

    $query = "SELECT * FROM products WHERE product_status = '1'";

    if ($_POST["cat"] !== '0') {
        $query .= " AND category_name='$cat'";
    }

    if (isset($_POST["minimum_price"], $_POST["maximum_price"]) && !empty($_POST["minimum_price"]) && !empty($_POST["maximum_price"])) {
        $query .= " AND price BETWEEN '" . $_POST["minimum_price"] . "' AND '" . $_POST["maximum_price"] . "'";
    }
    if (isset($_POST["brand"])) {
        $brand_filter = implode("','", $_POST["brand"]);
        $query .= " AND brand IN('" . $brand_filter . "')";
    }
    if (isset($_POST["category"])) {
        $category_filter = implode("','", $_POST["category"]);
        $query .= " AND category_name IN('" . $category_filter . "')";
    }
    if (isset($_POST["color"])) {
        $color_filter = implode("','", $_POST["color"]);
        $query .= " AND color IN('" . $color_filter . "')";
    }
    if (isset($_POST["material"])) {
        $material_filter = implode("','", $_POST["material"]);
        $query .= " AND material IN('" . $material_filter . "')";
    }
    if (isset($_POST["size"])) {
        $size_filter = implode("','", $_POST["size"]);
        $query .= " AND size IN('" . $size_filter . "')";
    }
    if (isset($_POST['sorting']) && $_POST['sorting'] != "") {
        $sorting = $_POST['sorting'];
        if ($sorting == 'newest' || $sorting == '') {
            $query .= " ORDER BY id DESC";
        } else if ($sorting == 'most_viewed') {
            $query .= " ORDER BY counter DESC";
        }
    } else {
        $query .= " ORDER BY id DESC";
    }

    // Fetch total products for pagination
    $total_query = "SELECT COUNT(*) as total FROM products WHERE product_status = '1'";
    $total_statement = $conn->prepare($total_query);
    $total_statement->execute();
    $total_products = $total_statement->fetch(PDO::FETCH_ASSOC)['total'];

    // Apply limit and offset for pagination
    $query .= " LIMIT $start, $limit";
    $statement = $conn->prepare($query);
    $statement->execute();
    $result = $statement->fetchAll();

    // Generate product output
    $output = '';
    if ($statement->rowCount() > 0) {
        foreach ($result as $row) {
            $output .= '
            <div class="col-lg-4 col-md-6 col-sm-12 pb-1">
                <div class="card product-item border-0 mb-4">
                    <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                        <img loading="lazy" class="img-fluid w-100" src="images/' . $row['photo'] . '" alt="">
                    </div>
                    <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                        <h6 class="text-truncate mb-3">' . $row['name'] . '</h6>
                        <div class="d-flex justify-content-center">
                            <h6>₦' . $row['price'] . '</h6>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-center bg-light border">
                        <a href="detail.php?product=' . $row['slug'] . '" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                    </div>
                </div>
            </div>';
        }
    } else {
        $output = '<h3 class="text-center my-5">No Data Found</h3>';
    }

    // Generate pagination links
    $total_pages = ceil($total_products / $limit);
    $pagination = '<ul class="pagination justify-content-center mb-3">';

    // Add "Previous" button
    if ($page > 1) {
        $prev_page = $page - 1;
        $pagination .= "<li class='page-item'><a class='page-link' href='#' data-page='$prev_page'>Previous</a></li>";
    } else {
        $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>Previous</a></li>";
    }

    // Add page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $page) ? 'active' : '';
        $pagination .= "<li class='page-item $active'><a class='page-link' href='#' data-page='$i'>$i</a></li>";
    }

    // Add "Next" button
    if ($page < $total_pages) {
        $next_page = $page + 1;
        $pagination .= "<li class='page-item'><a class='page-link' href='#' data-page='$next_page'>Next</a></li>";
    } else {
        $pagination .= "<li class='page-item disabled'><a class='page-link' href='#'>Next</a></li>";
    }

    $pagination .= '</ul>';

    // Combine output
    echo $output . $pagination;
}
?>
