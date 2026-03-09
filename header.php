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
      <div class="col-lg-3 col-md-4 col-8">
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
      <div class="col-lg-4 col-4 text-end">
        <div class="d-inline-flex align-items-center gap-2">
          <?php if (isset($_SESSION['user'])): ?>
            <a href="profile" class="btn btn-sm btn-outline-primary">Account</a>
            <a href="logout" class="btn btn-sm btn-primary">Logout</a>
          <?php else: ?>
            <a href="signin" class="btn btn-sm btn-outline-primary">Login</a>
            <a href="signup" class="btn btn-sm btn-primary">Create Account</a>
          <?php endif; ?>
          <a href="#" class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart_count badge bg-dark ms-1"></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 300px;">
            <li class="dropdown-header">You have <span class="cart_count"></span> item(s)</li>
            <li>
              <div id="cart_menu" class="px-2" style="max-height: 230px; overflow: auto;"></div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li class="text-center"><a href="cart" class="btn btn-sm btn-primary">Go to cart</a></li>
          </ul>
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
