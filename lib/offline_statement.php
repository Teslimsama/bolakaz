<?php

if (!function_exists('app_statement_escape')) {
    function app_statement_escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_statement_generate_token')) {
    function app_statement_generate_token(): string
    {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('app_statement_generate_unique_token')) {
    function app_statement_generate_unique_token(PDO $conn): string
    {
        do {
            $token = app_statement_generate_token();
            $stmt = $conn->prepare("SELECT id FROM sales WHERE statement_share_token = :token LIMIT 1");
            $stmt->execute(['token' => $token]);
            $exists = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } while ($exists);

        return $token;
    }
}

if (!function_exists('app_statement_clean_text')) {
    function app_statement_clean_text($value): string
    {
        $decoded = (string) $value;
        for ($i = 0; $i < 3; $i++) {
            $next = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }

        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($decoded)));
        if ($clean === '') {
            return '';
        }

        $noise = [
            'error! fill up the edit form first',
            'fill up the edit form first',
        ];
        $cleanLower = strtolower($clean);
        foreach ($noise as $phrase) {
            if ($cleanLower === $phrase || strpos($cleanLower, $phrase) !== false) {
                return '';
            }
        }

        return $clean;
    }
}

if (!function_exists('app_statement_sanitize_phone_snapshot')) {
    function app_statement_sanitize_phone_snapshot($phone): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $phone));
    }
}

if (!function_exists('app_statement_normalize_phone')) {
    function app_statement_normalize_phone($phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return '';
        }

        if (strpos($digits, '0') === 0) {
            return '234' . substr($digits, 1);
        }

        if (strpos($digits, '234') === 0) {
            return $digits;
        }

        return $digits;
    }
}

if (!function_exists('app_statement_whatsapp_phone')) {
    function app_statement_whatsapp_phone($phone): string
    {
        $normalized = app_statement_normalize_phone($phone);
        if ($normalized === '') {
            return '';
        }

        return preg_match('/^234\d{10}$/', $normalized) ? $normalized : '';
    }
}

