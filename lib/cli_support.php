<?php

declare(strict_types=1);

function app_cli_project_root(): string
{
    return dirname(__DIR__);
}

function app_cli_command_definitions(): array
{
    return [
        'doctor' => [
            'safety' => 'SAFE',
            'usage' => 'php bolakaz doctor',
            'description' => 'Checks .env, dependencies, database connectivity, migration state, and sync readiness.',
        ],
        'setup' => [
            'safety' => 'SAFE',
            'usage' => 'php bolakaz setup --role=client',
            'description' => 'Prepares Doctrine metadata, runs pending migrations, and optionally seeds minimum starter data.',
        ],
        'migrate' => [
            'safety' => 'SAFE',
            'usage' => 'php bolakaz migrate',
            'description' => 'Runs only pending tracked Doctrine migrations and adopts older databases into migration tracking.',
        ],
        'migrate:status' => [
            'safety' => 'SAFE',
            'usage' => 'php bolakaz migrate:status',
            'description' => 'Shows executed and pending migration versions without changing data.',
        ],
        'migrate:fresh' => [
            'safety' => 'DANGEROUS',
            'usage' => 'php bolakaz migrate:fresh --force',
            'description' => 'Drops application tables, rebuilds from tracked migrations, and only seeds when asked.',
        ],
        'seed:minimum' => [
            'safety' => 'SAFE',
            'usage' => 'php bolakaz seed:minimum',
            'description' => 'Creates or refreshes the minimum starter data set for a fresh install.',
        ],
    ];
}

function app_cli_banner(string $command): void
{
    $definitions = app_cli_command_definitions();
    $definition = $definitions[$command] ?? null;
    if (!is_array($definition)) {
        return;
    }

    echo 'Bolakaz CLI' . PHP_EOL;
    echo 'Command: ' . $command . ' [' . $definition['safety'] . ']' . PHP_EOL;
    echo $definition['description'] . PHP_EOL . PHP_EOL;
}

function app_cli_report(string $status, string $message, ?string $hint = null): void
{
    echo '[' . strtoupper($status) . '] ' . $message . PHP_EOL;
    if ($hint !== null && trim($hint) !== '') {
        echo '       Fix: ' . trim($hint) . PHP_EOL;
    }
}

function app_cli_output_block(string $text): void
{
    $trimmed = trim($text);
    if ($trimmed === '') {
        return;
    }

    foreach (preg_split("/\r\n|\n|\r/", $trimmed) as $line) {
        echo '       ' . rtrim((string) $line) . PHP_EOL;
    }
}

function app_cli_env_file_path(string $root): string
{
    return $root . DIRECTORY_SEPARATOR . '.env';
}

function app_cli_example_env_file_path(string $root): string
{
    return $root . DIRECTORY_SEPARATOR . '.env.example';
}

function app_cli_parse_env_file(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return [];
    }

    $values = [];
    foreach ($lines as $index => $line) {
        $line = (string) $line;
        if ($index === 0) {
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;
        }

        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        if (str_starts_with($trimmed, 'export ')) {
            $trimmed = trim(substr($trimmed, 7));
        }

        $delimiterPos = strpos($trimmed, '=');
        if ($delimiterPos === false) {
            continue;
        }

        $key = trim(substr($trimmed, 0, $delimiterPos));
        if ($key === '') {
            continue;
        }

        $rawValue = trim(substr($trimmed, $delimiterPos + 1));
        if ($rawValue === '') {
            $values[$key] = '';
            continue;
        }

        $quote = $rawValue[0];
        if (($quote === '"' || $quote === "'") && substr($rawValue, -1) === $quote) {
            $rawValue = substr($rawValue, 1, -1);
            if ($quote === '"') {
                $rawValue = stripcslashes($rawValue);
            }
        }

        $values[$key] = $rawValue;
    }

    return $values;
}

function app_cli_env_value(array $env, string $key, string $default = ''): string
{
    $runtimeValue = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($runtimeValue !== false && $runtimeValue !== null && trim((string) $runtimeValue) !== '') {
        return trim((string) $runtimeValue);
    }

    if (array_key_exists($key, $env) && trim((string) $env[$key]) !== '') {
        return trim((string) $env[$key]);
    }

    return $default;
}

