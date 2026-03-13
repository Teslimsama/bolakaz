<?php

if (!function_exists('app_env')) {
    function app_env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        $text = trim((string)$value);
        return $text === '' ? $default : $text;
    }
}

if (!function_exists('app_load_dotenv')) {
    function app_load_dotenv(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $loaded = true;
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            return;
        }

        require_once $autoload;
        if (!class_exists('Dotenv\\Dotenv')) {
            return;
        }

        Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
    }
}

if (!function_exists('app_is_https_request')) {
    function app_is_https_request(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
        }

        if (!empty($_SERVER['SERVER_PORT'])) {
            return (int)$_SERVER['SERVER_PORT'] === 443;
        }

        return false;
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        static $cached = null;
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        app_load_dotenv();

        $configured = rtrim(app_env('APP_URL'), '/');
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL)) {
            $cached = $configured;
            return $cached;
        }

        $scheme = app_is_https_request() ? 'https' : 'http';
        $host = trim((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        if ($host === '') {
            $host = 'localhost';
        }

        $basePath = trim((string)dirname((string)($_SERVER['SCRIPT_NAME'] ?? '')), '/\\.');
        $cached = $scheme . '://' . $host;
        if ($basePath !== '') {
            $cached .= '/' . $basePath;
        }

        return $cached;
    }
}

if (!function_exists('app_home_url')) {
    function app_home_url(): string
    {
        return rtrim(app_base_url(), '/') . '/';
    }
}

if (!function_exists('app_absolute_url')) {
    function app_absolute_url(string $path = '', array $query = []): string
    {
        $target = trim($path);
        if ($target !== '' && preg_match('#^https?://#i', $target)) {
            $url = $target;
        } elseif ($target === '') {
            $url = app_home_url();
        } else {
            $url = app_home_url() . ltrim($target, '/');
        }

        if (!empty($query)) {
            $filtered = [];
            foreach ($query as $key => $value) {
                if ($value === null) {
                    continue;
                }

                $text = trim((string)$value);
                if ($text === '') {
                    continue;
                }
                $filtered[$key] = $text;
            }

            if (!empty($filtered)) {
                $separator = (strpos($url, '?') === false) ? '?' : '&';
                $url .= $separator . http_build_query($filtered);
            }
        }

        return $url;
    }
}

if (!function_exists('seo_page_key')) {
    function seo_page_key(): string
    {
        if (function_exists('storefront_page_key')) {
            return storefront_page_key();
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        return strtolower((string)pathinfo($script, PATHINFO_FILENAME));
    }
}

if (!function_exists('seo_site_name')) {
    function seo_site_name(): string
    {
        return 'Bolakaz';
    }
}

if (!function_exists('seo_default_description')) {
    function seo_default_description(): string
    {
        return 'Bolakaz delivers premium fashion essentials and curated accessories across Abuja, Nigeria.';
    }
}

if (!function_exists('seo_default_keywords')) {
    function seo_default_keywords(): string
    {
        return 'fashion store, premium clothing, accessories, Abuja, Nigeria, Bolakaz';
    }
}

if (!function_exists('seo_default_image')) {
    function seo_default_image(): string
    {
        $hero = app_image_url('banner1.png');
        return app_absolute_url($hero);
    }
}

if (!function_exists('seo_limit_text')) {
    function seo_limit_text(string $value, int $limit = 160): string
    {
        $text = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text ?? '');
        $text = trim((string)$text);
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $limit) {
                return $text;
            }

            return rtrim(mb_substr($text, 0, max(0, $limit - 1), 'UTF-8')) . '...';
        }

        if (strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(substr($text, 0, max(0, $limit - 1))) . '...';
    }
}

if (!function_exists('seo_default_robots')) {
    function seo_default_robots(?string $pageKey = null): string
    {
        $page = $pageKey ?? seo_page_key();
        $noindexPages = [
            'search',
            'cart',
            'checkout',
            'signin',
            'signup',
            'profile',
            'logout',
            'sales',
            'transaction',
            'offline_statement',
            'password_forgot',
            'password_new',
            'password_reset',
        ];

        if (in_array($page, $noindexPages, true)) {
            return 'noindex,follow';
        }

        return 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
    }
}

if (!function_exists('seo_default_title')) {
    function seo_default_title(): string
    {
        $title = '';
        if (isset($GLOBALS['pageTitle']) && is_string($GLOBALS['pageTitle'])) {
            $title = trim($GLOBALS['pageTitle']);
        }

        if ($title !== '') {
            return $title;
        }

        return seo_site_name() . ' | Premium Fashion Storefront';
    }
}

if (!function_exists('seo_default_canonical')) {
    function seo_default_canonical(): string
    {
        $page = seo_page_key();
        if ($page === 'index') {
            return app_home_url();
        }

        return app_absolute_url($page);
    }
}

if (!function_exists('seo_store_schema')) {
    function seo_store_schema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => seo_site_name(),
            'description' => seo_default_description(),
            'url' => app_home_url(),
            'image' => seo_default_image(),
            'telephone' => '+2348077747898',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Dogo Daji Street, Katampe, Kubwa Village',
                'addressLocality' => 'Abuja',
                'addressRegion' => 'FCT',
                'postalCode' => '901101',
                'addressCountry' => 'NG',
            ],
            'sameAs' => [
                'https://web.facebook.com/bolakaz20',
                'https://www.instagram.com/bolakaz_enterprise/',
            ],
        ];
    }
}

