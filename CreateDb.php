<?php

$servername = "localhost";
$database = "bolakaz";
$username = "root";
$password = "";

$db_connect = new mysqli($servername, $username, $password,  $database);

if ($db_connect->connect_error) {
   die("connection failed:" . $db_connect->connect_error);
}
$sql = "SELECT * FROM producttb";

$result = mysqli_query($db_connect, $sql);

if (mysqli_num_rows($result) > 0) {
   return $result;
}
