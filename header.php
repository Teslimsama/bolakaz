<?php
include_once __DIR__ . '/storefront.php';

if (!storefront_use_v2()) {
    include __DIR__ . '/legacy/header.legacy.php';
    return;
}
?>
<div class="sf-announcement">Season Drop Live | Free Delivery in Abuja on qualifying orders</div>
<div class="sf-header-wrap">
  <div class="sf-header">
    <?php $headerSearchValue = e((string)($_GET['q'] ?? '')); ?>
    <div class="row g-3 align-items-center">
      <div class="col-lg-3 col-md-4 col-12 col-sm-6">
        <a href="index" class="sf-brand">
          <span class="sf-brand-mark">B</span>
          <span>Bolakaz</span>
        </a>
      </div>
      <div class="col-lg-5 col-md-8 d-none d-md-block">
        <form method="GET" action="search" class="sf-search">
          <input type="text" class="form-control" name="q" placeholder="Search products, styles, collections" value="<?php echo $headerSearchValue; ?>">
          <button type="submit" class="sf-search-btn" aria-label="Search">
            <i class="fa fa-search"></i>
          </button>
        </form>
      </div>
      <div class="col-lg-4 col-12 col-sm-6 text-end sf-header-actions-wrap">
        <div class="d-inline-flex align-items-center gap-2 sf-header-actions">
          <?php if (isset($_SESSION['user'])): ?>
            <a href="profile" class="btn btn-sm btn-outline-primary">Account</a>
            <a href="logout" class="btn btn-sm btn-primary">Logout</a>
          <?php else: ?>
            <a href="signin" class="btn btn-sm btn-outline-primary">Login</a>
            <a href="signup" class="btn btn-sm btn-primary">Create Account</a>
          <?php endif; ?>
          <div class="dropdown sf-cart-dropdown">
            <button type="button" class="btn btn-outline-dark sf-cart-toggle dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Open cart">
              <i class="fas fa-shopping-cart"></i>
              <span class="cart_count badge bg-dark ms-1">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end sf-cart-menu p-0">
              <div class="sf-cart-head">
                <span>Your Cart</span>
                <span class="cart_count badge bg-dark">0</span>
              </div>
              <div id="cart_menu" class="sf-cart-body"></div>
              <div class="sf-cart-footer">
                <a href="cart" class="btn btn-sm btn-outline-primary">View Cart</a>
                <a href="checkout" class="btn btn-sm btn-primary">Checkout</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 d-md-none">
        <form method="GET" action="search" class="sf-search">
          <input type="text" class="form-control" name="q" placeholder="Search products" value="<?php echo $headerSearchValue; ?>">
          <button type="submit" class="sf-search-btn" aria-label="Search">
            <i class="fa fa-search"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