function app_cli_env_bool(array $env, string $key, bool $default = false): bool
{
    $value = strtolower(app_cli_env_value($env, $key, $default ? '1' : '0'));
    if ($value === '') {
        return $default;
    }

    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function app_cli_has_flag(array $args, string $flag): bool
{
    return in_array($flag, $args, true);
}

function app_cli_option_value(array $args, string $option): ?string
{
    $prefix = $option . '=';
    foreach ($args as $index => $arg) {
        if (strpos($arg, $prefix) === 0) {
            return trim((string) substr($arg, strlen($prefix)));
        }

        if ($arg === $option && array_key_exists($index + 1, $args)) {
            return trim((string) $args[$index + 1]);
        }
    }

    return null;
}

function app_cli_vendor_ready(string $root): bool
{
    return is_file($root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
}

function app_cli_doctrine_binary(string $root): ?string
{
    $paths = [
        $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'doctrine-migrations',
        $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'doctrine-migrations.bat',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function app_cli_connect(array $env): PDO
{
    $host = app_cli_env_value($env, 'DB_HOST', '127.0.0.1');
    $port = app_cli_env_value($env, 'DB_PORT', '3306');
    $name = app_cli_env_value($env, 'DB_NAME', 'bolakaz');
    $charset = app_cli_env_value($env, 'DB_CHARSET', 'utf8mb4');
    $user = app_cli_env_value($env, 'DB_USER', 'root');
    $pass = app_cli_env_value($env, 'DB_PASS', '');

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $host,
        $port,
        $name,
        $charset
    );

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function app_cli_table_exists(PDO $conn, string $table): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name LIMIT 1');
    $stmt->execute(['table_name' => $table]);
    return (bool) $stmt->fetchColumn();
}

function app_cli_column_exists(PDO $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name LIMIT 1');
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);
    return (bool) $stmt->fetchColumn();
}

function app_cli_index_exists(PDO $conn, string $table, string $index): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :table_name AND index_name = :index_name LIMIT 1');
    $stmt->execute([
        'table_name' => $table,
        'index_name' => $index,
    ]);
    return (bool) $stmt->fetchColumn();
}

function app_cli_table_count(PDO $conn, string $table): int
{
    if (!app_cli_table_exists($conn, $table)) {
        return 0;
    }

    $result = $conn->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`');
    return (int) ($result ? $result->fetchColumn() : 0);
}

function app_cli_app_tables(): array
{
    return [
        'variant_option_values',
        'product_legacy_map',
        'product_variants',
        'attribute_values',
        'attributes',
        'offline_payments',
        'details',
        'sales',
        'gallery_images',
        'gallery',
        'cart',
        'products_v2',
        'products',
        'category',
        'banner',
        'ads',
        'shippings',
        'coupons',
        'web_details',
        'users',
        'newsletter',
        'item_rating',
        'devices',
        'sync_queue',
        'sync_receipts',
        'sync_outbox',
        'sync_pull_queue',
        'sync_state',
        'doctrine_migration_versions',
    ];
}

function app_cli_existing_app_tables(PDO $conn): array
{
    $existing = [];
    foreach (app_cli_app_tables() as $table) {
        if (app_cli_table_exists($conn, $table)) {
            $existing[] = $table;
        }
    }

    return $existing;
}

function app_cli_has_existing_schema(PDO $conn): bool
{
    foreach (app_cli_existing_app_tables($conn) as $table) {
        if ($table !== 'doctrine_migration_versions') {
            return true;
        }
    }

    return false;
}

function app_cli_available_migrations(string $root): array
{
    $pattern = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . 'Version*.php';
    $files = glob($pattern) ?: [];
    sort($files, SORT_NATURAL);

    $versions = [];
    foreach ($files as $file) {
        $baseName = pathinfo($file, PATHINFO_FILENAME);
        if (!preg_match('/^Version\d+$/', $baseName)) {
            continue;
        }
        $versions[] = 'Bolakaz\\Migrations\\' . $baseName;
    }

    return $versions;
}

function app_cli_executed_migrations(PDO $conn): array
{
    if (!app_cli_table_exists($conn, 'doctrine_migration_versions')) {
        return [];
    }

    $rows = $conn->query('SELECT version FROM doctrine_migration_versions ORDER BY version ASC');
    $versions = [];
    if ($rows) {
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $version = trim((string) ($row['version'] ?? ''));
            if ($version !== '') {
                $versions[] = $version;
            }
        }
    }

    return $versions;
}

function app_cli_migration_status_data(string $root, PDO $conn): array
{
    $available = app_cli_available_migrations($root);
    $metadataExists = app_cli_table_exists($conn, 'doctrine_migration_versions');
    $executed = $metadataExists ? app_cli_executed_migrations($conn) : [];
    $pending = array_values(array_diff($available, $executed));

    return [
        'available' => $available,
        'executed' => $executed,
        'pending' => $pending,
        'metadata_exists' => $metadataExists,
        'has_existing_schema' => app_cli_has_existing_schema($conn),
    ];
}

function app_cli_build_command(array $parts): string
{
    return implode(' ', array_map(static function ($part): string {
        return escapeshellarg((string) $part);
    }, $parts));
}

function app_cli_run_command(array $parts, string $cwd): array
{
    $command = app_cli_build_command($parts);
    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);
    if (!is_resource($process)) {
        return [
            'exit_code' => 1,
            'stdout' => '',
            'stderr' => 'Unable to start subprocess.',
            'combined' => 'Unable to start subprocess.',
        ];
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    $combined = trim((string) $stdout);
    $stderr = trim((string) $stderr);
    if ($stderr !== '') {
        $combined = trim($combined . PHP_EOL . $stderr);
    }

    return [
        'exit_code' => (int) $exitCode,
        'stdout' => trim((string) $stdout),
        'stderr' => $stderr,
        'combined' => $combined,
    ];
}

function app_cli_run_doctrine(array $args, string $root): array
{
    $binary = app_cli_doctrine_binary($root);
    if ($binary === null) {
        return [
            'exit_code' => 1,
            'stdout' => '',
            'stderr' => 'Doctrine migrations binary is missing.',
            'combined' => 'Doctrine migrations binary is missing.',
        ];
    }

    $parts = [
        PHP_BINARY,
        $binary,
        '--configuration=' . ($root . DIRECTORY_SEPARATOR . 'migrations.php'),
        '--db-configuration=' . ($root . DIRECTORY_SEPARATOR . 'migrations-db.php'),
    ];

    return app_cli_run_command(array_merge($parts, $args), $root);
}

function app_cli_run_php_script(string $scriptPath, array $args, string $root): array
{
    $parts = array_merge([PHP_BINARY, $scriptPath], $args);
    return app_cli_run_command($parts, $root);
}

function app_cli_prepare_metadata(string $root, PDO $conn): array
{
    $metadataExistsBefore = app_cli_table_exists($conn, 'doctrine_migration_versions');
    $hasExistingSchema = app_cli_has_existing_schema($conn);

    $syncResult = app_cli_run_doctrine(['migrations:sync-metadata-storage', '--no-interaction'], $root);
    if (($syncResult['exit_code'] ?? 1) !== 0) {
        return [
            'ok' => false,
            'storage_created' => false,
            'baseline_marked' => false,
            'output' => $syncResult['combined'] ?? '',
        ];
    }

    $storageCreated = !$metadataExistsBefore && app_cli_table_exists($conn, 'doctrine_migration_versions');
    $executed = app_cli_executed_migrations($conn);
    $baselineVersion = 'Bolakaz\\Migrations\\Version20260313090000';
    $baselineMarked = false;

    if ($hasExistingSchema && empty($executed)) {
        $markResult = app_cli_run_doctrine(['migrations:version', $baselineVersion, '--add', '--no-interaction'], $root);
        if (($markResult['exit_code'] ?? 1) !== 0) {
            return [
                'ok' => false,
                'storage_created' => $storageCreated,
                'baseline_marked' => false,
                'output' => $markResult['combined'] ?? '',
            ];
        }

        $baselineMarked = true;
    }

    return [
        'ok' => true,
        'storage_created' => $storageCreated,
        'baseline_marked' => $baselineMarked,
        'output' => '',
    ];
}

function app_cli_sync_warnings(array $env, ?PDO $conn = null): array
{
    $warnings = [];
    $role = strtolower(app_cli_env_value($env, 'SYNC_ROLE', 'client'));
    $enabled = app_cli_env_bool($env, 'SYNC_ENABLED', false);
    $serverUrl = app_cli_env_value($env, 'SYNC_SERVER_URL', '');
    $token = app_cli_env_value($env, 'SYNC_TOKEN', '');
    $deviceId = app_cli_env_value($env, 'SYNC_DEVICE_ID', '');
    $deviceName = app_cli_env_value($env, 'SYNC_DEVICE_NAME', '');

    if (!in_array($role, ['client', 'server'], true)) {
        $warnings[] = [
            'status' => 'WARN',
            'message' => 'SYNC_ROLE is set to "' . ($role !== '' ? $role : '(blank)') . '".',
            'hint' => 'Set SYNC_ROLE to client on the local machine or server on the hosted copy.',
        ];
        return $warnings;
    }

    if (!$enabled) {
        $warnings[] = [
            'status' => 'WARN',
            'message' => 'Sync is currently disabled.',
            'hint' => 'Set SYNC_ENABLED=true after SYNC_SERVER_URL and SYNC_TOKEN are ready on both systems.',
        ];
        return $warnings;
    }

    if ($serverUrl === '') {
        $warnings[] = [
            'status' => 'FAIL',
            'message' => 'SYNC_SERVER_URL is missing while sync is enabled.',
            'hint' => 'Set SYNC_SERVER_URL to the live base URL shown in sync/ENV-SETUP.md.',
        ];
    }

    if ($token === '') {
        $warnings[] = [
            'status' => 'FAIL',
            'message' => 'SYNC_TOKEN is missing while sync is enabled.',
            'hint' => 'Use the same SYNC_TOKEN value on the local client and the live server.',
        ];
    }

    if ($deviceId === '') {
        $warnings[] = [
            'status' => 'WARN',
            'message' => 'SYNC_DEVICE_ID is blank.',
            'hint' => 'Set a stable device name like mom-pc-01 so sync receipts stay readable.',
        ];
    }

    if ($deviceName === '') {
        $warnings[] = [
            'status' => 'WARN',
            'message' => 'SYNC_DEVICE_NAME is blank.',
            'hint' => 'Set a friendly name like "Mom PC 01" or "Bolakaz Live Server".',
        ];
    }

    if ($conn !== null && $enabled) {
        $requiredTables = ['devices', 'sync_queue', 'sync_outbox', 'sync_pull_queue', 'sync_state'];
        foreach ($requiredTables as $table) {
            if (!app_cli_table_exists($conn, $table)) {
                $warnings[] = [
                    'status' => 'WARN',
                    'message' => 'Sync table "' . $table . '" is missing.',
                    'hint' => 'Run php bolakaz migrate so the tracked sync migrations can build the sync schema.',
                ];
                break;
            }
        }
    }

    return $warnings;
}

function app_cli_require_vendor_or_fail(string $root): bool
{
    if (app_cli_vendor_ready($root) && app_cli_doctrine_binary($root) !== null) {
        return true;
    }

    app_cli_report('FAIL', 'Composer dependencies are missing.', 'Run composer install from the project root before using this command.');
    return false;
}

function app_cli_command_doctor(array $env, string $root): int
{
    app_cli_banner('doctor');

    $hasFail = false;
    $envPath = app_cli_env_file_path($root);
    $exampleEnvPath = app_cli_example_env_file_path($root);

    if (is_file($envPath)) {
        app_cli_report('PASS', '.env file found at ' . $envPath . '.');
    } else {
        $hasFail = true;
        app_cli_report('FAIL', '.env file is missing.', 'Copy ' . $exampleEnvPath . ' to ' . $envPath . ' and fill in the real values.');
    }

    if (app_cli_vendor_ready($root)) {
        app_cli_report('PASS', 'Composer autoload is present.');
    } else {
        $hasFail = true;
        app_cli_report('FAIL', 'Composer autoload is missing.', 'Run composer install from the project root.');
    }

    if (app_cli_doctrine_binary($root) !== null) {
        app_cli_report('PASS', 'Doctrine migrations runtime is installed.');
    } else {
        $hasFail = true;
        app_cli_report('FAIL', 'Doctrine migrations runtime is missing.', 'Run composer install so doctrine/migrations and doctrine/dbal are available.');
    }

    $appUrl = app_cli_env_value($env, 'APP_URL', '');
    if ($appUrl !== '') {
        app_cli_report('PASS', 'APP_URL is set to ' . $appUrl . '.');
    } else {
        app_cli_report('WARN', 'APP_URL is blank.', 'Set APP_URL so links, statements, and sync endpoints use the correct base URL.');
    }

    $appEnv = strtolower(app_cli_env_value($env, 'APP_ENV', 'production'));
    $hcaptchaSiteKey = app_cli_env_value($env, 'HCAPTCHA_SITE_KEY', '');
    $hcaptchaSecret = app_cli_env_value($env, 'HCAPTCHA_SECRET_KEY', '');
    if ($appEnv === 'local') {
        app_cli_report('PASS', 'APP_ENV is local, so captcha can stay bypassed during local development.');
    } elseif ($hcaptchaSiteKey !== '' && $hcaptchaSecret !== '') {
        app_cli_report('PASS', 'hCaptcha keys are configured for non-local use.');
    } else {
        app_cli_report('WARN', 'hCaptcha keys are incomplete for a non-local environment.', 'Set HCAPTCHA_SITE_KEY and HCAPTCHA_SECRET_KEY in .env before using public sign-in or sign-up.');
    }

    $conn = null;
    try {
        $conn = app_cli_connect($env);
        app_cli_report(
            'PASS',
            'Database connection succeeded for ' . app_cli_env_value($env, 'DB_NAME', 'bolakaz') . ' on ' . app_cli_env_value($env, 'DB_HOST', '127.0.0.1') . '.'
        );
    } catch (Throwable $e) {
        $hasFail = true;
        app_cli_report('FAIL', 'Database connection failed: ' . $e->getMessage(), 'Check DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, and whether MySQL is running.');
    }

    if ($conn instanceof PDO) {
        $status = app_cli_migration_status_data($root, $conn);
        $availableCount = count($status['available']);
        app_cli_report('PASS', 'Tracked migration files found: ' . $availableCount . '.');

        if ($status['metadata_exists']) {
            app_cli_report('PASS', 'Doctrine migration metadata table exists.');
            if (count($status['pending']) === 0) {
                app_cli_report('PASS', 'No pending tracked migrations remain.');
            } else {
                app_cli_report('WARN', count($status['pending']) . ' tracked migration(s) are still pending.', 'Run php bolakaz migrate to apply the pending migrations.');
            }
        } elseif ($status['has_existing_schema']) {
            app_cli_report('WARN', 'This database has Bolakaz tables but is not yet tracked by Doctrine.', 'Run php bolakaz migrate so the CLI can adopt the existing schema and continue safely.');
        } else {
            app_cli_report('WARN', 'This database is reachable but looks blank.', 'Run php bolakaz setup --role=client or php bolakaz setup --role=server to build the schema.');
        }

        foreach (app_cli_sync_warnings($env, $conn) as $warning) {
            if (($warning['status'] ?? '') === 'FAIL') {
                $hasFail = true;
            }
            app_cli_report((string) ($warning['status'] ?? 'WARN'), (string) ($warning['message'] ?? ''), (string) ($warning['hint'] ?? ''));
        }
    }

    return $hasFail ? 1 : 0;
}

function app_cli_command_migrate_status(array $env, string $root): int
{
    app_cli_banner('migrate:status');

    if (!app_cli_require_vendor_or_fail($root)) {
        return 1;
    }

    try {
        $conn = app_cli_connect($env);
    } catch (Throwable $e) {
        app_cli_report('FAIL', 'Database connection failed: ' . $e->getMessage(), 'Check DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, and whether MySQL is running.');
        return 1;
    }

    $status = app_cli_migration_status_data($root, $conn);
    app_cli_report('PASS', 'Tracked migrations available: ' . count($status['available']) . '.');

    if (!$status['metadata_exists']) {
        if ($status['has_existing_schema']) {
            app_cli_report('WARN', 'Doctrine metadata is missing on an existing Bolakaz database.', 'Run php bolakaz migrate to adopt the current database into migration tracking.');
        } else {
            app_cli_report('WARN', 'Doctrine metadata is missing on a blank database.', 'Run php bolakaz migrate or php bolakaz setup --role=client to initialize it.');
        }
        return 0;
    }

    app_cli_report('PASS', 'Executed migrations: ' . count($status['executed']) . '.');
    if (count($status['pending']) === 0) {
        app_cli_report('PASS', 'Pending migrations: 0.');
        return 0;
    }

    app_cli_report('WARN', 'Pending migrations: ' . count($status['pending']) . '.', 'Run php bolakaz migrate to apply the pending versions.');
    foreach ($status['pending'] as $version) {
        echo ' - ' . $version . PHP_EOL;
    }

    return 0;
}

function app_cli_command_migrate(array $env, string $root): int
{
    app_cli_banner('migrate');

    if (!app_cli_require_vendor_or_fail($root)) {
        return 1;
    }

    try {
        $conn = app_cli_connect($env);
    } catch (Throwable $e) {
        app_cli_report('FAIL', 'Database connection failed: ' . $e->getMessage(), 'Check DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, and whether MySQL is running.');
        return 1;
    }

    $prepare = app_cli_prepare_metadata($root, $conn);
    if (!($prepare['ok'] ?? false)) {
        app_cli_report('FAIL', 'Unable to prepare Doctrine migration metadata.', 'Check composer dependencies and database permissions, then try again.');
        app_cli_output_block((string) ($prepare['output'] ?? ''));
        return 1;
    }

    if (!empty($prepare['storage_created'])) {
        app_cli_report('APPLIED', 'Doctrine migration metadata storage is ready.');
    }
    if (!empty($prepare['baseline_marked'])) {
        app_cli_report('APPLIED', 'Existing schema adopted into Doctrine by marking the baseline migration as already executed.');
    }

    $before = app_cli_migration_status_data($root, $conn);
    $pendingBefore = count($before['pending']);
    if ($pendingBefore === 0) {
        app_cli_report('SKIPPED', 'No pending migrations to apply.');
        return 0;
    }

    $result = app_cli_run_doctrine(['migrations:migrate', '--no-interaction', '--allow-no-migration'], $root);
    if (($result['exit_code'] ?? 1) !== 0) {
        app_cli_report('FAIL', 'Doctrine migration run failed.', 'Review the error output below, fix the failing migration or schema issue, then run php bolakaz migrate again.');
        app_cli_output_block((string) ($result['combined'] ?? ''));
        return 1;
    }

    $after = app_cli_migration_status_data($root, $conn);
    $pendingAfter = count($after['pending']);
    $appliedCount = max(0, $pendingBefore - $pendingAfter);

    if ($pendingAfter === 0) {
        app_cli_report('APPLIED', 'Applied ' . $appliedCount . ' migration(s).');
        return 0;
    }

    app_cli_report('WARN', 'Migration command finished, but ' . $pendingAfter . ' migration(s) are still pending.', 'Run php bolakaz migrate:status to inspect what is left.');
    return 1;
}

function app_cli_command_seed_minimum(array $env, string $root): int
{
    app_cli_banner('seed:minimum');

    $script = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seed_minimum.php';
    if (!is_file($script)) {
        app_cli_report('FAIL', 'database/seed_minimum.php is missing.', 'Restore the seed script before using this command.');
        return 1;
    }

    $result = app_cli_run_php_script($script, [], $root);
    if (($result['exit_code'] ?? 1) !== 0) {
        app_cli_report('FAIL', 'Minimum seed failed.', 'Run php bolakaz migrate first and make sure the database user can insert and update data.');
        app_cli_output_block((string) ($result['combined'] ?? ''));
        return 1;
    }

    app_cli_output_block((string) ($result['combined'] ?? ''));
    return 0;
}

function app_cli_drop_app_tables(PDO $conn): array
{
    $dropped = [];
    $skipped = [];
    $tables = array_values(array_reverse(app_cli_app_tables()));

    $conn->exec('SET FOREIGN_KEY_CHECKS = 0');
    try {
        foreach ($tables as $table) {
            if (app_cli_table_exists($conn, $table)) {
                $conn->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . '`');
                $dropped[] = $table;
            } else {
                $skipped[] = $table;
            }
        }
    } finally {
        $conn->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    return [
        'dropped' => $dropped,
        'skipped' => $skipped,
    ];
}

