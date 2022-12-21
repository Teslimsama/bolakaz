<?php
	include 'includes/session.php';
	include 'includes/slugify.php';

	if(isset($_POST['add'])){
		$name = $_POST['name'];
		$slug = slugify($name);
		$category = $_POST['category'];
		$category_name = $_POST['category_name'];
		$price = $_POST['price'];
		$description = $_POST['description'];
		$filename = $_FILES['photo']['name'];
		$size =$_POST['size'];
		$material= $_POST['material'];
		$color= $_POST['color'];
		$brand= $_POST['brand'];
		$qty= $_POST['quantity'];
		$product_status = 1;

		$conn = $pdo->open();

		$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM products WHERE slug=:slug");
		$stmt->execute(['slug'=>$slug]);
		$row = $stmt->fetch();

		if($row['numrows'] > 0){
			$_SESSION['error'] = 'Product already exist';
		}
		else{
			if(!empty($filename)){
				$ext = pathinfo($filename, PATHINFO_EXTENSION);
				$new_filename = $slug.'.'.$ext;
				move_uploaded_file($_FILES['photo']['tmp_name'], '../images/'.$new_filename);	
			}
			else{
				$new_filename = '';
			}

			try{
				$stmt = $conn->prepare("INSERT INTO products (category_id, category_name, name, description, slug, price, color, size, brand, material, qty, photo, product_status) VALUES (:category, :category_name, :name, :description, :slug, :price, :color, :size, :brand, :material, :qty, :photo, :product_status)");
				$stmt->execute(['category'=>$category, 'category_name'=>$category_name,'name'=>$name, 'description'=>$description, 'slug'=>$slug, 'price'=>$price, 'color'=>$color, 'size'=>$size, 'brand'=>$brand, 'material'=>$material, 'qty'=>$qty, 'photo'=>$new_filename,'product_status'=>$product_status]);
				$_SESSION['success'] = 'User added successfully';

			}
			catch(PDOException $e){
				$_SESSION['error'] = $e->getMessage();
			}
		}

		$pdo->close();
	}
	else{
		$_SESSION['error'] = 'Fill up product form first';
	}

	header('location: products.php');

?>