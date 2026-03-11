<header class="main-header">
  <a href="home" class="logo">
    <span class="logo-lg"><span style="display:inline-block;background:#0f766e;color:#fff;border-radius:8px;padding:4px 10px;margin-right:8px;">BK</span>Bolakaz Admin</span>
  </a>

  <nav class="navbar navbar-static-top" role="navigation">
    <a href="#" class="sidebar-toggle" data-admin-sidebar-toggle aria-label="Toggle sidebar">
      <i class="fa fa-bars"></i>
    </a>

    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">
        <li class="dropdown user user-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <img src="<?php echo (!empty($admin['photo'])) ? '../images/' . $admin['photo'] : '../images/profile.jpg'; ?>" class="user-image" alt="Admin">
            <span class="hidden-xs"><?php echo e($admin['firstname'] . ' ' . $admin['lastname']); ?></span>
          </a>
          <ul class="dropdown-menu">
            <li class="user-header" style="background:#0f766e;">
              <img src="<?php echo (!empty($admin['photo'])) ? '../images/' . $admin['photo'] : '../images/profile.jpg'; ?>" class="img-circle" alt="Admin">
              <p>
                <?php echo e($admin['firstname'] . ' ' . $admin['lastname']); ?>
                <small>Member since <?php echo e(date('M Y', strtotime((string)$admin['created_on']))); ?></small>
              </p>
            </li>
            <li class="user-footer">
              <div class="pull-left">
                <a href="#profile" data-toggle="modal" class="btn btn-default btn-flat" id="admin_profile">Update</a>
              </div>
              <div class="pull-right">
                <a href="../logout.php" class="btn btn-danger btn-flat">Sign out</a>
              </div>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
</header>
<?php include 'profile_modal.php'; ?>
