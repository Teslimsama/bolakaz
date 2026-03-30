<?php

if (!function_exists('sync_env_value')) {
    function sync_env_value(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        $value = trim((string) $value);
        return $value === '' ? $default : $value;
    }
}

if (!function_exists('sync_env_bool_value')) {
    function sync_env_bool_value(string $key, bool $default = false): bool
    {
        if (function_exists('app_env_bool')) {
            return app_env_bool($key, $default);
        }

        $value = strtolower(sync_env_value($key, $default ? '1' : '0'));
        if ($value === '') {
            return $default;
        }

        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('sync_project_root')) {
    function sync_project_root(): string
    {
        return dirname(__DIR__);
    }
}

if (!function_exists('sync_now')) {
    function sync_now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('sync_config')) {
    function sync_config(): array
    {
        static $config = null;
        if (is_array($config)) {
            return $config;
        }

        $serverUrl = rtrim(sync_env_value('SYNC_SERVER_URL', sync_env_value('APP_URL', '')), '/');
        $pingEndpoint = sync_env_value('SYNC_PING_ENDPOINT', '/sync/ping');
        $pushEndpoint = sync_env_value('SYNC_PUSH_ENDPOINT', '/sync/push');
        $pushMediaEndpoint = sync_env_value('SYNC_PUSH_MEDIA_ENDPOINT', '/sync/push-media');
        $pullEndpoint = sync_env_value('SYNC_PULL_ENDPOINT', '/sync/pull');
        $deviceId = sync_env_value('SYNC_DEVICE_ID', php_uname('n'));
        $deviceName = sync_env_value('SYNC_DEVICE_NAME', $deviceId);

        $config = [
            'enabled' => sync_env_bool_value('SYNC_ENABLED', false),
            'role' => strtolower(sync_env_value('SYNC_ROLE', 'client')),
            'device_id' => $deviceId !== '' ? $deviceId : 'device-' . substr(md5(sync_project_root()), 0, 12),
            'device_name' => $deviceName !== '' ? $deviceName : 'Bolakaz Device',
            'server_url' => $serverUrl,
            'token' => sync_env_value('SYNC_TOKEN', ''),
            'ping_endpoint' => $pingEndpoint,
            'push_endpoint' => $pushEndpoint,
            'push_media_endpoint' => $pushMediaEndpoint,
            'pull_endpoint' => $pullEndpoint,
            'batch_size' => max(1, (int) sync_env_value('SYNC_BATCH_SIZE', '20')),
            'timeout_seconds' => max(2, (int) sync_env_value('SYNC_TIMEOUT_SECONDS', '10')),
            'max_attempts' => max(1, (int) sync_env_value('SYNC_MAX_ATTEMPTS', '10')),
            'retry_backoff_minutes' => max(1, (int) sync_env_value('SYNC_RETRY_BACKOFF_MINUTES', '5')),
            'lock_stale_minutes' => 10,
        ];

        return $config;
    }
}

if (!function_exists('sync_is_enabled')) {
    function sync_is_enabled(): bool
    {
        $config = sync_config();
        return (bool) ($config['enabled'] ?? false);
    }
}

if (!function_exists('sync_is_client')) {
    function sync_is_client(): bool
    {
        $config = sync_config();
        return ($config['role'] ?? 'client') === 'client';
    }
}

if (!function_exists('sync_is_server')) {
    function sync_is_server(): bool
    {
        $config = sync_config();
        return ($config['role'] ?? '') === 'server';
    }
}

if (!function_exists('sync_log_message')) {
    function sync_log_message(string $level, string $message, array $context = []): void
    {
        if (function_exists('app_log')) {
            app_log($level, $message, $context);
            return;
        }

        error_log('[sync][' . strtoupper($level) . '] ' . $message . ' ' . json_encode($context));
    }
}

if (!function_exists('sync_generate_uuid')) {
    function sync_generate_uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}

if (!function_exists('sync_entity_definitions')) {
    function sync_entity_definitions(): array
    {
        static $definitions = null;
        if (is_array($definitions)) {
            return $definitions;
        }

        $definitions = [
            'users' => [
                'table' => 'users',
                'pk' => 'id',
                'order' => 10,
                'pull_enabled' => true,
                'pull_priority' => 10,
                'columns' => ['email', 'password', 'type', 'firstname', 'lastname', 'address', 'phone', 'gender', 'dob', 'photo', 'status', 'account_state', 'is_placeholder_email', 'activate_code', 'reset_code', 'created_on', 'referral', 'created_at', 'updated_at'],
                'media' => [
                    ['field' => 'photo', 'disk' => 'images'],
                ],
                'delete_snapshot' => ['email', 'firstname', 'lastname', 'photo', 'status', 'account_state'],
            ],
            'shipping' => [
                'table' => 'shippings',
                'pk' => 'id',
                'order' => 10,
                'pull_enabled' => true,
                'pull_priority' => 10,
                'columns' => ['type', 'price', 'status', 'created_at', 'updated_at'],
                'delete_snapshot' => ['type', 'price', 'status'],
            ],
            'coupon' => [
                'table' => 'coupons',
                'pk' => 'id',
                'order' => 10,
                'pull_enabled' => true,
                'pull_priority' => 10,
                'columns' => ['code', 'type', 'value', 'status', 'expire_date', 'influencer_id', 'created_at', 'updated_at'],
                'delete_snapshot' => ['code', 'type', 'value', 'status'],
            ],
            'web_details' => [
                'table' => 'web_details',
                'pk' => 'id',
                'order' => 10,
                'pull_enabled' => true,
                'pull_priority' => 10,
                'columns' => ['site_name', 'site_address', 'site_email', 'site_number', 'short_description', 'description', 'created_at', 'updated_at'],
                'delete_snapshot' => ['site_name', 'site_email', 'site_number'],
            ],
            'category' => [
                'table' => 'category',
                'pk' => 'id',
                'order' => 20,
                'columns' => ['name', 'cat_image', 'cat_slug', 'is_parent', 'status', 'created_at', 'updated_at'],
                'refs' => [
                    ['column' => 'parent_id', 'entity' => 'category', 'payload_key' => 'parent_uuid', 'nullable' => true],
                ],
                'media' => [
                    ['field' => 'cat_image', 'disk' => 'images'],
                ],
                'delete_snapshot' => ['name', 'cat_slug', 'status', 'cat_image'],
            ],
            'products' => [
                'table' => 'products',
                'pk' => 'id',
                'order' => 30,
                'columns' => ['category_name', 'name', 'description', 'additional_info', 'slug', 'price', 'color', 'size', 'brand', 'material', 'qty', 'photo', 'date_view', 'counter', 'product_status', 'created_at', 'updated_at'],
                'refs' => [
                    ['column' => 'category_id', 'entity' => 'category', 'payload_key' => 'category_uuid', 'nullable' => false],
                    ['column' => 'subcategory_id', 'entity' => 'category', 'payload_key' => 'subcategory_uuid', 'nullable' => true],
                ],
                'media' => [
                    ['field' => 'photo', 'disk' => 'images'],
                ],
                'delete_snapshot' => ['name', 'slug', 'brand', 'photo', 'product_status'],
            ],
            'gallery_images' => [
                'table' => 'gallery_images',
                'pk' => 'id',
                'order' => 40,
                'columns' => ['file_name', 'uploaded_on', 'created_at', 'updated_at'],
                'refs' => [
                    ['column' => 'product_id', 'entity' => 'products', 'payload_key' => 'product_uuid', 'nullable' => false],
                ],
                'mirror_columns' => [
                    'gallery_id' => 'product_id',
                ],
                'media' => [
                    ['field' => 'file_name', 'disk' => 'images'],
                ],
                'delete_snapshot' => ['file_name', 'uploaded_on'],
            ],
            'banner' => [
                'table' => 'banner',
                'pk' => 'id',
                'order' => 50,
                'pull_enabled' => true,
                'pull_priority' => 20,
                'columns' => ['name', 'image_path', 'caption_heading', 'caption_text', 'link', 'created_at', 'updated_at'],
                'media' => [
                    ['field' => 'image_path', 'disk' => 'images'],
                ],
                'delete_snapshot' => ['name', 'caption_heading', 'caption_text', 'image_path'],
            ],
            'ads' => [
                'table' => 'ads',
                'pk' => 'id',
                'order' => 50,
                'pull_enabled' => true,
                'pull_priority' => 20,
                'columns' => ['text_align', 'image_path', 'discount', 'collection', 'link', 'created_at', 'updated_at'],
                'media' => [
                    ['field' => 'image_path', 'disk' => 'images'],
                ],
                'delete_snapshot' => ['discount', 'collection', 'link', 'image_path'],
            ],
            'sales' => [
                'table' => 'sales',
                'pk' => 'id',
                'order' => 60,
                'columns' => ['is_offline', 'tx_ref', 'txid', 'Status', 'payment_status', 'customer_name', 'statement_share_token', 'phone', 'email', 'address_1', 'address_2', 'sales_date', 'due_date', 'created_at', 'updated_at'],
                'refs' => [
                    ['column' => 'user_id', 'entity' => 'users', 'payload_key' => 'user_uuid', 'nullable' => true, 'default' => 0],
                    ['column' => 'coupon_id', 'entity' => 'coupon', 'payload_key' => 'coupon_uuid', 'nullable' => true],
                    ['column' => 'shipping_id', 'entity' => 'shipping', 'payload_key' => 'shipping_uuid', 'nullable' => true],
                ],
                'delete_snapshot' => ['tx_ref', 'customer_name', 'payment_status', 'sales_date'],
            ],
            'details' => [
                'table' => 'details',
                'pk' => 'id',
                'order' => 70,
                'columns' => ['quantity', 'unit_price', 'product_name_snapshot', 'product_slug_snapshot', 'created_at', 'updated_at'],
                'refs' => [
                    ['column' => 'sales_id', 'entity' => 'sales', 'payload_key' => 'sales_uuid', 'nullable' => false],
                    ['column' => 'product_id', 'entity' => 'products', 'payload_key' => 'product_uuid', 'nullable' => false],
                ],
                'delete_snapshot' => ['quantity', 'unit_price', 'product_name_snapshot', 'product_slug_snapshot'],
            ],
            'offline_payments' => [
                'table' => 'offline_payments',
                'pk' => 'id',
                'order' => 80,
                'columns' => ['amount', 'payment_method', 'payment_date', 'note', 'created_at', 'updated_at'],
                'refs' => [
                    ['column' => 'sales_id', 'entity' => 'sales', 'payload_key' => 'sales_uuid', 'nullable' => false],
                ],
                'delete_snapshot' => ['amount', 'payment_method', 'payment_date', 'note'],
            ],
        ];

        return $definitions;
    }
}

if (!function_exists('sync_entity_definition')) {
    function sync_entity_definition(string $entityType): ?array
    {
        $definitions = sync_entity_definitions();
        return $definitions[$entityType] ?? null;
    }
}

if (!function_exists('sync_entity_order_rank')) {
    function sync_entity_order_rank(string $entityType): int
    {
        $definition = sync_entity_definition($entityType);
        return (int) ($definition['order'] ?? 999);
    }
}

if (!function_exists('sync_entity_pull_enabled')) {
    function sync_entity_pull_enabled(string $entityType): bool
    {
        $definition = sync_entity_definition($entityType);
        return (bool) ($definition['pull_enabled'] ?? false);
    }
}

if (!function_exists('sync_entity_pull_priority')) {
    function sync_entity_pull_priority(string $entityType): int
    {
        $definition = sync_entity_definition($entityType);
        return (int) ($definition['pull_priority'] ?? 999);
    }
}

if (!function_exists('sync_table_exists')) {
    function sync_table_exists(PDO $conn, string $table): bool
    {
        static $cache = [];
        $key = spl_object_hash($conn) . ':' . $table;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table LIMIT 1');
            $stmt->execute(['table' => $table]);
            $cache[$key] = (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $cache[$key] = false;
        }

        return $cache[$key];
    }
}

if (!function_exists('sync_column_exists')) {
    function sync_column_exists(PDO $conn, string $table, string $column): bool
    {
        static $cache = [];
        $key = spl_object_hash($conn) . ':' . $table . ':' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $stmt = $conn->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column LIMIT 1');
            $stmt->execute(['table' => $table, 'column' => $column]);
            $cache[$key] = (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $cache[$key] = false;
        }

        return $cache[$key];
    }
}

if (!function_exists('sync_schema_ready')) {
    function sync_schema_ready(PDO $conn): bool
    {
        if (!sync_is_enabled()) {
            return false;
        }

        return sync_table_exists($conn, 'sync_queue') && sync_table_exists($conn, 'devices');
    }
}

if (!function_exists('sync_pull_schema_ready')) {
    function sync_pull_schema_ready(PDO $conn): bool
    {
        return sync_schema_ready($conn)
            && sync_table_exists($conn, 'sync_pull_queue')
            && sync_table_exists($conn, 'sync_state')
            && sync_table_exists($conn, 'sync_outbox');
    }
}

if (!function_exists('sync_public_file_url')) {
    function sync_public_file_url(string $disk, string $relativePath): string
    {
        $baseUrl = rtrim(sync_env_value('APP_URL', (string) (sync_config()['server_url'] ?? '')), '/');
        if ($baseUrl === '') {
            return '';
        }

        return sync_join_url($baseUrl, trim($disk, '/') . '/' . ltrim($relativePath, '/'));
    }
}

if (!function_exists('sync_ensure_state_row')) {
    function sync_ensure_state_row(PDO $conn, ?string $deviceId = null): bool
    {
        if (!sync_table_exists($conn, 'sync_state')) {
            return false;
        }

        $config = sync_config();
        $deviceId = trim((string) ($deviceId ?? $config['device_id']));
        if ($deviceId === '') {
            return false;
        }

        $stmt = $conn->prepare('SELECT device_id FROM sync_state WHERE device_id = :device_id LIMIT 1');
        $stmt->execute(['device_id' => $deviceId]);
        if ($stmt->fetchColumn()) {
            return true;
        }

        $insert = $conn->prepare('INSERT INTO sync_state (device_id, created_at, updated_at) VALUES (:device_id, :created_at, :updated_at)');
        return $insert->execute([
            'device_id' => $deviceId,
            'created_at' => sync_now(),
            'updated_at' => sync_now(),
        ]);
    }
}

if (!function_exists('sync_get_state_row')) {
    function sync_get_state_row(PDO $conn, ?string $deviceId = null): array
    {
        if (!sync_ensure_state_row($conn, $deviceId)) {
            return [];
        }

        $config = sync_config();
        $deviceId = trim((string) ($deviceId ?? $config['device_id']));
        $stmt = $conn->prepare('SELECT * FROM sync_state WHERE device_id = :device_id LIMIT 1');
        $stmt->execute(['device_id' => $deviceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}

if (!function_exists('sync_update_state')) {
    function sync_update_state(PDO $conn, array $fields, ?string $deviceId = null): bool
    {
        if (empty($fields) || !sync_ensure_state_row($conn, $deviceId)) {
            return false;
        }

        $config = sync_config();
        $deviceId = trim((string) ($deviceId ?? $config['device_id']));
        $set = [];
        $params = [
            'device_id' => $deviceId,
            'updated_at' => sync_now(),
        ];

        foreach ($fields as $column => $value) {
            $param = 'field_' . $column;
            $set[] = $column . ' = :' . $param;
            $params[$param] = $value;
        }

        $set[] = 'updated_at = :updated_at';
        $sql = 'UPDATE sync_state SET ' . implode(', ', $set) . ' WHERE device_id = :device_id';
        $stmt = $conn->prepare($sql);
        return $stmt->execute($params);
    }
}

if (!function_exists('sync_normalize_value')) {
    function sync_normalize_value($value)
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }
}

if (!function_exists('sync_filter_data')) {
    function sync_filter_data(array $data): array
    {
        $filtered = [];
        foreach ($data as $key => $value) {
            $filtered[$key] = sync_normalize_value($value);
        }

        return $filtered;
    }
}

if (!function_exists('sync_entity_uuid_by_id')) {
    function sync_entity_uuid_by_id(PDO $conn, string $entityType, $id): ?string
    {
        if ((int) $id <= 0) {
            return null;
        }

        $definition = sync_entity_definition($entityType);
        if (!$definition) {
            return null;
        }

        $table = $definition['table'];
        $pk = $definition['pk'];
        if (!sync_column_exists($conn, $table, 'uuid')) {
            return null;
        }

        $stmt = $conn->prepare("SELECT uuid FROM {$table} WHERE {$pk} = :id LIMIT 1");
        $stmt->execute(['id' => (int) $id]);
        $uuid = $stmt->fetchColumn();

        if (!is_string($uuid) || trim($uuid) === '') {
            return null;
        }

        return trim($uuid);
    }
}

if (!function_exists('sync_entity_local_id_by_uuid')) {
    function sync_entity_local_id_by_uuid(PDO $conn, string $entityType, string $uuid): ?int
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition || trim($uuid) === '') {
            return null;
        }

        $table = $definition['table'];
        $pk = $definition['pk'];
        if (!sync_column_exists($conn, $table, 'uuid')) {
            return null;
        }

        $stmt = $conn->prepare("SELECT {$pk} FROM {$table} WHERE uuid = :uuid LIMIT 1");
        $stmt->execute(['uuid' => $uuid]);
        $value = $stmt->fetchColumn();

        if ($value === false || $value === null) {
            return null;
        }

        return (int) $value;
    }
}

if (!function_exists('sync_get_entity_row')) {
    function sync_get_entity_row(PDO $conn, string $entityType, int $id): ?array
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition || $id <= 0) {
            return null;
        }

        $table = $definition['table'];
        $pk = $definition['pk'];
        $stmt = $conn->prepare("SELECT * FROM {$table} WHERE {$pk} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('sync_get_entity_row_by_uuid')) {
    function sync_get_entity_row_by_uuid(PDO $conn, string $entityType, string $uuid): ?array
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition || trim($uuid) === '') {
            return null;
        }

        $table = $definition['table'];
        $stmt = $conn->prepare("SELECT * FROM {$table} WHERE uuid = :uuid LIMIT 1");
        $stmt->execute(['uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('sync_ensure_entity_uuid')) {
    function sync_ensure_entity_uuid(PDO $conn, string $entityType, int $id): ?string
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition || $id <= 0) {
            return null;
        }

        $table = $definition['table'];
        $pk = $definition['pk'];
        if (!sync_column_exists($conn, $table, 'uuid')) {
            return null;
        }

        $row = sync_get_entity_row($conn, $entityType, $id);
        if (!$row) {
            return null;
        }

        $uuid = trim((string) ($row['uuid'] ?? ''));
        if ($uuid !== '') {
            return $uuid;
        }

        $uuid = sync_generate_uuid();
        $stmt = $conn->prepare("UPDATE {$table} SET uuid = :uuid WHERE {$pk} = :id");
        $stmt->execute([
            'uuid' => $uuid,
            'id' => $id,
        ]);

        return $uuid;
    }
}

