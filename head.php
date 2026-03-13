<?php
include_once __DIR__ . '/storefront.php';
require_once __DIR__ . '/lib/seo.php';

if (!storefront_use_v2()) {
    include __DIR__ . '/legacy/head.legacy.php';
    return;
}

$seoOverrides = (isset($seoMeta) && is_array($seoMeta)) ? $seoMeta : [];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#111827">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Bolakaz">
<meta name="author" content="Bolakaz">
<?php echo seo_render_meta_tags($seoOverrides); ?>

<link rel="apple-touch-icon" sizes="180x180" href="favicomatic/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="favicomatic/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicomatic/favicon-16x16.png">
<link rel="icon" href="favicomatic/favicon.ico">
<link rel="manifest" href="favicomatic/site.webmanifest">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="css/vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
<link href="magnify/magnify.min.css" rel="stylesheet">
<link href="css/style.css?v=<?php echo file_exists(__DIR__ . '/css/style.css') ? filemtime(__DIR__ . '/css/style.css') : time(); ?>" rel="stylesheet">
<link href="css/storefront-v2.css?v=<?php echo file_exists(__DIR__ . '/css/storefront-v2.css') ? filemtime(__DIR__ . '/css/storefront-v2.css') : time(); ?>" rel="stylesheet">

<meta name="csrf-token" content="<?php echo function_exists('app_get_csrf_token') ? htmlspecialchars(app_get_csrf_token(), ENT_QUOTES, 'UTF-8') : ''; ?>">
