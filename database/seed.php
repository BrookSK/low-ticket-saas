<?php

/**
 * Seeder de dados iniciais essenciais (idempotente).
 *
 * Popula: roles, permissions, settings padrao, produtos comerciais,
 * planos, upsell, templates de e-mail e whatsapp, categorias globais e
 * cria o Super Admin inicial.
 *
 * Uso: php database/seed.php [--demo]
 *   --demo  Tambem popula dados de demonstracao (clientes, orcamentos, etc).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/Support/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Encrypter;
use App\Services\SettingsService;

Config::load(dirname(__DIR__) . '/config');

$db = new Database(Config::get('database'));
$encrypter = new Encrypter(Config::get('app.key', 'change-me'));
$settings = new SettingsService($db, $encrypter);

$withDemo = in_array('--demo', $argv ?? [], true);

echo "Populando dados iniciais...\n";

/* -------------------------------------------------------------------------
 | Roles e permissoes
 |------------------------------------------------------------------------ */
$roles = [
    ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Acesso total ao sistema'],
    ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrador'],
    ['name' => 'Usuario', 'slug' => 'user', 'description' => 'Usuario final da plataforma'],
];
foreach ($roles as $r) {
    $exists = $db->selectOne('SELECT id FROM roles WHERE slug = ?', [$r['slug']]);
    if (!$exists) {
        $db->insert('INSERT INTO roles (name, slug, description, created_at) VALUES (?,?,?,?)',
            [$r['name'], $r['slug'], $r['description'], now()]);
        echo "  role: {$r['slug']}\n";
    }
}

$permissions = [
    'manage_settings', 'manage_users', 'manage_products', 'manage_orders',
    'manage_coupons', 'manage_marketing', 'view_reports', 'view_logs',
    'manage_automations', 'manage_upsells',
];
foreach ($permissions as $p) {
    $exists = $db->selectOne('SELECT id FROM permissions WHERE slug = ?', [$p]);
    if (!$exists) {
        $db->insert('INSERT INTO permissions (name, slug, created_at) VALUES (?,?,?)',
            [ucwords(str_replace('_', ' ', $p)), $p, now()]);
    }
}

/* -------------------------------------------------------------------------
 | Settings padrao (substitui .env). Sensiveis ficam vazios ate config.
 |------------------------------------------------------------------------ */
$defaultSettings = [
    // Geral
    ['app.name', 'LowTicket SaaS', 'string', false, 'general', true],
    ['app.url', '', 'string', false, 'general', true],
    ['app.currency', 'BRL', 'string', false, 'general', true],
    ['app.timezone', 'America/Sao_Paulo', 'string', false, 'general', false],
    ['app.logo', '', 'string', false, 'general', true],
    ['app.favicon', '', 'string', false, 'general', true],
    ['app.support_email', '', 'string', false, 'general', true],

    // Pagamentos
    ['payment.provider', '', 'string', false, 'payment', false],
    ['payment.mode', 'sandbox', 'string', false, 'payment', false],
    ['payment.mercadopago.access_token', '', 'string', true, 'payment', false],
    ['payment.mercadopago.public_key', '', 'string', false, 'payment', false],
    ['payment.mercadopago.webhook_secret', '', 'string', true, 'payment', false],
    ['payment.stripe.secret_key', '', 'string', true, 'payment', false],
    ['payment.stripe.publishable_key', '', 'string', false, 'payment', false],
    ['payment.stripe.webhook_secret', '', 'string', true, 'payment', false],
    ['payment.asaas.api_key', '', 'string', true, 'payment', false],
    ['payment.asaas.webhook_token', '', 'string', true, 'payment', false],

    // E-mail (SMTP)
    ['mail.host', '', 'string', false, 'mail', false],
    ['mail.port', '587', 'int', false, 'mail', false],
    ['mail.username', '', 'string', false, 'mail', false],
    ['mail.password', '', 'string', true, 'mail', false],
    ['mail.encryption', 'tls', 'string', false, 'mail', false],
    ['mail.from_address', '', 'string', false, 'mail', false],
    ['mail.from_name', 'LowTicket SaaS', 'string', false, 'mail', false],

    // WhatsApp
    ['whatsapp.provider', '', 'string', false, 'whatsapp', false],
    ['whatsapp.token', '', 'string', true, 'whatsapp', false],
    ['whatsapp.phone_number', '', 'string', false, 'whatsapp', false],
    ['whatsapp.phone_number_id', '', 'string', false, 'whatsapp', false],
    ['whatsapp.api_url', '', 'string', false, 'whatsapp', false],
    ['whatsapp.webhook_verify_token', '', 'string', true, 'whatsapp', false],

    // Google
    ['google.analytics_id', '', 'string', false, 'google', true],
    ['google.ads_conversion_id', '', 'string', false, 'google', true],
    ['google.ads_conversion_label', '', 'string', false, 'google', true],
    ['google.tag_manager_id', '', 'string', false, 'google', true],
    ['google.analytics_enabled', '0', 'bool', false, 'google', true],
    ['google.ads_enabled', '0', 'bool', false, 'google', true],
    ['google.gtm_enabled', '0', 'bool', false, 'google', true],
    ['google.ads_api_configured', '0', 'bool', false, 'google', false],

    // Meta
    ['meta.pixel_id', '', 'string', false, 'meta', true],
    ['meta.pixel_enabled', '0', 'bool', false, 'meta', true],

    // Seguranca
    ['security.max_login_attempts', '5', 'int', false, 'security', false],
    ['security.lockout_minutes', '15', 'int', false, 'security', false],
    ['security.session_lifetime', '120', 'int', false, 'security', false],

    // Marketing
    ['marketing.cookie_consent_enabled', '1', 'bool', false, 'marketing', true],
    ['marketing.attribution_window_days', '30', 'int', false, 'marketing', false],

    // Automacoes de recuperacao (checkout abandonado)
    ['automation.recovery_email_1_minutes', '30', 'int', false, 'automation', false],
    ['automation.recovery_whatsapp_hours', '4', 'int', false, 'automation', false],
    ['automation.recovery_email_2_hours', '24', 'int', false, 'automation', false],
];

