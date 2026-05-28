<?php

/**
 * Hostinger public_html/index.php
 *
 * Layout on server:
 *   domains/your-site.com/
 *     nfci/              <- upload full Laravel project here (app, vendor, .env, …)
 *     public_html/       <- upload ONLY this deploy/hostinger/public_html/ folder contents
 *       index.php
 *       .htaccess
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$laravelRoot = dirname(__DIR__) . '/nfci';

// ---- Early bootstrap error capture ----
// If Laravel itself fails to boot, we still get a useful error log + JSON response.
try {
    if (file_exists($maintenance = $laravelRoot . '/storage/framework/maintenance.php')) {
        require $maintenance;
    }

    require $laravelRoot . '/vendor/autoload.php';

    /** @var Application $app */
    $app = require_once $laravelRoot . '/bootstrap/app.php';

    $app->handleRequest(Request::capture());

} catch (\Throwable $e) {
    // Write to a fallback log that does NOT depend on Laravel
    $logDir = $laravelRoot . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/bootstrap-errors.log';
    $entry   = '[' . date('Y-m-d H:i:s') . '] BOOTSTRAP ERROR: '
             . get_class($e) . ': ' . $e->getMessage()
             . ' in ' . $e->getFile() . ':' . $e->getLine()
             . "\n" . $e->getTraceAsString() . "\n"
             . str_repeat('-', 80) . "\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

    // Also attempt to write to PHP's error log (shows in Hostinger error log panel)
    @error_log("BOOTSTRAP ERROR: " . get_class($e) . ': ' . $e->getMessage()
             . ' in ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Application failed to start. Check bootstrap-errors.log for details.',
        'data'    => null,
        'debug'   => [
            'exception' => get_class($e),
            'error'     => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit(1);
}
