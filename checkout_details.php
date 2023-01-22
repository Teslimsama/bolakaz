<?php
include 'session.php';
$conn = $pdo->open();

$output = '';

if (isset($_SESSION['user'])) {
	if (isset($_SESSION['cart'])) {
		foreach ($_SESSION['cart'] as $row) {
			$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM cart WHERE user_id=:user_id AND product_id=:product_id");
			$stmt->execute(['user_id' => $user['id'], 'product_id' => $row['productid']]);
			$crow = $stmt->fetch();
			if ($crow['numrows'] < 1) {
				$stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
				$stmt->execute(['user_id' => $user['id'], 'product_id' => $row['productid'], 'quantity' => $row['quantity']]);
			} else {
				$stmt = $conn->prepare("UPDATE cart SET quantity=:quantity WHERE user_id=:user_id AND product_id=:product_id");
				$stmt->execute(['quantity' => $row['quantity'], 'user_id' => $user['id'], 'product_id' => $row['productid']]);
			}
		}
		unset($_SESSION['cart']);
	}

	try {
		$total = 0;
		$stmt = $conn->prepare("SELECT *, cart.id AS cartid FROM cart LEFT JOIN products ON products.id=cart.product_id WHERE user_id=:user");
		$stmt->execute(['user' => $user['id']]);
		foreach ($stmt as $row) {
			$image = (!empty($row['photo'])) ? 'images/' . $row['photo'] : 'images/noimage.jpg';
			$subtotal = $row['price'] * $row['quantity'];
			$total += $subtotal;
			$total_c = $total + 10;

			$output .= "					  <div class='d-flex justify-content-between'>
			<p>" . $row['quantity'] . " &times;</p>
			<p>" . $row['name'] . "</p>
			<p>Size(" . $row['size'] . ")</p>
			<p>Color(" . $row['color'] . ")</p>
			<p> $" . number_format($row['price'], 2) . "</p>
		</div>
        
                        ";
		}
		$output .= "
				

 <hr class='mt-0'>
 <div class='d-flex justify-content-between mb-3 pt-1'>
     <h6 class='font-weight-medium'>Subtotal</h6>
     <h6 class='font-weight-medium'>$ " . number_format($total, 2) . "</h6>
 </div>
 <div class='d-flex justify-content-between'>
     <h6 class='font-weight-medium'>Shipping</h6>
     <h6 class='font-weight-medium'>$10</h6>
 </div>
			";

		$output .= "</div>
<div class='card-footer border-secondary bg-transparent'>
    <div class='d-flex justify-content-between mt-2'>
        <h5 class='font-weight-bold'>Total</h5>
<input type='hidden' value='" . $total_c  . "' id='amount'>
        <h5 class='font-weight-bold'> $" . number_format($total + 10, 2) . "</h5>
    </div>
</div>
</div>";
	} catch (PDOException $e) {
		$output .= $e->getMessage();
	}
} else {
	if (count($_SESSION['cart']) != 0) {
		$total = 0;
		foreach ($_SESSION['cart'] as $row) {
			$stmt = $conn->prepare("SELECT *, products.name AS prodname, category.name AS catname FROM products LEFT JOIN category ON category.id=products.category_id WHERE products.id=:id");
			$stmt->execute(['id' => $row['productid']]);
			$product = $stmt->fetch();
			$image = (!empty($product['photo'])) ? 'images/' . $product['photo'] : 'images/noimage.jpg';
			$subtotal = $product['price'] * $row['quantity'];
			$total += $subtotal;
			$total_c = $total + 10;
			$output .= "
							 <div class='d-flex justify-content-between'>
     <p>" . $row['name'] . "</p>
     <p>$ " . number_format($row['price'], 2) . "</p>
 </div>
        
                        ";
		}
		$output .= "
				 
 <hr class='mt-0'>
 <div class='d-flex justify-content-between mb-3 pt-1'>
     <h6 class='font-weight-medium'>Subtotal</h6>
     <h6 class='font-weight-medium'>$ " . number_format($total, 2) . "</h6>
 </div>
 <div class='d-flex justify-content-between'>
     <h6 class='font-weight-medium'>Shipping</h6>
     <h6 class='font-weight-medium'>$10</h6>
 </div>
			";
		$output .= "</div>
<div class='card-footer border-secondary bg-transparent'>
    <div class='d-flex justify-content-between mt-2'>
        <h5 class='font-weight-bold'>Total</h5>
		<input type='hidden' value=' " . $total_c . "' id='amount'>
        <h5 class='font-weight-bold'> $" . number_format($total + 10, 2) . "</h5>

    </div>
</div>
</div>";
	} else {
		$output .= "
				<tr>
					<td colspan='6' align='center'>Shopping cart empty</td>
				<tr>
			";
	}
}

$pdo->close();
echo json_encode($output);
// echo $output;
// 	