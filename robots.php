<?php

require_once __DIR__ . '/lib/seo.php';

header('Content-Type: text/plain; charset=UTF-8');

$lines = [
    'User-agent: *',
    'Allow: /',
    'Disallow: /admin/',
    'Disallow: /action.php',
    'Disallow: /process.php',
    'Disallow: /cart',
    'Disallow: /cart.php',
    'Disallow: /cart_add.php',
    'Disallow: /cart_fetch.php',
    'Disallow: /cart_update.php',
    'Disallow: /cart_delete.php',
    'Disallow: /checkout',
    'Disallow: /checkout.php',
    'Disallow: /signin',
    'Disallow: /signin.php',
    'Disallow: /signup',
    'Disallow: /signup.php',
    'Disallow: /profile',
    'Disallow: /profile.php',
    'Disallow: /logout',
    'Disallow: /logout.php',
    'Disallow: /search',
    'Disallow: /search.php',
    'Disallow: /password_forgot',
    'Disallow: /password_forgot.php',
    'Disallow: /password_new',
    'Disallow: /password_new.php',
    'Disallow: /password_reset',
    'Disallow: /password_reset.php',
    'Disallow: /transaction',
    'Disallow: /transaction.php',
    'Disallow: /sales',
    'Disallow: /sales.php',
    'Sitemap: ' . app_absolute_url('sitemap.xml'),
];

echo implode(PHP_EOL, $lines) . PHP_EOL;
