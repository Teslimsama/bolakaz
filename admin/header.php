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
  </style>
</head>