if (!function_exists('sync_should_queue_row')) {
    function sync_should_queue_row(PDO $conn, string $entityType, array $row): bool
    {
        if ($entityType === 'users') {
            return (int) ($row['type'] ?? 0) === 0;
        }

        if ($entityType === 'sales') {
            return (int) ($row['is_offline'] ?? 0) === 1;
        }

        if ($entityType === 'details' || $entityType === 'offline_payments') {
            $salesId = (int) ($row['sales_id'] ?? 0);
            if ($salesId <= 0) {
                return false;
            }
            $sales = sync_get_entity_row($conn, 'sales', $salesId);
            return is_array($sales) && (int) ($sales['is_offline'] ?? 0) === 1;
        }

        return true;
    }
}

if (!function_exists('sync_build_media_manifest')) {
    function sync_build_media_manifest(string $entityType, array $row): array
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition) {
            return [];
        }

        $root = sync_project_root();
        $manifest = [];
        foreach (($definition['media'] ?? []) as $mediaDef) {
            $field = (string) ($mediaDef['field'] ?? '');
            $disk = trim((string) ($mediaDef['disk'] ?? 'images'), '/');
            $filename = trim((string) ($row[$field] ?? ''));
            if ($field === '' || $filename === '') {
                continue;
            }

            $absolutePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $disk . '/' . $filename);
            if (!is_file($absolutePath)) {
                continue;
            }

            $manifest[] = [
                'field' => $field,
                'disk' => $disk,
                'relative_path' => $filename,
                'file_name' => basename($filename),
                'original_name' => basename($filename),
                'mime_type' => function_exists('mime_content_type') ? (string) mime_content_type($absolutePath) : 'application/octet-stream',
                'size' => (int) @filesize($absolutePath),
                'file_size' => (int) @filesize($absolutePath),
                'file_hash' => is_file($absolutePath) ? (string) @hash_file('sha256', $absolutePath) : '',
                'file_url' => sync_public_file_url($disk, $filename),
            ];
        }

        return $manifest;
    }
}

if (!function_exists('sync_build_entity_data')) {
    function sync_build_entity_data(PDO $conn, string $entityType, array $row): array
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition) {
            return [];
        }

        $data = [
            'uuid' => trim((string) ($row['uuid'] ?? '')),
            'created_at' => trim((string) ($row['created_at'] ?? sync_now())),
            'updated_at' => trim((string) ($row['updated_at'] ?? sync_now())),
        ];

        foreach (($definition['columns'] ?? []) as $column) {
            if (array_key_exists($column, $row)) {
                $data[$column] = sync_normalize_value($row[$column]);
            }
        }

        foreach (($definition['refs'] ?? []) as $ref) {
            $column = (string) ($ref['column'] ?? '');
            $payloadKey = (string) ($ref['payload_key'] ?? '');
            $relatedEntity = (string) ($ref['entity'] ?? '');
            if ($column === '' || $payloadKey === '' || $relatedEntity === '') {
                continue;
            }

            $data[$payloadKey] = sync_entity_uuid_by_id($conn, $relatedEntity, (int) ($row[$column] ?? 0));
        }

        if ($entityType === 'gallery_images' && !empty($data['product_uuid'])) {
            $data['gallery_uuid'] = $data['product_uuid'];
        }

        return sync_filter_data($data);
    }
}

if (!function_exists('sync_build_delete_snapshot')) {
    function sync_build_delete_snapshot(string $entityType, array $row): array
    {
        $definition = sync_entity_definition($entityType);
        $snapshot = [];
        foreach (($definition['delete_snapshot'] ?? []) as $column) {
            if (array_key_exists($column, $row)) {
                $snapshot[$column] = sync_normalize_value($row[$column]);
            }
        }

        return $snapshot;
    }
}

if (!function_exists('sync_build_queue_payload')) {
    function sync_build_queue_payload(PDO $conn, string $entityType, array $row, string $actionType, array $options = []): array
    {
        $uuid = trim((string) ($row['uuid'] ?? ''));
        $updatedAt = trim((string) ($row['updated_at'] ?? sync_now()));
        $queueUuid = trim((string) ($options['queue_uuid'] ?? sync_generate_uuid()));

        if ($actionType === 'delete') {
            $deletedAt = trim((string) ($options['deleted_at'] ?? sync_now()));
            return [
                'meta' => [
                    'queue_uuid' => $queueUuid,
                    'source_side' => sync_is_server() ? 'server' : 'client',
                    'entity_type' => $entityType,
                    'entity_uuid' => $uuid,
                    'action_type' => 'delete',
                    'source_updated_at' => $updatedAt !== '' ? $updatedAt : $deletedAt,
                    'source_device_id' => sync_config()['device_id'],
                ],
                'data' => [
                    'uuid' => $uuid,
                    'deleted_at' => $deletedAt,
                    'minimal_snapshot' => sync_build_delete_snapshot($entityType, $row),
                ],
                'media' => [],
            ];
        }

        return [
            'meta' => [
                'queue_uuid' => $queueUuid,
                'source_side' => sync_is_server() ? 'server' : 'client',
                'entity_type' => $entityType,
                'entity_uuid' => $uuid,
                'action_type' => 'upsert',
                'source_updated_at' => $updatedAt,
                'source_device_id' => sync_config()['device_id'],
            ],
            'data' => sync_build_entity_data($conn, $entityType, $row),
            'media' => sync_build_media_manifest($entityType, $row),
        ];
    }
}

if (!function_exists('sync_insert_queue_item')) {
    function sync_insert_queue_item(PDO $conn, string $entityType, string $entityUuid, string $actionType, array $payload, string $sourceUpdatedAt): bool
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            throw new RuntimeException('Unable to encode sync payload.');
        }

        $stmt = $conn->prepare(
            'INSERT INTO sync_queue
                (queue_uuid, entity_type, entity_uuid, action_type, payload_json, status, attempts, locked_at, next_attempt_at, source_updated_at, source_device_id, last_error, created_at, updated_at, synced_at)
             VALUES
                (:queue_uuid, :entity_type, :entity_uuid, :action_type, :payload_json, :status, :attempts, NULL, :next_attempt_at, :source_updated_at, :source_device_id, NULL, :created_at, :updated_at, NULL)'
        );

        return $stmt->execute([
            'queue_uuid' => (string) ($payload['meta']['queue_uuid'] ?? sync_generate_uuid()),
            'entity_type' => $entityType,
            'entity_uuid' => $entityUuid,
            'action_type' => $actionType,
            'payload_json' => $json,
            'status' => 'pending',
            'attempts' => 0,
            'next_attempt_at' => sync_now(),
            'source_updated_at' => $sourceUpdatedAt !== '' ? $sourceUpdatedAt : sync_now(),
            'source_device_id' => sync_config()['device_id'],
            'created_at' => sync_now(),
            'updated_at' => sync_now(),
        ]);
    }
}

