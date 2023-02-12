<?php

include 'session.php'; 
if (isset($_POST['search'])) {
    $searchTerm = $_POST['search'];

    // Connect to database
    // $conn = mysqli_connect("localhost", "root", "", "bolakaz");

    // Perform the search
    $query = "SELECT * FROM products WHERE name LIKE '%$searchTerm%'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
   ;
    // $result = mysqli_query($conn, $query);

    // Prepare the search results for display
    $products = array();
    while ($row =  $result = $stmt->fetch()) {
        $products[] = $row;
    }
    echo json_encode($products);
}
