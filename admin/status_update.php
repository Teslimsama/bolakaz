<?php 
	include 'session.php';

	// if(isset($_POST['id'])){
	// 	$id = $_POST['id'];
		
	// 	$conn = $pdo->open();

	// 	$stmt = $conn->prepare("SELECT * FROM sales WHERE id=:id");
	// 	$stmt->execute(['id'=>$id]);
	// 	$row = $stmt->fetch();
		
	// 	$pdo->close();

	// 	echo json_encode($row);
	// 	// echo $row;
	// }

// Assuming you have a database connection here

if (isset($_POST['id'])) {
    $id = $_POST['id'];

	// Fetch the current status
	$stmt = $conn->prepare("SELECT Status FROM sales WHERE id = :id");
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$previousStatus = $row['Status'];
	if ($previousStatus === 'success' || $previousStatus === 'successful') {
		$currentStatus ='failed';
	} elseif ($previousStatus === 'failed') {
		$currentStatus ='pending';
	} 
	else{
		$currentStatus = 'success';
	}
	
    // Perform your database update based on the provided id
    // Replace the following with your actual database update logic
    $stmt = $conn->prepare("UPDATE sales SET Status = '$currentStatus' WHERE id = :id");
    $stmt->bindParam(':id', $id);
	// print_r($stmt);
    $stmt->execute();

    $response = array('success' => true, 'message' => 'Update successful');
    echo json_encode($response);
} else {
    $response = array('success' => false, 'message' => 'ID not provided');
    echo json_encode($response);
}
?>
