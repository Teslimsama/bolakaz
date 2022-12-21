<?php
	include 'includes/session.php';

	$output = '';

	$conn = $pdo->open();

	$stmt = $conn->prepare("SELECT * FROM category");
	$stmt->execute();

	foreach($stmt as $row){
		$output .= "<input type='hidden'  name='category_name' value='" . $row['name'] . "'>
			<option value='".$row['id']. "' class='append_items'>".$row['name']. "</option>
		";
	}

	$pdo->close();
	echo json_encode($output);

?>