foreach ($defaultSettings as [$key, $value, $type, $encrypt, $group, $public]) {
    if (!$settings->has($key)) {
        $settings->set($key, $value, $type, $encrypt, $group, $public);
    }
}
echo "  settings padrao aplicadas\n";

/* -------------------------------------------------------------------------
 | Produtos comerciais (configuraveis; precos NAO hardcoded no codigo)
 |------------------------------------------------------------------------ */
$products = [
    [
        'name' => 'Gerador de Orcamentos',
        'slug' => 'gerador-orcamentos',
        'description' => 'Crie orcamentos profissionais em minutos, gere PDF e envie pelo WhatsApp.',
        'price' => 19.90,
        'promo_price' => null,
        'type' => 'one_time',
        'grants_access' => 'orcamentos,clientes,servicos',
        'features' => json_encode(['Orcamentos ilimitados', 'PDF profissional', 'Link publico', 'Envio por WhatsApp'], JSON_UNESCAPED_UNICODE),
        'sort_order' => 1,
    ],
    [
        'name' => 'Kit Financeiro + Precificador',
        'slug' => 'kit-financeiro',
        'description' => 'Controle receitas e despesas e descubra quanto cobrar pelos seus servicos.',
        'price' => 29.90,
        'promo_price' => null,
        'type' => 'one_time',
        'grants_access' => 'financeiro,precificador',
        'features' => json_encode(['Controle financeiro', 'Dashboard', 'Precificador inteligente', 'Relatorios'], JSON_UNESCAPED_UNICODE),
        'sort_order' => 2,
    ],
    [
        'name' => 'Plano Completo',
        'slug' => 'plano-completo',
        'description' => 'Tudo em um so lugar: orcamentos, financeiro, precificador, clientes e relatorios.',
        'price' => 39.90,
        'promo_price' => null,
        'type' => 'bundle',
        'grants_access' => 'orcamentos,financeiro,precificador,clientes,servicos,relatorios',
        'features' => json_encode(['Todos os recursos', 'Relatorios completos', 'Suporte prioritario'], JSON_UNESCAPED_UNICODE),
        'sort_order' => 3,
    ],
];

$productIds = [];
foreach ($products as $p) {
    $existing = $db->selectOne('SELECT id FROM products WHERE slug = ?', [$p['slug']]);
    if ($existing) {
        $productIds[$p['slug']] = (int) $existing['id'];
        continue;
    }
    $id = $db->insert(
        'INSERT INTO products (name, slug, description, price, promo_price, type, grants_access, features, is_active, sort_order, created_at, updated_at)
         VALUES (?,?,?,?,?,?,?,?,1,?,?,?)',
        [$p['name'], $p['slug'], $p['description'], $p['price'], $p['promo_price'], $p['type'],
         $p['grants_access'], $p['features'], $p['sort_order'], now(), now()]
    );
    $productIds[$p['slug']] = $id;
    echo "  produto: {$p['slug']}\n";
}

/* -------------------------------------------------------------------------
 | Upsell: comprou Gerador de Orcamentos -> oferece Kit Financeiro
 |------------------------------------------------------------------------ */
