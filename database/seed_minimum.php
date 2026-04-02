<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/CreateDb.php';

$defaults = [
    'admin-email' => trim((string)($_ENV['SEED_ADMIN_EMAIL'] ?? $_SERVER['SEED_ADMIN_EMAIL'] ?? getenv('SEED_ADMIN_EMAIL') ?: 'seed-admin@bolakaz.local')),
    'admin-password' => trim((string)($_ENV['SEED_ADMIN_PASSWORD'] ?? $_SERVER['SEED_ADMIN_PASSWORD'] ?? getenv('SEED_ADMIN_PASSWORD') ?: 'SeedAdmin123!')),
    'staff-email' => trim((string)($_ENV['SEED_STAFF_EMAIL'] ?? $_SERVER['SEED_STAFF_EMAIL'] ?? getenv('SEED_STAFF_EMAIL') ?: 'seed-staff@bolakaz.local')),
    'staff-password' => trim((string)($_ENV['SEED_STAFF_PASSWORD'] ?? $_SERVER['SEED_STAFF_PASSWORD'] ?? getenv('SEED_STAFF_PASSWORD') ?: 'SeedStaff123!')),
    'customer-email' => trim((string)($_ENV['SEED_CUSTOMER_EMAIL'] ?? $_SERVER['SEED_CUSTOMER_EMAIL'] ?? getenv('SEED_CUSTOMER_EMAIL') ?: 'seed-customer@bolakaz.local')),
    'customer-password' => trim((string)($_ENV['SEED_CUSTOMER_PASSWORD'] ?? $_SERVER['SEED_CUSTOMER_PASSWORD'] ?? getenv('SEED_CUSTOMER_PASSWORD') ?: 'SeedUser123!')),
    'site-name' => trim((string)($_ENV['SEED_SITE_NAME'] ?? $_SERVER['SEED_SITE_NAME'] ?? getenv('SEED_SITE_NAME') ?: 'Bolakaz Starter Store')),
    'site-email' => trim((string)($_ENV['SEED_SITE_EMAIL'] ?? $_SERVER['SEED_SITE_EMAIL'] ?? getenv('SEED_SITE_EMAIL') ?: 'hello@bolakaz.local')),
];

$options = $defaults;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        echo "Usage: php database/seed_minimum.php [--admin-email=...] [--admin-password=...] [--staff-email=...] [--staff-password=...] [--customer-email=...] [--customer-password=...] [--site-name=...] [--site-email=...]" . PHP_EOL;
        exit(0);
    }

    foreach (array_keys($defaults) as $name) {
        $prefix = '--' . $name . '=';
        if (strpos($arg, $prefix) === 0) {
            $options[$name] = trim((string)substr($arg, strlen($prefix)));
            break;
        }
    }
}

function seed_output(string $status, string $message): void
{
    echo '[' . $status . '] ' . $message . PHP_EOL;
}

function fetch_one(PDO $conn, string $sql, array $params = []): ?array
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function fetch_all(PDO $conn, string $sql, array $params = []): array
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return is_array($rows) ? $rows : [];
}

function fetch_value(PDO $conn, string $sql, array $params = []): mixed
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function column_allows_null(PDO $conn, string $table, string $column): bool
{
    $value = fetch_value(
        $conn,
        "SELECT is_nullable
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = :table_name
           AND column_name = :column_name
         LIMIT 1",
        [
            'table_name' => $table,
            'column_name' => $column,
        ]
    );

    return strtoupper((string)$value) === 'YES';
}

function sales_seed_txid_value(PDO $conn, string $txRef): ?int
{
    if (column_allows_null($conn, 'sales', 'txid')) {
        return null;
    }

    $hash = abs(crc32($txRef));
    return ($hash > 0) ? $hash : random_int(100000, 999999999);
}

function required_tables_exist(PDO $conn, array $tables): bool
{
    foreach ($tables as $table) {
        $exists = fetch_value(
            $conn,
            "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name LIMIT 1",
            ['table_name' => $table]
        );
        if (!$exists) {
            return false;
        }
    }

    return true;
}

function bcrypt_hash(string $password): string
{
    $hash = password_hash($password, PASSWORD_BCRYPT);
    if (!is_string($hash) || $hash === '') {
        throw new RuntimeException('Unable to hash password for seed user.');
    }

    return $hash;
}

