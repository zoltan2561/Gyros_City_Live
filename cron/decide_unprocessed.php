<?php
// public_html/cron/decide_unprocessed.php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require __DIR__ . '/../bootstrap/app.php';

    /** @var \Illuminate\Contracts\Console\Kernel $consoleKernel */
    $consoleKernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $consoleKernel->bootstrap();

    // Keep the existing cron path and schedule, without HTTP, cookies or a CSRF token.
    $status = $consoleKernel->call('orders:alert-unprocessed');
    echo $consoleKernel->output();
    exit($status);
} catch (\Throwable $exception) {
    echo date('Y-m-d H:i:s T') . ' | status=failed | exception=' . get_class($exception) . PHP_EOL;
    try {
        if (isset($app) && $app->bound('log')) {
            $app->make('log')->error('Unprocessed order cron failed', ['exception_class' => get_class($exception)]);
        }
    } catch (\Throwable $loggingException) {
        // The cron must still fail clearly when the logger itself is unavailable.
    }
    exit(1);
}
