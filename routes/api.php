<?php

/**
 * Rotas de API: webhooks de gateways e eventos de tracking.
 *
 * @var \App\Core\Router $router
 */

use App\Controllers\Webhook\WebhookController;
use App\Controllers\Api\TrackingController;

$router->group(['prefix' => '/api'], function ($router) {
    // Webhooks dos gateways de pagamento. Cada gateway tem sua rota.
    // A validacao da assinatura acontece dentro do controller/gateway.
    $router->post('/webhooks/{gateway}', WebhookController::class . '@handle')->name('webhook.handle');

    // Eventos de tracking enviados pelo front (analytics).
    $router->post('/tracking/event', TrackingController::class . '@event')->name('tracking.event');
});