if (isset($productIds['gerador-orcamentos'], $productIds['kit-financeiro'])) {
    $exists = $db->selectOne(
        'SELECT id FROM upsells WHERE trigger_product_id = ? AND offer_product_id = ?',
        [$productIds['gerador-orcamentos'], $productIds['kit-financeiro']]
    );
    if (!$exists) {
        $db->insert(
            'INSERT INTO upsells (trigger_product_id, offer_product_id, title, description, price, discount, sort_order, is_active, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,1,?,?)',
            [
                $productIds['gerador-orcamentos'], $productIds['kit-financeiro'],
                'Controle tambem suas financas e descubra quanto cobrar',
                'Adicione o Kit Financeiro + Precificador com condicao especial agora.',
                29.90, 0, 1, now(), now(),
            ]
        );
        echo "  upsell configurado\n";
    }
}

/* -------------------------------------------------------------------------
 | Planos (assinatura futura)
 |------------------------------------------------------------------------ */
$plans = [
    ['name' => 'Gratuito', 'slug' => 'free', 'price_monthly' => 0, 'grants_access' => 'orcamentos', 'sort_order' => 1],
    ['name' => 'Inicial', 'slug' => 'starter', 'price_monthly' => 19.90, 'grants_access' => 'orcamentos,clientes,servicos', 'sort_order' => 2],
    ['name' => 'Profissional', 'slug' => 'pro', 'price_monthly' => 39.90, 'grants_access' => 'orcamentos,financeiro,precificador,clientes,servicos,relatorios', 'sort_order' => 3],
];
foreach ($plans as $pl) {
    if (!$db->selectOne('SELECT id FROM plans WHERE slug = ?', [$pl['slug']])) {
        $db->insert('INSERT INTO plans (name, slug, price_monthly, grants_access, is_active, sort_order, created_at, updated_at) VALUES (?,?,?,?,1,?,?,?)',
            [$pl['name'], $pl['slug'], $pl['price_monthly'], $pl['grants_access'], $pl['sort_order'], now(), now()]);
    }
}
echo "  planos aplicados\n";

/* -------------------------------------------------------------------------
 | Templates de e-mail
 |------------------------------------------------------------------------ */
$emailTemplates = [
    ['welcome', 'Boas-vindas', 'Bem-vindo(a) ao {{app_name}}!', "<p>Ola {{name}},</p><p>Sua conta foi criada com sucesso. Comece agora a criar orcamentos profissionais.</p><p><a href=\"{{dashboard_url}}\">Acessar minha conta</a></p>"],
    ['email_confirmation', 'Confirmacao de e-mail', 'Confirme seu e-mail', "<p>Ola {{name}},</p><p>Confirme seu e-mail clicando no link abaixo:</p><p><a href=\"{{confirm_url}}\">Confirmar e-mail</a></p>"],
    ['password_reset', 'Recuperacao de senha', 'Redefinicao de senha', "<p>Ola {{name}},</p><p>Recebemos um pedido para redefinir sua senha. Use o link abaixo (valido por 1 hora):</p><p><a href=\"{{reset_url}}\">Redefinir senha</a></p><p>Se nao foi voce, ignore este e-mail.</p>"],
    ['purchase_approved', 'Compra aprovada', 'Pagamento aprovado - {{app_name}}', "<p>Ola {{name}},</p><p>Seu pagamento foi aprovado! Seu acesso ja esta liberado.</p><p><a href=\"{{dashboard_url}}\">Acessar agora</a></p>"],
    ['payment_failed', 'Pagamento recusado', 'Nao conseguimos aprovar seu pagamento', "<p>Ola {{name}},</p><p>Seu pagamento nao foi aprovado. Voce pode tentar novamente:</p><p><a href=\"{{checkout_url}}\">Tentar novamente</a></p>"],
    ['quote_sent', 'Orcamento enviado', 'Voce recebeu um orcamento', "<p>Ola {{customer_name}},</p><p>Voce recebeu um orcamento no valor de {{quote_total}}.</p><p><a href=\"{{quote_url}}\">Ver orcamento</a></p>"],
    ['quote_approved', 'Orcamento aprovado', 'Seu orcamento foi aprovado', "<p>Ola {{name}},</p><p>O orcamento {{quote_number}} foi aprovado pelo cliente.</p>"],
    ['quote_refused', 'Orcamento recusado', 'Seu orcamento foi recusado', "<p>Ola {{name}},</p><p>O orcamento {{quote_number}} foi recusado pelo cliente.</p>"],
    ['upsell', 'Oferta especial', 'Uma oferta especial para voce', "<p>Ola {{name}},</p><p>{{upsell_title}}</p><p><a href=\"{{upsell_url}}\">Ver oferta</a></p>"],
    ['reminder', 'Lembrete', 'Voce tem algo pendente', "<p>Ola {{name}},</p><p>{{reminder_body}}</p>"],
    ['contact', 'Contato', 'Nova mensagem de contato', "<p>De: {{from_name}} ({{from_email}})</p><p>{{message}}</p>"],
];
foreach ($emailTemplates as [$slug, $name, $subject, $body]) {
    if (!$db->selectOne('SELECT id FROM email_templates WHERE slug = ?', [$slug])) {
        $db->insert('INSERT INTO email_templates (slug, name, subject, body, is_active, created_at, updated_at) VALUES (?,?,?,?,1,?,?)',
            [$slug, $name, $subject, $body, now(), now()]);
    }
}
echo "  templates de e-mail aplicados\n";

