<?php
include 'session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search') {
    $conn = $pdo->open();
    $query = trim((string) ($_POST['query'] ?? ''));
    $limit = 9;
    $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
    $offset = ($page - 1) * $limit;

    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE product_status = '1' AND (name LIKE :query OR description LIKE :query)");
    $countStmt->execute([':query' => '%' . $query . '%']);
    $total = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $conn->prepare("SELECT * FROM products WHERE product_status = '1' AND (name LIKE :query OR description LIKE :query) ORDER BY id DESC LIMIT :offset, :limit");
    $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        foreach ($results as $product) {
            $image = app_image_url($product['photo'] ?? '');
            echo '
            <div class="col-lg-4 col-md-6 col-sm-12 pb-1">
                <div class="card product-item border-0 mb-4">
                    <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                        <div class="sf-media sf-media-product">
                            <img loading="lazy" class="img-fluid w-100" src="' . e($image) . '" alt="' . e($product['name'] ?? 'Product') . '" onerror="this.onerror=null;this.src=\'' . e(app_placeholder_image()) . '\';">
                        </div>
                    </div>
                    <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                        <h6 class="text-truncate mb-3">' . e($product['name'] ?? '') . '</h6>
                        <div class="d-flex justify-content-center">
                            <h6>' . app_money($product['price'] ?? 0) . '</h6>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-center bg-light border">
                        <a href="detail.php?product=' . e((string)($product['slug'] ?? '')) . '" class="btn btn-sm text-dark p-0">
                            <i class="fas fa-eye text-primary mr-1"></i>View Detail
                        </a>
                    </div>
                </div>
            </div>';
        }

        $totalPages = (int)ceil($total / $limit);
        if ($totalPages > 1) {
            echo '<div class="pagination d-flex justify-content-center mt-3">';
            for ($i = 1; $i <= $totalPages; $i++) {
                $active = $i === $page ? 'active' : '';
                echo '<button class="btn btn-sm btn-primary mx-1 keyup-page-link ' . $active . '" data-page="' . $i . '">' . $i . '</button>';
            }
            echo '</div>';
        }
    } else {
        echo "<p>No products found matching your search.</p>";
    }

    $pdo->close();
}
