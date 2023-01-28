<?php include 'session.php'; ?>
<?php
if (isset($_SESSION['user'])) {
	header('location: cart.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bolakaz</title>
	<script src="https://kit.fontawesome.com/e9de02addb.js" crossorigin="anonymous"></script>
	<!-- CSS only -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
	<link href="css/navbar.css" rel="stylesheet" media="all">
	<link href="css/signin.css" rel="stylesheet" media="all">
	<!-- favicon  -->
	<link rel="apple-touch-icon" sizes="180x180" href="favicomatic/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="favicomatic/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="favicomatic/favicon-16x16.png">
	<link rel="manifest" href="favicomatic/site.webmanifest">

</head>

<body>
	<div class="container mt-3">
		<nav class="navbar navbar-expand-lg ftco_navbar ftco-navbar-light" id="ftco-navbar">
			<div class="container">
				<a class="navbar-brand" href="index.html">Bolakaz.Enterprise</a>
				<div class="social-media order-lg-last">
					<p class="mb-0 d-flex">
						<a href="#" class="d-flex align-items-center justify-content-center"><span class="fa fa-facebook"><i class="sr-only">Facebook</i></span></a>
						<a href="#" class="d-flex align-items-center justify-content-center"><span class="fa fa-twitter"><i class="sr-only">Twitter</i></span></a>
						<a href="#" class="d-flex align-items-center justify-content-center"><span class="fa fa-instagram"><i class="sr-only">Instagram</i></span></a>
						<a href="#" class="d-flex align-items-center justify-content-center"><span class="fa fa-dribbble"><i class="sr-only">Dribbble</i></span></a>
					</p>
				</div>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
					<span class="fa fa-bars"></span>
				</button>
				<div class="collapse navbar-collapse" id="ftco-nav">
					<ul class="navbar-nav ml-auto mr-md-3">
						<li class="nav-item active"><a href="index" class="nav-link">Home</a></li>
						<li class="nav-item"><a href="#" class="nav-link">About</a></li>
						<li class="nav-item"><a href="signup" class="nav-link">Signup</a></li>

						<li class="nav-item"><a href="contact" class="nav-link">Contact</a></li>
					</ul>
				</div>
			</div>
		</nav>
		<div class="container">
			<!-- code here -->

			<div class="card">
				<div class="card-image">
					<h2 class="card-heading">
						Sign In
						<small>Make life easier</small>
					</h2>

				</div>
				<div class="msg pt-2 m-0">
					<?php
					if (isset($_SESSION['error'])) {
						echo "
						<div class='alert alert-danger alert-dismissible fade show' role='alert'>
							<p>" . $_SESSION['error'] . "</p> 
							<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
						</div>
						";
						unset($_SESSION['error']);
					}
					if (isset($_SESSION['success'])) {
						echo "
						<div class='alert alert-success alert-dismissible fade show' role='alert'>
							<p>" . $_SESSION['success'] . "</p> 
							<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
						</div>
						";
						unset($_SESSION['success']);
					}
					?>

				</div>
				<form class="card-form" action="app/signin.app.php" method="POST">
					<div class="input">
						<input type="text" class="input-field" name="email" placeholder="Email" required />
						<label class="input-label">Email</label>
					</div>
					<div class="input">
						<input type="password" class="input-field" id="myInput" name="password" placeholder="Password" required />
						
						<label class="input-label">Password</label>
					</div>



					<div class="action">
						<button type="submit" name="login" class="action-button">Submit</button>
					</div>
				</form>
				<div class="hey ms-3 mt-3">
					<a href="password_forgot">I have forgotten my password</a>
				</div>
			</div>
		</div>
		<script>
			function myFunction() {
				var x = document.getElementById("myInput");
				var y = document.getElementById("hide1");
				var z = document.getElementById("hide2");

				if (x.type === 'password') {
					x.type = "text";
					y.style.display = "block";
					z.style.display = "none";
				} else {
					x.type = "password";
					y.style.display = "none";
					z.style.display = "block";
				}
			}
		</script>
		<!-- JavaScript Bundle with Popper -->
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
		<script src="js/jquery.min.js"></script>

		<script src="js/bootstrap.min.js"></script>


</body>

</html>