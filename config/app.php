<?php

/**
 * Configuracao MINIMA de bootstrap.
 *
 * IMPORTANTE: este projeto NAO usa .env. Praticamente todas as configuracoes
 * (nome, url, moeda, gateways, e-mail, WhatsApp, Google, etc.) sao gerenciadas
 * pelo Painel Administrativo e ficam na tabela `settings`.
 *
 * Aqui fica apenas o indispensavel para a aplicacao subir e conectar ao banco,
 * alem do mapeamento de middleware e da chave de criptografia.
 */

return [
    // Nome padrao (pode ser sobrescrito por setting('app.name')).
    'name' => 'Meu Orçamento',

    // Deixe vazio para derivar automaticamente do host. Pode ser definido no painel.
    'url' => '',

    // Ative apenas em desenvolvimento.
    'debug' => true,

    // Chave usada para criptografar valores sensiveis da tabela settings.
    // Gere uma chave forte na instalacao (veja README). NAO comitar em producao.
    'key' => 'base64:CHANGE_THIS_TO_A_RANDOM_32_BYTE_BASE64_KEY==',

    'currency' => 'BRL',
    'timezone' => 'America/Sao_Paulo',

    // Mapeamento nome-curto => classe de middleware (usado no roteador).
    'middleware' => [
        'auth' => App\Middleware\AuthMiddleware::class,
        'guest' => App\Middleware\GuestMiddleware::class,
        'admin' => App\Middleware\AdminMiddleware::class,
        'role' => App\Middleware\RoleMiddleware::class,
        'csrf' => App\Middleware\VerifyCsrfMiddleware::class,
        'throttle' => App\Middleware\ThrottleMiddleware::class,
        'tracking' => App\Middleware\TrackingMiddleware::class,
        'product' => App\Middleware\ProductAccessMiddleware::class,
    ],
];
