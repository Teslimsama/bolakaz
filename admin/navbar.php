<header class="main-header">
  <a href="home" class="logo">
    <span class="logo-mark">BK</span>
    <span class="logo-text">Bolakaz <small>Admin</small></span>
  </a>

  <nav class="navbar navbar-static-top" role="navigation">
    <a href="#" class="sidebar-toggle" data-admin-sidebar-toggle aria-label="Toggle sidebar">
      <i class="fa fa-bars"></i>
    </a>

    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">
        <li class="dropdown admin-sync-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            <span class="admin-sync-pill is-processing" id="adminSyncPill">
              <i class="fa fa-refresh"></i>
              <span id="adminSyncLabel">Sync status</span>
            </span>
          </a>
          <ul class="dropdown-menu">
            <li class="admin-sync-dropdown">
              <h4>Local Sync</h4>
              <p id="adminSyncSummary">Checking local queue and server reachability.</p>
              <div class="admin-sync-stats">
                <div class="admin-sync-stat">
                  <span class="admin-sync-stat-label">Pending</span>
                  <span class="admin-sync-stat-value" id="adminSyncPending">0</span>
                </div>
                <div class="admin-sync-stat">
                  <span class="admin-sync-stat-label">Failed</span>
                  <span class="admin-sync-stat-value" id="adminSyncFailed">0</span>
                </div>
                <div class="admin-sync-stat">
                  <span class="admin-sync-stat-label">Conflict</span>
                  <span class="admin-sync-stat-value" id="adminSyncConflict">0</span>
                </div>
                <div class="admin-sync-stat">
                  <span class="admin-sync-stat-label">Processing</span>
                  <span class="admin-sync-stat-value" id="adminSyncProcessing">0</span>
                </div>
              </div>
              <div class="admin-sync-meta">
                <div><strong>Last attempt:</strong> <span id="adminSyncLastAttempt">Never</span></div>
                <div><strong>Last success:</strong> <span id="adminSyncLastSuccess">Never</span></div>
              </div>
              <div class="admin-sync-actions">
                <button type="button" class="btn btn-primary btn-sm" id="adminSyncNow">Sync Now</button>
                <button type="button" class="btn btn-default btn-sm" id="adminSyncRetry">Retry Failed</button>
              </div>
            </li>
          </ul>
        </li>
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
                <a href="../logout" class="btn btn-danger btn-flat">Sign out</a>
              </div>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
</header>
<?php include 'profile_modal.php'; ?>
