<?php
require_once __DIR__ . '/../lib/sync.php';

$syncUiConfig = sync_config();
$syncRole = strtolower((string) ($syncUiConfig['role'] ?? 'client'));
$isSyncServer = $syncRole === 'server';
$syncTitle = $isSyncServer ? 'Live Sync Hub' : 'Local Sync';
$syncSummary = $isSyncServer
  ? 'This site receives local pushes and provides limited pull updates.'
  : 'Pushes local changes first, then pulls selected live updates.';
$syncNote = $isSyncServer
  ? 'Live-to-local pull in v1.5 covers customers, shipping, coupon, web details, banners, and ads. Offline sales stay local-owned on Mom PC.'
  : 'Pull scope in v1.5: customers, shipping, coupon, web details, banners, and ads. Offline sales stay local-owned on this device.';
$syncPillLabel = $isSyncServer ? 'Live hub' : 'Sync status';
?>
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
        <li class="dropdown admin-sync-menu" id="adminSyncPanel" data-sync-role="<?php echo e($syncRole); ?>">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            <span class="admin-sync-pill<?php echo $isSyncServer ? '' : ' is-processing'; ?>" id="adminSyncPill">
              <i class="fa fa-refresh"></i>
              <span id="adminSyncLabel"><?php echo e($syncPillLabel); ?></span>
            </span>
          </a>
          <ul class="dropdown-menu">
            <li class="admin-sync-dropdown">
              <h4 id="adminSyncTitle"><?php echo e($syncTitle); ?></h4>
              <p id="adminSyncSummary"><?php echo e($syncSummary); ?></p>
              <div class="admin-sync-note" id="adminSyncNote"><?php echo e($syncNote); ?></div>
              <div class="admin-sync-alert d-none" id="adminSyncAlert"></div>
              <div class="admin-sync-stats">
                <div class="admin-sync-stat">
                  <span class="admin-sync-stat-label" id="adminSyncPendingLabel"><?php echo $isSyncServer ? 'Devices' : 'Pending Push'; ?></span>
                  <span class="admin-sync-stat-value" id="adminSyncPending">0</span>
                </div>
                <div class="admin-sync-stat">
                  <span class="admin-sync-stat-label" id="adminSyncFailedLabel"><?php echo $isSyncServer ? 'Synced' : 'Pending Pull'; ?></span>
                  <span class="admin-sync-stat-value" id="adminSyncFailed">0</span>
                </div>
                <div class="admin-sync-stat">
                  <span class="admin-sync-stat-label" id="adminSyncConflictLabel"><?php echo $isSyncServer ? 'Failed' : 'Conflict'; ?></span>
                  <span class="admin-sync-stat-value" id="adminSyncConflict">0</span>
                </div>
                <div class="admin-sync-stat">
                  <span class="admin-sync-stat-label" id="adminSyncProcessingLabel"><?php echo $isSyncServer ? 'Conflict' : 'Superseded'; ?></span>
                  <span class="admin-sync-stat-value" id="adminSyncProcessing">0</span>
                </div>
              </div>
              <div class="admin-sync-meta">
                <div><strong id="adminSyncLastAttemptLabel"><?php echo $isSyncServer ? 'Last inbound attempt:' : 'Last push:'; ?></strong> <span id="adminSyncLastAttempt">Never</span></div>
                <div><strong id="adminSyncLastSuccessLabel"><?php echo $isSyncServer ? 'Last inbound success:' : 'Last pull:'; ?></strong> <span id="adminSyncLastSuccess">Never</span></div>
              </div>
              <?php if (!$isSyncServer): ?>
              <div class="admin-sync-actions">
                <button type="button" class="btn btn-primary btn-sm" id="adminSyncNow">Sync Now</button>
                <button type="button" class="btn btn-default btn-sm" id="adminSyncRetry">Retry Failed</button>
              </div>
              <?php else: ?>
              <div class="admin-sync-actions admin-sync-actions-disabled">
                <span>Push controls are available only on the local client app.</span>
              </div>
              <?php endif; ?>
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
