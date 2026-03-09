<?php
include_once __DIR__ . '/storefront.php';

if (!storefront_use_v2()) {
    include __DIR__ . '/legacy/footer.legacy.php';
    return;
}
?>
<footer class="sf-footer">
  <div class="sf-foot-grid">
    <div class="row g-4">
      <div class="col-lg-4">
        <h4>Bolakaz</h4>
        <p>Curated premium fashion, elevated essentials, and modern accessories designed for everyday confidence.</p>
        <p class="mb-1"><i class="fa fa-map-marker me-2"></i> Katampe, Kubwa Village, Abuja, Nigeria</p>
        <p class="mb-1"><i class="fa fa-envelope me-2"></i> info@unibooks.com</p>
        <p class="mb-0"><i class="fa fa-phone me-2"></i> +234 8077747898</p>
      </div>
      <div class="col-lg-3 col-md-6">
        <h5>Explore</h5>
        <ul class="list-unstyled">
          <li><a href="index">Home</a></li>
          <li><a href="shop">Shop</a></li>
          <li><a href="cart">Cart</a></li>
          <li><a href="checkout">Checkout</a></li>
          <li><a href="contact">Contact</a></li>
        </ul>
      </div>
      <div class="col-lg-2 col-md-6">
        <h5>Policies</h5>
        <ul class="list-unstyled">
          <li><a href="privacy_policy">Privacy</a></li>
          <li><a href="terms_and_condition">Terms</a></li>
          <li><a href="return_and_refund_policy">Returns</a></li>
          <li><a href="cookie_policy">Cookies</a></li>
          <li><a href="disclaimer">Disclaimer</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h5>Newsletter</h5>
        <form action="newletter.php" method="POST" class="d-grid gap-2">
          <input type="text" class="form-control" name="name" placeholder="Your Name" required>
          <input type="email" class="form-control" name="email" placeholder="Your Email" required>
          <button class="btn btn-primary" type="submit">Subscribe</button>
        </form>
      </div>
    </div>
    <hr class="my-4" style="border-color: rgba(255,255,255,0.12);">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
      <small>&copy; <script>document.write(new Date().getFullYear())</script> Bolakaz. All rights reserved.</small>
      <small>Crafted for modern premium retail.</small>
    </div>
  </div>
</footer>

<div class="sf-cookie p-3" data-cookie-banner>
  <h6 class="mb-2">Cookie Notice</h6>
  <p class="mb-3 small">We use cookies to deliver faster checkout and personalized storefront experiences.</p>
  <div class="d-flex gap-2 justify-content-end">
    <a class="btn btn-sm btn-outline-primary" target="_blank" href="cookie_policy">Learn more</a>
    <button class="btn btn-sm btn-primary" type="button" data-cookie-accept>Accept</button>
  </div>
</div>

<?php include 'scripts.php'; ?>

