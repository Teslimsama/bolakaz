<?php
session_start();
require_once ('CreateDb.php');
include 'alert.message.php' ;

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $img = $_POST['img'];
    $price = $_POST['price'];
    $sql = "INSERT INTO producttb (product_name, product_price,product_image) VALUES(?,?,?);";
  
    $stmt = mysqli_prepare($db_connect, $sql);
      // Bind variables to the prepared statement as parameters
      mysqli_stmt_bind_param($stmt,'sss',$name, $price,$img);
      if(mysqli_stmt_execute($stmt)){
          header("location: input");
          $_SESSION['success'] = "input submitted";
    }else{
        header("location: input");
        $_SESSION['error'] = "input failed"; 
        
      }
    
  }
  else {
    echo 'didnt work asshole';
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://kit.fontawesome.com/e9de02addb.js" crossorigin="anonymous"></script>
  <!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
</head>
<body>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap');

*, *:after, *:before {
	box-sizing: border-box;
}

body {
	font-family: "DM Sans", sans-serif;
	line-height: 1.5;
	background-color: #f1f3fb;
	padding: 0 2rem;
}

img {
	max-width: 100%;
	display: block;
}


/* // iOS Reset  */
input {
	appearance: none;
	border-radius: 0;
}

.card {
	margin: 2rem auto;
	display: flex;
	flex-direction: column;
	width: 100%;
	max-width: 425px;
	background-color: #FFF;
	border-radius: 10px;
	box-shadow: 0 10px 20px 0 rgba(#999, .25);
	padding: .75rem;
}

.card-image {
	border-radius: 8px;
	overflow: hidden;
	padding-bottom: 65%;
	background-image: url('https://assets.codepen.io/285131/coffee_1.jpg');
	background-repeat: no-repeat;
	background-size: 150%;
	background-position: 0 5%;
	position: relative;
}

.card-heading {
	position: absolute;
	left: 10%;
	top: 15%;
	right: 10%;
	font-size: 1.75rem;
	font-weight: 700;
	color: #735400;
	line-height: 1.222;
	
}
.card-heading small {
		display: block;
		font-size: .75em;
		font-weight: 400;
		margin-top: .25em;
	}
.card-form {
	padding: 2rem 1rem 0;
}

.input {
	display: flex;
	flex-direction: column-reverse;
	position: relative;
	padding-top: 1.5rem;
	
}
.input {
		margin-top: 1.5rem;
	}
.input-label {
	color: #8597a3;
	position: absolute;
	top: 1.5rem;
	transition: .25s ease;
}

.input-field {
	border: 0;
	z-index: 1;
	background-color: transparent;
	border-bottom: 2px solid #eee; 
	font: inherit;
	font-size: 1.125rem;
	padding: .25rem 0;
	
}
.input-field:focus, .input-field:valid {
		outline: 0;
		border-bottom-color: #6658d3;
	}
    .input-field +.input-label {
        color: #6658d3;
        transform: translateY(-1.5rem);
    }
.action {
	margin-top: 2rem;
}

.action-button {
	font: inherit;
	font-size: 1.25rem;
	padding: 1em;
	width: 100%;
	font-weight: 500;
	background-color: #6658d3;
	border-radius: 6px;
	color: #FFF;
	border: 0;
}
.action-button:focus {
    outline: 0;
}

.card-info {
	padding: 1rem 1rem;
	text-align: center;
	font-size: .875rem;
	color: #8597a3;
	
}

.card-info a {
		display: block;
		color: #6658d3;
		text-decoration: none;
	}



    </style>
<div class="container">
	<!-- code here -->

	<div class="card">
		<div class="card-image">	
			<h2 class="card-heading">
				Input Products
				<small>Make life easier</small>
			</h2>
            <div class="msg">
         <?php echo ErrorMessage(); echo SuccessMessage();?>

            </div>
		</div>
		<form class="card-form" method="POST">
			<div class="input">
				<input type="text" class="input-field" name="name" placeholder="Productname" required/>
				<label class="input-label">Productname</label>
			</div>
			<div class="input">
				<input type="text" class="input-field" name="img" placeholder="Productimage" required/>
				<label class="input-label">Productimg</label>
			</div>
			<div class="input">
				<input type="text" class="input-field" name="price" placeholder="Productprice" required/>
				<label class="input-label">Productprice</label>
			</div>
						
						
			<div class="action">
				<button type="submit" name="submit" class="action-button">Submit</button>
			</div>
		</form>
		
	</div>
</div>
<!-- JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
</body>
</html>