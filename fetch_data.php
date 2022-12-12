<?php

//fetch_data.php
include 'includes/session.php';

// $conn = new PDO("mysql:host=localhost;dbname=testing", "root", "");


if(isset($_POST["action"]))
{
	$conn = $pdo->open();
	$query = "
		SELECT * FROM producto WHERE product_status = '1'
	";
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
                                <img class="img-fluid w-100" src="img/' . $row['photo'] . '" alt="">
                            </div>
                            <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                                <h6 class="text-truncate mb-3">' . $row['name'] . '</h6>
                                <div class="d-flex justify-content-center">
                                    <h6>$' . $row['price'] . '</h6><h6 class="text-muted ml-2"><del>$' . $row['price'] . '</del></h6>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between bg-light border">
                                <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
								
                                <button type="submit" class="btn btn-sm text-dark p-0"><i class="fas fa-shopping-cart text-primary mr-1"></i>Add To Cart</button>
                            </div>
                        </div>
                    </div>
			';
		}
	}
	else
	{
		$output = '<h3>No Data Found</h3>';
	}
	echo $output;
}

?>