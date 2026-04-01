<?php include 'session.php'; ?>
<?php require_once __DIR__ . '/lib/recaptcha_enterprise.php'; ?>
<?php
if (isset($_SESSION['user'])) {
    header('location: cart');
    exit;
}

$captchaBypassedForLocal = app_is_local_env();
$recaptchaEnterpriseSiteKey = app_recaptcha_enterprise_site_key();
$enterpriseCaptchaEnabled = app_recaptcha_enterprise_enabled();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Sign In"; include "head.php"; ?>
    <link href="css/auth-modern.css" rel="stylesheet" media="all">
    <?php if ($enterpriseCaptchaEnabled): ?>
        <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo e($recaptchaEnterpriseSiteKey); ?>"></script>
    <?php endif; ?>
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

                    <form action="app/signin.app.php" method="POST" class="row g-3" id="signin-form" <?php if ($enterpriseCaptchaEnabled): ?>data-recaptcha-enterprise="1"<?php endif; ?>>
                        <div class="col-12">
                            <label class="form-label" for="signin-email">Email</label>
                            <input id="signin-email" type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="signin-password">Password</label>
                            <input id="signin-password" type="password" class="form-control" name="password" required>
                        </div>
                        <div class="col-12">
                            <?php if ($enterpriseCaptchaEnabled): ?>
                                <input type="hidden" name="recaptcha_token" id="signin-recaptcha-token" value="">
                                <input type="hidden" name="recaptcha_action" value="LOGIN">
                            <?php endif; ?>
                            <button type="submit" name="login" class="btn btn-primary w-100">Submit</button>
                        </div>
                    </form>

                    <p class="auth-form-note mb-0"><a href="password_forgot">I have forgotten my password</a></p>
                </div>
            </div>
        </section>
    </main>

    <?php include 'scripts.php'; ?>
    <?php if ($enterpriseCaptchaEnabled): ?>
    <script>
    (function() {
        var siteKey = <?php echo json_encode($recaptchaEnterpriseSiteKey); ?>;
        var form = document.getElementById('signin-form');
        var tokenInput = document.getElementById('signin-recaptcha-token');
        if (!form || !tokenInput || !siteKey) {
            return;
        }

        var submittingWithToken = false;
        var submitButton = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', function(e) {
            if (submittingWithToken) {
                return;
            }

            e.preventDefault();

            if (!window.grecaptcha || !grecaptcha.enterprise || typeof grecaptcha.enterprise.execute !== 'function') {
                if (submitButton) {
                    submitButton.disabled = false;
                }
                return;
            }

            grecaptcha.enterprise.ready(async function() {
                try {
                    if (submitButton) {
                        submitButton.disabled = true;
                    }
                    tokenInput.value = '';
                    var token = await grecaptcha.enterprise.execute(siteKey, { action: 'LOGIN' });
                    tokenInput.value = token || '';
                    submittingWithToken = true;
                    form.submit();
                } catch (error) {
                    submittingWithToken = false;
                    tokenInput.value = '';
                    if (submitButton) {
                        submitButton.disabled = false;
                    }
                    window.alert('Security check failed. Please try again.');
                }
            });
        });
    })();
    </script>
    <?php endif; ?>
</body>

</html>
