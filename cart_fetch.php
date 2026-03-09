<?php
include 'session.php';
$conn = $pdo->open();

$output = array('list' => '', 'count' => 0);

if (isset($_SESSION['user'])) {
	try {
		$stmt = $conn->prepare("SELECT *, products.name AS prodname, category.name AS catname FROM cart LEFT JOIN products ON products.id=cart.product_id LEFT JOIN category ON category.id=products.category_id WHERE user_id=:user_id");
		$stmt->execute(['user_id' => $user['id']]);
		foreach ($stmt as $row) {
			$output['count']++;
			$image = app_image_url($row['photo'] ?? '');
			$productname = (strlen($row['prodname']) > 30) ? substr_replace($row['prodname'], '...', 27) : $row['prodname'];
			$output['list'] .= "
					<li>
						<a href='detail.php?product=" . e($row['slug']) . "'>
							<div class='pull-left'>
								<img src='" . e($image) . "' class='thumbnail' alt='Product image' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">
							</div>
							<h4>
		                        <b>" . e($row['catname']) . "</b>
		                        <small>&times; " . $row['quantity'] . "</small>
		                    </h4>
		                    <p>" . e($productname) . "</p>
						</a>
					</li>
				";
		}
	} catch (PDOException $e) {
		$output['message'] = $e->getMessage();
	}
} else {
	if (!isset($_SESSION['cart'])) {
		$_SESSION['cart'] = array();
	}

	if (empty($_SESSION['cart'])) {
		$output['count'] = 0;
	} else {
		foreach ($_SESSION['cart'] as $row) {
			$output['count']++;
			$stmt = $conn->prepare("SELECT *, products.name AS prodname, category.name AS catname FROM products LEFT JOIN category ON category.id=products.category_id WHERE products.id=:id");
			$stmt->execute(['id' => $row['productid']]);
			$product = $stmt->fetch();
			$image = app_image_url($product['photo'] ?? '');
			$output['list'] .= "
					<li>
						<a href='detail.php?product=" . e($product['slug']) . "'>
					<div class='container-flex'>
						
							<div class='pull-left img'>
								<img src='" . e($image) . "' class='img-circle' alt='Product image' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">
							</div>
							<h4>
		                        <b>" . e($product['catname']) . "</b>
		                        <small style=''>&times; " . $row['quantity'] . "</small>
		                    </h4>
		                    <p>" . e($product['prodname']) . "</p>
					</div>
					
						</a>
					</li>
				";
		}
	}
}

$pdo->close();
// print_r($output) ;
echo json_encode($output);
