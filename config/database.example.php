<?php

/**
 * Exemplo de configuracao de banco. Copie para config/database.php e ajuste.
 * Em producao, mantenha config/database.php fora do versionamento.
 */

return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'lowticket_saas',
    'username' => 'usuario_do_banco',
    'password' => 'senha_do_banco',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