if (!function_exists('sync_build_outbox_payload')) {
    function sync_build_outbox_payload(PDO $conn, string $entityType, array $row, string $actionType, array $options = []): array
    {
        $uuid = trim((string) ($row['uuid'] ?? ''));
        $updatedAt = trim((string) ($row['updated_at'] ?? sync_now()));
        $eventUuid = trim((string) ($options['event_uuid'] ?? sync_generate_uuid()));

        if ($actionType === 'delete') {
            $deletedAt = trim((string) ($options['deleted_at'] ?? sync_now()));
            return [
                'meta' => [
                    'event_uuid' => $eventUuid,
                    'entity_type' => $entityType,
                    'entity_uuid' => $uuid,
                    'action_type' => 'delete',
                    'source_side' => 'server',
                    'source_device_id' => sync_config()['device_id'],
                    'source_updated_at' => $updatedAt !== '' ? $updatedAt : $deletedAt,
                ],
                'data' => [
                    'uuid' => $uuid,
                    'deleted_at' => $deletedAt,
                    'minimal_snapshot' => sync_build_delete_snapshot($entityType, $row),
                ],
                'media' => [],
            ];
        }

        return [
            'meta' => [
                'event_uuid' => $eventUuid,
                'entity_type' => $entityType,
                'entity_uuid' => $uuid,
                'action_type' => 'upsert',
                'source_side' => 'server',
                'source_device_id' => sync_config()['device_id'],
                'source_updated_at' => $updatedAt,
            ],
            'data' => sync_build_entity_data($conn, $entityType, $row),
            'media' => sync_build_media_manifest($entityType, $row),
        ];
    }
}

if (!function_exists('sync_insert_outbox_item')) {
    function sync_insert_outbox_item(PDO $conn, string $entityType, string $entityUuid, string $actionType, array $payload, string $sourceUpdatedAt): bool
    {
        if (!sync_table_exists($conn, 'sync_outbox')) {
            return false;
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            throw new RuntimeException('Unable to encode sync outbox payload.');
        }

        $stmt = $conn->prepare(
            'INSERT INTO sync_outbox
                (event_uuid, entity_type, entity_uuid, action_type, payload_json, source_side, source_device_id, source_updated_at, created_at)
             VALUES
                (:event_uuid, :entity_type, :entity_uuid, :action_type, :payload_json, :source_side, :source_device_id, :source_updated_at, :created_at)'
        );

        return $stmt->execute([
            'event_uuid' => (string) ($payload['meta']['event_uuid'] ?? sync_generate_uuid()),
            'entity_type' => $entityType,
            'entity_uuid' => $entityUuid,
            'action_type' => $actionType,
            'payload_json' => $json,
            'source_side' => (string) ($payload['meta']['source_side'] ?? 'server'),
            'source_device_id' => (string) ($payload['meta']['source_device_id'] ?? sync_config()['device_id']),
            'source_updated_at' => $sourceUpdatedAt !== '' ? $sourceUpdatedAt : sync_now(),
            'created_at' => sync_now(),
        ]);
    }
}

if (!function_exists('sync_record_native_server_change')) {
    function sync_record_native_server_change(PDO $conn, string $entityType, int $entityId, string $actionType = 'upsert', array $options = []): bool
    {
        if (!sync_is_server() || !sync_table_exists($conn, 'sync_outbox')) {
            return false;
        }

        if (!sync_entity_pull_enabled($entityType)) {
            return true;
        }

        $row = $options['row'] ?? sync_get_entity_row($conn, $entityType, $entityId);
        if (!is_array($row)) {
            return false;
        }

        if (!sync_should_queue_row($conn, $entityType, $row)) {
            return true;
        }

        $uuid = trim((string) ($row['uuid'] ?? ''));
        if ($uuid === '' && $actionType !== 'delete') {
            $uuid = (string) sync_ensure_entity_uuid($conn, $entityType, $entityId);
            $row = sync_get_entity_row($conn, $entityType, $entityId) ?? $row;
        }

        if ($uuid === '' && $actionType === 'delete') {
            $uuid = trim((string) ($row['uuid'] ?? ''));
        }

        if ($uuid === '') {
            return false;
        }

        $payload = sync_build_outbox_payload($conn, $entityType, $row, $actionType, $options);
        return sync_insert_outbox_item(
            $conn,
            $entityType,
            $uuid,
            $actionType,
            $payload,
            trim((string) ($payload['meta']['source_updated_at'] ?? sync_now()))
        );
    }
}

if (!function_exists('sync_enqueue_entity_change')) {
    function sync_enqueue_entity_change(PDO $conn, string $entityType, int $entityId, string $actionType = 'upsert', array $options = []): bool
    {
        if (!sync_schema_ready($conn)) {
            return false;
        }

        $row = $options['row'] ?? sync_get_entity_row($conn, $entityType, $entityId);
        if (!is_array($row)) {
            return false;
        }

        if (!sync_should_queue_row($conn, $entityType, $row)) {
            return true;
        }

        $uuid = trim((string) ($row['uuid'] ?? ''));
        if ($uuid === '' && $actionType !== 'delete') {
            $uuid = (string) sync_ensure_entity_uuid($conn, $entityType, $entityId);
            $row = sync_get_entity_row($conn, $entityType, $entityId) ?? $row;
        }

        if ($uuid === '' && $actionType === 'delete') {
            $uuid = trim((string) ($row['uuid'] ?? ''));
        }

        if ($uuid === '') {
            return false;
        }

        $payload = sync_build_queue_payload($conn, $entityType, $row, $actionType, $options);
        return sync_insert_queue_item(
            $conn,
            $entityType,
            $uuid,
            $actionType,
            $payload,
            trim((string) ($payload['meta']['source_updated_at'] ?? sync_now()))
        );
    }
}

if (!function_exists('sync_enqueue_entity_delete')) {
    function sync_enqueue_entity_delete(PDO $conn, string $entityType, array $row, array $options = []): bool
    {
        if (!sync_schema_ready($conn) || empty($row)) {
            return false;
        }

        if (!sync_should_queue_row($conn, $entityType, $row)) {
            return true;
        }

        return sync_enqueue_entity_change(
            $conn,
            $entityType,
            (int) ($row['id'] ?? 0),
            'delete',
            array_merge($options, ['row' => $row])
        );
    }
}

if (!function_exists('sync_should_enforce_queue')) {
    function sync_should_enforce_queue(PDO $conn): bool
    {
        return sync_is_enabled() && sync_schema_ready($conn);
    }
}

if (!function_exists('sync_enqueue_or_fail')) {
    function sync_enqueue_or_fail(PDO $conn, string $entityType, int $entityId, string $actionType = 'upsert', array $options = []): void
    {
        if (!sync_should_enforce_queue($conn)) {
            return;
        }

        $ok = sync_is_server()
            ? sync_record_native_server_change($conn, $entityType, $entityId, $actionType, $options)
            : sync_enqueue_entity_change($conn, $entityType, $entityId, $actionType, $options);

        if (!$ok) {
            throw new RuntimeException('Unable to queue sync event for ' . $entityType . '.');
        }
    }
}

if (!function_exists('sync_enqueue_delete_or_fail')) {
    function sync_enqueue_delete_or_fail(PDO $conn, string $entityType, array $row, array $options = []): void
    {
        if (!sync_should_enforce_queue($conn)) {
            return;
        }

        $ok = sync_is_server()
            ? sync_record_native_server_change($conn, $entityType, (int) ($row['id'] ?? 0), 'delete', array_merge($options, ['row' => $row]))
            : sync_enqueue_entity_delete($conn, $entityType, $row, $options);

        if (!$ok) {
            throw new RuntimeException('Unable to queue delete sync event for ' . $entityType . '.');
        }
    }
}

