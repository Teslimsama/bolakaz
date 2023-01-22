<?php include 'session.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Title Page-->
	<title>Bolakaz</title>

	<!-- favicon  -->
	<link rel="apple-touch-icon" sizes="180x180" href="favicomatic/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="favicomatic/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="favicomatic/favicon-16x16.png">
	<link rel="manifest" href="favicomatic/site.webmanifest">

	<!-- Google Web Fonts -->
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

	<!-- Font Awesome -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
	<script src="https://kit.fontawesome.com/e9de02addb.js" crossorigin="anonymous"></script>
	<!-- CSS only -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">

	<!-- Magnify -->
	<link rel="stylesheet" href="magnify/magnify.min.css">
	<!-- Libraries Stylesheet -->
	<link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

	<!-- Customized Bootstrap Stylesheet -->
	<link href="css/style.css" rel="stylesheet">
</head>

<body>
	<?php
	include "header.php"
	?>
	<?php include 'navbar.php'; ?>


	<input type="hidden" value="">
	<!-- Page Header Start -->
	<div class="container-fluid bg-secondary mb-5">
		<div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 300px">
			<h1 class="font-weight-semi-bold text-uppercase mb-3">Our Shop</h1>
			<div class="d-inline-flex">
				<p class="m-0"><a href="index">Home</a></p>
				<p class="m-0 px-2">-</p>
				<p class="m-0">Search</p>
			</div>
		</div>
	</div>
	<!-- Page Header End -->


	<!-- Shop Start -->
	<div class="container-fluid pt-5">
		<form method="post" id="search_form">
			<div class="row px-xl-5">
				<!-- Shop Product Start -->
				<div class="col-lg-12 col-md-12">
					<div class="row pb-3">
						<div class="col-12 pb-1">
							<div class="d-flex align-items-center justify-content-between mb-4">

								<div class="input-group">
									<input type="text" class="form-control" name="search" class="form-control" id="search" placeholder="Search by name">
									<div class="input-group-append">
										<span class="input-group-text bg-transparent text-primary">
											<i class="fa fa-search"></i>
										</span>
									</div>
								</div>

								<div class="dropdown ml-4">
									<button class="btn border dropdown-toggle" type="button" id="triggerId" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										Sort by
									</button>
									<div class="dropdown-menu dropdown-menu-right" aria-labelledby="triggerId">
										<label class="dropdown-item"><input type="radio" <?php if (isset($_POST['sorting']) && ($_POST['sorting'] == 'newest' || $_POST['sorting'] == '')) {
																								echo "checked";
																							} ?> name="sorting" class="custom-control-input common_selector sorting" value="newest">Latest</label>

										<label class="dropdown-item"><input type="radio" <?php if (isset($_POST['sorting']) && ($_POST['sorting'] == 'most_viewed' || $_POST['sorting'] == '')) {
																								echo "checked";
																							} ?> name="sorting" class="custom-control-input common_selector sorting" value="most_viewed">
											Popularity</label>

										<!-- <label class="dropdown-item"><input type="radio"<?php if (isset($_POST['sorting']) && ($_POST['sorting'] == 'best' || $_POST['sorting'] == '')) {
																									echo "checked";
																								} ?>  name="sorting" class="custom-control-input common_selector"value="high">
                                            Best Rating</label> -->
									</div>
								</div>
							</div>
						</div>
						<div class='row'>
							<?php

							$conn = $pdo->open();

							$stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM products WHERE name LIKE :keyword");
							$stmt->execute(['keyword' => '%' . $_POST['keyword'] . '%']);
							$row = $stmt->fetch();
							if ($row['numrows'] < 1) {
								echo '<h1 class="page-header">No results found for <i>' . $_POST['keyword'] . '</i></h1>';
							} else {
								echo '<h1 class="page-header">Search results for <i>' . $_POST['keyword'] . '</i></h1>';
								try {

									$stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE :keyword");
									$stmt->execute(['keyword' => '%' . $_POST['keyword'] . '%']);

									foreach ($stmt as $row) {
										$highlighted = preg_filter('/' . preg_quote($_POST['keyword'], '/') . '/i', '<b>$0</b>', $row['name']);
										$image = (!empty($row['photo'])) ? 'images/' . $row['photo'] : 'images/noimage.jpg';


										echo "
<div class='col-lg-4 col-md-6 col-sm-12 pb-1'>	       							
    <div class='card product-item border-0 mb-4'>
        <div class='card-header product-img position-relative overflow-hidden bg-transparent border p-0'>
            <img class='img-fluid w-100' src='" . $image . "' alt=''>
        </div>
        <div class='card-body border-left border-right text-center p-0 pt-4 pb-3'>
            <h6 class='text-truncate mb-3'> " . $highlighted . " </h6>
            <div class='d-flex justify-content-center'>
                <h6>$" . $row['price'] . "</h6>
                <h6 class='text-muted ml-2'><del>$ " . $row['price'] . "</del></h6>
            </div>
        </div>
        <div class='card-footer d-flex justify-content-center bg-light border'>
            <a href='detail.php?product=" . $row['slug'] . " ' class='btn btn-sm text-dark p-0'><i class='fas fa-eye text-primary mr-1'></i>View Detail</a>

        </div>
    </div>
</div>
	       						";
									}
								} catch (PDOException $e) {
									echo "There is some problem in connection: " . $e->getMessage();
								}
							}

							$pdo->close();

							?>


						</div>

		</form>
	</div>
	</div>
	<!-- Shop Product End -->
	</div>
	</div>
	<!-- Shop End -->

	<!-- Footer Start -->


	<?php
	include "footer.php"
	?>
	<!-- Footer End -->


	<!-- Back to Top -->
	<a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>


	<!-- JavaScript Libraries -->
	<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
	<script src="js/jquery-1.10.2.min.js"></script>
	<script src="js/jquery-ui.js"></script>
	<script src="js/filter.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
	<script src="lib/easing/easing.min.js"></script>
	<script src="lib/owlcarousel/owl.carousel.min.js"></script>
	<!-- JavaScript Bundle with Popper -->
	<script src="js/bootstrap.min.js"></script>

	<!-- Contact Javascript File -->
	<script src="mail/jqBootstrapValidation.min.js"></script>
	<script src="mail/contact.js"></script>

	<!-- Template Javascript -->
	<script src="js/main.js"></script>



</body>

</html>