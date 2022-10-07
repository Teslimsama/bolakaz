<?php
	include 'includes/session.php';
	$conn = $pdo->open();

	$output = '';

	if(isset($_SESSION['user'])){
		if(isset($_SESSION['cart'])){
			foreach($_SESSION['cart'] as $row){
				$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM cart WHERE user_id=:user_id AND product_id=:product_id");
				$stmt->execute(['user_id'=>$user['id'], 'product_id'=>$row['productid']]);
				$crow = $stmt->fetch();
				if($crow['numrows'] < 1){
					$stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
					$stmt->execute(['user_id'=>$user['id'], 'product_id'=>$row['productid'], 'quantity'=>$row['quantity']]);
				}
				else{
					$stmt = $conn->prepare("UPDATE cart SET quantity=:quantity WHERE user_id=:user_id AND product_id=:product_id");
					$stmt->execute(['quantity'=>$row['quantity'], 'user_id'=>$user['id'], 'product_id'=>$row['productid']]);
				}
			}
			unset($_SESSION['cart']);
		}

		try{
			$total = 0;
			$stmt = $conn->prepare("SELECT *, cart.id AS cartid FROM cart LEFT JOIN products ON products.id=cart.product_id WHERE user_id=:user");
			$stmt->execute(['user'=>$user['id']]);
			foreach($stmt as $row){
				$image = (!empty($row['photo'])) ? 'images/'.$row['photo'] : 'images/noimage.jpg';
				$subtotal = $row['price']*$row['quantity'];
				$total += $subtotal;
				$output .= "
					 <tr>
                            <td class='align-middle'><img src'" . $image . "' alt=' style='width: 50px;'>" . $row['name'] . "</td>
                            <td class='align-middle'>&#36; " . number_format($row['price'], 2) . "</td>
                            <td class='align-middle'>
                                <div class='input-group quantity mx-auto' style='width: 100px;'>
                                    <div class='input-group-btn'>
                                        <button id='minus' class='btn btn-sm btn-primary btn-minus minus' data-id='" . $row['cartid'] . "' >
                                        <i class='fa fa-minus'></i>
                                        </button>
                                    </div>
                                    <input type='text' class='form-control form-control-sm bg-secondary text-center'  value='" . $row['quantity'] . "' id='qty_" . $row['cartid'] . "'>
                                    <div class='input-group-btn'>
                                        <button id='add' class='btn btn-sm btn-primary btn-plus add' data-id='" . $row['cartid'] . "'>
                                            <i class='fa fa-plus'></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class='align-middle'>&#36; " . number_format($subtotal, 2) . "</td>
                            <td class='align-middle'><button type='submit' name='remove' data-id='" . $row['cartid'] . "' class='btn btn-sm btn-primary cart_delete'><i class='fa fa-times'></i></button></td>
                        </tr>
        
                        ";
			}
			// $output .= "
			// 	<tr>
			// 		<td colspan='5' align='right'><b>Total</b></td>
			// 		<td><b>&#36; ".number_format($total, 2)."</b></td>
			// 	<tr>
			// ";

		}
		catch(PDOException $e){
			$output .= $e->getMessage();
		}

	}
	else{
		if(count($_SESSION['cart']) != 0){
			$total = 0;
			foreach($_SESSION['cart'] as $row){
				$stmt = $conn->prepare("SELECT *, products.name AS prodname, category.name AS catname FROM products LEFT JOIN category ON category.id=products.category_id WHERE products.id=:id");
				$stmt->execute(['id'=>$row['productid']]);
				$product = $stmt->fetch();
				$image = (!empty($product['photo'])) ? 'images/'.$product['photo'] : 'images/noimage.jpg';
				$subtotal = $product['price']*$row['quantity'];
				$total += $subtotal;
				$output .= "
								 <tr>
        
                            <td class='align-middle'><img src'" . $image . "' alt=' style='width: 50px;'>" . $row['name'] . "</td>
                            <td class='align-middle'>&#36; " . number_format($row['price'], 2) . "</td>
                            <td class='align-middle'>
                                <div class='input-group quantity mx-auto' style='width: 100px;'>
                                    <div class='input-group-btn'>
                                        <button id='minus' class='btn btn-sm btn-primary btn-minus minus' data-id='" . $row['cartid'] . "' >
                                        <i class='fa fa-minus'></i>
                                        </button>
                                    </div>
                                    <input type='text' class='form-control form-control-sm bg-secondary text-center'  value='" . $row['quantity'] . "' id='qty_" . $row['cartid'] . "'>
                                    <div class='input-group-btn'>
                                        <button id='add' class='btn btn-sm btn-primary btn-plus add' data-id='" . $row['cartid'] . "'>
                                            <i class='fa fa-plus'></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class='align-middle'>&#36; " . number_format($subtotal, 2) . "</td>
                            <td class='align-middle'><button type='submit' name='remove' data-id='" . $row['cartid'] . "' class='btn btn-sm btn-primary cart_delete'><i class='fa fa-times'></i></button></td>
                        </tr>
        
                        ";
			}
			// $output .= "
			// 	<tr>
			// 		<td colspan='5' align='right'><b>Total</b></td>
			// 		<td><b>&#36; ".number_format($total, 1)."</b></td>
			// 	<tr>
			// ";
		}

		else{
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
?>

