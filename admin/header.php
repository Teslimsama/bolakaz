<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Bolakaz Admin</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1" name="viewport">

  <link rel="apple-touch-icon" sizes="180x180" href="../favicomatic/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../favicomatic/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../favicomatic/favicon-16x16.png">
  <link rel="manifest" href="../favicomatic/site.webmanifest">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">

  <!-- Base compatibility libs retained for existing admin pages -->
  <link rel="stylesheet" href="../bower_components/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../bower_components/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="../bower_components/select2/dist/css/select2.min.css">
  <link rel="stylesheet" href="../bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="../bower_components/bootstrap-daterangepicker/daterangepicker.css">
  <link rel="stylesheet" href="../plugins/timepicker/bootstrap-timepicker.min.css">
  <link rel="stylesheet" href="../bower_components/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css">

  <?php
    $adminPage = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $needsBootstrapSelect = in_array($adminPage, ['products.php', 'test_image.php'], true);
    $adminModernCssVersion = (string) (@filemtime(__DIR__ . '/assets/admin-modern.css') ?: '1');
    if ($needsBootstrapSelect):
  ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select/dist/css/bootstrap-select.min.css">
  <?php endif; ?>

  <link rel="stylesheet" href="assets/admin-modern.css?v=<?php echo e($adminModernCssVersion); ?>">

  <style>
    .d-none { display: none !important; }
    .mt20 { margin-top: 20px; }
    .bold { font-weight: 700; }
    .admin-sync-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 999px;
      background: rgba(15, 118, 110, 0.12);
      color: #0f766e;
      font-size: 12px;
      font-weight: 700;
      line-height: 1;
    }
    .admin-sync-pill.is-offline,
    .admin-sync-pill.is-error {
      background: rgba(190, 24, 93, 0.12);
      color: #be123c;
    }
    .admin-sync-pill.is-processing {
      background: rgba(217, 119, 6, 0.14);
      color: #b45309;
    }
    .main-header .navbar-nav > .admin-sync-menu > .dropdown-menu {
      right: 0;
      left: auto;
      width: 360px;
      min-width: 360px;
      max-width: calc(100vw - 24px);
      border: 1px solid var(--admin-border, #e5e7eb);
      border-radius: 16px;
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.14);
      padding: 0;
      overflow: hidden;
      z-index: 1085;
    }
    .admin-sync-dropdown {
      min-width: 0;
      padding: 18px 18px 16px !important;
      text-align: left;
      color: #0f172a;
      background: #fff;
    }
    .admin-sync-dropdown h4 {
      margin: 0 0 6px;
      font-size: 16px;
      font-weight: 800;
    }
    .admin-sync-dropdown p {
      margin: 0 0 12px;
      color: #64748b;
      font-size: 12px;
    }
    .admin-sync-note {
      margin: 0 0 12px;
      padding: 10px 12px;
      border-radius: 12px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      color: #334155;
      font-size: 12px;
      line-height: 1.45;
    }
    .admin-sync-alert {
      margin: 0 0 12px;
      padding: 10px 12px;
      border-radius: 12px;
      background: rgba(217, 119, 6, 0.12);
      border: 1px solid rgba(217, 119, 6, 0.2);
      color: #92400e;
      font-size: 12px;
      line-height: 1.45;
      font-weight: 600;
    }
    .admin-sync-stats {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 10px;
      margin-bottom: 12px;
    }
    .admin-sync-stat {
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 10px 12px;
      background: #f8fafc;
    }
    .admin-sync-stat-label {
      display: block;
      font-size: 11px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: .04em;
      margin-bottom: 4px;
    }
    .admin-sync-stat-value {
      font-size: 18px;
      font-weight: 800;
      color: #0f172a;
    }
    .admin-sync-meta {
      font-size: 12px;
      color: #475569;
      margin-bottom: 12px;
    }
    .admin-sync-meta strong {
      color: #0f172a;
    }
    .admin-sync-actions {
      display: flex;
      gap: 8px;
    }
    .admin-sync-actions .btn {
      flex: 1;
    }
    .admin-sync-actions-disabled {
      display: block;
      padding: 10px 12px;
      border-radius: 12px;
      background: #f8fafc;
      border: 1px dashed #cbd5e1;
      color: #475569;
      font-size: 12px;
      line-height: 1.45;
    }
    @media (max-width: 767px) {
      .main-header .navbar-nav > .admin-sync-menu > .dropdown-menu {
        position: fixed;
        top: 60px;
        right: 8px;
        left: auto;
        width: min(92vw, 360px);
        min-width: 0;
        max-height: calc(100vh - 72px);
        overflow-y: auto;
      }
      .admin-sync-dropdown {
        padding: 16px 14px 14px !important;
      }
      .admin-sync-stats {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
      }
      .admin-sync-stat {
        padding: 9px 10px;
      }
      .admin-sync-actions {
        flex-direction: column;
      }
      .admin-sync-actions .btn {
        width: 100%;
      }
    }
  </style>
</head>
