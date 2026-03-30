<aside class="main-sidebar" aria-label="Sidebar navigation">
  <section class="sidebar">
    <div class="user-panel">
      <div class="image">
        <img src="<?php echo (!empty($admin['photo'])) ? '../images/' . $admin['photo'] : '../images/profile.jpg'; ?>" class="img-circle" alt="User Image">
      </div>
      <div class="info">
        <p><?php echo e($admin['firstname'] . ' ' . $admin['lastname']); ?></p>
        <a><i class="fa fa-circle text-success"></i> Online</a>
      </div>
    </div>

    <ul class="sidebar-menu" data-widget="tree">
      <li class="header">Analytics</li>
      <li><a href="home"><i class="fa fa-dashboard"></i><span>Dashboard</span></a></li>
      <li><a href="sales"><i class="fa fa-money"></i><span>Sales</span></a></li>
      <li><a href="offline_sales"><i class="fa fa-handshake-o"></i><span>Offline Sales</span></a></li>

      <li class="header">Commerce</li>
      <li><a href="users"><i class="fa fa-users"></i><span>Customers</span></a></li>
      <li class="treeview">
        <a href="#">
          <i class="fa fa-barcode"></i>
          <span>Catalog</span>
          <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
        </a>
        <ul class="treeview-menu">
          <li><a href="products"><i class="fa fa-circle-o"></i> Product List</a></li>
          <li><a href="category"><i class="fa fa-circle-o"></i> Categories</a></li>
        </ul>
      </li>
      <li><a href="shipping"><i class="fa fa-truck"></i><span>Shipping</span></a></li>
      <li><a href="coupon"><i class="fa fa-ticket"></i><span>Coupons</span></a></li>

      <li class="header">Marketing</li>
      <li><a href="banner"><i class="fa fa-film"></i><span>Banners</span></a></li>
      <li><a href="ads"><i class="fa fa-bullhorn"></i><span>Ads</span></a></li>

      <li class="header">Settings</li>
      <li><a href="web_details"><i class="fa fa-sliders"></i><span>Web Details</span></a></li>
    </ul>
  </section>
</aside>
<div class="admin-sidebar-backdrop" aria-hidden="true"></div>
