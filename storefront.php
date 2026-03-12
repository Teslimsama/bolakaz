<?php

function storefront_page_key(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return strtolower(pathinfo($script, PATHINFO_FILENAME));
}

function storefront_page_group(?string $pageKey = null): string
{
    $page = $pageKey ?? storefront_page_key();

    if ($page === 'index') {
        return 'home';
    }

    if (in_array($page, ['shop', 'detail', 'search'], true)) {
        return 'shop_detail';
    }

    if (in_array($page, ['cart', 'checkout'], true)) {
        return 'cart_checkout';
    }

    return 'remaining';
}

function storefront_enabled_groups(): array
{
    $raw = $_ENV['STOREFRONT_V2_GROUPS'] ?? getenv('STOREFRONT_V2_GROUPS') ?? 'home,shop_detail,cart_checkout,remaining';
    $parts = array_map('trim', explode(',', (string)$raw));
    return array_values(array_filter($parts, static fn($item) => $item !== ''));
}

function storefront_v2_enabled(): bool
{
    $raw = $_ENV['STOREFRONT_V2_ENABLED'] ?? getenv('STOREFRONT_V2_ENABLED') ?? '0';
    return in_array(strtolower((string)$raw), ['1', 'true', 'yes', 'on'], true);
}

function storefront_use_v2(?string $pageKey = null): bool
{
    if (!storefront_v2_enabled()) {
        return false;
    }

    $group = storefront_page_group($pageKey);
    return in_array($group, storefront_enabled_groups(), true);
}

function storefront_active_nav(string $item): string
{
    $page = storefront_page_key();

    $map = [
        'home' => ['index'],
        'shop' => ['shop', 'detail', 'search'],
        'cart' => ['cart'],
        'checkout' => ['checkout'],
        'contact' => ['contact'],
        'profile' => ['profile'],
    ];

    return in_array($page, $map[$item] ?? [], true) ? 'is-active' : '';
}

function storefront_title(string $default = 'Bolakaz'): string
{
    global $pageTitle;
    $title = isset($pageTitle) && is_string($pageTitle) && trim($pageTitle) !== '' ? $pageTitle : $default;
    return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
}

