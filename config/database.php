<?php

/**
 * Credenciais de conexao ao banco.
 *
 * Este e o UNICO ponto de configuracao sensivel em arquivo, pois sem ele
 * a aplicacao nao consegue nem ler a tabela `settings`. Todo o restante
 * das configuracoes fica no banco, gerenciado pelo painel admin.
 *
 * Ajuste estes valores no seu servidor. Recomenda-se definir permissoes
 * restritas neste arquivo (chmod 600) e mante-lo fora do controle de versao
 * em producao (ver config/database.example.php).
 */

return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'lowticket_saas',
    'username' => 'lowticket_saas',
    'password' => 'O2kc&4eCRcy9cv*v',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
