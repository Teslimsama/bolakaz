<?php
include 'session.php';
$conn = $pdo->open();
$name=$_POST['name'];
$email=$_POST['email'];

$stmt = $conn->prepare("INSERT INTO newsletter (email, name) VALUES (:email, :name )");
$stmt->execute(['email'=>$email, 'name'=>$name]);