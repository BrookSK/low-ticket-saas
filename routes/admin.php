<?php

/**
 * Rotas do painel administrativo (/admin). Login separado e protegido.
 *
 * @var \App\Core\Router $router
 */

use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\SalesController;
use App\Controllers\Admin\UsersController;
use App\Controllers\Admin\ProductsController;
use App\Controllers\Admin\CouponsController;
use App\Controllers\Admin\UpsellsController;
use App\Controllers\Admin\OrdersController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\MarketingController;
use App\Controllers\Admin\EmailTemplatesController;
use App\Controllers\Admin\WhatsappController;
use App\Controllers\Admin\AutomationsController;
use App\Controllers\Admin\ReportsController;
use App\Controllers\Admin\LogsController;

/* Login administrativo (visitante do admin). */
$router->group(['prefix' => '/admin'], function ($router) {
    $router->get('/login', AdminAuthController::class . '@show')->name('admin.login');
    $router->post('/login', AdminAuthController::class . '@login')->middleware(['csrf', 'throttle:10,1']);
    $router->post('/logout', AdminAuthController::class . '@logout')->middleware(['csrf'])->name('admin.logout');
});

/* Area administrativa protegida (exige papel admin/super_admin). */
$router->group(['prefix' => '/admin', 'middleware' => ['admin']], function ($router) {
    $router->get('', AdminDashboardController::class . '@index')->name('admin.home');
    $router->get('/dashboard', AdminDashboardController::class . '@index')->name('admin.dashboard');

    // Dashboard de vendas com metricas.
    $router->get('/vendas', SalesController::class . '@index')->name('admin.sales');
    $router->get('/vendas/dados', SalesController::class . '@data')->name('admin.sales.data');

    // Usuarios.
    $router->get('/usuarios', UsersController::class . '@index')->name('admin.users');
    $router->get('/usuarios/{id}', UsersController::class . '@show')->name('admin.users.show');
    $router->post('/usuarios/{id}/bloquear', UsersController::class . '@block')->middleware(['csrf']);
    $router->post('/usuarios/{id}/desbloquear', UsersController::class . '@unblock')->middleware(['csrf']);
    $router->post('/usuarios/{id}/acesso', UsersController::class . '@grantAccess')->middleware(['csrf']);

    // Pedidos e pagamentos.
    $router->get('/pedidos', OrdersController::class . '@index')->name('admin.orders');
    $router->get('/pedidos/{id}', OrdersController::class . '@show')->name('admin.orders.show');
    $router->post('/pedidos/{id}/reembolsar', OrdersController::class . '@refund')->middleware(['csrf']);

    // Produtos comerciais.
    $router->get('/produtos', ProductsController::class . '@index')->name('admin.products');
    $router->get('/produtos/novo', ProductsController::class . '@create')->name('admin.products.create');
    $router->post('/produtos', ProductsController::class . '@store')->middleware(['csrf']);
    $router->get('/produtos/{id}/editar', ProductsController::class . '@edit')->name('admin.products.edit');
    $router->put('/produtos/{id}', ProductsController::class . '@update')->middleware(['csrf']);
    $router->delete('/produtos/{id}', ProductsController::class . '@destroy')->middleware(['csrf']);

    // Upsells configuraveis.
    $router->get('/upsells', UpsellsController::class . '@index')->name('admin.upsells');
    $router->post('/upsells', UpsellsController::class . '@store')->middleware(['csrf']);
    $router->put('/upsells/{id}', UpsellsController::class . '@update')->middleware(['csrf']);
    $router->delete('/upsells/{id}', UpsellsController::class . '@destroy')->middleware(['csrf']);

    // Cupons.
    $router->get('/cupons', CouponsController::class . '@index')->name('admin.coupons');
    $router->post('/cupons', CouponsController::class . '@store')->middleware(['csrf']);
    $router->put('/cupons/{id}', CouponsController::class . '@update')->middleware(['csrf']);
    $router->delete('/cupons/{id}', CouponsController::class . '@destroy')->middleware(['csrf']);

    // Configuracoes gerais (Geral, Pagamentos, E-mail, WhatsApp, Google, Meta, Seguranca, Marketing).
    $router->get('/configuracoes', SettingsController::class . '@index')->name('admin.settings');
    $router->get('/configuracoes/{group}', SettingsController::class . '@group')->name('admin.settings.group');
    $router->post('/configuracoes/{group}', SettingsController::class . '@save')->middleware(['csrf']);

    // Marketing / Analytics / Performance.
    $router->get('/marketing', MarketingController::class . '@index')->name('admin.marketing');
    $router->get('/marketing/performance', MarketingController::class . '@performance')->name('admin.marketing.performance');
    $router->get('/marketing/atribuicao', MarketingController::class . '@attribution')->name('admin.marketing.attribution');

    // Templates de e-mail.
    $router->get('/emails', EmailTemplatesController::class . '@index')->name('admin.emails');
    $router->get('/emails/{id}/editar', EmailTemplatesController::class . '@edit')->name('admin.emails.edit');
    $router->put('/emails/{id}', EmailTemplatesController::class . '@update')->middleware(['csrf']);

    // WhatsApp.
    $router->get('/whatsapp', WhatsappController::class . '@index')->name('admin.whatsapp');
    $router->post('/whatsapp/templates', WhatsappController::class . '@storeTemplate')->middleware(['csrf']);
    $router->post('/whatsapp/testar', WhatsappController::class . '@test')->middleware(['csrf']);

    // Automacoes.
    $router->get('/automacoes', AutomationsController::class . '@index')->name('admin.automations');
    $router->post('/automacoes', AutomationsController::class . '@store')->middleware(['csrf']);
    $router->put('/automacoes/{id}', AutomationsController::class . '@update')->middleware(['csrf']);
    $router->post('/automacoes/{id}/toggle', AutomationsController::class . '@toggle')->middleware(['csrf']);

    // Relatorios (com export CSV/PDF).
    $router->get('/relatorios', ReportsController::class . '@index')->name('admin.reports');
    $router->get('/relatorios/vendas', ReportsController::class . '@sales')->name('admin.reports.sales');
    $router->get('/relatorios/financeiro', ReportsController::class . '@finance')->name('admin.reports.finance');
    $router->get('/relatorios/marketing', ReportsController::class . '@marketing')->name('admin.reports.marketing');
    $router->get('/relatorios/usuarios', ReportsController::class . '@users')->name('admin.reports.users');
    $router->get('/relatorios/{type}/export/{format}', ReportsController::class . '@export')->name('admin.reports.export');

    // Logs.
    $router->get('/logs', LogsController::class . '@index')->name('admin.logs');
});
