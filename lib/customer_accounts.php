<?php

if (!function_exists('app_customer_db_has_column')) {
    function app_customer_db_has_column(PDO $conn, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $stmt = $conn->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column_name");
        $stmt->execute(['column_name' => $column]);
        $cache[$key] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);

        return $cache[$key];
    }
}

if (!function_exists('app_customer_generate_uuid')) {
    function app_customer_generate_uuid(): string
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

if (!function_exists('app_customer_split_name')) {
    function app_customer_split_name(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($fullName === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $fullName, 2) ?: [];
        $firstname = trim((string) ($parts[0] ?? ''));
        $lastname = trim((string) ($parts[1] ?? ''));

        return [$firstname, $lastname];
    }
}

if (!function_exists('app_customer_full_name')) {
    function app_customer_full_name(array $row): string
    {
        $fullName = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['lastname'] ?? ''));
        return $fullName !== '' ? $fullName : 'Customer';
    }
}

if (!function_exists('app_customer_generate_placeholder_email')) {
    function app_customer_generate_placeholder_email(?string $uuid = null): string
    {
        $uuid = trim((string) ($uuid ?? ''));
        if ($uuid === '') {
            $uuid = app_customer_generate_uuid();
        }

        return 'offline+' . strtolower(str_replace('-', '', $uuid)) . '@local.invalid';
    }
}

if (!function_exists('app_customer_is_placeholder_email')) {
    function app_customer_is_placeholder_email($email, ?int $flag = null): bool
    {
        if ($flag !== null) {
            return $flag === 1;
        }

        $email = strtolower(trim((string) $email));
        return $email !== '' && substr($email, -14) === '@local.invalid';
    }
}

if (!function_exists('app_customer_email_exists')) {
    function app_customer_email_exists(PDO $conn, string $email, int $excludeId = 0): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }

        if ($excludeId > 0) {
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $stmt->execute([
                'email' => $email,
                'id' => $excludeId,
            ]);
        } else {
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
        }

        return (bool) $stmt->fetchColumn();
    }
}

if (!function_exists('app_customer_generate_temp_password')) {
    function app_customer_generate_temp_password(int $length = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }

        return $password;
    }
}

if (!function_exists('app_customer_row_state')) {
    function app_customer_row_state(PDO $conn, array $row): string
    {
        $type = (int) ($row['type'] ?? 0);
        if ($type !== 0) {
            return 'active';
        }

        if (app_customer_db_has_column($conn, 'users', 'account_state')) {
            $state = strtolower(trim((string) ($row['account_state'] ?? '')));
            if (in_array($state, ['incomplete', 'pending_activation', 'active'], true)) {
                return $state;
            }
        }

        return ((int) ($row['status'] ?? 0) === 1) ? 'active' : 'pending_activation';
    }
}

