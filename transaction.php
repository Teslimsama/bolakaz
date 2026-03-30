<?php
	include 'session.php';
	require_once __DIR__ . '/lib/sales_snapshot.php';

	$id = $_POST['id'];

	$conn = $pdo->open();

	$output = array('list'=>'');

	$priceSql = app_sales_detail_price_sql($conn, 'details', 'products');
	$nameSql = app_sales_detail_name_sql($conn, 'details', 'products');
	$stmt = $conn->prepare("SELECT {$nameSql} AS item_name, {$priceSql} AS item_price, details.quantity, sales.tx_ref, sales.sales_date FROM details LEFT JOIN products ON products.id=details.product_id LEFT JOIN sales ON sales.id=details.sales_id WHERE details.sales_id=:id");
	$stmt->execute(['id'=>$id]);

	$total = 0;
	foreach($stmt as $row){
		$output['transaction'] = $row['tx_ref'];
		$output['date'] = date('M d, Y', strtotime($row['sales_date']));
		$subtotal = ((float)$row['item_price']) * ((int)$row['quantity']);
		$total += $subtotal;
		$output['list'] .= "
			<tr class='prepend_items'>
				<td>".$row['item_name']."</td>
				<td> ₦".number_format((float)$row['item_price'], 2)."</td>
				<td>".$row['quantity']."</td>
				<td> ₦".number_format($subtotal, 2)."</td>
			</tr>
		";
	}
	
	$output['total'] = '<b> ₦'.number_format($total, 2).'<b>';
	$pdo->close();
	echo json_encode($output);
