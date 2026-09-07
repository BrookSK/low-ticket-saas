<?php

/**
 * Runner de tarefas agendadas.
 *
 * Configure no crontab do servidor para rodar a cada minuto:
 *   * * * * * php /caminho/do/projeto/bin/cron.php >> /caminho/storage/logs/cron.log 2>&1
 *
 * Executa: automacoes agendadas, recuperacao de checkout, contas vencidas,
 * expiracao de orcamentos.
 */

declare(strict_types=1);

use App\Core\Application;
use App\Services\CronService;

require dirname(__DIR__) . '/app/Support/autoload.php';

$app = new Application(dirname(__DIR__));
$app->bootstrap();
date_default_timezone_set((string) config('app.timezone', 'UTC'));

try {
    $result = $app->make(CronService::class)->runAll();
    echo '[' . date('Y-m-d H:i:s') . '] cron ok: ' . json_encode($result) . "\n";
} catch (\Throwable $e) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] cron erro: ' . $e->getMessage() . "\n");
    exit(1);
}
