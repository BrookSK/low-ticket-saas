<?php

/**
 * Front controller. Unico ponto de entrada web.
 * Apenas /public deve ser exposto publicamente pelo servidor.
 */

declare(strict_types=1);

use App\Core\Application;

define('LTSAAS_START', microtime(true));

// Autoloader (Composer com fallback interno).
require dirname(__DIR__) . '/app/Support/autoload.php';

// Inicializa a aplicacao.
$app = new Application(dirname(__DIR__));
$app->bootstrap();

// Timezone conforme configuracao de bootstrap.
date_default_timezone_set((string) config('app.timezone', 'UTC'));

// Relatorio de erros conforme modo debug.
if (config('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

$app->run();