if (!function_exists('sync_ensure_device_row')) {
    function sync_ensure_device_row(PDO $conn, ?string $deviceId = null, ?string $deviceName = null, string $status = 'active'): bool
    {
        if (!sync_table_exists($conn, 'devices')) {
            return false;
        }

        $config = sync_config();
        $deviceId = trim((string) ($deviceId ?? $config['device_id']));
        $deviceName = trim((string) ($deviceName ?? $config['device_name']));
        if ($deviceId === '') {
            return false;
        }

        $now = sync_now();
        $stmt = $conn->prepare('SELECT id FROM devices WHERE device_id = :device_id LIMIT 1');
        $stmt->execute(['device_id' => $deviceId]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $update = $conn->prepare('UPDATE devices SET device_name = :device_name, last_seen_at = :last_seen_at, status = :status, updated_at = :updated_at WHERE id = :id');
            return $update->execute([
                'device_name' => $deviceName !== '' ? $deviceName : $deviceId,
                'last_seen_at' => $now,
                'status' => $status,
                'updated_at' => $now,
                'id' => (int) $existingId,
            ]);
        }

        $insert = $conn->prepare('INSERT INTO devices (device_id, device_name, last_seen_at, last_sync_at, status, created_at, updated_at) VALUES (:device_id, :device_name, :last_seen_at, NULL, :status, :created_at, :updated_at)');
        return $insert->execute([
            'device_id' => $deviceId,
            'device_name' => $deviceName !== '' ? $deviceName : $deviceId,
            'last_seen_at' => $now,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

if (!function_exists('sync_mark_device_last_sync')) {
    function sync_mark_device_last_sync(PDO $conn, ?string $deviceId = null): bool
    {
        if (!sync_table_exists($conn, 'devices')) {
            return false;
        }

        $config = sync_config();
        $deviceId = trim((string) ($deviceId ?? $config['device_id']));
        if ($deviceId === '') {
            return false;
        }

        $stmt = $conn->prepare('UPDATE devices SET last_seen_at = :last_seen_at, last_sync_at = :last_sync_at, updated_at = :updated_at WHERE device_id = :device_id');
        return $stmt->execute([
            'last_seen_at' => sync_now(),
            'last_sync_at' => sync_now(),
            'updated_at' => sync_now(),
            'device_id' => $deviceId,
        ]);
    }
}

if (!function_exists('sync_compute_backoff_minutes')) {
    function sync_compute_backoff_minutes(int $attempts): int
    {
        $config = sync_config();
        $base = (int) ($config['retry_backoff_minutes'] ?? 5);
        $attempts = max(1, $attempts);
        $multiplier = 2 ** max(0, $attempts - 1);

        return min(1440, $base * $multiplier);
    }
}

if (!function_exists('sync_requeue_retryable_failures')) {
    function sync_requeue_retryable_failures(PDO $conn): void
    {
        if (!sync_schema_ready($conn)) {
            return;
        }

        $config = sync_config();
        $stmt = $conn->prepare("UPDATE sync_queue SET status = 'pending', updated_at = :updated_at WHERE status = 'failed' AND attempts < :max_attempts AND (next_attempt_at IS NULL OR next_attempt_at <= :now)");
        $stmt->execute([
            'updated_at' => sync_now(),
            'max_attempts' => (int) ($config['max_attempts'] ?? 10),
            'now' => sync_now(),
        ]);
    }
}

if (!function_exists('sync_reset_failed_items')) {
    function sync_reset_failed_items(PDO $conn, array $statuses = ['failed', 'conflict']): int
    {
        if (!sync_schema_ready($conn) || empty($statuses)) {
            return 0;
        }

        $allowed = ['failed', 'conflict'];
        $statuses = array_values(array_intersect($allowed, $statuses));
        if (empty($statuses)) {
            return 0;
        }

        $placeholders = [];
        $params = [
            'updated_at' => sync_now(),
            'next_attempt_at' => sync_now(),
        ];
        foreach ($statuses as $index => $status) {
            $key = 'status_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $status;
        }

        $sql = "UPDATE sync_queue SET status = 'pending', last_error = NULL, locked_at = NULL, next_attempt_at = :next_attempt_at, updated_at = :updated_at WHERE status IN (" . implode(', ', $placeholders) . ')';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}

if (!function_exists('sync_reset_failed_pull_items')) {
    function sync_reset_failed_pull_items(PDO $conn, array $statuses = ['failed']): int
    {
        if (!sync_table_exists($conn, 'sync_pull_queue') || empty($statuses)) {
            return 0;
        }

        $allowed = ['failed'];
        $statuses = array_values(array_intersect($allowed, $statuses));
        if (empty($statuses)) {
            return 0;
        }

        $placeholders = [];
        $params = [
            'updated_at' => sync_now(),
            'next_attempt_at' => sync_now(),
        ];
        foreach ($statuses as $index => $status) {
            $key = 'status_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $status;
        }

        $sql = "UPDATE sync_pull_queue
                SET status = 'pending',
                    applied = 0,
                    attempts = 0,
                    locked_at = NULL,
                    last_error = NULL,
                    conflict_reason = NULL,
                    conflict_detected_at = NULL,
                    next_attempt_at = :next_attempt_at,
                    updated_at = :updated_at
                WHERE applied = 0
                  AND status IN (" . implode(', ', $placeholders) . ')';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}

if (!function_exists('sync_claim_queue_items')) {
    function sync_claim_queue_items(PDO $conn, int $limit = 20): array
    {
        if (!sync_schema_ready($conn)) {
            return [];
        }

        sync_requeue_retryable_failures($conn);

        $config = sync_config();
        $limit = max(1, $limit);
        $staleAt = date('Y-m-d H:i:s', time() - ((int) ($config['lock_stale_minutes'] ?? 10) * 60));

        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare(
                "SELECT *
                 FROM sync_queue
                 WHERE
                    ((status = 'pending' AND (next_attempt_at IS NULL OR next_attempt_at <= :now))
                     OR (status = 'processing' AND locked_at IS NOT NULL AND locked_at <= :stale_at))
                 ORDER BY
                    CASE entity_type
                        WHEN 'users' THEN 10
                        WHEN 'shipping' THEN 10
                        WHEN 'coupon' THEN 10
                        WHEN 'web_details' THEN 10
                        WHEN 'category' THEN 20
                        WHEN 'products' THEN 30
                        WHEN 'gallery_images' THEN 40
                        WHEN 'banner' THEN 50
                        WHEN 'ads' THEN 50
                        WHEN 'sales' THEN 60
                        WHEN 'details' THEN 70
                        WHEN 'offline_payments' THEN 80
                        ELSE 999
                    END ASC,
                    created_at ASC,
                    id ASC
                 LIMIT {$limit}"
            );
            $stmt->execute([
                'now' => sync_now(),
                'stale_at' => $staleAt,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                $conn->commit();
                return [];
            }

            $ids = array_map(static fn(array $row): int => (int) $row['id'], $rows);
            $params = [
                'locked_at' => sync_now(),
                'updated_at' => sync_now(),
            ];
            $placeholders = [];
            foreach ($ids as $index => $id) {
                $key = 'id_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }

            $updateSql = "UPDATE sync_queue SET status = 'processing', locked_at = :locked_at, updated_at = :updated_at WHERE id IN (" . implode(', ', $placeholders) . ')';
            $update = $conn->prepare($updateSql);
            $update->execute($params);
            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $e;
        }

        foreach ($rows as &$row) {
            $decoded = json_decode((string) ($row['payload_json'] ?? ''), true);
            $row['payload'] = is_array($decoded) ? $decoded : [];
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('sync_update_entity_last_synced_at')) {
    function sync_update_entity_last_synced_at(PDO $conn, string $entityType, string $entityUuid, string $syncedAt): bool
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition || trim($entityUuid) === '') {
            return false;
        }

        $table = $definition['table'];
        if (!sync_column_exists($conn, $table, 'last_synced_at') || !sync_column_exists($conn, $table, 'uuid')) {
            return false;
        }

        $stmt = $conn->prepare("UPDATE {$table} SET last_synced_at = :last_synced_at WHERE uuid = :uuid");
        return $stmt->execute([
            'last_synced_at' => $syncedAt,
            'uuid' => $entityUuid,
        ]);
    }
}

if (!function_exists('sync_mark_queue_synced')) {
    function sync_mark_queue_synced(PDO $conn, int $queueId, string $entityType, string $entityUuid, ?string $syncedAt = null): bool
    {
        $syncedAt = trim((string) ($syncedAt ?? sync_now()));
        $stmt = $conn->prepare("UPDATE sync_queue SET status = 'synced', locked_at = NULL, last_error = NULL, synced_at = :synced_at, updated_at = :updated_at WHERE id = :id");
        $ok = $stmt->execute([
            'synced_at' => $syncedAt,
            'updated_at' => sync_now(),
            'id' => $queueId,
        ]);

        if ($ok) {
            sync_update_entity_last_synced_at($conn, $entityType, $entityUuid, $syncedAt);
        }

        return $ok;
    }
}

if (!function_exists('sync_mark_queue_issue')) {
    function sync_mark_queue_issue(PDO $conn, int $queueId, string $status, string $message, int $attemptIncrement = 0): bool
    {
        $status = in_array($status, ['failed', 'conflict', 'superseded'], true) ? $status : 'failed';
        $stmt = $conn->prepare('SELECT attempts FROM sync_queue WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $queueId]);
        $attempts = (int) ($stmt->fetchColumn() ?: 0);
        $attempts += max(0, $attemptIncrement);

        $nextAttemptAt = null;
        if ($status === 'failed') {
            $config = sync_config();
            if ($attempts < (int) ($config['max_attempts'] ?? 10)) {
                $nextAttemptAt = date('Y-m-d H:i:s', time() + (sync_compute_backoff_minutes($attempts) * 60));
            }
        }

        $conflictReason = in_array($status, ['conflict', 'superseded'], true) ? mb_substr($message, 0, 1000) : null;
        $conflictDetectedAt = in_array($status, ['conflict', 'superseded'], true) ? sync_now() : null;

        $update = $conn->prepare(
            'UPDATE sync_queue
             SET status = :status,
                 attempts = :attempts,
                 locked_at = NULL,
                 last_error = :last_error,
                 conflict_reason = :conflict_reason,
                 conflict_detected_at = :conflict_detected_at,
                 next_attempt_at = :next_attempt_at,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        return $update->execute([
            'status' => $status,
            'attempts' => $attempts,
            'last_error' => mb_substr($message, 0, 1000),
            'conflict_reason' => $conflictReason,
            'conflict_detected_at' => $conflictDetectedAt,
            'next_attempt_at' => $nextAttemptAt,
            'updated_at' => sync_now(),
            'id' => $queueId,
        ]);
    }
}

if (!function_exists('sync_authorization_token_from_request')) {
    function sync_authorization_token_from_request(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!is_string($header) || stripos($header, 'Bearer ') !== 0) {
            return '';
        }

        return trim(substr($header, 7));
    }
}

if (!function_exists('sync_server_require_token')) {
    function sync_server_require_token(): void
    {
        $expected = trim((string) (sync_config()['token'] ?? ''));
        $provided = sync_authorization_token_from_request();
        if ($expected === '' || !hash_equals($expected, $provided)) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized sync request.',
            ]);
            exit;
        }
    }
}

if (!function_exists('sync_server_register_device')) {
    function sync_server_register_device(PDO $conn, string $deviceId, string $deviceName): array
    {
        sync_ensure_device_row($conn, $deviceId, $deviceName, 'active');

        $stmt = $conn->prepare('SELECT * FROM devices WHERE device_id = :device_id LIMIT 1');
        $stmt->execute(['device_id' => $deviceId]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if (!empty($device) && strtolower((string) ($device['status'] ?? 'active')) === 'disabled') {
            return [
                'allowed' => false,
                'device' => $device,
                'message' => 'This device is disabled for sync.',
            ];
        }

        return [
            'allowed' => true,
            'device' => $device,
            'message' => '',
        ];
    }
}

if (!function_exists('sync_server_record_receipt')) {
    function sync_server_record_receipt(PDO $conn, string $deviceId, array $item, string $status, string $message): void
    {
        if (!sync_table_exists($conn, 'sync_receipts')) {
            return;
        }

        $queueUuid = trim((string) ($item['queue_uuid'] ?? $item['queue_id'] ?? ''));
        $stmt = $conn->prepare('INSERT INTO sync_receipts (device_id, queue_uuid, entity_type, entity_uuid, action_type, status, message, received_at, processed_at) VALUES (:device_id, :queue_uuid, :entity_type, :entity_uuid, :action_type, :status, :message, :received_at, :processed_at)');
        $stmt->execute([
            'device_id' => $deviceId,
            'queue_uuid' => $queueUuid,
            'entity_type' => (string) ($item['entity_type'] ?? ''),
            'entity_uuid' => (string) ($item['entity_uuid'] ?? ''),
            'action_type' => (string) ($item['action_type'] ?? ''),
            'status' => $status,
            'message' => mb_substr($message, 0, 1000),
            'received_at' => sync_now(),
            'processed_at' => sync_now(),
        ]);
    }
}

if (!function_exists('sync_server_compare_conflict')) {
    function sync_server_compare_conflict(array $existingRow, string $incomingUpdatedAt): bool
    {
        $serverUpdatedAt = trim((string) ($existingRow['updated_at'] ?? ''));
        if ($serverUpdatedAt === '' || $incomingUpdatedAt === '') {
            return false;
        }

        $serverTime = strtotime($serverUpdatedAt);
        $incomingTime = strtotime($incomingUpdatedAt);
        if ($serverTime === false || $incomingTime === false) {
            return false;
        }

        return $serverTime > $incomingTime;
    }
}

if (!function_exists('sync_server_prepare_columns')) {
    function sync_server_prepare_columns(PDO $conn, string $entityType, array $data): array
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition) {
            return [
                'ok' => false,
                'message' => 'Unsupported entity type.',
                'fields' => [],
            ];
        }

        $fields = [
            'uuid' => trim((string) ($data['uuid'] ?? '')),
        ];

        foreach (($definition['columns'] ?? []) as $column) {
            if (array_key_exists($column, $data)) {
                $fields[$column] = $data[$column];
            }
        }

        foreach (($definition['refs'] ?? []) as $ref) {
            $column = (string) ($ref['column'] ?? '');
            $payloadKey = (string) ($ref['payload_key'] ?? '');
            $relatedEntity = (string) ($ref['entity'] ?? '');
            $nullable = (bool) ($ref['nullable'] ?? false);
            $default = $ref['default'] ?? null;
            if ($column === '' || $payloadKey === '' || $relatedEntity === '') {
                continue;
            }

            $relatedUuid = trim((string) ($data[$payloadKey] ?? ''));
            if ($relatedUuid === '') {
                if (!$nullable) {
                    if ($default !== null) {
                        $fields[$column] = $default;
                        continue;
                    }

                    return [
                        'ok' => false,
                        'message' => 'Missing dependency ' . $payloadKey . '.',
                        'fields' => [],
                    ];
                }

                $fields[$column] = $default;
                continue;
            }

            $relatedId = sync_entity_local_id_by_uuid($conn, $relatedEntity, $relatedUuid);
            if ($relatedId === null) {
                return [
                    'ok' => false,
                    'message' => 'Missing dependency for ' . $payloadKey . '.',
                    'fields' => [],
                ];
            }

            $fields[$column] = $relatedId;
        }

        if ($entityType === 'gallery_images') {
            $productId = (int) ($fields['product_id'] ?? 0);
            if ($productId <= 0) {
                return [
                    'ok' => false,
                    'message' => 'Gallery image is missing a product dependency.',
                    'fields' => [],
                ];
            }
            $fields['gallery_id'] = $productId;
        }

        if ($entityType === 'sales') {
            $fields['is_offline'] = 1;
            if (!isset($fields['user_id'])) {
                $fields['user_id'] = 0;
            }
        }

        return [
            'ok' => true,
            'message' => '',
            'fields' => $fields,
        ];
    }
}

if (!function_exists('sync_server_upsert_entity')) {
    function sync_server_upsert_entity(PDO $conn, string $entityType, array $data, string $sourceUpdatedAt): array
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition) {
            return ['status' => 'failed', 'message' => 'Unsupported entity type.', 'server_uuid' => ''];
        }

        $uuid = trim((string) ($data['uuid'] ?? ''));
        if ($uuid === '') {
            return ['status' => 'failed', 'message' => 'Entity uuid is required.', 'server_uuid' => ''];
        }

        $existing = sync_get_entity_row_by_uuid($conn, $entityType, $uuid);
        if (is_array($existing) && sync_server_compare_conflict($existing, $sourceUpdatedAt)) {
            return ['status' => 'conflict', 'message' => 'Server row is newer than the queued change.', 'server_uuid' => $uuid];
        }

        $prepared = sync_server_prepare_columns($conn, $entityType, $data);
        if (!(bool) ($prepared['ok'] ?? false)) {
            return ['status' => 'failed', 'message' => (string) ($prepared['message'] ?? 'Unable to prepare sync fields.'), 'server_uuid' => $uuid];
        }

        $fields = $prepared['fields'];
        $table = $definition['table'];
        $pk = $definition['pk'];

        if (!array_key_exists('created_at', $fields)) {
            $fields['created_at'] = sync_now();
        }
        if (!array_key_exists('updated_at', $fields)) {
            $fields['updated_at'] = $sourceUpdatedAt !== '' ? $sourceUpdatedAt : sync_now();
        } else {
            $fields['updated_at'] = $sourceUpdatedAt !== '' ? $sourceUpdatedAt : $fields['updated_at'];
        }

        if (is_array($existing)) {
            $setParts = [];
            $params = ['uuid_lookup' => $uuid];
            foreach ($fields as $column => $value) {
                if ($column === $pk) {
                    continue;
                }
                $paramKey = 'field_' . $column;
                $setParts[] = $column . ' = :' . $paramKey;
                $params[$paramKey] = $value;
            }

            $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . ' WHERE uuid = :uuid_lookup';
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        } else {
            $columns = [];
            $placeholders = [];
            $params = [];
            foreach ($fields as $column => $value) {
                $columns[] = $column;
                $placeholders[] = ':' . $column;
                $params[$column] = $value;
            }

            $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        }

        return ['status' => 'synced', 'message' => 'Upserted successfully.', 'server_uuid' => $uuid];
    }
}

if (!function_exists('sync_server_delete_entity')) {
    function sync_server_delete_entity(PDO $conn, string $entityType, string $uuid): array
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition) {
            return ['status' => 'failed', 'message' => 'Unsupported entity type.', 'server_uuid' => ''];
        }

        if ($uuid === '') {
            return ['status' => 'failed', 'message' => 'Entity uuid is required.', 'server_uuid' => ''];
        }

        $existing = sync_get_entity_row_by_uuid($conn, $entityType, $uuid);
        if (!$existing) {
            return ['status' => 'synced', 'message' => 'Delete already applied.', 'server_uuid' => $uuid];
        }

        $table = $definition['table'];
        $stmt = $conn->prepare("DELETE FROM {$table} WHERE uuid = :uuid");
        $stmt->execute(['uuid' => $uuid]);

        return ['status' => 'synced', 'message' => 'Deleted successfully.', 'server_uuid' => $uuid];
    }
}

if (!function_exists('sync_server_store_uploaded_media')) {
    function sync_server_store_uploaded_media(string $entityUuid, array $manifest, array $files, array $data): array
    {
        $root = sync_project_root();
        foreach ($manifest as $item) {
            $field = (string) ($item['field'] ?? '');
            $disk = trim((string) ($item['disk'] ?? 'images'), '/');
            if ($field === '' || !isset($files[$field]) || !is_array($files[$field])) {
                return [
                    'ok' => false,
                    'message' => 'Missing uploaded file for ' . $field . '.',
                    'data' => $data,
                ];
            }

            $file = $files[$field];
            $tmp = (string) ($file['tmp_name'] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                return [
                    'ok' => false,
                    'message' => 'Invalid uploaded file for ' . $field . '.',
                    'data' => $data,
                ];
            }

            $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            if ($ext === '') {
                $ext = strtolower((string) pathinfo((string) ($item['original_name'] ?? ''), PATHINFO_EXTENSION));
            }
            $canonicalName = $entityUuid . '_' . $field . ($ext !== '' ? '.' . $ext : '');
            $directory = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $disk);
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }

            $target = $directory . DIRECTORY_SEPARATOR . $canonicalName;
            if (!move_uploaded_file($tmp, $target)) {
                return [
                    'ok' => false,
                    'message' => 'Unable to store uploaded media for ' . $field . '.',
                    'data' => $data,
                ];
            }

            $data[$field] = $canonicalName;
        }

        return [
            'ok' => true,
            'message' => '',
            'data' => $data,
        ];
    }
}

