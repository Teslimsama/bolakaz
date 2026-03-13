<?php

declare(strict_types=1);

return [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
    ],
    'migrations_paths' => [
        'Bolakaz\Migrations' => 'database/migrations',
    ],
    'all_or_nothing' => false,
    'transactional' => false,
    'check_database_platform' => true,
];