if (!function_exists('app_statement_base_url')) {
    function app_statement_base_url(): string
    {
        $configured = trim((string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
        $dir = str_replace('\\', '/', dirname($scriptName));
        $dir = rtrim(($dir === '.' ? '' : $dir), '/');
        if (preg_match('#/admin$#', $dir)) {
            $dir = str_replace('\\', '/', dirname($dir));
            $dir = rtrim(($dir === '.' ? '' : $dir), '/');
        }

        return $scheme . '://' . $host . ($dir !== '' ? $dir : '');
    }
}

if (!function_exists('app_statement_public_url')) {
    function app_statement_public_url(array $statement): string
    {
        $token = trim((string) ($statement['share_token'] ?? ''));
        if ($token === '') {
            return '';
        }

        return app_statement_base_url() . '/offline_statement.php?token=' . rawurlencode($token);
    }
}

if (!function_exists('app_statement_status_label')) {
    function app_statement_status_label($status): string
    {
        $status = strtolower(trim((string) $status));
        if ($status === 'paid') {
            return 'Paid';
        }
        if ($status === 'partial') {
            return 'Partial';
        }

        return 'Unpaid';
    }
}

if (!function_exists('app_statement_status_class')) {
    function app_statement_status_class($status): string
    {
        $status = strtolower(trim((string) $status));
        if ($status === 'paid') {
            return 'paid';
        }
        if ($status === 'partial') {
            return 'partial';
        }

        return 'unpaid';
    }
}

if (!function_exists('app_statement_status_from_totals')) {
    function app_statement_status_from_totals($paymentStatus, float $paid, float $total): string
    {
        $paymentStatus = strtolower(trim((string) $paymentStatus));
        if (in_array($paymentStatus, ['paid', 'partial', 'unpaid'], true)) {
            return $paymentStatus;
        }
        if ($total > 0 && $paid >= $total) {
            return 'paid';
        }
        if ($paid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }
}

if (!function_exists('app_statement_customer_name_from_row')) {
    function app_statement_customer_name_from_row(array $row): string
    {
        $explicit = trim((string) ($row['customer_name'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $userName = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['lastname'] ?? ''));
        if ($userName !== '') {
            return $userName;
        }

        return 'Guest';
    }
}

if (!function_exists('app_statement_phone_from_row')) {
    function app_statement_phone_from_row(array $row): string
    {
        $salePhone = app_statement_sanitize_phone_snapshot($row['phone'] ?? '');
        if ($salePhone !== '') {
            return $salePhone;
        }

        return app_statement_sanitize_phone_snapshot($row['user_phone'] ?? '');
    }
}

if (!function_exists('app_statement_fetch_seller')) {
    function app_statement_fetch_seller(PDO $conn): array
    {
        $seller = [
            'site_name' => 'Bolakaz',
            'site_number' => '+2348077747898',
            'site_email' => '',
            'site_address' => 'Dogo Daji Street, Katampe, Kubwa Village, Abuja',
        ];

        try {
            $stmt = $conn->prepare("SELECT site_name, site_number, site_email, site_address FROM web_details ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $siteName = app_statement_clean_text($row['site_name'] ?? '');
                $siteNumber = app_statement_clean_text($row['site_number'] ?? '');
                $siteEmail = app_statement_clean_text($row['site_email'] ?? '');
                $siteAddress = app_statement_clean_text($row['site_address'] ?? '');

                $seller['site_name'] = $siteName !== '' ? $siteName : $seller['site_name'];
                $seller['site_number'] = $siteNumber !== '' ? $siteNumber : $seller['site_number'];
                $seller['site_email'] = $siteEmail !== '' ? $siteEmail : $seller['site_email'];
                $seller['site_address'] = $siteAddress !== '' ? $siteAddress : $seller['site_address'];
            }
        } catch (Throwable $e) {
            // Fall back to defaults.
        }

        return $seller;
    }
}

if (!function_exists('app_statement_fetch_items')) {
    function app_statement_fetch_items(PDO $conn, int $saleId): array
    {
        $stmt = $conn->prepare("SELECT details.quantity, products.name, products.price
            FROM details
            LEFT JOIN products ON products.id = details.product_id
            WHERE details.sales_id = :id
            ORDER BY details.id ASC");
        $stmt->execute(['id' => $saleId]);

        $items = [];
        $total = 0.0;
        foreach ($stmt as $row) {
            $price = (float) ($row['price'] ?? 0);
            $qty = max(1, (int) ($row['quantity'] ?? 0));
            $subtotal = $price * $qty;
            $total += $subtotal;

            $items[] = [
                'name' => (string) ($row['name'] ?? 'Item'),
                'price' => $price,
                'price_formatted' => app_money($price),
                'quantity' => $qty,
                'subtotal' => $subtotal,
                'subtotal_formatted' => app_money($subtotal),
            ];
        }

        return [
            'rows' => $items,
            'total' => $total,
            'total_formatted' => app_money($total),
        ];
    }
}

if (!function_exists('app_statement_fetch_payments')) {
    function app_statement_fetch_payments(PDO $conn, int $saleId): array
    {
        $stmt = $conn->prepare("SELECT amount, payment_method, payment_date, note
            FROM offline_payments
            WHERE sales_id = :id
            ORDER BY payment_date DESC, id DESC");
        $stmt->execute(['id' => $saleId]);

        $rows = [];
        $paid = 0.0;
        foreach ($stmt as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            $paid += $amount;
            $rows[] = [
                'payment_method' => (string) ($row['payment_method'] ?? ''),
                'payment_date' => (string) ($row['payment_date'] ?? ''),
                'payment_date_formatted' => !empty($row['payment_date']) ? date('M d, Y', strtotime((string) $row['payment_date'])) : '',
                'amount' => $amount,
                'amount_formatted' => app_money($amount),
                'note' => trim((string) ($row['note'] ?? '')),
            ];
        }

        return [
            'rows' => $rows,
            'paid' => $paid,
            'paid_formatted' => app_money($paid),
        ];
    }
}

if (!function_exists('app_statement_build')) {
    function app_statement_build(PDO $conn, array $sale): array
    {
        $saleId = (int) ($sale['id'] ?? 0);
        $items = app_statement_fetch_items($conn, $saleId);
        $payments = app_statement_fetch_payments($conn, $saleId);
        $total = (float) ($items['total'] ?? 0);
        $paid = (float) ($payments['paid'] ?? 0);
        $balance = max(0, $total - $paid);
        $status = app_statement_status_from_totals($sale['payment_status'] ?? '', $paid, $total);
        $customerPhone = app_statement_phone_from_row($sale);
        $statement = [
            'sale_id' => $saleId,
            'user_id' => (int) ($sale['user_id'] ?? 0),
            'tx_ref' => trim((string) ($sale['tx_ref'] ?? '')),
            'share_token' => trim((string) ($sale['statement_share_token'] ?? '')),
            'customer_name' => app_statement_customer_name_from_row($sale),
            'customer_phone' => $customerPhone,
            'customer_phone_whatsapp' => app_statement_whatsapp_phone($customerPhone),
            'customer_email' => trim((string) ($sale['email'] ?? '')),
            'customer_address' => app_statement_clean_text(($sale['address_1'] ?? '') . ' ' . ($sale['address_2'] ?? '')) !== ''
                ? app_statement_clean_text(($sale['address_1'] ?? '') . ' ' . ($sale['address_2'] ?? ''))
                : app_statement_clean_text($sale['user_address'] ?? ''),
            'sales_date' => (string) ($sale['sales_date'] ?? ''),
            'sales_date_formatted' => !empty($sale['sales_date']) ? date('M d, Y', strtotime((string) $sale['sales_date'])) : '',
            'due_date' => (string) ($sale['due_date'] ?? ''),
            'due_date_formatted' => !empty($sale['due_date']) ? date('M d, Y', strtotime((string) $sale['due_date'])) : '',
            'payment_status' => $status,
            'payment_status_label' => app_statement_status_label($status),
            'payment_status_class' => app_statement_status_class($status),
            'items' => $items['rows'],
            'payments' => $payments['rows'],
            'items_total' => $total,
            'items_total_formatted' => app_money($total),
            'amount_paid' => $paid,
            'amount_paid_formatted' => app_money($paid),
            'balance' => $balance,
            'balance_formatted' => app_money($balance),
            'generated_at' => date('M d, Y g:i A'),
            'seller' => app_statement_fetch_seller($conn),
        ];

        $statement['public_url'] = app_statement_public_url($statement);
        $statement['whatsapp_url'] = app_statement_whatsapp_url($statement);

        return $statement;
    }
}

if (!function_exists('app_statement_fetch_by_sale_id')) {
    function app_statement_fetch_by_sale_id(PDO $conn, int $saleId): ?array
    {
        if ($saleId <= 0) {
            return null;
        }

        $stmt = $conn->prepare("SELECT sales.*, users.firstname, users.lastname, users.phone AS user_phone, users.address AS user_address
            FROM sales
            LEFT JOIN users ON users.id = sales.user_id
            WHERE sales.id = :id AND sales.is_offline = 1
            LIMIT 1");
        $stmt->execute(['id' => $saleId]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sale) {
            return null;
        }

        return app_statement_build($conn, $sale);
    }
}

if (!function_exists('app_statement_fetch_by_token')) {
    function app_statement_fetch_by_token(PDO $conn, string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $stmt = $conn->prepare("SELECT sales.*, users.firstname, users.lastname, users.phone AS user_phone, users.address AS user_address
            FROM sales
            LEFT JOIN users ON users.id = sales.user_id
            WHERE sales.statement_share_token = :token AND sales.is_offline = 1
            LIMIT 1");
        $stmt->execute(['token' => $token]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sale) {
            return null;
        }

        return app_statement_build($conn, $sale);
    }
}

if (!function_exists('app_statement_filename')) {
    function app_statement_filename(array $statement): string
    {
        $ref = trim((string) ($statement['tx_ref'] ?? 'statement'));
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $ref);
        $safe = trim((string) $safe, '-');
        if ($safe === '') {
            $safe = 'statement';
        }

        return $safe . '.pdf';
    }
}

if (!function_exists('app_statement_whatsapp_message')) {
    function app_statement_whatsapp_message(array $statement): string
    {
        $lines = [
            'Statement of Account for ' . (string) ($statement['customer_name'] ?? 'Customer'),
            'Reference: ' . (string) ($statement['tx_ref'] ?? ''),
            'Total Paid: ' . (string) ($statement['amount_paid_formatted'] ?? app_money(0)),
            'Current Balance: ' . (string) ($statement['balance_formatted'] ?? app_money(0)),
        ];

        if (!empty($statement['due_date_formatted'])) {
            $lines[] = 'Due Date: ' . (string) $statement['due_date_formatted'];
        }

        $publicUrl = trim((string) ($statement['public_url'] ?? ''));
        if ($publicUrl !== '') {
            $lines[] = 'View Statement: ' . $publicUrl;
        }

        return implode("\n", $lines);
    }
}

if (!function_exists('app_statement_whatsapp_url')) {
    function app_statement_whatsapp_url(array $statement): string
    {
        $phone = trim((string) ($statement['customer_phone_whatsapp'] ?? ''));
        $publicUrl = trim((string) ($statement['public_url'] ?? ''));
        if ($phone === '' || $publicUrl === '') {
            return '';
        }

        return 'https://wa.me/' . rawurlencode($phone) . '?text=' . rawurlencode(app_statement_whatsapp_message($statement));
    }
}

if (!function_exists('app_statement_render_document_html')) {
    function app_statement_render_document_html(array $statement, bool $forPdf = false): string
    {
        $seller = $statement['seller'] ?? [];
        $items = $statement['items'] ?? [];
        $payments = $statement['payments'] ?? [];
        $statusLabel = ($statement['payment_status_label'] ?? 'Unpaid');
        $sellerAddress = trim((string) ($seller['site_address'] ?? ''));
        $customerAddress = trim((string) ($statement['customer_address'] ?? ''));
        $statusClass = (string) ($statement['payment_status_class'] ?? 'unpaid');
        
        $statusBg = '#FCE7E7';
        $statusText = '#9F1D1D';
        if ($statusClass === 'paid') {
            $statusBg = '#DCFCE7';
            $statusText = '#166534';
        } elseif ($statusClass === 'partial') {
            $statusBg = '#FEF3C7';
            $statusText = '#92400E';
        }
        
        $sellerName = app_statement_escape($seller['site_name'] ?? 'Bolakaz');
        $customerName = app_statement_escape($statement['customer_name'] ?? 'Guest');
        $sellerPhone = trim((string) ($seller['site_number'] ?? ''));
        $sellerEmail = trim((string) ($seller['site_email'] ?? ''));
        $customerPhone = trim((string) ($statement['customer_phone'] ?? ''));
        $customerEmail = trim((string) ($statement['customer_email'] ?? ''));
        
        // CSS in TCPDF is limited; inline styles or simplified classes are best.
        $metaLabelStyle = 'color:#667085;font-size:10px;font-weight:bold;';
        $metaValueStyle = 'color:#101828;font-size:12px;font-weight:bold;';
        $sectionBarStyle = 'background-color:#111827;color:#FFFFFF;font-size:13px;font-weight:bold;';

        ob_start();
        ?>
<?php if (!$forPdf): ?>
<style>
  .statement-sheet { max-width: 940px; margin: 0 auto; background: #fff; color: #1f2937; font-family: Arial, sans-serif; }
  .statement-sheet table { width: 100%; border-collapse: collapse; }
  .statement-sheet .label-caps { text-transform: uppercase; letter-spacing: 0.7px; }
  .statement-sheet .title-caps { text-transform: uppercase; letter-spacing: 1.2px; }
</style>
<?php endif; ?>

<div class="statement-sheet">
  <table border="0" cellpadding="0" cellspacing="0" style="width:100%; border:1px solid #D7DEE7; <?php echo $forPdf ? '' : 'box-shadow: 0 18px 48px rgba(15,23,42,0.08);'; ?>">
    <tr>
      <td style="padding: 26px 28px 20px 28px;">
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td width="72%" style="vertical-align:top;">
              <div style="color:#667085; font-size:11px; font-weight:bold;" class="title-caps"><?php echo strtoupper($sellerName); ?></div>
              <div style="color:#101828; font-size:28px; font-weight:bold; line-height:1.1; padding-top:8px;">Statement of Account</div>
              <div style="color:#667085; font-size:12px; padding-top:8px;">Current balance summary for goods supplied through the offline sales flow.</div>
            </td>
            <td width="28%" style="vertical-align:top;" align="right">
              <table border="0" cellpadding="0" cellspacing="0" align="right">
                <tr>
                  <td bgcolor="<?php echo $statusBg; ?>" style="border:1px solid <?php echo $statusText; ?>; color:<?php echo $statusText; ?>; font-size:12px; font-weight:bold; padding:8px 14px;" align="center">
                    <?php echo $statusLabel; ?>
                  </td>
                </tr>
                <tr><td style="font-size:2px; line-height:2px;">&nbsp;</td></tr>
                <tr>
                  <td style="color:#667085; font-size:11px;">Generated <?php echo app_statement_escape($statement['generated_at'] ?? ''); ?></td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding: 0 28px;">
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td width="48%" style="border:1px solid #D7DEE7; background-color:#F8FAFC; padding:14px 16px;">
              <div style="<?php echo $metaLabelStyle; ?>" class="label-caps"><?php echo strtoupper('Seller'); ?></div>
              <div style="color:#101828; font-size:15px; font-weight:bold; padding-top:8px;"><?php echo $sellerName; ?></div>
              <?php if ($sellerAddress !== ''): ?>
              <div style="color:#344054; font-size:12px; padding-top:6px;"><?php echo app_statement_escape($sellerAddress); ?></div>
              <?php endif; ?>
              <?php if ($sellerPhone !== ''): ?>
              <div style="color:#344054; font-size:12px; padding-top:6px;">Phone: <?php echo app_statement_escape($sellerPhone); ?></div>
              <?php endif; ?>
              <?php if ($sellerEmail !== ''): ?>
              <div style="color:#344054; font-size:12px; padding-top:4px;">Email: <?php echo app_statement_escape($sellerEmail); ?></div>
              <?php endif; ?>
            </td>
            <td width="4%">&nbsp;</td>
            <td width="48%" style="border:1px solid #D7DEE7; background-color:#F8FAFC; padding:14px 16px;">
              <div style="<?php echo $metaLabelStyle; ?>" class="label-caps"><?php echo strtoupper('Debtor'); ?></div>
              <div style="color:#101828; font-size:15px; font-weight:bold; padding-top:8px;"><?php echo $customerName; ?></div>
              <?php if ($customerAddress !== ''): ?>
              <div style="color:#344054; font-size:12px; padding-top:6px;"><?php echo app_statement_escape($customerAddress); ?></div>
              <?php endif; ?>
              <?php if ($customerPhone !== ''): ?>
              <div style="color:#344054; font-size:12px; padding-top:6px;">Phone: <?php echo app_statement_escape($customerPhone); ?></div>
              <?php endif; ?>
              <?php if ($customerEmail !== ''): ?>
              <div style="color:#344054; font-size:12px; padding-top:4px;">Email: <?php echo app_statement_escape($customerEmail); ?></div>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding: 18px 28px 0 28px;">
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td width="24%" style="border:1px solid #D7DEE7; background-color:#FFFFFF; padding:10px 12px;">
              <div style="<?php echo $metaLabelStyle; ?>" class="label-caps"><?php echo strtoupper('Statement Ref'); ?></div>
              <div style="<?php echo $metaValueStyle; ?> padding-top:4px;"><?php echo app_statement_escape($statement['tx_ref'] ?? ''); ?></div>
            </td>
            <td width="24%" style="border:1px solid #D7DEE7; background-color:#FFFFFF; padding:10px 12px;">
              <div style="<?php echo $metaLabelStyle; ?>" class="label-caps"><?php echo strtoupper('Sale Date'); ?></div>
              <div style="<?php echo $metaValueStyle; ?> padding-top:4px;"><?php echo app_statement_escape($statement['sales_date_formatted'] ?? ''); ?></div>
            </td>
            <td width="24%" style="border:1px solid #D7DEE7; background-color:#FFFFFF; padding:10px 12px;">
              <div style="<?php echo $metaLabelStyle; ?>" class="label-caps"><?php echo strtoupper('Due Date'); ?></div>
              <div style="<?php echo $metaValueStyle; ?> padding-top:4px;"><?php echo app_statement_escape($statement['due_date_formatted'] ?? 'Not set'); ?></div>
            </td>
            <td width="28%" style="border:1px solid #D7DEE7; background-color:#FFFFFF; padding:10px 12px;">
              <div style="<?php echo $metaLabelStyle; ?>" class="label-caps"><?php echo strtoupper('Balance Status'); ?></div>
              <div style="<?php echo $metaValueStyle; ?> padding-top:4px;"><?php echo $statusLabel; ?></div>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding: 20px 28px 0 28px;">
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td style="<?php echo $sectionBarStyle; ?> padding:10px 12px;">Purchased Items</td>
          </tr>
        </table>
        <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-color:#D7DEE7;">
          <tr bgcolor="#F4F6F8">
            <td width="46%" style="color:#344054; font-size:12px; font-weight:bold;">Product</td>
            <td width="18%" align="right" style="color:#344054; font-size:12px; font-weight:bold;">Price</td>
            <td width="12%" align="right" style="color:#344054; font-size:12px; font-weight:bold;">Qty</td>
            <td width="24%" align="right" style="color:#344054; font-size:12px; font-weight:bold;">Subtotal</td>
          </tr>
          <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
          <tr>
            <td style="color:#101828; font-size:12px;"><?php echo app_statement_escape($item['name'] ?? 'Item'); ?></td>
            <td align="right" style="color:#101828; font-size:12px;"><?php echo app_statement_escape($item['price_formatted'] ?? app_money(0)); ?></td>
            <td align="right" style="color:#101828; font-size:12px;"><?php echo (int) ($item['quantity'] ?? 0); ?></td>
            <td align="right" style="color:#101828; font-size:12px;"><?php echo app_statement_escape($item['subtotal_formatted'] ?? app_money(0)); ?></td>
          </tr>
            <?php endforeach; ?>
          <?php else: ?>
          <tr>
            <td colspan="4" style="color:#667085; font-size:12px;">No items found for this sale.</td>
          </tr>
          <?php endif; ?>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding: 14px 28px 0 28px;">
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td width="58%">&nbsp;</td>
            <td width="42%">
              <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-color:#D7DEE7;">
                <tr>
                  <td width="58%" bgcolor="#F8FAFC" style="color:#475467; font-size:12px; font-weight:bold;">Total Purchase</td>
                  <td width="42%" align="right" style="color:#101828; font-size:12px;"><?php echo app_statement_escape($statement['items_total_formatted'] ?? app_money(0)); ?></td>
                </tr>
                <tr>
                  <td width="58%" bgcolor="#F8FAFC" style="color:#475467; font-size:12px; font-weight:bold;">Total Paid</td>
                  <td width="42%" align="right" style="color:#101828; font-size:12px;"><?php echo app_statement_escape($statement['amount_paid_formatted'] ?? app_money(0)); ?></td>
                </tr>
                <tr>
                  <td width="58%" bgcolor="#111827" style="color:#FFFFFF; font-size:12px; font-weight:bold;">Balance Owed</td>
                  <td width="42%" align="right" bgcolor="#F9FAFB" style="color:#101828; font-size:13px; font-weight:bold;"><?php echo app_statement_escape($statement['balance_formatted'] ?? app_money(0)); ?></td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding: 20px 28px 0 28px;">
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td style="<?php echo $sectionBarStyle; ?> padding:10px 12px;">Payment History</td>
          </tr>
        </table>
        <?php if (!empty($payments)): ?>
        <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-color:#D7DEE7;">
          <tr bgcolor="#F4F6F8">
            <td width="28%" style="color:#344054; font-size:12px; font-weight:bold;">Date</td>
            <td width="44%" style="color:#344054; font-size:12px; font-weight:bold;">Method</td>
            <td width="28%" align="right" style="color:#344054; font-size:12px; font-weight:bold;">Amount</td>
          </tr>
          <?php foreach ($payments as $payment): ?>
          <tr>
            <td style="color:#101828; font-size:12px;"><?php echo app_statement_escape($payment['payment_date_formatted'] ?? ''); ?></td>
            <td style="color:#101828; font-size:12px;"><?php echo app_statement_escape($payment['payment_method'] ?? ''); ?></td>
            <td align="right" style="color:#101828; font-size:12px;"><?php echo app_statement_escape($payment['amount_formatted'] ?? app_money(0)); ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php else: ?>
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td style="border:1px solid #D7DEE7; background-color:#F8FAFC; color:#667085; font-size:12px; padding:14px 12px;">No payments recorded yet.</td>
          </tr>
        </table>
        <?php endif; ?>
      </td>
    </tr>

    <tr>
      <td style="padding: 20px 28px 26px 28px;">
        <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
          <tr>
            <td style="border-top:1px solid #D7DEE7; color:#667085; font-size:11px; padding-top:12px;">This statement summarizes the current balance for the offline sale above.</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</div>
<?php
        return (string) ob_get_clean();
    }
}

if (!function_exists('app_statement_output_pdf')) {
    function app_statement_output_pdf(array $statement, string $filename = ''): void
    {
        require_once __DIR__ . '/../tcpdf/tcpdf.php';

        $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor((string) (($statement['seller']['site_name'] ?? 'Bolakaz')));
        $pdf->SetTitle('Statement of Account - ' . (string) ($statement['tx_ref'] ?? ''));
        $pdf->SetMargins(8, 10, 8);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->AddPage();
        $pdf->writeHTML(app_statement_render_document_html($statement, true), true, false, true, false, '');
        $pdf->Output($filename !== '' ? $filename : app_statement_filename($statement), 'I');
        exit;
    }
}
