<?php

/**
 * Rotas web publicas e da area do usuario.
 *
 * @var \App\Core\Router $router
 */

use App\Controllers\Site\LandingController;
use App\Controllers\Site\PageController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\PasswordController;
use App\Controllers\App\DashboardController;
use App\Controllers\App\CustomerController;
use App\Controllers\App\ServiceController;
use App\Controllers\App\QuoteController;
use App\Controllers\App\FinanceController;
use App\Controllers\App\PricingController;
use App\Controllers\App\SettingsController as UserSettingsController;
use App\Controllers\App\OnboardingController;
use App\Controllers\Public\PublicQuoteController;
use App\Controllers\Checkout\CheckoutController;
use App\Controllers\Checkout\UpsellController;

/* ---------------------------------------------------------------------------
 | Landing page e paginas institucionais (tracking captura UTM)
 |-------------------------------------------------------------------------- */
$router->group(['middleware' => ['tracking']], function ($router) {
    $router->get('/', LandingController::class . '@index')->name('home');

    // Paginas de campanha reutilizam componentes (segmento por nicho).
    $router->get('/orcamento', LandingController::class . '@campaign')->name('campaign.generic');
    $router->get('/orcamento-{nicho}', LandingController::class . '@campaign')->name('campaign.niche');

    // Paginas de venda dedicadas por produto (para trafego pago segmentado).
    $router->get('/precificador-de-servicos', LandingController::class . '@pricingPage')->name('sales.pricing');
    $router->get('/controle-financeiro', LandingController::class . '@financePage')->name('sales.finance');

    $router->get('/precos', PageController::class . '@pricing')->name('pricing');
    $router->get('/faq', PageController::class . '@faq')->name('faq');
    $router->get('/contato', PageController::class . '@contact')->name('contact');
    $router->post('/contato', PageController::class . '@sendContact')->middleware(['csrf', 'throttle:5,1']);
    $router->get('/termos-de-uso', PageController::class . '@terms')->name('terms');
    $router->get('/politica-de-privacidade', PageController::class . '@privacy')->name('privacy');
    $router->get('/politica-de-cookies', PageController::class . '@cookies')->name('cookies');
    $router->get('/sitemap.xml', PageController::class . '@sitemap')->name('sitemap');
});

/* ---------------------------------------------------------------------------
 | Autenticacao (apenas visitantes)
 |-------------------------------------------------------------------------- */
$router->group(['middleware' => ['guest']], function ($router) {
    $router->get('/cadastro', RegisterController::class . '@show')->name('register');
    $router->post('/cadastro', RegisterController::class . '@store')->middleware(['csrf', 'throttle:10,10']);

    $router->get('/login', LoginController::class . '@show')->name('login');
    $router->post('/login', LoginController::class . '@login')->middleware(['csrf', 'throttle:10,1']);

    $router->get('/esqueci-senha', PasswordController::class . '@requestForm')->name('password.request');
    $router->post('/esqueci-senha', PasswordController::class . '@sendLink')->middleware(['csrf', 'throttle:5,10']);
    $router->get('/redefinir-senha/{token}', PasswordController::class . '@resetForm')->name('password.reset');
    $router->post('/redefinir-senha', PasswordController::class . '@reset')->middleware(['csrf', 'throttle:5,10']);
});

// Confirmacao de e-mail (link enviado por e-mail).
$router->get('/confirmar-email/{token}', RegisterController::class . '@confirmEmail')->name('email.confirm');

// Logout.
$router->post('/logout', LoginController::class . '@logout')->middleware(['auth', 'csrf'])->name('logout');

/* ---------------------------------------------------------------------------
 | Link publico do orcamento (sem autenticacao, token seguro)
 |-------------------------------------------------------------------------- */
$router->group(['middleware' => ['tracking']], function ($router) {
    $router->get('/orcamento/{token}', PublicQuoteController::class . '@show')->name('quote.public');
    $router->post('/orcamento/{token}/aprovar', PublicQuoteController::class . '@approve')->middleware(['csrf']);
    $router->post('/orcamento/{token}/recusar', PublicQuoteController::class . '@reject')->middleware(['csrf']);
    $router->get('/orcamento/{token}/pdf', PublicQuoteController::class . '@pdf')->name('quote.public.pdf');
});

/* ---------------------------------------------------------------------------
 | Checkout e upsell
 |-------------------------------------------------------------------------- */
$router->group(['middleware' => ['tracking']], function ($router) {
    $router->get('/checkout/{slug}', CheckoutController::class . '@show')->name('checkout');
    $router->post('/checkout/{slug}', CheckoutController::class . '@process')->middleware(['csrf']);
    $router->get('/checkout/sucesso/{order}', CheckoutController::class . '@success')->name('checkout.success');
    $router->get('/checkout/erro/{order}', CheckoutController::class . '@error')->name('checkout.error');
    $router->post('/cupom/validar', CheckoutController::class . '@validateCoupon')->middleware(['csrf']);

    $router->get('/upsell/{order}', UpsellController::class . '@show')->name('upsell');
    $router->post('/upsell/{order}/aceitar', UpsellController::class . '@accept')->middleware(['csrf']);
    $router->post('/upsell/{order}/recusar', UpsellController::class . '@decline')->middleware(['csrf']);
});

