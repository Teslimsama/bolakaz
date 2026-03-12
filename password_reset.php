<?php include 'session.php'; ?>
<?php
$token = trim((string)($_GET['code'] ?? ''));
$userId = (int)($_GET['user'] ?? 0);

if ($token === '' || $userId <= 0) {
    $_SESSION['error'] = 'Invalid or expired reset link.';
    header('location: password_forgot.php');
    exit();
}

$conn = $pdo->open();
try {
    $stmt = $conn->prepare("SELECT reset_code FROM users WHERE id=:id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !app_validate_reset_code((string)($row['reset_code'] ?? ''), $token)) {
        $_SESSION['error'] = 'Invalid or expired reset link.';
        header('location: password_forgot.php');
        exit();
    }
} finally {
    $pdo->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $pageTitle = 'Bolakaz | Reset Password'; include 'head.php'; ?>
  <link href="css/auth-modern.css" rel="stylesheet" media="all">
</head>

<body>
  <main class="auth-shell">
    <nav class="auth-nav">
      <a class="auth-brand" href="index">BOLAKAZ.ENTERPRISE</a>
      <div class="auth-links">
        <a href="signin">Login</a>
        <a href="password_forgot">Forgot Password</a>
      </div>
    </nav>

    <section class="auth-card">
      <div class="auth-grid">
        <aside class="auth-side">
          <h1>Set New Password</h1>
          <p>Choose a strong password with at least 8 characters.</p>
        </aside>

        <div class="auth-form-wrap">
          <h2>Reset Password</h2>

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

          <form class="row g-3" action="password_new.php?code=<?php echo urlencode($token); ?>&user=<?php echo $userId; ?>" method="POST">
            <div class="col-12">
              <label class="form-label" for="new-password">New password</label>
              <input id="new-password" type="password" class="form-control" name="password" minlength="8" required>
            </div>
            <div class="col-12">
              <label class="form-label" for="confirm-password">Confirm password</label>
              <input id="confirm-password" type="password" class="form-control" name="repassword" minlength="8" required>
            </div>
            <div class="col-12">
              <button type="submit" name="reset" class="btn btn-primary w-100">Update Password</button>
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