function app_cli_confirm_fresh_reset(bool $force): bool
{
    if ($force) {
        return true;
    }

    echo 'WARNING: This command is destructive and will drop the application tables before rebuilding them.' . PHP_EOL;
    echo 'Type "CONFIRM" to continue: ';
    $input = fgets(STDIN);
    $input = $input === false ? '' : trim($input);
    echo PHP_EOL;

    return $input === 'CONFIRM';
}

function app_cli_command_migrate_fresh(array $env, string $root, bool $force, bool $seedMinimum): int
{
    app_cli_banner('migrate:fresh');

    if (!app_cli_require_vendor_or_fail($root)) {
        return 1;
    }

    try {
        $conn = app_cli_connect($env);
    } catch (Throwable $e) {
        app_cli_report('FAIL', 'Database connection failed: ' . $e->getMessage(), 'Check DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, and whether MySQL is running.');
        return 1;
    }

    if (!app_cli_confirm_fresh_reset($force)) {
        app_cli_report('SKIPPED', 'migrate:fresh was cancelled.');
        return 1;
    }

    $drop = app_cli_drop_app_tables($conn);
    if (!empty($drop['dropped'])) {
        app_cli_report('APPLIED', 'Dropped ' . count($drop['dropped']) . ' application table(s).');
    } else {
        app_cli_report('SKIPPED', 'No existing application tables were found to drop.');
    }

    $prepare = app_cli_prepare_metadata($root, $conn);
    if (!($prepare['ok'] ?? false)) {
        app_cli_report('FAIL', 'Unable to prepare fresh migration metadata.', 'Check composer dependencies and database permissions, then try again.');
        app_cli_output_block((string) ($prepare['output'] ?? ''));
        return 1;
    }

    $result = app_cli_run_doctrine(['migrations:migrate', '--no-interaction', '--allow-no-migration'], $root);
    if (($result['exit_code'] ?? 1) !== 0) {
        app_cli_report('FAIL', 'Fresh migration run failed.', 'Review the error output below, fix the problem, and run php bolakaz migrate:fresh again if you still want a clean rebuild.');
        app_cli_output_block((string) ($result['combined'] ?? ''));
        return 1;
    }

    $after = app_cli_migration_status_data($root, $conn);
    if (count($after['pending']) === 0) {
        app_cli_report('APPLIED', 'Fresh database rebuild completed from tracked migrations.');
    } else {
        app_cli_report('WARN', count($after['pending']) . ' migration(s) still appear pending after rebuild.', 'Run php bolakaz migrate:status to inspect the remaining versions.');
        return 1;
    }

    if ($seedMinimum) {
        $seedExitCode = app_cli_command_seed_minimum($env, $root);
        if ($seedExitCode !== 0) {
            return $seedExitCode;
        }
    } else {
        app_cli_report('SKIPPED', 'Starter seed was not requested.');
    }

    return 0;
}

