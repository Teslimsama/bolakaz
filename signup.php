<?php include 'session.php'; ?>
<?php require_once __DIR__ . '/lib/hcaptcha.php'; ?>
<?php
if (isset($_SESSION['user'])) {
    header('location: cart');
    exit;
}

$captchaBypassedForLocal = app_is_local_env();
$hcaptchaSiteKey = app_hcaptcha_site_key();
$captchaEnabled = (!$captchaBypassedForLocal && $hcaptchaSiteKey !== '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php $pageTitle = "Bolakaz | Sign Up"; include "head.php"; ?>
    <link href="css/auth-modern.css" rel="stylesheet" media="all">
    <?php if ($captchaEnabled): ?>
        <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    <?php endif; ?>
</head>

<body>
    <main class="auth-shell">
        <nav class="auth-nav">
            <a class="auth-brand" href="index">BOLAKAZ.ENTERPRISE</a>
            <div class="auth-links">
                <a href="index">Home</a>
                <a href="signin">Login</a>
                <a href="contact">Contact</a>
            </div>
        </nav>

        <section class="auth-card">
            <div class="auth-grid">
                <aside class="auth-side">
                    <h1>Create Account</h1>
                    <p>Join Bolakaz to save favorites, checkout faster, and receive curated premium drops.</p>
                </aside>

                <div class="auth-form-wrap">
                    <h2>Sign Up</h2>

                    <?php
                    if (isset($_SESSION['error'])) {
                        echo "<div class='alert alert-danger' role='alert'>" . e($_SESSION['error']) . "</div>";
                        unset($_SESSION['error']);
                    }
                    if (isset($_SESSION['success'])) {
                        echo "<div class='alert alert-success' role='alert'>" . e($_SESSION['success']) . "</div>";
                        unset($_SESSION['success']);
                    }
                    if (!$captchaBypassedForLocal && !$captchaEnabled) {
                        echo "<div class='alert alert-warning' role='alert'>Captcha is unavailable in this environment. Signup remains enabled.</div>";
                    }
                    ?>

                    <form action="register.php" method="POST" class="row g-3" id="signup-form">
                        <div class="col-md-6">
                            <label class="form-label" for="firstname">First name</label>
                            <input id="firstname" class="form-control" type="text" name="firstname" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lastname">Last name</label>
                            <input id="lastname" class="form-control" type="text" name="lastname" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">Password</label>
                            <input id="password" class="form-control" type="password" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="repassword">Confirm password</label>
                            <input id="repassword" class="form-control" type="password" name="repassword" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="dob">Birthday</label>
                            <input id="dob" class="form-control" type="date" name="dob" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="gender">Gender</label>
                            <select id="gender" class="form-select" name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input id="email" class="form-control" type="email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone number</label>
                            <input id="phone" class="form-control" type="tel" name="phone" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="referral">Referral</label>
                            <select id="referral" class="form-select" name="referral" required>
                                <option disabled selected value="">Choose option</option>
                                <option value="A friend">A friend</option>
                                <option value="facebook">Facebook</option>
                                <option value="twitter">Twitter</option>
                                <option value="instagram">Instagram</option>
                                <option value="ad">From an Ad</option>
                            </select>
                        </div>

                        <?php if ($captchaEnabled): ?>
                            <div class="col-12">
                                <div class="h-captcha" data-sitekey="<?php echo e($hcaptchaSiteKey); ?>"></div>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <button class="btn btn-primary w-100" name="submit" type="submit">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <?php include 'scripts.php'; ?>
</body>

</html>