/* -------------------------------------------------------------------------
 | Templates de WhatsApp
 |------------------------------------------------------------------------ */
$waTemplates = [
    ['quote_created', 'Orcamento criado', 'Ola {{customer_name}}! Preparei seu orcamento no valor de {{quote_total}}.'],
    ['quote_sent', 'Orcamento enviado', 'Ola {{customer_name}}! Seu orcamento no valor de {{quote_total}} esta disponivel: {{quote_url}}'],
    ['quote_approved', 'Orcamento aprovado', 'Otimas noticias! O orcamento {{quote_number}} foi aprovado.'],
    ['payment_approved', 'Pagamento aprovado', 'Ola {{name}}! Seu pagamento foi aprovado e o acesso liberado.'],
    ['checkout_recovery', 'Recuperacao de checkout', 'Ola {{name}}, notamos que voce nao concluiu sua compra. Finalize aqui: {{checkout_url}}'],
    ['reminder', 'Lembrete', 'Ola {{name}}, este e um lembrete: {{reminder_body}}'],
];
foreach ($waTemplates as [$slug, $name, $body]) {
    if (!$db->selectOne('SELECT id FROM whatsapp_templates WHERE slug = ?', [$slug])) {
        $db->insert('INSERT INTO whatsapp_templates (slug, name, body, is_active, created_at, updated_at) VALUES (?,?,?,1,?,?)',
            [$slug, $name, $body, now(), now()]);
    }
}
echo "  templates de whatsapp aplicados\n";

/* -------------------------------------------------------------------------
 | Categorias globais
 |------------------------------------------------------------------------ */
$categories = [
    ['Servicos', 'revenue'], ['Vendas', 'revenue'], ['Outros', 'revenue'],
    ['Materiais', 'expense'], ['Impostos', 'expense'], ['Transporte', 'expense'], ['Marketing', 'expense'], ['Outros', 'expense'],
];
foreach ($categories as [$name, $type]) {
    if (!$db->selectOne('SELECT id FROM categories WHERE name = ? AND type = ? AND user_id IS NULL', [$name, $type])) {
        $db->insert('INSERT INTO categories (user_id, name, type, created_at) VALUES (NULL,?,?,?)', [$name, $type, now()]);
    }
}
echo "  categorias globais aplicadas\n";

/* -------------------------------------------------------------------------
 | Super Admin inicial
 |------------------------------------------------------------------------ */
$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@lowticket.local';
$adminPass = getenv('ADMIN_PASSWORD') ?: 'Admin@12345';

$existingAdmin = $db->selectOne('SELECT id FROM users WHERE email = ?', [$adminEmail]);
if (!$existingAdmin) {
    $userId = $db->insert(
        'INSERT INTO users (name, email, password, email_verified_at, status, onboarding_done, created_at, updated_at)
         VALUES (?,?,?,?,?,1,?,?)',
        ['Super Admin', $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT), now(), 'active', now(), now()]
    );
    $superRole = $db->selectOne('SELECT id FROM roles WHERE slug = ?', ['super_admin']);
    if ($superRole) {
        $db->insert('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)', [$userId, $superRole['id']]);
    }
    echo "  SUPER ADMIN criado: {$adminEmail} / senha: {$adminPass}\n";
    echo "  >> ALTERE A SENHA no primeiro acesso!\n";
} else {
    echo "  super admin ja existe ({$adminEmail})\n";
}

/* -------------------------------------------------------------------------
 | Dados de demonstracao (opcional)
 |------------------------------------------------------------------------ */
if ($withDemo) {
    require __DIR__ . '/seed_demo.php';
    seedDemo($db);
}

echo "Seed concluido.\n";