/* ---------------------------------------------------------------------------
 | Area logada do usuario
 |-------------------------------------------------------------------------- */
$router->group(['middleware' => ['auth', 'tracking']], function ($router) {
    $router->get('/dashboard', DashboardController::class . '@index')->name('dashboard');

    // Onboarding pos-cadastro.
    $router->get('/onboarding', OnboardingController::class . '@show')->name('onboarding');
    $router->post('/onboarding', OnboardingController::class . '@store')->middleware(['csrf']);

    // Clientes.
    $router->get('/clientes', CustomerController::class . '@index')->name('customers.index');
    $router->get('/clientes/novo', CustomerController::class . '@create')->name('customers.create');
    $router->post('/clientes', CustomerController::class . '@store')->middleware(['csrf']);
    $router->get('/clientes/{id}', CustomerController::class . '@show')->name('customers.show');
    $router->get('/clientes/{id}/editar', CustomerController::class . '@edit')->name('customers.edit');
    $router->put('/clientes/{id}', CustomerController::class . '@update')->middleware(['csrf']);
    $router->delete('/clientes/{id}', CustomerController::class . '@destroy')->middleware(['csrf']);

    // Servicos / produtos de catalogo.
    $router->get('/servicos', ServiceController::class . '@index')->name('services.index');
    $router->get('/servicos/novo', ServiceController::class . '@create')->name('services.create');
    $router->post('/servicos', ServiceController::class . '@store')->middleware(['csrf']);
    $router->get('/servicos/{id}/editar', ServiceController::class . '@edit')->name('services.edit');
    $router->put('/servicos/{id}', ServiceController::class . '@update')->middleware(['csrf']);
    $router->delete('/servicos/{id}', ServiceController::class . '@destroy')->middleware(['csrf']);

    // Orcamentos (protegido por acesso ao produto "orcamentos").
    $router->group(['middleware' => ['product:orcamentos']], function ($router) {
        $router->get('/orcamentos', QuoteController::class . '@index')->name('quotes.index');
        $router->get('/orcamentos/novo', QuoteController::class . '@create')->name('quotes.create');
        $router->post('/orcamentos', QuoteController::class . '@store')->middleware(['csrf']);
        $router->get('/orcamentos/{id}', QuoteController::class . '@show')->name('quotes.show');
        $router->get('/orcamentos/{id}/editar', QuoteController::class . '@edit')->name('quotes.edit');
        $router->put('/orcamentos/{id}', QuoteController::class . '@update')->middleware(['csrf']);
        $router->delete('/orcamentos/{id}', QuoteController::class . '@destroy')->middleware(['csrf']);
        $router->post('/orcamentos/{id}/enviar', QuoteController::class . '@send')->middleware(['csrf']);
        $router->get('/orcamentos/{id}/pdf', QuoteController::class . '@pdf')->name('quotes.pdf');
        $router->post('/orcamentos/{id}/duplicar', QuoteController::class . '@duplicate')->middleware(['csrf']);
    });

    // Financeiro (produto "financeiro").
    $router->group(['middleware' => ['product:financeiro']], function ($router) {
        $router->get('/financeiro', FinanceController::class . '@dashboard')->name('finance.dashboard');
        $router->get('/receitas', FinanceController::class . '@revenues')->name('revenues.index');
        $router->post('/receitas', FinanceController::class . '@storeRevenue')->middleware(['csrf']);
        $router->put('/receitas/{id}', FinanceController::class . '@updateRevenue')->middleware(['csrf']);
        $router->delete('/receitas/{id}', FinanceController::class . '@destroyRevenue')->middleware(['csrf']);
        $router->get('/despesas', FinanceController::class . '@expenses')->name('expenses.index');
        $router->post('/despesas', FinanceController::class . '@storeExpense')->middleware(['csrf']);
        $router->put('/despesas/{id}', FinanceController::class . '@updateExpense')->middleware(['csrf']);
        $router->delete('/despesas/{id}', FinanceController::class . '@destroyExpense')->middleware(['csrf']);
    });

    // Precificador (produto "precificador").
    $router->group(['middleware' => ['product:precificador']], function ($router) {
        $router->get('/precificador', PricingController::class . '@index')->name('pricing.index');
        $router->post('/precificador/calcular', PricingController::class . '@calculate')->middleware(['csrf']);
        $router->post('/precificador/salvar', PricingController::class . '@save')->middleware(['csrf']);
    });

    // Configuracoes do usuario.
    $router->get('/configuracoes', UserSettingsController::class . '@index')->name('user.settings');
    $router->post('/configuracoes/perfil', UserSettingsController::class . '@updateProfile')->middleware(['csrf']);
    $router->post('/configuracoes/senha', UserSettingsController::class . '@updatePassword')->middleware(['csrf']);
    $router->post('/configuracoes/empresa', UserSettingsController::class . '@updateCompany')->middleware(['csrf']);
    $router->post('/conta/excluir', UserSettingsController::class . '@deleteAccount')->middleware(['csrf']);
});
