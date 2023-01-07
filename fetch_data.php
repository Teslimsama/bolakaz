<?php

//fetch_data.php
include 'includes/session.php';
if(isset($_POST["action"]))
{
	$conn = $pdo->open();
	$cat = $_POST["cat"];
	

	$query = "SELECT * FROM products WHERE product_status = '1' ";
	
	

	if ($_POST["cat"] !== '0') {
		$query .= " AND category_name='$cat'";
	}
	else {
		$query.=" ";
	}
	
	
	if(isset($_POST["minimum_price"], $_POST["maximum_price"]) && !empty($_POST["minimum_price"]) && !empty($_POST["maximum_price"]))
	{
		$query .= "
		 AND price BETWEEN '".$_POST["minimum_price"]."' AND '".$_POST["maximum_price"]."'
		";
	}
	if(isset($_POST["brand"]))
	{
		$brand_filter = implode("','", $_POST["brand"]);
		$query .= "
		 AND brand IN('".$brand_filter."')
		";
	}
	if(isset($_POST["category"]))
	{
		$category_filter = implode("','", $_POST["category"]);
		$query .= "
		 AND category_name IN('".$category_filter."')
		";
	}
	if(isset($_POST["color"]))
	{
		$color_filter = implode("','", $_POST["color"]);
		$query .= "
		 AND color IN('".$color_filter."')
		";
	}
	if(isset($_POST["material"]))
	{
		$material_filter = implode("','", $_POST["material"]);
		$query .= "
		 AND material IN('".$material_filter."')
		";
	}
	if(isset($_POST["size"]))
	{
		$size_filter = implode("','", $_POST["size"]);
		$query .= "
		 AND size IN('".$size_filter."')
		";
	}
	if (isset($_POST['sorting']) && $_POST['sorting'] != "") {
		$sorting = implode("','", $_POST['sorting']);
		if ($sorting == 'newest' || $sorting == '') {
			$query .= " ORDER BY id DESC";
		} else if ($sorting == 'most_viewed') {
			$query .= " ORDER BY counter DESC";
		}
		//  else if ($sorting == 'best') {
		// 	$query .= " ORDER BY price DESC";
		// }
	} else {
		$query .= " ORDER BY id DESC";
	}		

	$statement = $conn->prepare($query);
	$statement->execute();
	$result = $statement->fetchAll();
	$total_row = $statement->rowCount();
	$output = '';
	if($total_row > 0)
	{
		foreach($result as $row)
		{
			$output .= '
			<div class="col-lg-4 col-md-6 col-sm-12 pb-1">
                        <div class="card product-item border-0 mb-4">
                            <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                                <img class="img-fluid w-100" src="images/' . $row['photo'] . '" alt="">
                            </div>
                            <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                                <h6 class="text-truncate mb-3">' .$row['name']. '</h6>
                                <div class="d-flex justify-content-center">
                                    <h6>$' . $row['price'] . '</h6><h6 class="text-muted ml-2"><del>$' . $row['price'] . '</del></h6>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-center bg-light border">
                                <a href="detail.php?product='. $row['slug'] .'" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
								'.$cat.'
                            </div>
                        </div>
                    </div>
			';
		}
	}
	else
	{
		$output = '<h3 class="text-center my-5" style="
    display: flex;
    flex-direction: column;
    align-content: center;
    align-items: center;
">No Data Found</h3>';
	}
	echo $output;
}

?>