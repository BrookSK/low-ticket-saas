<?php

/**
 * Runner de migrations.
 * Executa o schema.sql (idempotente) contra o banco configurado em config/database.php.
 *
 * Uso: php database/migrate.php
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/Support/autoload.php';

use App\Core\Config;
use App\Core\Database;

Config::load(dirname(__DIR__) . '/config');

$db = new Database(Config::get('database'));

$schemaFile = __DIR__ . '/schema.sql';
if (!is_file($schemaFile)) {
    fwrite(STDERR, "schema.sql nao encontrado.\n");
    exit(1);
}

$sql = file_get_contents($schemaFile);

// Divide os comandos por ";" no fim de linha, preservando o conteudo.
$statements = array_filter(
    array_map('trim', preg_split('/;\s*[\r\n]/', $sql)),
    fn($s) => $s !== '' && !str_starts_with($s, '--')
);

echo "Executando migrations...\n";
$count = 0;

try {
    $pdo = $db->pdo();
    foreach ($statements as $statement) {
        // Ignora linhas de comentario puro.
        $clean = trim($statement);
        if ($clean === '' || str_starts_with($clean, '--')) {
            continue;
        }
        $pdo->exec($clean);
        $count++;
    }
    echo "OK. {$count} comandos executados.\n";
    echo "Schema aplicado com sucesso.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "Erro ao aplicar schema: " . $e->getMessage() . "\n");
    exit(1);
}