if (!function_exists('seo_website_schema')) {
    function seo_website_schema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => seo_site_name(),
            'url' => app_home_url(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => app_absolute_url('search', ['q' => '{search_term_string}']),
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}

if (!function_exists('seo_breadcrumb_schema')) {
    function seo_breadcrumb_schema(array $items): array
    {
        $list = [];
        $position = 1;
        foreach ($items as $item) {
            $name = trim((string)($item['name'] ?? ''));
            $url = trim((string)($item['url'] ?? ''));
            if ($name === '' || $url === '') {
                continue;
            }

            $list[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $name,
                'item' => $url,
            ];
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }
}

if (!function_exists('seo_resolve_meta')) {
    function seo_resolve_meta(array $overrides = []): array
    {
        $page = seo_page_key();
        $defaults = [
            'title' => seo_default_title(),
            'description' => seo_default_description(),
            'keywords' => seo_default_keywords(),
            'canonical' => seo_default_canonical(),
            'url' => seo_default_canonical(),
            'robots' => seo_default_robots($page),
            'image' => seo_default_image(),
            'image_alt' => seo_site_name() . ' storefront',
            'type' => 'website',
            'site_name' => seo_site_name(),
            'locale' => 'en_NG',
            'twitter_card' => 'summary_large_image',
            'jsonLd' => [seo_store_schema()],
        ];

        if ($page === 'index') {
            $defaults['jsonLd'][] = seo_website_schema();
        }

        $meta = array_merge($defaults, $overrides);

        $meta['title'] = trim((string)($meta['title'] ?? $defaults['title']));
        $meta['description'] = seo_limit_text((string)($meta['description'] ?? $defaults['description']), 170);
        $meta['keywords'] = trim((string)($meta['keywords'] ?? ''));
        $meta['canonical'] = trim((string)($meta['canonical'] ?? $defaults['canonical']));
        $meta['url'] = trim((string)($meta['url'] ?? $meta['canonical']));
        $meta['robots'] = trim((string)($meta['robots'] ?? $defaults['robots']));
        $meta['image'] = trim((string)($meta['image'] ?? $defaults['image']));
        $meta['image_alt'] = trim((string)($meta['image_alt'] ?? $defaults['image_alt']));
        $meta['type'] = trim((string)($meta['type'] ?? $defaults['type']));
        $meta['site_name'] = trim((string)($meta['site_name'] ?? $defaults['site_name']));
        $meta['locale'] = trim((string)($meta['locale'] ?? $defaults['locale']));
        $meta['twitter_card'] = trim((string)($meta['twitter_card'] ?? $defaults['twitter_card']));
        $meta['price'] = isset($meta['price']) ? (float)$meta['price'] : null;
        $meta['currency'] = trim((string)($meta['currency'] ?? 'NGN'));

        if (!isset($meta['jsonLd']) || !is_array($meta['jsonLd'])) {
            $meta['jsonLd'] = $defaults['jsonLd'];
        }

        return $meta;
    }
}

if (!function_exists('seo_render_meta_tags')) {
    function seo_render_meta_tags(array $overrides = []): string
    {
        $meta = seo_resolve_meta($overrides);
        $lines = [];

        $lines[] = '<title>' . e($meta['title']) . '</title>';
        $lines[] = '<meta name="description" content="' . e($meta['description']) . '">';
        if ($meta['keywords'] !== '') {
            $lines[] = '<meta name="keywords" content="' . e($meta['keywords']) . '">';
        }
        $lines[] = '<meta name="robots" content="' . e($meta['robots']) . '">';
        $lines[] = '<link rel="canonical" href="' . e($meta['canonical']) . '">';
        $lines[] = '<meta property="og:site_name" content="' . e($meta['site_name']) . '">';
        $lines[] = '<meta property="og:title" content="' . e($meta['title']) . '">';
        $lines[] = '<meta property="og:description" content="' . e($meta['description']) . '">';
        $lines[] = '<meta property="og:image" content="' . e($meta['image']) . '">';
        $lines[] = '<meta property="og:image:alt" content="' . e($meta['image_alt']) . '">';
        $lines[] = '<meta property="og:url" content="' . e($meta['url']) . '">';
        $lines[] = '<meta property="og:type" content="' . e($meta['type']) . '">';
        $lines[] = '<meta property="og:locale" content="' . e($meta['locale']) . '">';
        $lines[] = '<meta name="twitter:card" content="' . e($meta['twitter_card']) . '">';
        $lines[] = '<meta name="twitter:title" content="' . e($meta['title']) . '">';
        $lines[] = '<meta name="twitter:description" content="' . e($meta['description']) . '">';
        $lines[] = '<meta name="twitter:image" content="' . e($meta['image']) . '">';
        $lines[] = '<meta name="twitter:image:alt" content="' . e($meta['image_alt']) . '">';

        if ($meta['type'] === 'product' && $meta['price'] !== null) {
            $lines[] = '<meta property="product:price:amount" content="' . e(number_format((float)$meta['price'], 2, '.', '')) . '">';
            $lines[] = '<meta property="product:price:currency" content="' . e($meta['currency']) . '">';
        }

        foreach ($meta['jsonLd'] as $schema) {
            if (!is_array($schema) || empty($schema)) {
                continue;
            }

            $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (!is_string($json) || $json === '') {
                continue;
            }

            $lines[] = '<script type="application/ld+json">' . $json . '</script>';
        }

        return implode(PHP_EOL, $lines);
    }
}
