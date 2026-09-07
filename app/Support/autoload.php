<?php

/**
 * Autoloader de fallback (PSR-4) para quando o Composer nao estiver disponivel.
 * Se o vendor/autoload.php existir, ele tem prioridade e este arquivo apenas
 * garante o carregamento dos helpers.
 */

$vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';

if (is_file($vendorAutoload)) {
    require $vendorAutoload;
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR; // .../app/

        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

        if (is_file($file)) {
            require $file;
        }
    });

    require __DIR__ . '/helpers.php';
}