if (!function_exists('sync_server_process_item')) {
    function sync_server_process_item(PDO $conn, array $item): array
    {
        $payload = $item['payload'] ?? [];
        if (!is_array($payload)) {
            return ['status' => 'failed', 'message' => 'Invalid queue payload.', 'server_uuid' => ''];
        }

        $meta = $payload['meta'] ?? [];
        $data = $payload['data'] ?? [];
        $entityType = trim((string) ($item['entity_type'] ?? $meta['entity_type'] ?? ''));
        $entityUuid = trim((string) ($item['entity_uuid'] ?? $meta['entity_uuid'] ?? ''));
        $actionType = trim((string) ($item['action_type'] ?? $meta['action_type'] ?? ''));
        $sourceUpdatedAt = trim((string) ($item['source_updated_at'] ?? $meta['source_updated_at'] ?? ''));

        if ($entityType === '' || $entityUuid === '' || $actionType === '') {
            return ['status' => 'failed', 'message' => 'Queue item metadata is incomplete.', 'server_uuid' => ''];
        }

        if ($actionType === 'delete') {
            return sync_server_delete_entity($conn, $entityType, $entityUuid);
        }

        return sync_server_upsert_entity($conn, $entityType, is_array($data) ? $data : [], $sourceUpdatedAt);
    }
}

if (!function_exists('sync_join_url')) {
    function sync_join_url(string $base, string $path): string
    {
        if ($path === '') {
            return $base;
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('sync_http_json_request')) {
    function sync_http_json_request(string $url, array $payload): array
    {
        $config = sync_config();
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if (($config['token'] ?? '') !== '') {
            $headers[] = 'Authorization: Bearer ' . $config['token'];
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($body)) {
            return ['success' => false, 'status_code' => 0, 'body' => null, 'message' => 'Unable to encode JSON request.'];
        }

        if (!function_exists('curl_init')) {
            return ['success' => false, 'status_code' => 0, 'body' => null, 'message' => 'cURL is required for sync HTTP requests.'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int) ($config['timeout_seconds'] ?? 10),
            CURLOPT_TIMEOUT => (int) ($config['timeout_seconds'] ?? 10),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return ['success' => false, 'status_code' => $statusCode, 'body' => null, 'message' => $error !== '' ? $error : 'Sync HTTP request failed.'];
        }

        $decoded = json_decode((string) $response, true);
        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status_code' => $statusCode,
            'body' => is_array($decoded) ? $decoded : null,
            'message' => is_string($response) ? $response : '',
        ];
    }
}

if (!function_exists('sync_http_multipart_request')) {
    function sync_http_multipart_request(string $url, array $postFields, array $fileFields): array
    {
        $config = sync_config();
        if (!function_exists('curl_init')) {
            return ['success' => false, 'status_code' => 0, 'body' => null, 'message' => 'cURL is required for sync media uploads.'];
        }

        foreach ($fileFields as $field => $path) {
            if (!is_file($path)) {
                return ['success' => false, 'status_code' => 0, 'body' => null, 'message' => 'Missing media file: ' . $field];
            }

            $mime = function_exists('mime_content_type') ? (string) mime_content_type($path) : 'application/octet-stream';
            $postFields[$field] = curl_file_create($path, $mime, basename($path));
        }

        $headers = [
            'Accept: application/json',
        ];
        if (($config['token'] ?? '') !== '') {
            $headers[] = 'Authorization: Bearer ' . $config['token'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int) ($config['timeout_seconds'] ?? 10),
            CURLOPT_TIMEOUT => (int) ($config['timeout_seconds'] ?? 10),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $postFields,
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return ['success' => false, 'status_code' => $statusCode, 'body' => null, 'message' => $error !== '' ? $error : 'Sync media request failed.'];
        }

        $decoded = json_decode((string) $response, true);
        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status_code' => $statusCode,
            'body' => is_array($decoded) ? $decoded : null,
            'message' => is_string($response) ? $response : '',
        ];
    }
}

if (!function_exists('sync_http_get_json_request')) {
    function sync_http_get_json_request(string $url, array $query = []): array
    {
        $config = sync_config();
        $headers = [
            'Accept: application/json',
        ];
        if (($config['token'] ?? '') !== '') {
            $headers[] = 'Authorization: Bearer ' . $config['token'];
        }

        $finalUrl = $url;
        if (!empty($query)) {
            $separator = strpos($url, '?') === false ? '?' : '&';
            $finalUrl .= $separator . http_build_query($query);
        }

        if (!function_exists('curl_init')) {
            return ['success' => false, 'status_code' => 0, 'body' => null, 'message' => 'cURL is required for sync HTTP requests.'];
        }

        $ch = curl_init($finalUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int) ($config['timeout_seconds'] ?? 10),
            CURLOPT_TIMEOUT => (int) ($config['timeout_seconds'] ?? 10),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return ['success' => false, 'status_code' => $statusCode, 'body' => null, 'message' => $error !== '' ? $error : 'Sync HTTP GET request failed.'];
        }

        $decoded = json_decode((string) $response, true);
        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status_code' => $statusCode,
            'body' => is_array($decoded) ? $decoded : null,
            'message' => is_string($response) ? $response : '',
        ];
    }
}

if (!function_exists('sync_download_file_to_temp')) {
    function sync_download_file_to_temp(string $url): array
    {
        $config = sync_config();
        if (!function_exists('curl_init')) {
            return ['success' => false, 'path' => '', 'status_code' => 0, 'message' => 'cURL is required for sync media downloads.'];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'syncpull_');
        if (!is_string($tempPath) || $tempPath === '') {
            return ['success' => false, 'path' => '', 'status_code' => 0, 'message' => 'Unable to allocate a temporary file for download.'];
        }

        $handle = @fopen($tempPath, 'wb');
        if ($handle === false) {
            @unlink($tempPath);
            return ['success' => false, 'path' => '', 'status_code' => 0, 'message' => 'Unable to open a temporary file for download.'];
        }

        $headers = [];
        if (($config['token'] ?? '') !== '') {
            $headers[] = 'Authorization: Bearer ' . $config['token'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => (int) ($config['timeout_seconds'] ?? 10),
            CURLOPT_TIMEOUT => max(15, (int) ($config['timeout_seconds'] ?? 10) * 3),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($handle);

        if ($ok === false || $errno !== 0 || $statusCode < 200 || $statusCode >= 300) {
            @unlink($tempPath);
            return [
                'success' => false,
                'path' => '',
                'status_code' => $statusCode,
                'message' => $error !== '' ? $error : 'Unable to download sync media.',
            ];
        }

        return [
            'success' => true,
            'path' => $tempPath,
            'status_code' => $statusCode,
            'message' => '',
        ];
    }
}

if (!function_exists('sync_ping_server')) {
    function sync_ping_server(): array
    {
        $config = sync_config();
        $url = sync_join_url((string) ($config['server_url'] ?? ''), (string) ($config['ping_endpoint'] ?? '/sync/ping'));
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return ['success' => false, 'message' => 'Sync server URL is not configured.'];
        }

        return sync_http_json_request($url, [
            'device_id' => $config['device_id'],
            'device_name' => $config['device_name'],
            'sent_at' => sync_now(),
        ]);
    }
}

if (!function_exists('sync_build_outbound_item')) {
    function sync_build_outbound_item(array $queueRow): array
    {
        $payload = $queueRow['payload'] ?? [];
        return [
            'queue_id' => (int) ($queueRow['id'] ?? 0),
            'queue_uuid' => (string) ($queueRow['queue_uuid'] ?? ''),
            'entity_type' => (string) ($queueRow['entity_type'] ?? ''),
            'entity_uuid' => (string) ($queueRow['entity_uuid'] ?? ''),
            'action_type' => (string) ($queueRow['action_type'] ?? ''),
            'source_updated_at' => (string) ($queueRow['source_updated_at'] ?? ''),
            'payload' => is_array($payload) ? $payload : [],
        ];
    }
}

if (!function_exists('sync_media_file_paths')) {
    function sync_media_file_paths(array $payload): array
    {
        $paths = [];
        $root = sync_project_root();
        $media = $payload['media'] ?? [];
        if (!is_array($media)) {
            return $paths;
        }

        foreach ($media as $item) {
            $field = (string) ($item['field'] ?? '');
            $disk = trim((string) ($item['disk'] ?? 'images'), '/');
            $relativePath = trim((string) ($item['relative_path'] ?? ''));
            if ($field === '' || $relativePath === '') {
                continue;
            }

            $absolutePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $disk . '/' . $relativePath);
            $paths[$field] = $absolutePath;
        }

        return $paths;
    }
}

if (!function_exists('sync_push_json_batch')) {
    function sync_push_json_batch(array $items): array
    {
        $config = sync_config();
        $url = sync_join_url((string) ($config['server_url'] ?? ''), (string) ($config['push_endpoint'] ?? '/sync/push'));
        return sync_http_json_request($url, [
            'device_id' => $config['device_id'],
            'device_name' => $config['device_name'],
            'sent_at' => sync_now(),
            'items' => $items,
        ]);
    }
}

if (!function_exists('sync_push_media_item')) {
    function sync_push_media_item(array $item): array
    {
        $config = sync_config();
        $url = sync_join_url((string) ($config['server_url'] ?? ''), (string) ($config['push_media_endpoint'] ?? '/sync/push-media'));
        $payload = $item['payload'] ?? [];
        $meta = $payload['meta'] ?? [];
        $data = $payload['data'] ?? [];
        $media = $payload['media'] ?? [];
        $fileFields = sync_media_file_paths($payload);

        return sync_http_multipart_request($url, [
            'device_id' => $config['device_id'],
            'device_name' => $config['device_name'],
            'queue_id' => (int) ($item['queue_id'] ?? 0),
            'queue_uuid' => (string) ($item['queue_uuid'] ?? ''),
            'entity_type' => (string) ($item['entity_type'] ?? ''),
            'entity_uuid' => (string) ($item['entity_uuid'] ?? ''),
            'action_type' => (string) ($item['action_type'] ?? ''),
            'source_updated_at' => (string) ($item['source_updated_at'] ?? ''),
            'meta_json' => json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'data_json' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'media_manifest_json' => json_encode($media, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ], $fileFields);
    }
}

if (!function_exists('sync_pull_from_server')) {
    function sync_pull_from_server(int $afterId, int $limit): array
    {
        $config = sync_config();
        $url = sync_join_url((string) ($config['server_url'] ?? ''), (string) ($config['pull_endpoint'] ?? '/sync/pull'));
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return ['success' => false, 'message' => 'Sync pull URL is not configured.'];
        }

        return sync_http_get_json_request($url, [
            'device_id' => $config['device_id'],
            'device_name' => $config['device_name'],
            'after_id' => max(0, $afterId),
            'limit' => max(1, $limit),
        ]);
    }
}

