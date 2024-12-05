<aside class="main-sidebar">
  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">
    <!-- Sidebar user panel -->
    <div class="user-panel">
      <div class="pull-left image">
        <img src="<?php echo (!empty($admin['photo'])) ? '../images/' . $admin['photo'] : '../images/profile.jpg'; ?>" class="img-circle" alt="User Image">
      </div>
      <div class="pull-left info">
        <p><?php echo $admin['firstname'] . ' ' . $admin['lastname']; ?></p>
        <a><i class="fa fa-circle text-success"></i> Online</a>
      </div>
    </div>
    <!-- sidebar menu: : style can be found in sidebar.less -->
    <ul class="sidebar-menu" data-widget="tree">
      <li class="header">REPORTS</li>
      <li><a href="home"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a></li>
      <li><a href="sales"><i class="fa fa-money"></i> <span>Sales</span></a></li>
      <li class="header">MANAGE</li>
      <li><a href="users"><i class="fa fa-users"></i> <span>Users</span></a></li>
      <li class="treeview">
        <a href="#">
          <i class="fa fa-barcode"></i>
          <span>Products</span>
          <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
          </span>
        </a>
        <ul class="treeview-menu">
          <li><a href="products"><i class="fa fa-circle-o"></i> Product List</a></li>
          <li><a href="category"><i class="fa fa-circle-o"></i> Category</a></li>
        </ul>
      </li>
      <li class="header">SETTINGS</li>
      <li><a href="banner"><i class="fa fa-film"></i> <span>Banners</span></a></li>
      <li><a href="web_details"><i class="fa fa-sliders"></i> <span>Web Details</span></a></li>
      <li><a href="coupon"><i class="fa fa-ticket"></i> <span>Coupons</span></a></li>
      <li><a href="shipping"><i class="fa fa-truck-fast"></i> <span>Shipping</span></a></li>
      <li><a href="ads"><i class="fa fa-rectangle-ad"></i><span>Ads</span></a></li>

    </ul>
  </section>
  <!-- /.sidebar -->
</aside>