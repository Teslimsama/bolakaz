<?php include 'session.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $pageTitle = 'Bolakaz | Password Recovery'; include 'head.php'; ?>
  <link href="css/auth-modern.css" rel="stylesheet" media="all">
</head>

<body>
  <main class="auth-shell">
    <nav class="auth-nav">
      <a class="auth-brand" href="index">BOLAKAZ.ENTERPRISE</a>
      <div class="auth-links">
        <a href="signin">Login</a>
        <a href="signup">Create Account</a>
      </div>
    </nav>

    <section class="auth-card">
      <div class="auth-grid">
        <aside class="auth-side">
          <h1>Recover Access</h1>
          <p>Enter your account email and we will send a secure reset link.</p>
        </aside>

        <div class="auth-form-wrap">
          <h2>Forgot Password</h2>

          <?php
          if (isset($_SESSION['error'])) {
              echo "<div class='alert alert-danger' role='alert'>" . e($_SESSION['error']) . "</div>";
              unset($_SESSION['error']);
          }
          if (isset($_SESSION['success'])) {
              echo "<div class='alert alert-success' role='alert'>" . e($_SESSION['success']) . "</div>";
              unset($_SESSION['success']);
          }
          ?>

          <form class="row g-3" action="reset.php" method="POST">
            <div class="col-12">
              <label class="form-label" for="reset-email">Email</label>
              <input id="reset-email" type="email" class="form-control" name="email" required>
            </div>
            <div class="col-12">
              <button type="submit" name="reset" class="btn btn-primary w-100">Send Reset Link</button>
            </div>
          </form>

          <p class="auth-form-note mb-0"><a href="signin">Back to login</a></p>
        </div>
      </div>
    </section>
  </main>

  <?php include 'scripts.php'; ?>
</body>

</html>
