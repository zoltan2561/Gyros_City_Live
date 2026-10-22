<?php

// Run with PHP's auto_prepend_file option, before PHPUnit loads Composer.
// This legacy dependency's autoload file runs a daily network self-update.
// Keep its class loader, but skip that side effect during isolated tests.
$testRoot = dirname(__DIR__);
$composerFiles = require $testRoot.'/vendor/composer/autoload_files.php';
foreach ($composerFiles as $identifier => $path) {
    if (str_ends_with(str_replace('\\', '/', $path), '/myfatoorah/library/autoload.php')) {
        $GLOBALS['__composer_autoload_files'][$identifier] = true;
    }
}
