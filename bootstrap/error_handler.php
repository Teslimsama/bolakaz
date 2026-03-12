<?php

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

if (!function_exists('app_env_bool')) {
    function app_env_bool(string $key, bool $default = false): bool
    {
        $value = $_ENV[$key] ?? getenv($key);
        if (!is_string($value)) {
            return $default;
        }

        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return $default;
        }

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('app_logger')) {
    function app_logger(): Logger
    {
        static $logger = null;
        if ($logger instanceof Logger) {
            return $logger;
        }

        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logFile = $logDir . '/app.log';
        if (!file_exists($logFile)) {
            @touch($logFile);
        }

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logFile, Level::Debug));
        return $logger;
    }
}

if (!function_exists('app_log')) {
    function app_log(string $level, string $message, array $context = []): void
    {
        try {
            $logger = app_logger();
            switch (strtolower($level)) {
                case 'debug':
                    $logger->debug($message, $context);
                    break;
                case 'info':
                    $logger->info($message, $context);
                    break;
                case 'notice':
                    $logger->notice($message, $context);
                    break;
                case 'warning':
                    $logger->warning($message, $context);
                    break;
                case 'error':
                    $logger->error($message, $context);
                    break;
                case 'critical':
                    $logger->critical($message, $context);
                    break;
                default:
                    $logger->error($message, $context);
                    break;
            }
        } catch (\Throwable $e) {
            error_log($message);
        }
    }
}

if (!function_exists('app_render_error_page')) {
    function app_render_error_page(int $statusCode = 500): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        $custom = __DIR__ . '/../errors/500.php';
        if (file_exists($custom)) {
            include $custom;
            return;
        }

        echo 'An unexpected error occurred.';
    }
}

if (!function_exists('app_register_error_handlers')) {
    function app_register_error_handlers(): void
    {
        $debug = app_env_bool('APP_DEBUG', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        if ($debug && class_exists(\Whoops\Run::class)) {
            $whoops = new \Whoops\Run();
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
            $whoops->register();
        }

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            // PHP 8.1+ emits many vendor deprecations (e.g. return type compatibility)
            // for older packages. Log but do not escalate to exceptions.
            if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
                try {
                    app_logger()->notice($message, [
                        'severity' => $severity,
                        'file' => $file,
                        'line' => $line,
                    ]);
                } catch (\Throwable $loggingException) {
                    // Ignore logging failure and continue.
                }
                return true;
            }

            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (\Throwable $e) use ($debug): void {
            try {
                app_logger()->error($e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $loggingException) {
                // Ignore secondary logging failures.
            }

            if ($debug) {
                echo '<pre style="padding:16px;background:#111;color:#eee;white-space:pre-wrap;">';
                echo htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8');
                echo '</pre>';
                return;
            }

            app_render_error_page(500);
        });

        register_shutdown_function(function () use ($debug): void {
            $error = error_get_last();
            if (!$error) {
                return;
            }

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array($error['type'], $fatalTypes, true)) {
                return;
            }

            $exception = new \ErrorException(
                $error['message'] ?? 'Fatal error',
                0,
                $error['type'],
                $error['file'] ?? 'unknown',
                $error['line'] ?? 0
            );

            try {
                app_logger()->critical($exception->getMessage(), [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            } catch (\Throwable $loggingException) {
                // Ignore secondary logging failures.
            }

            if ($debug) {
                echo '<pre style="padding:16px;background:#111;color:#eee;white-space:pre-wrap;">';
                echo htmlspecialchars((string)$exception, ENT_QUOTES, 'UTF-8');
                echo '</pre>';
                return;
            }

            app_render_error_page(500);
        });
    }
}