function ensure_seed_user(PDO $conn, array $data): array
{
    $matchingRows = fetch_all(
        $conn,
        "SELECT id FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(:email)) ORDER BY id ASC",
        ['email' => $data['email']]
    );
    $existing = $matchingRows[0] ?? null;
    $params = [
        'email' => $data['email'],
        'password' => bcrypt_hash($data['password']),
        'type' => $data['type'],
        'firstname' => $data['firstname'],
        'lastname' => $data['lastname'],
        'address' => $data['address'],
        'phone' => $data['phone'],
        'gender' => $data['gender'],
        'dob' => '',
        'photo' => '',
        'status' => 1,
        'activate_code' => null,
        'created_on' => date('Y-m-d'),
        'referral' => 'seed-minimum',
    ];

    if ($existing) {
        $params['id'] = (int)$existing['id'];
        $stmt = $conn->prepare("UPDATE users
            SET email = :email, password = :password, type = :type, firstname = :firstname, lastname = :lastname,
                address = :address, phone = :phone, gender = :gender, dob = :dob, photo = :photo, status = :status,
                activate_code = :activate_code, created_on = :created_on, referral = :referral
            WHERE id = :id");
        $stmt->execute($params);

        if (count($matchingRows) > 1) {
            $duplicateIds = array_map(static function (array $row): int {
                return (int)($row['id'] ?? 0);
            }, array_slice($matchingRows, 1));
            $duplicateIds = array_values(array_filter($duplicateIds, static function (int $value): bool {
                return $value > 0;
            }));

            if (!empty($duplicateIds)) {
                $placeholders = implode(',', array_fill(0, count($duplicateIds), '?'));
                $delete = $conn->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                $delete->execute($duplicateIds);
            }
        }

        return [
            'id' => (int)$existing['id'],
            'action' => 'updated',
            'email' => $data['email'],
        ];
    }

    $stmt = $conn->prepare("INSERT INTO users (email, password, type, firstname, lastname, address, phone, gender, dob, photo, status, activate_code, created_on, referral)
        VALUES (:email, :password, :type, :firstname, :lastname, :address, :phone, :gender, :dob, :photo, :status, :activate_code, :created_on, :referral)");
    $stmt->execute($params);

    return [
        'id' => (int)$conn->lastInsertId(),
        'action' => 'created',
        'email' => $data['email'],
    ];
}

function ensure_category(PDO $conn): array
{
    $seedSlug = 'starter-category';
    $seedName = 'Starter Category';
    $placeholder = 'storefront-placeholder.svg';

    $existingSeed = fetch_one($conn, "SELECT id, name, cat_slug FROM category WHERE cat_slug = :slug LIMIT 1", ['slug' => $seedSlug]);
    if ($existingSeed) {
        $stmt = $conn->prepare("UPDATE category
            SET name = :name, cat_image = :cat_image, is_parent = 1, parent_id = NULL, status = 'active'
            WHERE id = :id");
        $stmt->execute([
            'name' => $seedName,
            'cat_image' => $placeholder,
            'id' => (int)$existingSeed['id'],
        ]);

        return [
            'id' => (int)$existingSeed['id'],
            'name' => $seedName,
            'slug' => $seedSlug,
            'action' => 'updated',
        ];
    }

    $existing = fetch_one($conn, "SELECT id, name, cat_slug FROM category WHERE status = 'active' AND cat_slug <> '' ORDER BY id ASC LIMIT 1");
    if ($existing) {
        return [
            'id' => (int)$existing['id'],
            'name' => (string)$existing['name'],
            'slug' => (string)$existing['cat_slug'],
            'action' => 'reused',
        ];
    }

    $stmt = $conn->prepare("INSERT INTO category (name, cat_image, cat_slug, is_parent, parent_id, status)
        VALUES (:name, :cat_image, :cat_slug, 1, NULL, 'active')");
    $stmt->execute([
        'name' => $seedName,
        'cat_image' => $placeholder,
        'cat_slug' => $seedSlug,
    ]);

    return [
        'id' => (int)$conn->lastInsertId(),
        'name' => $seedName,
        'slug' => $seedSlug,
        'action' => 'created',
    ];
}

function ensure_shipping(PDO $conn): array
{
    $seedType = 'Starter Shipping';
    $existingSeed = fetch_one($conn, "SELECT id, type FROM shippings WHERE type = :type LIMIT 1", ['type' => $seedType]);
    if ($existingSeed) {
        $stmt = $conn->prepare("UPDATE shippings
            SET price = :price, status = 'active', updated_at = NOW()
            WHERE id = :id");
        $stmt->execute([
            'price' => '2500.00',
            'id' => (int)$existingSeed['id'],
        ]);

        return [
            'id' => (int)$existingSeed['id'],
            'type' => $seedType,
            'action' => 'updated',
        ];
    }

    $existing = fetch_one($conn, "SELECT id, type FROM shippings WHERE status = 'active' ORDER BY id ASC LIMIT 1");
    if ($existing) {
        return [
            'id' => (int)$existing['id'],
            'type' => (string)$existing['type'],
            'action' => 'reused',
        ];
    }

    $stmt = $conn->prepare("INSERT INTO shippings (type, price, status, created_at, updated_at)
        VALUES (:type, :price, 'active', NOW(), NOW())");
    $stmt->execute([
        'type' => $seedType,
        'price' => '2500.00',
    ]);

    return [
        'id' => (int)$conn->lastInsertId(),
        'type' => $seedType,
        'action' => 'created',
    ];
}

function ensure_coupon(PDO $conn): array
{
    $seedCode = 'STARTER10';
    $expiresAt = date('Y-m-d 23:59:59', strtotime('+30 days'));

    $existingSeed = fetch_one($conn, "SELECT id, code FROM coupons WHERE code = :code LIMIT 1", ['code' => $seedCode]);
    if ($existingSeed) {
        $stmt = $conn->prepare("UPDATE coupons
            SET type = 'percent', value = :value, status = 'active', expire_date = :expire_date, updated_at = NOW()
            WHERE id = :id");
        $stmt->execute([
            'value' => '10.00',
            'expire_date' => $expiresAt,
            'id' => (int)$existingSeed['id'],
        ]);

        return [
            'id' => (int)$existingSeed['id'],
            'code' => $seedCode,
            'action' => 'updated',
        ];
    }

    $existing = fetch_one($conn, "SELECT id, code FROM coupons ORDER BY id ASC LIMIT 1");
    if ($existing) {
        return [
            'id' => (int)$existing['id'],
            'code' => (string)$existing['code'],
            'action' => 'reused',
        ];
    }

    $stmt = $conn->prepare("INSERT INTO coupons (code, type, value, status, expire_date, created_at, updated_at, influencer_id)
        VALUES (:code, 'percent', :value, 'active', :expire_date, NOW(), NOW(), NULL)");
    $stmt->execute([
        'code' => $seedCode,
        'value' => '10.00',
        'expire_date' => $expiresAt,
    ]);

    return [
        'id' => (int)$conn->lastInsertId(),
        'code' => $seedCode,
        'action' => 'created',
    ];
}

function ensure_web_details(PDO $conn, string $siteName, string $siteEmail): array
{
    $seedRow = fetch_one($conn, "SELECT id FROM web_details WHERE site_email = :site_email LIMIT 1", ['site_email' => $siteEmail]);
    $params = [
        'site_name' => $siteName,
        'site_address' => 'Abuja, Nigeria',
        'site_email' => $siteEmail,
        'site_number' => '+2348000000000',
        'short_description' => 'Starter web details for a fresh Bolakaz installation.',
        'description' => 'Starter web details seeded automatically so the admin and storefront have the minimum site profile data they expect.',
    ];

    if ($seedRow) {
        $params['id'] = (int)$seedRow['id'];
        $stmt = $conn->prepare("UPDATE web_details
            SET site_name = :site_name, site_address = :site_address, site_email = :site_email, site_number = :site_number,
                short_description = :short_description, description = :description
            WHERE id = :id");
        $stmt->execute($params);

        return [
            'id' => (int)$seedRow['id'],
            'action' => 'updated',
        ];
    }

    $existing = fetch_one($conn, "SELECT id FROM web_details ORDER BY id ASC LIMIT 1");
    if ($existing) {
        return [
            'id' => (int)$existing['id'],
            'action' => 'reused',
        ];
    }

    $stmt = $conn->prepare("INSERT INTO web_details (site_name, site_address, site_email, site_number, short_description, description)
        VALUES (:site_name, :site_address, :site_email, :site_number, :short_description, :description)");
    $stmt->execute($params);

    return [
        'id' => (int)$conn->lastInsertId(),
        'action' => 'created',
    ];
}

function ensure_product(PDO $conn, array $category): array
{
    $seedSlug = 'starter-product';
    $seedName = 'Starter Product';
    $placeholder = 'storefront-placeholder.svg';
    $now = date('Y-m-d H:i:s');
    $additionalInfo = json_encode([
        'fit' => 'Regular',
        'care_instructions' => 'Machine wash cold',
        'composition' => 'Cotton blend',
        'shipping_class' => 'Standard',
    ], JSON_UNESCAPED_SLASHES);
    if (!is_string($additionalInfo) || $additionalInfo === '') {
        $additionalInfo = '{}';
    }

    $existingSeed = fetch_one($conn, "SELECT id FROM products WHERE slug = :slug LIMIT 1", ['slug' => $seedSlug]);
    $params = [
        'category_id' => (int)$category['id'],
        'category_name' => (string)$category['name'],
        'subcategory_id' => null,
        'name' => $seedName,
        'description' => 'Starter product seeded automatically so the storefront, checkout, and tests have a working product.',
        'additional_info' => $additionalInfo,
        'slug' => $seedSlug,
        'price' => 15000,
        'color' => 'Black,Blue',
        'size' => 'M,L',
        'brand' => 'Bolakaz',
        'material' => 'Cotton',
        'qty' => 25,
        'photo' => $placeholder,
        'date_view' => $now,
        'counter' => 0,
        'product_status' => 1,
    ];

    if ($existingSeed) {
        $params['id'] = (int)$existingSeed['id'];
        $stmt = $conn->prepare("UPDATE products
            SET category_id = :category_id, category_name = :category_name, subcategory_id = :subcategory_id, name = :name,
                description = :description, additional_info = :additional_info, slug = :slug, price = :price, color = :color,
                size = :size, brand = :brand, material = :material, qty = :qty, photo = :photo, date_view = :date_view,
                counter = :counter, product_status = :product_status
            WHERE id = :id");
        $stmt->execute($params);

        return [
            'id' => (int)$existingSeed['id'],
            'slug' => $seedSlug,
            'action' => 'updated',
        ];
    }

    $existing = fetch_one($conn, "SELECT id, slug FROM products WHERE product_status = 1 AND qty > 0 AND slug <> '' ORDER BY id ASC LIMIT 1");
    if ($existing) {
        return [
            'id' => (int)$existing['id'],
            'slug' => (string)$existing['slug'],
            'action' => 'reused',
        ];
    }

    $stmt = $conn->prepare("INSERT INTO products (category_id, category_name, subcategory_id, name, description, additional_info, slug, price, color, size, brand, material, qty, photo, date_view, counter, product_status)
        VALUES (:category_id, :category_name, :subcategory_id, :name, :description, :additional_info, :slug, :price, :color, :size, :brand, :material, :qty, :photo, :date_view, :counter, :product_status)");
    $stmt->execute($params);

    return [
        'id' => (int)$conn->lastInsertId(),
        'slug' => $seedSlug,
        'action' => 'created',
    ];
}

function ensure_banner(PDO $conn, array $product): array
{
    $seedName = 'Starter Banner';
    $placeholder = 'storefront-placeholder.svg';
    $seedLink = 'detail.php?product=' . rawurlencode((string)$product['slug']);

    $existingSeed = fetch_one($conn, "SELECT id FROM banner WHERE name = :name LIMIT 1", ['name' => $seedName]);
    if ($existingSeed) {
        $stmt = $conn->prepare("UPDATE banner
            SET image_path = :image_path, caption_heading = :caption_heading, caption_text = :caption_text, link = :link
            WHERE id = :id");
        $stmt->execute([
            'image_path' => $placeholder,
            'caption_heading' => 'Starter Banner',
            'caption_text' => 'Seeded automatically for a fresh install.',
            'link' => $seedLink,
            'id' => (int)$existingSeed['id'],
        ]);

        return [
            'id' => (int)$existingSeed['id'],
            'action' => 'updated',
        ];
    }

    $existing = fetch_one($conn, "SELECT id FROM banner ORDER BY id ASC LIMIT 1");
    if ($existing) {
        return [
            'id' => (int)$existing['id'],
            'action' => 'reused',
        ];
    }

    $stmt = $conn->prepare("INSERT INTO banner (name, image_path, caption_heading, caption_text, link)
        VALUES (:name, :image_path, :caption_heading, :caption_text, :link)");
    $stmt->execute([
        'name' => $seedName,
        'image_path' => $placeholder,
        'caption_heading' => 'Starter Banner',
        'caption_text' => 'Seeded automatically for a fresh install.',
        'link' => $seedLink,
    ]);

    return [
        'id' => (int)$conn->lastInsertId(),
        'action' => 'created',
    ];
}

function ensure_ad(PDO $conn, array $category): array
{
    $seedCollection = 'Starter Collection';
    $placeholder = 'storefront-placeholder.svg';
    $seedLink = 'shop.php?category=' . rawurlencode((string)$category['slug']);

    $existingSeed = fetch_one($conn, "SELECT id FROM ads WHERE collection = :collection LIMIT 1", ['collection' => $seedCollection]);
    if ($existingSeed) {
        $stmt = $conn->prepare("UPDATE ads
            SET text_align = :text_align, image_path = :image_path, discount = :discount, link = :link
            WHERE id = :id");
        $stmt->execute([
            'text_align' => 'left',
            'image_path' => $placeholder,
            'discount' => '10% OFF',
            'link' => $seedLink,
            'id' => (int)$existingSeed['id'],
        ]);

        return [
            'id' => (int)$existingSeed['id'],
            'action' => 'updated',
        ];
    }

    $existing = fetch_one($conn, "SELECT id FROM ads ORDER BY id ASC LIMIT 1");
    if ($existing) {
        return [
            'id' => (int)$existing['id'],
            'action' => 'reused',
        ];
    }

    $stmt = $conn->prepare("INSERT INTO ads (text_align, image_path, discount, collection, link)
        VALUES (:text_align, :image_path, :discount, :collection, :link)");
    $stmt->execute([
        'text_align' => 'left',
        'image_path' => $placeholder,
        'discount' => '10% OFF',
        'collection' => $seedCollection,
        'link' => $seedLink,
    ]);

    return [
        'id' => (int)$conn->lastInsertId(),
        'action' => 'created',
    ];
}

function ensure_sale_detail(PDO $conn, int $saleId, int $productId, int $quantity): void
{
    $existing = fetch_one($conn, "SELECT id FROM details WHERE sales_id = :sales_id LIMIT 1", ['sales_id' => $saleId]);
    if ($existing) {
        return;
    }

    $stmt = $conn->prepare("INSERT INTO details (sales_id, product_id, variant_id, quantity)
        VALUES (:sales_id, :product_id, NULL, :quantity)");
    $stmt->execute([
        'sales_id' => $saleId,
        'product_id' => $productId,
        'quantity' => $quantity,
    ]);
}

function ensure_online_sale(PDO $conn, array $customer, array $product, array $shipping, array $coupon): array
{
    $seedTxRef = 'SEED-ONLINE-ORDER';
    $seedTxId = sales_seed_txid_value($conn, $seedTxRef);
    $existingSeed = fetch_one($conn, "SELECT id FROM sales WHERE tx_ref = :tx_ref LIMIT 1", ['tx_ref' => $seedTxRef]);
    if ($existingSeed) {
        ensure_sale_detail($conn, (int)$existingSeed['id'], (int)$product['id'], 1);
        return [
            'id' => (int)$existingSeed['id'],
            'action' => 'updated',
        ];
    }

    $existing = fetch_one($conn, "SELECT sales.id FROM sales INNER JOIN details ON details.sales_id = sales.id WHERE sales.is_offline = 0 ORDER BY sales.id ASC LIMIT 1");
    if ($existing) {
        return [
            'id' => (int)$existing['id'],
            'action' => 'reused',
        ];
    }

    $stmt = $conn->prepare("INSERT INTO sales (user_id, is_offline, tx_ref, txid, Status, payment_status, customer_name, statement_share_token, phone, email, coupon_id, shipping_id, address_1, address_2, sales_date, due_date)
        VALUES (:user_id, 0, :tx_ref, :txid, :status, :payment_status, :customer_name, NULL, :phone, :email, :coupon_id, :shipping_id, :address_1, :address_2, :sales_date, NULL)");
    $stmt->execute([
        'user_id' => (int)$customer['id'],
        'tx_ref' => $seedTxRef,
        'txid' => $seedTxId,
        'status' => 'success',
        'payment_status' => 'paid',
        'customer_name' => trim((string)$customer['firstname'] . ' ' . (string)$customer['lastname']),
        'phone' => (string)$customer['phone'],
        'email' => (string)$customer['email'],
        'coupon_id' => (int)$coupon['id'],
        'shipping_id' => (int)$shipping['id'],
        'address_1' => (string)$customer['address'],
        'address_2' => 'Starter Suite',
        'sales_date' => date('Y-m-d'),
    ]);

    $saleId = (int)$conn->lastInsertId();
    ensure_sale_detail($conn, $saleId, (int)$product['id'], 1);

    return [
        'id' => $saleId,
        'action' => 'created',
    ];
}

function ensure_offline_sale(PDO $conn, array $customer, array $product): array
{
    $seedTxRef = 'SEED-OFFLINE-ORDER';
    $seedTxId = sales_seed_txid_value($conn, $seedTxRef);
    $existingSeed = fetch_one($conn, "SELECT id FROM sales WHERE tx_ref = :tx_ref LIMIT 1", ['tx_ref' => $seedTxRef]);
    if ($existingSeed) {
        $saleId = (int)$existingSeed['id'];
        ensure_sale_detail($conn, $saleId, (int)$product['id'], 1);
        $paymentExists = fetch_value($conn, "SELECT id FROM offline_payments WHERE sales_id = :sales_id LIMIT 1", ['sales_id' => $saleId]);
        if (!$paymentExists) {
            $stmt = $conn->prepare("INSERT INTO offline_payments (sales_id, amount, payment_method, payment_date, note)
                VALUES (:sales_id, :amount, :payment_method, :payment_date, :note)");
            $stmt->execute([
                'sales_id' => $saleId,
                'amount' => 5000,
                'payment_method' => 'Cash',
                'payment_date' => date('Y-m-d'),
                'note' => 'Seeded starter payment',
            ]);
        }

        return [
            'id' => $saleId,
            'action' => 'updated',
        ];
    }

    $existing = fetch_one($conn, "SELECT sales.id FROM sales INNER JOIN details ON details.sales_id = sales.id WHERE sales.is_offline = 1 ORDER BY sales.id ASC LIMIT 1");
    if ($existing) {
        return [
            'id' => (int)$existing['id'],
            'action' => 'reused',
        ];
    }

    $stmt = $conn->prepare("INSERT INTO sales (user_id, is_offline, tx_ref, txid, Status, payment_status, customer_name, statement_share_token, phone, email, coupon_id, shipping_id, address_1, address_2, sales_date, due_date)
        VALUES (:user_id, 1, :tx_ref, :txid, :status, :payment_status, :customer_name, NULL, :phone, :email, NULL, NULL, :address_1, :address_2, :sales_date, :due_date)");
    $stmt->execute([
        'user_id' => (int)$customer['id'],
        'tx_ref' => $seedTxRef,
        'txid' => $seedTxId,
        'status' => 'success',
        'payment_status' => 'partial',
        'customer_name' => 'Starter Walk-in Customer',
        'phone' => (string)$customer['phone'],
        'email' => (string)$customer['email'],
        'address_1' => (string)$customer['address'],
        'address_2' => 'Starter Offline Counter',
        'sales_date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+7 days')),
    ]);

    $saleId = (int)$conn->lastInsertId();
    ensure_sale_detail($conn, $saleId, (int)$product['id'], 1);

    $stmt = $conn->prepare("INSERT INTO offline_payments (sales_id, amount, payment_method, payment_date, note)
        VALUES (:sales_id, :amount, :payment_method, :payment_date, :note)");
    $stmt->execute([
        'sales_id' => $saleId,
        'amount' => 5000,
        'payment_method' => 'Cash',
        'payment_date' => date('Y-m-d'),
        'note' => 'Seeded starter payment',
    ]);

    return [
        'id' => $saleId,
        'action' => 'created',
    ];
}

$requiredTables = [
    'users',
    'category',
    'products',
    'shippings',
    'coupons',
    'banner',
    'ads',
    'web_details',
    'sales',
    'details',
    'offline_payments',
];

$conn = $pdo->open();

try {
    if (!required_tables_exist($conn, $requiredTables)) {
        throw new RuntimeException('Required tables are missing. Run the database migrations first.');
    }

    $conn->beginTransaction();

    $admin = ensure_seed_user($conn, [
        'email' => $options['admin-email'],
        'password' => $options['admin-password'],
        'type' => 1,
        'firstname' => 'Seed',
        'lastname' => 'Admin',
        'address' => 'Starter Admin Address',
        'phone' => '08000000001',
        'gender' => 'Other',
    ]);

    $staff = ensure_seed_user($conn, [
        'email' => $options['staff-email'],
        'password' => $options['staff-password'],
        'type' => 2,
        'firstname' => 'Seed',
        'lastname' => 'Staff',
        'address' => 'Starter Staff Address',
        'phone' => '08000000003',
        'gender' => 'Other',
    ]);

    $customer = ensure_seed_user($conn, [
        'email' => $options['customer-email'],
        'password' => $options['customer-password'],
        'type' => 0,
        'firstname' => 'Seed',
        'lastname' => 'Customer',
        'address' => 'Starter Customer Address',
        'phone' => '08000000002',
        'gender' => 'Other',
    ]);
    $customer['firstname'] = 'Seed';
    $customer['lastname'] = 'Customer';
    $customer['address'] = 'Starter Customer Address';
    $customer['phone'] = '08000000002';

    $category = ensure_category($conn);
    $shipping = ensure_shipping($conn);
    $coupon = ensure_coupon($conn);
    $webDetails = ensure_web_details($conn, $options['site-name'], $options['site-email']);
    $product = ensure_product($conn, $category);
    $banner = ensure_banner($conn, $product);
    $ad = ensure_ad($conn, $category);
    $onlineSale = ensure_online_sale($conn, $customer + ['email' => $options['customer-email']], $product, $shipping, $coupon);
    $offlineSale = ensure_offline_sale($conn, $customer + ['email' => $options['customer-email']], $product);

    $conn->commit();

    seed_output('PASS', 'Admin user ' . $admin['action'] . ': ' . $admin['email']);
    seed_output('PASS', 'Staff user ' . $staff['action'] . ': ' . $staff['email']);
    seed_output('PASS', 'Customer user ' . $customer['action'] . ': ' . $customer['email']);
    seed_output('PASS', 'Category ' . $category['action'] . ': ' . $category['name'] . ' (' . $category['slug'] . ')');
    seed_output('PASS', 'Shipping ' . $shipping['action'] . ': ' . $shipping['type']);
    seed_output('PASS', 'Coupon ' . $coupon['action'] . ': ' . $coupon['code']);
    seed_output('PASS', 'Web details ' . $webDetails['action'] . ': row #' . $webDetails['id']);
    seed_output('PASS', 'Product ' . $product['action'] . ': ' . $product['slug']);
    seed_output('PASS', 'Banner ' . $banner['action'] . ': row #' . $banner['id']);
    seed_output('PASS', 'Ad ' . $ad['action'] . ': row #' . $ad['id']);
    seed_output('PASS', 'Online sale ' . $onlineSale['action'] . ': row #' . $onlineSale['id']);
    seed_output('PASS', 'Offline sale ' . $offlineSale['action'] . ': row #' . $offlineSale['id']);

    echo PHP_EOL;
    echo 'Seeded credentials:' . PHP_EOL;
    echo '  admin: ' . $options['admin-email'] . ' / ' . $options['admin-password'] . PHP_EOL;
    echo '  staff: ' . $options['staff-email'] . ' / ' . $options['staff-password'] . PHP_EOL;
    echo '  customer: ' . $options['customer-email'] . ' / ' . $options['customer-password'] . PHP_EOL;
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    seed_output('FAIL', $e->getMessage());
    $pdo->close();
    exit(1);
}

$pdo->close();
