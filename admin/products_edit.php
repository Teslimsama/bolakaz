<?php
	include 'session.php';
	include 'slugify.php';

	if(isset($_POST['edit'])){
		$id = $_POST['id'];
		$name = $_POST['name'];
		$slug = slugify($name);
		$category = $_POST['category'];
		$sql=$conn->prepare("SELECT * FROM category WHERE $category=id ");
		$sql->execute(['category'=>$category]);
		foreach ($sql as $go) {
		$category_name = $go['cat_slug'];
		
		}
		
		$price = $_POST['price'];
		$description = $_POST['description'];
		$size =$_POST['size'];
		$material= $_POST['material'];
		$color= $_POST['color'];
		$brand= $_POST['brand'];
		$qty= $_POST['quantity'];

		$conn = $pdo->open();

		try{
			$stmt = $conn->prepare("UPDATE products SET name=:name, slug=:slug, category_id=:category,category_name=:category_name, price=:price, description=:description, color=:color, size=:size, brand=:brand, material=:material, qty=:qty WHERE id=:id");
			$stmt->execute(['name'=>$name, 'slug'=>$slug, 'category'=>$category, 'category_name' => $category_name, 'price'=>$price, 'description'=>$description, 'color' => $color, 'size' => $size, 'brand' => $brand, 'material' => $material, 'qty' => $qty, 'id'=>$id]);
			$_SESSION['success'] = 'Product updated successfully';
		}
		catch(PDOException $e){
			$_SESSION['error'] = $e->getMessage();
		}
		
		$pdo->close();
	}
	else{
		$_SESSION['error'] = 'Fill up edit product form first';
	}

	header('location: products.php');