function app_cli_setup_role(array $env, ?string $explicitRole): string
{
    $role = trim((string) ($explicitRole ?? ''));
    if ($role === '') {
        $role = strtolower(app_cli_env_value($env, 'SYNC_ROLE', 'client'));
    }

    if (!in_array($role, ['client', 'server'], true)) {
        $role = 'client';
    }

    return $role;
}

function app_cli_command_setup(array $env, string $root, string $role, bool $seedMinimum): int
{
    app_cli_banner('setup');

    if (!app_cli_require_vendor_or_fail($root)) {
        return 1;
    }

    try {
        $conn = app_cli_connect($env);
    } catch (Throwable $e) {
        app_cli_report('FAIL', 'Database connection failed: ' . $e->getMessage(), 'Check DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, and whether MySQL is running.');
        return 1;
    }

    $envRole = strtolower(app_cli_env_value($env, 'SYNC_ROLE', 'client'));
    if ($envRole !== $role) {
        app_cli_report('WARN', 'Requested role is "' . $role . '" but SYNC_ROLE is "' . $envRole . '" in .env.', 'Update SYNC_ROLE in .env if this machine should really behave as ' . $role . '.');
    } else {
        app_cli_report('PASS', 'Setup target role matches SYNC_ROLE=' . $envRole . '.');
    }

    foreach (app_cli_sync_warnings($env, $conn) as $warning) {
        app_cli_report((string) ($warning['status'] ?? 'WARN'), (string) ($warning['message'] ?? ''), (string) ($warning['hint'] ?? ''));
    }

    $didSomething = false;
    $prepare = app_cli_prepare_metadata($root, $conn);
    if (!($prepare['ok'] ?? false)) {
        app_cli_report('FAIL', 'Unable to prepare Doctrine migration metadata.', 'Check composer dependencies and database permissions, then try again.');
        app_cli_output_block((string) ($prepare['output'] ?? ''));
        return 1;
    }

    if (!empty($prepare['storage_created'])) {
        $didSomething = true;
        app_cli_report('APPLIED', 'Doctrine migration metadata storage is ready.');
    }
    if (!empty($prepare['baseline_marked'])) {
        $didSomething = true;
        app_cli_report('APPLIED', 'Existing schema adopted into Doctrine by marking the baseline migration as already executed.');
    }

    $before = app_cli_migration_status_data($root, $conn);
    $pendingBefore = count($before['pending']);
    if ($pendingBefore > 0) {
        $result = app_cli_run_doctrine(['migrations:migrate', '--no-interaction', '--allow-no-migration'], $root);
        if (($result['exit_code'] ?? 1) !== 0) {
            app_cli_report('FAIL', 'Migration step failed during setup.', 'Review the error output below, fix the problem, and run php bolakaz setup again.');
            app_cli_output_block((string) ($result['combined'] ?? ''));
            return 1;
        }

        $after = app_cli_migration_status_data($root, $conn);
        $appliedCount = max(0, $pendingBefore - count($after['pending']));
        $didSomething = $didSomething || $appliedCount > 0;
        app_cli_report('APPLIED', 'Applied ' . $appliedCount . ' migration(s) during setup.');
    } else {
        app_cli_report('SKIPPED', 'Database schema is already up to date.');
    }

    if ($seedMinimum) {
        $seedExitCode = app_cli_command_seed_minimum($env, $root);
        if ($seedExitCode !== 0) {
            return $seedExitCode;
        }
        $didSomething = true;
    }

    if (!$didSomething) {
        echo 'Setup already completed. Nothing to do.' . PHP_EOL;
        return 0;
    }

    app_cli_report('PASS', 'Setup completed for the ' . $role . ' role.');
    return 0;
}

function app_cli_help_text(array $env): string
{
    $lines = [];
    $lines[] = 'Bolakaz CLI';
    $lines[] = '';
    $lines[] = 'Usage:';
    $lines[] = '  php bolakaz <command> [options]';
    $lines[] = '';
    $lines[] = 'Commands:';

    foreach (app_cli_command_definitions() as $definition) {
        $lines[] = '  ' . $definition['usage'] . ' [' . $definition['safety'] . ']';
        $lines[] = '    ' . $definition['description'];
    }

    $lines[] = '';
    $lines[] = 'Options:';
    $lines[] = '  --role=client|server   Explicit setup target. Defaults to SYNC_ROLE or client.';
    $lines[] = '  --seed-minimum         Also run the starter seed after setup or migrate:fresh.';
    $lines[] = '  --force                Skip the CONFIRM prompt for migrate:fresh.';
    $lines[] = '';
    $lines[] = 'Current .env role: ' . app_cli_setup_role($env, null);

    return implode(PHP_EOL, $lines) . PHP_EOL;
}