if (!function_exists('app_customer_has_real_email')) {
    function app_customer_has_real_email(array $row): bool
    {
        $email = trim((string) ($row['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return !app_customer_is_placeholder_email($email, isset($row['is_placeholder_email']) ? (int) $row['is_placeholder_email'] : null);
    }
}

if (!function_exists('app_customer_can_login')) {
    function app_customer_can_login(PDO $conn, array $row): bool
    {
        if ((int) ($row['type'] ?? 0) !== 0) {
            return (int) ($row['status'] ?? 0) === 1;
        }

        return (int) ($row['status'] ?? 0) === 1
            && app_customer_row_state($conn, $row) === 'active'
            && app_customer_has_real_email($row);
    }
}

if (!function_exists('app_customer_can_resend_activation')) {
    function app_customer_can_resend_activation(PDO $conn, array $row): bool
    {
        if ((int) ($row['type'] ?? 0) !== 0) {
            return false;
        }

        return app_customer_row_state($conn, $row) === 'pending_activation'
            && app_customer_has_real_email($row)
            && (int) ($row['status'] ?? 0) !== 1;
    }
}

if (!function_exists('app_customer_build_email_payload')) {
    function app_customer_build_email_payload(PDO $conn, string $email, string $uuid, int $excludeId = 0): array
    {
        $email = trim($email);
        if ($email === '') {
            $placeholder = app_customer_generate_placeholder_email($uuid);
            return [
                'email' => $placeholder,
                'is_placeholder_email' => 1,
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid email address.');
        }

        if (app_customer_email_exists($conn, $email, $excludeId)) {
            throw new RuntimeException('Email already taken.');
        }

        return [
            'email' => $email,
            'is_placeholder_email' => 0,
        ];
    }
}

if (!function_exists('app_customer_create_incomplete_profile')) {
    function app_customer_create_incomplete_profile(PDO $conn, array $input): array
    {
        $fullName = trim((string) ($input['full_name'] ?? ''));
        if ($fullName === '') {
            throw new RuntimeException('Customer name is required.');
        }

        [$firstname, $lastname] = app_customer_split_name($fullName);
        if ($firstname === '') {
            throw new RuntimeException('Customer name is required.');
        }

        $uuid = app_customer_generate_uuid();
        $emailPayload = app_customer_build_email_payload(
            $conn,
            trim((string) ($input['email'] ?? '')),
            $uuid
        );
        $temporaryPassword = app_customer_generate_temp_password();
        $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        $createdOn = date('Y-m-d');

        $columns = [
            'uuid',
            'email',
            'password',
            'type',
            'firstname',
            'lastname',
            'address',
            'phone',
            'gender',
            'dob',
            'photo',
            'status',
            'activate_code',
            'reset_code',
            'created_on',
            'referral',
        ];
        $values = [
            'uuid' => $uuid,
            'email' => $emailPayload['email'],
            'password' => $passwordHash,
            'type' => 0,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'address' => trim((string) ($input['address'] ?? '')),
            'phone' => trim((string) ($input['phone'] ?? '')),
            'gender' => trim((string) ($input['gender'] ?? '')),
            'dob' => trim((string) ($input['dob'] ?? '')),
            'photo' => trim((string) ($input['photo'] ?? '')),
            'status' => 1,
            'activate_code' => null,
            'reset_code' => null,
            'created_on' => $createdOn,
            'referral' => trim((string) ($input['referral'] ?? '')),
        ];

        if (app_customer_db_has_column($conn, 'users', 'account_state')) {
            $columns[] = 'account_state';
            $values['account_state'] = 'incomplete';
        }
        if (app_customer_db_has_column($conn, 'users', 'is_placeholder_email')) {
            $columns[] = 'is_placeholder_email';
            $values['is_placeholder_email'] = (int) $emailPayload['is_placeholder_email'];
        }

        $placeholderSql = [];
        foreach ($columns as $column) {
            $placeholderSql[] = ':' . $column;
        }

        $sql = 'INSERT INTO users (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholderSql) . ')';
        $stmt = $conn->prepare($sql);
        $stmt->execute($values);

        return [
            'id' => (int) $conn->lastInsertId(),
            'uuid' => $uuid,
            'email' => $emailPayload['email'],
            'is_placeholder_email' => (int) $emailPayload['is_placeholder_email'],
            'generated_password' => $temporaryPassword,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'account_state' => 'incomplete',
        ];
    }
}

if (!function_exists('app_customer_enable_login')) {
    function app_customer_enable_login(PDO $conn, int $userId): array
    {
        if ($userId <= 0) {
            throw new RuntimeException('Select a valid customer first.');
        }

        $stmt = $conn->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int) ($row['type'] ?? 0) !== 0) {
            throw new RuntimeException('Customer not found.');
        }

        if (!app_customer_has_real_email($row)) {
            throw new RuntimeException('Add a real email address before enabling login for this customer.');
        }

        $temporaryPassword = app_customer_generate_temp_password();
        $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        $fields = [
            'password = :password',
            'status = :status',
            'activate_code = :activate_code',
            'reset_code = :reset_code',
        ];
        $params = [
            'password' => $passwordHash,
            'status' => 1,
            'activate_code' => null,
            'reset_code' => null,
            'id' => $userId,
        ];

        if (app_customer_db_has_column($conn, 'users', 'account_state')) {
            $fields[] = 'account_state = :account_state';
            $params['account_state'] = 'active';
        }
        if (app_customer_db_has_column($conn, 'users', 'is_placeholder_email')) {
            $fields[] = 'is_placeholder_email = :is_placeholder_email';
            $params['is_placeholder_email'] = 0;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $update = $conn->prepare($sql);
        $update->execute($params);

        return [
            'password' => $temporaryPassword,
            'customer_name' => app_customer_full_name($row),
        ];
    }
}
