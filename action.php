<?php
include 'includes/session.php';
include 'Rating.php';
$rating = new Rating();

if(!empty($_POST['action']) && $_POST['action'] == 'saveRating'
	&& !empty($user['id']) 
    && !empty($_POST['rating']) 
	&& !empty($_POST['itemid'])) {
		// $userID = $_POST['user'];	
		$userID = $user['id'];	
		$rating->saveRating($_POST, $userID);	
		$data = array(
			"success"	=> 1,	
		);
		echo json_encode($data);		
}

 
?>