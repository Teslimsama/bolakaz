<?php
include_once __DIR__ . '/storefront.php';

if (!storefront_use_v2()) {
    include __DIR__ . '/legacy/head.legacy.php';
    return;
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Bolakaz delivers premium fashion essentials and curated accessories across Abuja, Nigeria.">
<meta name="keywords" content="fashion store, premium clothing, accessories, Abuja, Nigeria, Bolakaz">
<meta name="author" content="Bolakaz">
<meta name="theme-color" content="#111827">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Bolakaz">
<title><?php echo storefront_title('Bolakaz | Premium Fashion Storefront'); ?></title>

<link rel="apple-touch-icon" sizes="180x180" href="favicomatic/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="favicomatic/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicomatic/favicon-16x16.png">
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

<meta property="og:title" content="Bolakaz | Premium Fashion Storefront">
<meta property="og:description" content="Shop curated premium style, modern essentials, and seasonal edits at Bolakaz.">
<meta property="og:image" content="https://bolakaz.unibooks.com.ng/images/banner1.png">
<meta property="og:url" content="https://bolakaz.unibooks.com.ng">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_NG">

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Store",
  "name": "Bolakaz",
  "description": "Premium clothing and accessories storefront in Abuja, Nigeria",
  "url": "https://bolakaz.unibooks.com.ng",
  "telephone": "+2348077747898",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Dogo Daji Street, Katampe, Kubwa Village",
    "addressLocality": "Abuja",
    "addressRegion": "FCT",
    "postalCode": "901101",
    "addressCountry": "NG"
  },
  "sameAs": [
    "https://web.facebook.com/bolakaz20",
    "https://www.instagram.com/bolakaz_enterprise/"
  ]
}
</script>
