<?php
include_once __DIR__ . '/storefront.php';

if (!storefront_use_v2()) {
    include __DIR__ . '/legacy/footer.legacy.php';
    return;
}

$newsletterNotice = $_SESSION['newsletter_notice'] ?? null;
unset($_SESSION['newsletter_notice']);
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
      <small>Powered by <a href="https://teslim.unibooks.com.ng" target="_blank" rel="noopener">TBO Digital Solutions</a></small>
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

<?php if (is_array($newsletterNotice) && !empty($newsletterNotice['message'])): ?>
<div class="modal fade" id="newsletterStatusModal" tabindex="-1" aria-labelledby="newsletterStatusTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newsletterStatusTitle"><?php echo e((string)($newsletterNotice['title'] ?? 'Newsletter Update')); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0"><?php echo e((string)$newsletterNotice['message']); ?></p>
      </div>
      <div class="modal-footer">
        <?php
          $noticeType = (string)($newsletterNotice['type'] ?? 'info');
          $btnClass = 'btn-secondary';
          if ($noticeType === 'success') {
              $btnClass = 'btn-primary';
          } elseif ($noticeType === 'danger') {
              $btnClass = 'btn-danger';
          } elseif ($noticeType === 'info') {
              $btnClass = 'btn-info text-white';
          }
        ?>
        <button type="button" class="btn <?php echo e($btnClass); ?>" data-bs-dismiss="modal">Okay</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include 'scripts.php'; ?>

<?php if (is_array($newsletterNotice) && !empty($newsletterNotice['message'])): ?>
<script>
  (function () {
    var modalEl = document.getElementById('newsletterStatusModal');
    if (!modalEl) {
      return;
    }
    if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
      var modal = new window.bootstrap.Modal(modalEl);
      modal.show();
      return;
    }
    if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
      window.jQuery(modalEl).modal('show');
      return;
    }
    alert(<?php echo json_encode((string)$newsletterNotice['message']); ?>);
  })();
</script>
<?php endif; ?>