if (!function_exists('sync_insert_pull_item')) {
    function sync_insert_pull_item(PDO $conn, array $item): bool
    {
        if (!sync_table_exists($conn, 'sync_pull_queue')) {
            return false;
        }

        $payload = $item['payload'] ?? [];
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($payloadJson) || $payloadJson === '') {
            return false;
        }

        $stmt = $conn->prepare(
            'INSERT INTO sync_pull_queue
                (event_id, event_uuid, entity_type, entity_uuid, action_type, payload_json, status, applied, attempts, locked_at, next_attempt_at, last_error, conflict_reason, conflict_detected_at, source_side, source_device_id, source_updated_at, pulled_at, applied_at, created_at, updated_at)
             VALUES
                (:event_id, :event_uuid, :entity_type, :entity_uuid, :action_type, :payload_json, :status, :applied, :attempts, NULL, :next_attempt_at, NULL, NULL, NULL, :source_side, :source_device_id, :source_updated_at, :pulled_at, NULL, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                updated_at = VALUES(updated_at)'
        );

        $now = sync_now();
        return $stmt->execute([
            'event_id' => (int) ($item['event_id'] ?? 0),
            'event_uuid' => (string) ($item['event_uuid'] ?? ''),
            'entity_type' => (string) ($item['entity_type'] ?? ''),
            'entity_uuid' => (string) ($item['entity_uuid'] ?? ''),
            'action_type' => (string) ($item['action_type'] ?? ''),
            'payload_json' => $payloadJson,
            'status' => 'pending',
            'applied' => 0,
            'attempts' => 0,
            'next_attempt_at' => $now,
            'source_side' => (string) ($item['source_side'] ?? ''),
            'source_device_id' => (string) ($item['source_device_id'] ?? ''),
            'source_updated_at' => (string) ($item['source_updated_at'] ?? $now),
            'pulled_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

if (!function_exists('sync_store_pull_items')) {
    function sync_store_pull_items(PDO $conn, array $items, ?int $nextCursor = null): array
    {
        $inserted = 0;
        $state = sync_get_state_row($conn);
        $maxEventId = max(0, (int) ($state['last_pull_cursor_fetched'] ?? 0), (int) ($nextCursor ?? 0));

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $eventId = (int) ($item['event_id'] ?? 0);
            if ($eventId > $maxEventId) {
                $maxEventId = $eventId;
            }

            if (sync_insert_pull_item($conn, $item)) {
                $inserted++;
            }
        }

        sync_update_state($conn, [
            'last_pull_cursor_fetched' => $maxEventId > 0 ? $maxEventId : null,
            'last_pull_at' => sync_now(),
        ]);

        return [
            'inserted' => $inserted,
            'max_event_id' => $maxEventId,
        ];
    }
}

if (!function_exists('sync_compute_pull_backoff_minutes')) {
    function sync_compute_pull_backoff_minutes(int $attempts): int
    {
        if ($attempts <= 1) {
            return 1;
        }
        if ($attempts === 2) {
            return 5;
        }
        if ($attempts === 3) {
            return 15;
        }

        return 60;
    }
}

if (!function_exists('sync_requeue_retryable_pull_failures')) {
    function sync_requeue_retryable_pull_failures(PDO $conn): void
    {
        if (!sync_table_exists($conn, 'sync_pull_queue')) {
            return;
        }

        $config = sync_config();
        $stmt = $conn->prepare(
            "UPDATE sync_pull_queue
             SET status = 'pending', updated_at = :updated_at
             WHERE status = 'failed'
               AND applied = 0
               AND attempts < :max_attempts
               AND (next_attempt_at IS NULL OR next_attempt_at <= :now)"
        );
        $stmt->execute([
            'updated_at' => sync_now(),
            'max_attempts' => (int) ($config['max_attempts'] ?? 10),
            'now' => sync_now(),
        ]);
    }
}

if (!function_exists('sync_claim_pull_items')) {
    function sync_claim_pull_items(PDO $conn, int $limit = 20): array
    {
        if (!sync_table_exists($conn, 'sync_pull_queue')) {
            return [];
        }

        sync_requeue_retryable_pull_failures($conn);

        $limit = max(1, $limit);
        $config = sync_config();
        $staleAt = date('Y-m-d H:i:s', time() - ((int) ($config['lock_stale_minutes'] ?? 10) * 60));

        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare(
                "SELECT *
                 FROM sync_pull_queue
                 WHERE
                    applied = 0
                    AND (
                        (status = 'pending' AND (next_attempt_at IS NULL OR next_attempt_at <= :now))
                        OR (status = 'processing' AND locked_at IS NOT NULL AND locked_at <= :stale_at)
                    )
                 ORDER BY
                    CASE entity_type
                        WHEN 'shipping' THEN 10
                        WHEN 'coupon' THEN 10
                        WHEN 'web_details' THEN 10
                        WHEN 'banner' THEN 20
                        WHEN 'ads' THEN 20
                        ELSE 999
                    END ASC,
                    event_id ASC
                 LIMIT {$limit}"
            );
            $stmt->execute([
                'now' => sync_now(),
                'stale_at' => $staleAt,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                $conn->commit();
                return [];
            }

            $ids = array_map(static fn(array $row): int => (int) $row['id'], $rows);
            $params = [
                'locked_at' => sync_now(),
                'updated_at' => sync_now(),
            ];
            $placeholders = [];
            foreach ($ids as $index => $id) {
                $key = 'id_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }

            $updateSql = "UPDATE sync_pull_queue SET status = 'processing', locked_at = :locked_at, updated_at = :updated_at WHERE id IN (" . implode(', ', $placeholders) . ')';
            $update = $conn->prepare($updateSql);
            $update->execute($params);
            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $e;
        }

        foreach ($rows as &$row) {
            $decoded = json_decode((string) ($row['payload_json'] ?? ''), true);
            $row['payload'] = is_array($decoded) ? $decoded : [];
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('sync_mark_pull_item_applied')) {
    function sync_mark_pull_item_applied(PDO $conn, int $pullId, string $entityType, string $entityUuid, string $appliedAt): bool
    {
        $stmt = $conn->prepare(
            "UPDATE sync_pull_queue
             SET status = 'applied', applied = 1, locked_at = NULL, last_error = NULL, applied_at = :applied_at, updated_at = :updated_at
             WHERE id = :id"
        );
        $ok = $stmt->execute([
            'applied_at' => $appliedAt,
            'updated_at' => sync_now(),
            'id' => $pullId,
        ]);

        if ($ok) {
            sync_update_entity_last_synced_at($conn, $entityType, $entityUuid, $appliedAt);
        }

        return $ok;
    }
}

if (!function_exists('sync_mark_pull_item_issue')) {
    function sync_mark_pull_item_issue(PDO $conn, int $pullId, string $message, string $conflictReason = ''): bool
    {
        $stmt = $conn->prepare('SELECT attempts FROM sync_pull_queue WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $pullId]);
        $attempts = (int) ($stmt->fetchColumn() ?: 0);
        $attempts++;

        $config = sync_config();
        $nextAttemptAt = null;
        if ($attempts < (int) ($config['max_attempts'] ?? 10)) {
            $nextAttemptAt = date('Y-m-d H:i:s', time() + (sync_compute_pull_backoff_minutes($attempts) * 60));
        }

        $update = $conn->prepare(
            "UPDATE sync_pull_queue
             SET status = 'failed',
                 applied = 0,
                 attempts = :attempts,
                 locked_at = NULL,
                 last_error = :last_error,
                 conflict_reason = :conflict_reason,
                 conflict_detected_at = :conflict_detected_at,
                 next_attempt_at = :next_attempt_at,
                 updated_at = :updated_at
             WHERE id = :id"
        );

        $detectedAt = $conflictReason !== '' ? sync_now() : null;
        return $update->execute([
            'attempts' => $attempts,
            'last_error' => mb_substr($message, 0, 1000),
            'conflict_reason' => $conflictReason !== '' ? mb_substr($conflictReason, 0, 1000) : null,
            'conflict_detected_at' => $detectedAt,
            'next_attempt_at' => $nextAttemptAt,
            'updated_at' => sync_now(),
            'id' => $pullId,
        ]);
    }
}

if (!function_exists('sync_mark_local_pushes_superseded')) {
    function sync_mark_local_pushes_superseded(PDO $conn, string $entityType, string $entityUuid, string $reason): int
    {
        if (!sync_table_exists($conn, 'sync_queue') || trim($entityUuid) === '') {
            return 0;
        }

        $stmt = $conn->prepare(
            "UPDATE sync_queue
             SET status = 'superseded',
                 locked_at = NULL,
                 next_attempt_at = NULL,
                 last_error = :last_error,
                 conflict_reason = :conflict_reason,
                 conflict_detected_at = :conflict_detected_at,
                 updated_at = :updated_at
             WHERE entity_type = :entity_type
               AND entity_uuid = :entity_uuid
               AND status IN ('pending', 'processing', 'failed', 'conflict')"
        );
        $stmt->execute([
            'last_error' => mb_substr($reason, 0, 1000),
            'conflict_reason' => mb_substr($reason, 0, 1000),
            'conflict_detected_at' => sync_now(),
            'updated_at' => sync_now(),
            'entity_type' => $entityType,
            'entity_uuid' => $entityUuid,
        ]);

        return $stmt->rowCount();
    }
}

if (!function_exists('sync_apply_local_upsert_entity')) {
    function sync_apply_local_upsert_entity(PDO $conn, string $entityType, array $data, string $sourceUpdatedAt): array
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition) {
            return ['status' => 'failed', 'message' => 'Unsupported entity type.', 'entity_uuid' => ''];
        }

        $uuid = trim((string) ($data['uuid'] ?? ''));
        if ($uuid === '') {
            return ['status' => 'failed', 'message' => 'Entity uuid is required.', 'entity_uuid' => ''];
        }

        $prepared = sync_server_prepare_columns($conn, $entityType, $data);
        if (!(bool) ($prepared['ok'] ?? false)) {
            return ['status' => 'failed', 'message' => (string) ($prepared['message'] ?? 'Unable to prepare pull fields.'), 'entity_uuid' => $uuid];
        }

        $fields = $prepared['fields'];
        $table = $definition['table'];
        $pk = $definition['pk'];
        $existing = sync_get_entity_row_by_uuid($conn, $entityType, $uuid);

        if (!array_key_exists('created_at', $fields)) {
            $fields['created_at'] = is_array($existing) ? (string) ($existing['created_at'] ?? sync_now()) : sync_now();
        }
        $fields['updated_at'] = $sourceUpdatedAt !== '' ? $sourceUpdatedAt : sync_now();

        if (is_array($existing)) {
            $setParts = [];
            $params = ['uuid_lookup' => $uuid];
            foreach ($fields as $column => $value) {
                if ($column === $pk) {
                    continue;
                }
                $paramKey = 'field_' . $column;
                $setParts[] = $column . ' = :' . $paramKey;
                $params[$paramKey] = $value;
            }

            $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . ' WHERE uuid = :uuid_lookup';
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        } else {
            $columns = [];
            $placeholders = [];
            $params = [];
            foreach ($fields as $column => $value) {
                $columns[] = $column;
                $placeholders[] = ':' . $column;
                $params[$column] = $value;
            }

            $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        }

        return ['status' => 'synced', 'message' => 'Pulled update applied.', 'entity_uuid' => $uuid];
    }
}

if (!function_exists('sync_apply_local_delete_entity')) {
    function sync_apply_local_delete_entity(PDO $conn, string $entityType, string $entityUuid): array
    {
        $definition = sync_entity_definition($entityType);
        if (!$definition) {
            return ['status' => 'failed', 'message' => 'Unsupported entity type.', 'entity_uuid' => ''];
        }

        if ($entityUuid === '') {
            return ['status' => 'failed', 'message' => 'Entity uuid is required.', 'entity_uuid' => ''];
        }

        $existing = sync_get_entity_row_by_uuid($conn, $entityType, $entityUuid);
        if (!$existing) {
            return ['status' => 'synced', 'message' => 'Delete already applied locally.', 'entity_uuid' => $entityUuid];
        }

        $table = $definition['table'];
        $stmt = $conn->prepare("DELETE FROM {$table} WHERE uuid = :uuid");
        $stmt->execute(['uuid' => $entityUuid]);

        return ['status' => 'synced', 'message' => 'Pulled delete applied.', 'entity_uuid' => $entityUuid];
    }
}

if (!function_exists('sync_store_pulled_media')) {
    function sync_store_pulled_media(array $payload, array $data): array
    {
        $root = sync_project_root();
        $manifest = $payload['media'] ?? [];
        if (!is_array($manifest) || empty($manifest)) {
            return ['ok' => true, 'message' => '', 'data' => $data];
        }

        foreach ($manifest as $item) {
            $field = trim((string) ($item['field'] ?? ''));
            $disk = trim((string) ($item['disk'] ?? 'images'), '/');
            $relativePath = trim((string) ($item['relative_path'] ?? $item['file_name'] ?? ''));
            $fileUrl = trim((string) ($item['file_url'] ?? ''));
            $expectedHash = strtolower(trim((string) ($item['file_hash'] ?? '')));
            $expectedSize = (int) ($item['file_size'] ?? $item['size'] ?? 0);

            if ($field === '' || $relativePath === '' || $fileUrl === '') {
                return ['ok' => false, 'message' => 'Pulled media metadata is incomplete.', 'data' => $data];
            }

            $download = sync_download_file_to_temp($fileUrl);
            if (!(bool) ($download['success'] ?? false)) {
                return ['ok' => false, 'message' => (string) ($download['message'] ?? 'Unable to download pulled media.'), 'data' => $data];
            }

            $tempPath = (string) ($download['path'] ?? '');
            if ($tempPath === '' || !is_file($tempPath)) {
                return ['ok' => false, 'message' => 'Downloaded media file is missing.', 'data' => $data];
            }

            $actualSize = (int) @filesize($tempPath);
            if ($expectedSize > 0 && $actualSize !== $expectedSize) {
                @unlink($tempPath);
                return ['ok' => false, 'message' => 'Downloaded media size check failed.', 'data' => $data];
            }

            if ($expectedHash !== '') {
                $actualHash = strtolower((string) @hash_file('sha256', $tempPath));
                if ($actualHash === '' || $actualHash !== $expectedHash) {
                    @unlink($tempPath);
                    return ['ok' => false, 'message' => 'Downloaded media hash check failed.', 'data' => $data];
                }
            }

            $targetDirectory = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $disk);
            if (!is_dir($targetDirectory) && !@mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                @unlink($tempPath);
                return ['ok' => false, 'message' => 'Unable to prepare media directory.', 'data' => $data];
            }

            $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $targetParent = dirname($targetPath);
            if (!is_dir($targetParent) && !@mkdir($targetParent, 0775, true) && !is_dir($targetParent)) {
                @unlink($tempPath);
                return ['ok' => false, 'message' => 'Unable to prepare target media path.', 'data' => $data];
            }

            if (is_file($targetPath)) {
                @unlink($targetPath);
            }

            $moved = @rename($tempPath, $targetPath);
            if (!$moved) {
                $moved = @copy($tempPath, $targetPath);
                @unlink($tempPath);
            }

            if (!$moved) {
                return ['ok' => false, 'message' => 'Unable to store pulled media locally.', 'data' => $data];
            }

            $data[$field] = $relativePath;
        }

        return ['ok' => true, 'message' => '', 'data' => $data];
    }
}

if (!function_exists('sync_apply_pull_item')) {
    function sync_apply_pull_item(PDO $conn, array $row): array
    {
        $payload = $row['payload'] ?? [];
        if (!is_array($payload)) {
            $payload = json_decode((string) ($row['payload_json'] ?? ''), true) ?: [];
        }

        $meta = $payload['meta'] ?? [];
        $entityType = trim((string) ($row['entity_type'] ?? $meta['entity_type'] ?? ''));
        $entityUuid = trim((string) ($row['entity_uuid'] ?? $meta['entity_uuid'] ?? ''));
        $actionType = trim((string) ($row['action_type'] ?? $meta['action_type'] ?? ''));
        $sourceSide = trim((string) ($row['source_side'] ?? $meta['source_side'] ?? ''));
        $sourceDeviceId = trim((string) ($row['source_device_id'] ?? $meta['source_device_id'] ?? ''));
        $sourceUpdatedAt = trim((string) ($row['source_updated_at'] ?? $meta['source_updated_at'] ?? ''));
        $eventUuid = trim((string) ($row['event_uuid'] ?? $meta['event_uuid'] ?? ''));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if ($entityType === '' || $entityUuid === '' || $actionType === '') {
            return ['status' => 'failed', 'message' => 'Pull item metadata is incomplete.', 'entity_uuid' => $entityUuid];
        }

        if ((int) ($row['applied'] ?? 0) === 1) {
            return ['status' => 'applied', 'message' => 'Pull item already applied.', 'entity_uuid' => $entityUuid];
        }

        if ($eventUuid !== '' && !empty($row['id'])) {
            $stmt = $conn->prepare('SELECT applied FROM sync_pull_queue WHERE event_uuid = :event_uuid LIMIT 1');
            $stmt->execute(['event_uuid' => $eventUuid]);
            if ((int) ($stmt->fetchColumn() ?: 0) === 1) {
                return ['status' => 'applied', 'message' => 'Pull item already applied.', 'entity_uuid' => $entityUuid];
            }
        }

        if ($sourceSide === 'client' && $sourceDeviceId === (string) (sync_config()['device_id'] ?? '')) {
            return ['status' => 'applied', 'message' => 'Ignored pull event that originated from this device.', 'entity_uuid' => $entityUuid];
        }

        if (!sync_entity_pull_enabled($entityType)) {
            return ['status' => 'applied', 'message' => 'Ignored out-of-scope pull entity.', 'entity_uuid' => $entityUuid];
        }

        $supersedeReason = 'A newer live-server update replaced pending local changes for this record.';
        sync_mark_local_pushes_superseded($conn, $entityType, $entityUuid, $supersedeReason);

        if ($actionType !== 'delete') {
            $mediaResult = sync_store_pulled_media($payload, $data);
            if (!(bool) ($mediaResult['ok'] ?? false)) {
                return ['status' => 'failed', 'message' => (string) ($mediaResult['message'] ?? 'Unable to store pulled media locally.'), 'entity_uuid' => $entityUuid];
            }
            $data = $mediaResult['data'];
        }

        if ($actionType === 'delete') {
            return sync_apply_local_delete_entity($conn, $entityType, $entityUuid);
        }

        return sync_apply_local_upsert_entity($conn, $entityType, $data, $sourceUpdatedAt);
    }
}

if (!function_exists('sync_advance_pull_applied_cursor')) {
    function sync_advance_pull_applied_cursor(PDO $conn): int
    {
        $state = sync_get_state_row($conn);
        $cursor = (int) ($state['last_pull_cursor_applied'] ?? 0);
        if (!sync_table_exists($conn, 'sync_pull_queue')) {
            return $cursor;
        }

        $stmt = $conn->prepare(
            'SELECT event_id, applied
             FROM sync_pull_queue
             WHERE event_id > :cursor
             ORDER BY event_id ASC'
        );
        $stmt->execute(['cursor' => $cursor]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $nextCursor = $cursor;
        foreach ($rows as $row) {
            if ((int) ($row['applied'] ?? 0) !== 1) {
                break;
            }

            $nextCursor = (int) ($row['event_id'] ?? $nextCursor);
        }

        if ($nextCursor !== $cursor) {
            sync_update_state($conn, [
                'last_pull_cursor_applied' => $nextCursor,
            ]);
        }

        return $nextCursor;
    }
}

if (!function_exists('sync_process_pull_queue')) {
    function sync_process_pull_queue(PDO $conn, int $limit = 20): array
    {
        $result = [
            'processed' => 0,
            'applied' => 0,
            'failed' => 0,
            'results' => [],
        ];

        if (!sync_pull_schema_ready($conn)) {
            return $result;
        }

        $rows = sync_claim_pull_items($conn, $limit);
        foreach ($rows as $row) {
            $outcome = sync_apply_pull_item($conn, $row);
            $status = (string) ($outcome['status'] ?? 'failed');
            $entityType = (string) ($row['entity_type'] ?? '');
            $entityUuid = (string) ($row['entity_uuid'] ?? '');
            $appliedAt = sync_now();

            if ($status === 'synced' || $status === 'applied') {
                sync_mark_pull_item_applied($conn, (int) $row['id'], $entityType, $entityUuid, $appliedAt);
                $result['applied']++;
            } else {
                sync_mark_pull_item_issue($conn, (int) $row['id'], (string) ($outcome['message'] ?? 'Pull apply failed.'));
                $result['failed']++;
            }

            $result['processed']++;
            $result['results'][] = [
                'event_id' => (int) ($row['event_id'] ?? 0),
                'event_uuid' => (string) ($row['event_uuid'] ?? ''),
                'entity_type' => $entityType,
                'entity_uuid' => $entityUuid,
                'status' => $status,
                'message' => (string) ($outcome['message'] ?? ''),
            ];
        }

        sync_advance_pull_applied_cursor($conn);
        return $result;
    }
}

if (!function_exists('sync_client_status_snapshot')) {
    function sync_client_status_snapshot(PDO $conn, bool $includePing = true): array
    {
        $config = sync_config();
        $snapshot = [
            'enabled' => sync_is_enabled(),
            'role' => $config['role'],
            'device_id' => $config['device_id'],
            'device_name' => $config['device_name'],
            'online' => false,
            'last_sync_attempt' => null,
            'last_successful_sync' => null,
            'last_push_at' => null,
            'last_pull_at' => null,
            'overwrite_notice' => null,
            'counts' => [
                'pending_push' => 0,
                'pending_pull' => 0,
                'failed_push' => 0,
                'failed_pull' => 0,
                'conflict' => 0,
                'superseded' => 0,
                'push_processing' => 0,
                'pull_processing' => 0,
                'failed' => 0,
                'processing' => 0,
                'synced' => 0,
            ],
        ];

        if (!sync_schema_ready($conn)) {
            return $snapshot;
        }

        $countsStmt = $conn->query('SELECT status, COUNT(*) AS total FROM sync_queue GROUP BY status');
        foreach ($countsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $status = (string) ($row['status'] ?? '');
            $total = (int) ($row['total'] ?? 0);
            if ($status === 'pending') {
                $snapshot['counts']['pending_push'] = $total;
            } elseif ($status === 'failed') {
                $snapshot['counts']['failed_push'] = $total;
            } elseif ($status === 'processing') {
                $snapshot['counts']['push_processing'] = $total;
            } elseif ($status === 'conflict') {
                $snapshot['counts']['conflict'] = $total;
            } elseif ($status === 'superseded') {
                $snapshot['counts']['superseded'] = $total;
            } elseif ($status === 'synced') {
                $snapshot['counts']['synced'] = $total;
            }
        }

        if (sync_table_exists($conn, 'sync_pull_queue')) {
            $pullCountsStmt = $conn->query('SELECT status, COUNT(*) AS total FROM sync_pull_queue WHERE applied = 0 GROUP BY status');
            foreach ($pullCountsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $status = (string) ($row['status'] ?? '');
                $total = (int) ($row['total'] ?? 0);
                if ($status === 'pending') {
                    $snapshot['counts']['pending_pull'] = $total;
                } elseif ($status === 'failed') {
                    $snapshot['counts']['failed_pull'] = $total;
                } elseif ($status === 'processing') {
                    $snapshot['counts']['pull_processing'] = $total;
                }
            }
        }

        $snapshot['counts']['failed'] = $snapshot['counts']['failed_push'] + $snapshot['counts']['failed_pull'];
        $snapshot['counts']['processing'] = $snapshot['counts']['push_processing'] + $snapshot['counts']['pull_processing'];

        if (sync_table_exists($conn, 'devices')) {
            $deviceStmt = $conn->prepare('SELECT last_seen_at, last_sync_at FROM devices WHERE device_id = :device_id LIMIT 1');
            $deviceStmt->execute(['device_id' => $config['device_id']]);
            $device = $deviceStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $snapshot['last_sync_attempt'] = $device['last_seen_at'] ?? null;
            $snapshot['last_successful_sync'] = $device['last_sync_at'] ?? null;
        }

        $state = sync_get_state_row($conn);
        $snapshot['last_push_at'] = $state['last_push_at'] ?? null;
        $snapshot['last_pull_at'] = $state['last_pull_at'] ?? null;

        if ($snapshot['counts']['superseded'] > 0) {
            $snapshot['overwrite_notice'] = $snapshot['counts']['superseded'] . ' local change(s) were overwritten by newer live updates.';
        }

        if ($includePing && sync_is_client()) {
            $ping = sync_ping_server();
            $snapshot['online'] = (bool) ($ping['success'] ?? false);
            $snapshot['ping'] = $ping['body'] ?? null;
            if ($snapshot['online']) {
                sync_ensure_device_row($conn);
            }
        }

        return $snapshot;
    }
}

if (!function_exists('sync_server_status_snapshot')) {
    function sync_server_status_snapshot(PDO $conn): array
    {
        $config = sync_config();
        $snapshot = [
            'enabled' => sync_is_enabled(),
            'role' => $config['role'],
            'device_id' => $config['device_id'],
            'device_name' => $config['device_name'],
            'online' => true,
            'last_sync_attempt' => null,
            'last_successful_sync' => null,
            'source_device_id' => null,
            'source_device_name' => null,
            'counts' => [
                'pending' => 0,
                'failed' => 0,
                'conflict' => 0,
                'processing' => 0,
                'synced' => 0,
                'devices' => 0,
            ],
        ];

        if (!sync_schema_ready($conn)) {
            $snapshot['online'] = false;
            return $snapshot;
        }

        if (sync_table_exists($conn, 'devices')) {
            $devicesStmt = $conn->prepare('SELECT COUNT(*) FROM devices WHERE device_id <> :device_id');
            $devicesStmt->execute(['device_id' => $config['device_id']]);
            $snapshot['counts']['devices'] = (int) $devicesStmt->fetchColumn();

            $latestStmt = $conn->prepare(
                'SELECT device_id, device_name, last_seen_at, last_sync_at
                 FROM devices
                 WHERE device_id <> :device_id
                 ORDER BY COALESCE(last_sync_at, last_seen_at) DESC, id DESC
                 LIMIT 1'
            );
            $latestStmt->execute(['device_id' => $config['device_id']]);
            $latest = $latestStmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $snapshot['source_device_id'] = $latest['device_id'] ?? null;
            $snapshot['source_device_name'] = $latest['device_name'] ?? null;
            $snapshot['last_sync_attempt'] = $latest['last_seen_at'] ?? null;
            $snapshot['last_successful_sync'] = $latest['last_sync_at'] ?? null;
        }

        if (sync_table_exists($conn, 'sync_receipts')) {
            $countsStmt = $conn->query('SELECT status, COUNT(*) AS total FROM sync_receipts GROUP BY status');
            foreach ($countsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $status = (string) ($row['status'] ?? '');
                if (isset($snapshot['counts'][$status])) {
                    $snapshot['counts'][$status] = (int) ($row['total'] ?? 0);
                }
            }
        }

        return $snapshot;
    }
}

if (!function_exists('sync_status_snapshot')) {
    function sync_status_snapshot(PDO $conn, bool $includePing = true): array
    {
        if (sync_is_server()) {
            return sync_server_status_snapshot($conn);
        }

        return sync_client_status_snapshot($conn, $includePing);
    }
}

if (!function_exists('sync_run_push_phase')) {
    function sync_run_push_phase(PDO $conn, int $batchSize): array
    {
        $result = [
            'processed' => 0,
            'synced' => 0,
            'failed' => 0,
            'conflict' => 0,
            'results' => [],
        ];

        $rows = sync_claim_queue_items($conn, $batchSize);
        if (empty($rows)) {
            return $result;
        }

        $mediaItems = [];
        $jsonItems = [];
        foreach ($rows as $row) {
            $outbound = sync_build_outbound_item($row);
            $payload = $outbound['payload'] ?? [];
            $media = $payload['media'] ?? [];
            if (is_array($media) && !empty($media)) {
                $mediaItems[] = $outbound;
            } else {
                $jsonItems[] = $outbound;
            }
        }

        if (!empty($jsonItems)) {
            $response = sync_push_json_batch($jsonItems);
            if (!(bool) ($response['success'] ?? false) || !is_array($response['body'] ?? null)) {
                foreach ($jsonItems as $item) {
                    sync_mark_queue_issue($conn, (int) $item['queue_id'], 'failed', (string) ($response['message'] ?? 'JSON sync request failed.'), 1);
                    $result['failed']++;
                }
            } else {
                $remoteResults = $response['body']['results'] ?? [];
                $handledQueueIds = [];
                foreach ($remoteResults as $remoteResult) {
                    $queueId = (int) ($remoteResult['queue_id'] ?? 0);
                    $queueStatus = (string) ($remoteResult['status'] ?? 'failed');
                    $message = (string) ($remoteResult['message'] ?? '');
                    $entityType = (string) ($remoteResult['entity_type'] ?? '');
                    $entityUuid = (string) ($remoteResult['entity_uuid'] ?? '');
                    if ($queueId > 0) {
                        $handledQueueIds[$queueId] = true;
                    }

                    if ($queueStatus === 'synced') {
                        sync_mark_queue_synced($conn, $queueId, $entityType, $entityUuid);
                        $result['synced']++;
                    } elseif ($queueStatus === 'conflict') {
                        sync_mark_queue_issue($conn, $queueId, 'conflict', $message !== '' ? $message : 'Sync conflict.', 1);
                        $result['conflict']++;
                    } else {
                        sync_mark_queue_issue($conn, $queueId, 'failed', $message !== '' ? $message : 'Sync failed.', 1);
                        $result['failed']++;
                    }

                    $result['results'][] = $remoteResult;
                }

                foreach ($jsonItems as $item) {
                    $queueId = (int) ($item['queue_id'] ?? 0);
                    if ($queueId <= 0 || isset($handledQueueIds[$queueId])) {
                        continue;
                    }

                    sync_mark_queue_issue($conn, $queueId, 'failed', 'Sync response did not include this queue item.', 1);
                    $result['failed']++;
                }
            }
        }

        foreach ($mediaItems as $item) {
            $response = sync_push_media_item($item);
            if (!(bool) ($response['success'] ?? false) || !is_array($response['body'] ?? null)) {
                sync_mark_queue_issue($conn, (int) $item['queue_id'], 'failed', (string) ($response['message'] ?? 'Media sync request failed.'), 1);
                $result['failed']++;
                continue;
            }

            $remoteResult = $response['body']['result'] ?? null;
            if (!is_array($remoteResult)) {
                sync_mark_queue_issue($conn, (int) $item['queue_id'], 'failed', 'Invalid media sync response.', 1);
                $result['failed']++;
                continue;
            }

            $queueStatus = (string) ($remoteResult['status'] ?? 'failed');
            if ($queueStatus === 'synced') {
                sync_mark_queue_synced($conn, (int) $item['queue_id'], (string) $item['entity_type'], (string) $item['entity_uuid']);
                $result['synced']++;
            } elseif ($queueStatus === 'conflict') {
                sync_mark_queue_issue($conn, (int) $item['queue_id'], 'conflict', (string) ($remoteResult['message'] ?? 'Sync conflict.'), 1);
                $result['conflict']++;
            } else {
                sync_mark_queue_issue($conn, (int) $item['queue_id'], 'failed', (string) ($remoteResult['message'] ?? 'Media sync failed.'), 1);
                $result['failed']++;
            }

            $result['results'][] = $remoteResult;
        }

        $result['processed'] = count($rows);
        return $result;
    }
}

if (!function_exists('sync_run_pull_fetch_phase')) {
    function sync_run_pull_fetch_phase(PDO $conn, int $batchSize): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'fetched' => 0,
            'stored' => 0,
            'next_cursor' => 0,
            'results' => [],
        ];

        if (!sync_pull_schema_ready($conn)) {
            $result['message'] = 'Pull sync schema is missing.';
            return $result;
        }

        $state = sync_get_state_row($conn);
        $afterId = max(0, (int) ($state['last_pull_cursor_applied'] ?? 0));
        $response = sync_pull_from_server($afterId, $batchSize);
        if (!(bool) ($response['success'] ?? false) || !is_array($response['body'] ?? null)) {
            $result['message'] = (string) ($response['message'] ?? 'Unable to fetch live pull events.');
            return $result;
        }

        $body = $response['body'];
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];
        $store = sync_store_pull_items($conn, $items, (int) ($body['next_cursor'] ?? $afterId));

        $result['success'] = true;
        $result['fetched'] = count($items);
        $result['stored'] = (int) ($store['inserted'] ?? 0);
        $result['next_cursor'] = (int) ($store['max_event_id'] ?? 0);
        $result['results'] = $items;
        $result['message'] = $result['fetched'] > 0
            ? 'Fetched ' . $result['fetched'] . ' live update(s).'
            : 'No new live updates to pull.';

        return $result;
    }
}

if (!function_exists('sync_client_run')) {
    function sync_client_run(PDO $conn, array $options = []): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'processed' => 0,
            'synced' => 0,
            'failed' => 0,
            'conflict' => 0,
            'online' => false,
            'push' => [
                'processed' => 0,
                'synced' => 0,
                'failed' => 0,
                'conflict' => 0,
                'results' => [],
            ],
            'pull' => [
                'fetched' => 0,
                'stored' => 0,
                'processed' => 0,
                'applied' => 0,
                'failed' => 0,
                'results' => [],
            ],
            'results' => [],
        ];

        if (!sync_is_enabled() || !sync_is_client() || !sync_schema_ready($conn)) {
            $result['message'] = 'Sync is disabled, not configured for client mode, or the schema is missing.';
            return $result;
        }

        sync_ensure_device_row($conn);
        sync_ensure_state_row($conn);
        $ping = sync_ping_server();
        $result['online'] = (bool) ($ping['success'] ?? false);
        if (!$result['online']) {
            $result['message'] = (string) ($ping['message'] ?? 'Unable to reach sync server.');
            sync_ensure_device_row($conn);
            return $result;
        }

        $batchSize = (int) ($options['batch_size'] ?? sync_config()['batch_size'] ?? 20);
        $push = sync_run_push_phase($conn, $batchSize);
        $result['push'] = $push;
        $result['processed'] += (int) ($push['processed'] ?? 0);
        $result['synced'] += (int) ($push['synced'] ?? 0);
        $result['failed'] += (int) ($push['failed'] ?? 0);
        $result['conflict'] += (int) ($push['conflict'] ?? 0);
        $result['results'] = array_merge($result['results'], $push['results'] ?? []);
        sync_update_state($conn, [
            'last_push_at' => sync_now(),
        ]);

        $pullFetch = sync_run_pull_fetch_phase($conn, $batchSize);
        $result['pull']['fetched'] = (int) ($pullFetch['fetched'] ?? 0);
        $result['pull']['stored'] = (int) ($pullFetch['stored'] ?? 0);

        $pullApply = [
            'processed' => 0,
            'applied' => 0,
            'failed' => 0,
            'results' => [],
        ];

        if ((bool) ($pullFetch['success'] ?? false)) {
            $pullApply = sync_process_pull_queue($conn, $batchSize);
            sync_update_state($conn, [
                'last_pull_at' => sync_now(),
            ]);
        } else {
            $result['failed']++;
        }

        $result['pull']['processed'] = (int) ($pullApply['processed'] ?? 0);
        $result['pull']['applied'] = (int) ($pullApply['applied'] ?? 0);
        $result['pull']['failed'] = (int) ($pullApply['failed'] ?? 0) + ((bool) ($pullFetch['success'] ?? false) ? 0 : 1);
        $result['pull']['results'] = $pullApply['results'] ?? [];

        $result['processed'] += (int) ($pullApply['processed'] ?? 0);
        $result['synced'] += (int) ($pullApply['applied'] ?? 0);
        $result['failed'] += (int) ($pullApply['failed'] ?? 0);
        $result['results'] = array_merge($result['results'], $pullApply['results'] ?? []);

        $noQueueWork = $result['push']['processed'] === 0
            && $result['pull']['fetched'] === 0
            && $result['pull']['processed'] === 0;

        $result['success'] = $result['failed'] === 0 && $result['conflict'] === 0 && (bool) ($pullFetch['success'] ?? false);

        if (!$pullFetch['success']) {
            $result['message'] = (string) ($pullFetch['message'] ?? 'Unable to fetch live updates.');
        } elseif ($result['conflict'] > 0 || $result['failed'] > 0) {
            $result['message'] = 'Sync completed with issues. Review failed, conflict, or superseded items.';
        } elseif ($noQueueWork) {
            $result['message'] = 'No pending push items or new live pull updates.';
        } else {
            $result['message'] = 'Sync run completed. Local changes were pushed first, then live updates were pulled.';
        }

        if ($result['success']) {
            sync_mark_device_last_sync($conn);
        } else {
            sync_ensure_device_row($conn);
        }

        return $result;
    }
}

if (!function_exists('sync_spawn_runner')) {
    function sync_spawn_runner(): bool
    {
        $script = sync_project_root() . DIRECTORY_SEPARATOR . 'sync_runner.php';
        if (!is_file($script)) {
            return false;
        }

        $phpBinary = PHP_BINARY;
        if (!is_file($phpBinary)) {
            $phpBinary = 'php';
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            @pclose(@popen('start /B "" "' . $phpBinary . '" "' . $script . '" >NUL 2>&1', 'r'));
            return true;
        }

        @exec('"' . $phpBinary . '" "' . $script . '" >/dev/null 2>&1 &');
        return true;
    }
}
