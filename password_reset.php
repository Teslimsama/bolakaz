<?php include 'includes/session.php'; ?>
<?php
if (!isset($_GET['code']) or !isset($_GET['user'])) {
  header('location: index.php');
  exit();
}
?>
<?php include 'includes/header.php'; ?>

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
      <form class="card-form" action="password_new.php?code=<?php echo $_GET['code']; ?>&user=<?php echo $_GET['user']; ?>" method="POST">
        <div class="input">
          <input type="text" class="input-field" name="password" placeholder="New Password" required />
          <label class="input-label">Email</label>
        </div>
        <div class="input">
          <input type="password" class="input-field" name="repassword" placeholder="Confirm Password" required />
          <label class="input-label">Password</label>
        </div>



        <div class="action">
          <button type="submit" name="reset" class="action-button">Submit</button>
        </div>
      </form>
      <div class="hey ms-3 mt-3">
        <a href="#">I have forgotten my password</a>
      </div>
    </div>
  </div>

  <?php include 'includes/scripts.php' ?>
</body>

</html>