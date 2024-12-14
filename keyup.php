<?php
include 'session.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'search') {
    $query = $_POST['query'];
    $limit = 9; // Items per page
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Total products matching the search query
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE name LIKE :query OR description LIKE :query");
    $count_stmt->execute([':query' => '%' . $query . '%']);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch products for the current page
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE :query OR description LIKE :query LIMIT :offset, :limit");
    $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach ($results as $product) {
            echo '
            <div class="col-lg-4 col-md-6 col-sm-12 pb-1">
                <div class="card product-item border-0 mb-4">
                    <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                        <img loading="lazy" class="img-fluid w-100" src="images/' . htmlspecialchars($product['photo']) . '" alt="">
                    </div>
                    <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                        <h6 class="text-truncate mb-3">' . htmlspecialchars($product['name']) . '</h6>
                        <div class="d-flex justify-content-center">
                            <h6>₦' . htmlspecialchars($product['price']) . '</h6>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-center bg-light border">
                        <a href="detail.php?product=' . htmlspecialchars($product['slug']) . '" class="btn btn-sm text-dark p-0">
                            <i class="fas fa-eye text-primary mr-1"></i>View Detail
                        </a>
                    </div>
                </div>
            </div>';
        }

        // Generate pagination buttons
        $total_pages = ceil($total / $limit);
        if ($total_pages > 1) {
            echo '<div class="pagination d-flex justify-content-center mt-3">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $active = $i == $page ? 'active' : '';
                echo '<button class="btn btn-sm btn-primary mx-1 keyup-page-link ' . $active . '" data-page="' . $i . '">' . $i . '</button>';
            }
            echo '</div>';
        }
    } else {
        echo "<p>No products found matching your search.</p>";
    }
}
