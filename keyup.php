<?php
if (isset($_POST['query'])) {
    $search = $_POST['query'];
    $sql = "SELECT * FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%'";
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$results = $stmt->fetchAll();
$query_row = $stmt->rowCount();
$k = '';
while ($key = $results) {


    if ($query_row > 0) {
        $k .= '
			<div class="col-lg-4 col-md-6 col-sm-12 pb-1">
                        <div class="card product-item border-0 mb-4">
                            <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                                <img class="img-fluid w-100" src="images/' . $key['photo'] . '" alt="">
                            </div>
                            <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                                <h6 class="text-truncate mb-3">' . $key['name'] . '</h6>
                                <div class="d-flex justify-content-center">
                                    <h6>$' . $key['price'] . '</h6><h6 class="text-muted ml-2"><del>$' . $key['price'] . '</del></h6>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between bg-light border">
                                <a href="detail.php?product=' . $key['slug'] . '" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
								
                                
                            </div>
                        </div>
                    </div>
			';
    } else {
        $k .= '<h3 class="text-center my-5" style="
    display: flex;
    flex-direction: column;
    align-content: center;
    align-items: center;
">No Data Found</h3>';
    }
    echo $k;
}