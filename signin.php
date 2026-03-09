<?php include 'session.php'; ?>
<?php
if (isset($_SESSION['user'])) {
    header('location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Sign In"; include "head.php"; ?>
    <link href="css/auth-modern.css" rel="stylesheet" media="all">
</head>

<body>
    <main class="auth-shell">
        <nav class="auth-nav">
            <a class="auth-brand" href="index">BOLAKAZ.ENTERPRISE</a>
            <div class="auth-links">
                <a href="index">Home</a>
                <a href="signup">Create Account</a>
                <a href="contact">Contact</a>
            </div>
        </nav>

        <section class="auth-card">
            <div class="auth-grid">
                <aside class="auth-side">
                    <h1>Welcome Back</h1>
                    <p>Sign in to continue shopping, track orders, and manage your account details.</p>
                </aside>

                <div class="auth-form-wrap">
                    <h2>Sign In</h2>

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

                    <?php if (isset($_SESSION['pending_activation_email'])): ?>
                        <div class="alert alert-warning" role="alert">
                            Account activation is pending.
                            <form action="app/resend_activation.app.php" method="POST" class="mt-2 d-grid gap-2">
                                <input type="hidden" name="email" value="<?php echo e((string)$_SESSION['pending_activation_email']); ?>">
                                <button type="submit" name="resend_activation" class="btn btn-outline-primary btn-sm">Resend Activation Email</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <form action="app/signin.app.php" method="POST" class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="signin-email">Email</label>
                            <input id="signin-email" type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="signin-password">Password</label>
                            <input id="signin-password" type="password" class="form-control" name="password" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="login" class="btn btn-primary w-100">Submit</button>
                        </div>
                    </form>

                    <p class="auth-form-note mb-0"><a href="password_forgot">I have forgotten my password</a></p>
                </div>
            </div>
        </section>
    </main>

    <?php include 'scripts.php'; ?>
</body>

</html>
