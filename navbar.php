<?php
include_once __DIR__ . '/storefront.php';

if (!storefront_use_v2()) {
    include __DIR__ . '/legacy/navbar.legacy.php';
    return;
}
?>
<div class="sf-nav-wrap">
  <div class="sf-nav-container">
    <div class="row g-0 align-items-stretch">
      <div class="col-lg-3 sf-categories">
        <button class="sf-categories-btn d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#sfCategoryPanel" aria-expanded="false" aria-controls="sfCategoryPanel">
          <span>Collections</span>
          <i class="fa fa-chevron-down"></i>
        </button>
        <div class="collapse" id="sfCategoryPanel">
          <nav class="sf-categories-panel">
            <?php
            $conn = $pdo->open();
            try {
              $stmt = $conn->prepare("SELECT name, cat_slug FROM category ORDER BY name ASC");
              $stmt->execute();
              foreach ($stmt as $row) {
                echo "<a class='nav-link' href='shop.php?category=" . urlencode((string)$row['cat_slug']) . "'>" . htmlspecialchars(ucwords((string)$row['name']), ENT_QUOTES, 'UTF-8') . "</a>";
              }
            } catch (PDOException $e) {
              echo "<span class='nav-link'>Unable to load categories</span>";
            }
            $pdo->close();
            ?>
          </nav>
        </div>
      </div>
      <div class="col-lg-9">
        <nav class="navbar navbar-expand-lg sf-nav">
          <button class="navbar-toggler ms-auto my-2" type="button" data-bs-toggle="collapse" data-bs-target="#sfMainNav" aria-controls="sfMainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="sfMainNav">
            <div class="navbar-nav me-auto">
              <a href="index" class="nav-link <?php echo storefront_active_nav('home'); ?>">Home</a>
              <a href="shop" class="nav-link <?php echo storefront_active_nav('shop'); ?>">Shop</a>
              <a href="cart" class="nav-link <?php echo storefront_active_nav('cart'); ?>">Cart</a>
              <a href="checkout" class="nav-link <?php echo storefront_active_nav('checkout'); ?>">Checkout</a>
              <a href="contact" class="nav-link <?php echo storefront_active_nav('contact'); ?>">Contact</a>
              <a href="profile" class="nav-link <?php echo storefront_active_nav('profile'); ?>">Profile</a>
            </div>
          </div>
        </nav>
      </div>
    </div>
  </div>
</div>
