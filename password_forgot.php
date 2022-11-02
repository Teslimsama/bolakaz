<?php include 'includes/session.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
<link href="css/navbar.css" rel="stylesheet" media="all">
<link href="css/signin.css" rel="stylesheet" media="all">

<body>
 
    <div class="container">
      <!-- code here -->

      <div class="card">
        <div class="card-image">
          <h2 class="card-heading">
            Sign In
            <small>Make life easier</small>
          </h2>

        </div>
        <div class="msg">
          <?php
          if (isset($_SESSION['error'])) {
            echo "
						<div class='alert alert-danger text-center'>
							<p>" . $_SESSION['error'] . "</p> 
						</div>
						";
            unset($_SESSION['error']);
          }
          if (isset($_SESSION['success'])) {
            echo "
						<div class='alert alert-success text-center'>
							<p>" . $_SESSION['success'] . "</p> 
						</div>
						";
            unset($_SESSION['success']);
          }
          ?>

        </div>
        <form class="card-form" action="reset.php" method="POST">
          <div class="input">
            <input type="text" class="input-field" name="email" placeholder="Email" required />
            <label class="input-label">Email</label>
          </div>
          
          <div class="action">
            <button type="submit" name="reset" class="action-button">Submit</button>
          </div>
        </form>
        <div class="hey ms-3 mt-3">
          <a href="signin">Login</a>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
  <script src="js/jquery.min.js"></script>

  <script src="js/bootstrap.min.js"></script>
  <?php include 'includes/scripts.php' ?>
</body>

</html>