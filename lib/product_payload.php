<?php

if (!function_exists('product_normalize_values')) {
    function product_normalize_values($values, int $maxLength = 60): array
    {
        if (!is_array($values)) {
            return [];
        }

        $result = [];
        $seen = [];
        foreach ($values as $value) {
            $item = trim((string)$value);
            if ($item === '') {
                continue;
            }
            if (mb_strlen($item) > $maxLength) {
                $item = mb_substr($item, 0, $maxLength);
            }

            $key = mb_strtolower($item);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $item;
        }

        return $result;
    }
}

if (!function_exists('product_csv_to_array')) {
    function product_csv_to_array($value, int $maxLength = 60): array
    {
        $source = trim((string)$value);
        if ($source === '') {
            return [];
        }

        return product_normalize_values(explode(',', $source), $maxLength);
    }
}

if (!function_exists('product_values_to_csv')) {
    function product_values_to_csv(array $values): string
    {
        return implode(',', $values);
    }
}

if (!function_exists('product_collect_specs')) {
    function product_collect_specs(array $source, string $prefix = ''): array
    {
        $keys = [
            'fit' => 'Fit',
            'care_instructions' => 'Care Instructions',
            'composition' => 'Composition',
            'dimensions' => 'Dimensions',
            'shipping_class' => 'Shipping Class',
            'origin' => 'Origin',
        ];

        $specs = [];
        foreach ($keys as $key => $label) {
            $rawKey = $prefix . 'spec_' . $key;
            $value = trim((string)($source[$rawKey] ?? ''));
            if ($value !== '') {
                $specs[$key] = $value;
            }
        }

        return $specs;
    }
}

if (!function_exists('product_encode_specs')) {
    function product_encode_specs(array $specs): string
    {
        if (empty($specs)) {
            return '';
        }
        return (string)json_encode($specs, JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('product_decode_specs')) {
    function product_decode_specs($payload): array
    {
        $text = trim((string)$payload);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            return [];
        }

        $clean = [];
        foreach ($decoded as $key => $value) {
            $k = trim((string)$key);
            $v = trim((string)$value);
            if ($k === '' || $v === '') {
                continue;
            }
            $clean[$k] = $v;
        }
        return $clean;
    }
}

