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
    ['app.name', 'Meu Orçamento', 'string', false, 'general', true],
    ['app.url', 'https://meuorcamento.lrvweb.com.br', 'string', false, 'general', true],
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
    ['mail.from_name', 'Meu Orçamento', 'string', false, 'mail', false],

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
        'description' => 'Crie orcamentos profissionais em 2 minutos, gere PDF com a sua marca e envie pelo WhatsApp. Acompanhe quando o cliente visualiza e aprova.',
        'price' => 39.90,
        'promo_price' => 19.90,
        'type' => 'one_time',
        'grants_access' => 'orcamentos,clientes,servicos',
        'features' => json_encode([
            'Orcamentos ilimitados',
            'PDF profissional com a sua marca',
            'Link publico com aprovar/recusar',
            'Envio em 1 clique pelo WhatsApp',
            'Status em tempo real (enviado, visto, aprovado)',
            'Cadastro de clientes e servicos reutilizaveis',
        ], JSON_UNESCAPED_UNICODE),
        'sort_order' => 1,
    ],
    [
        'name' => 'Kit Financeiro + Precificador',
        'slug' => 'kit-financeiro',
        'description' => 'Saiba exatamente quanto cobrar e para onde vai o seu dinheiro. Controle receitas, despesas e descubra o preco ideal dos seus servicos.',
        'price' => 49.90,
        'promo_price' => 29.90,
        'type' => 'one_time',
        'grants_access' => 'financeiro,precificador',
        'features' => json_encode([
            'Precificador inteligente (preco minimo e ideal)',
            'Controle de receitas e despesas',
            'Dashboard com lucro real do mes',
            'Alertas de contas a receber e a pagar',
            'Comparativo mes a mes',
        ], JSON_UNESCAPED_UNICODE),
        'sort_order' => 2,
    ],
    [
        'name' => 'Plano Completo',
        'slug' => 'plano-completo',
        'description' => 'A caixa de ferramentas completa do seu negocio: orcamentos, financeiro, precificador, clientes e relatorios num so lugar, com o melhor custo-beneficio.',
        'price' => 89.80,
        'promo_price' => 39.90,
        'type' => 'bundle',
        'grants_access' => 'orcamentos,financeiro,precificador,clientes,servicos,relatorios',
        'features' => json_encode([
            'Tudo do Gerador de Orcamentos',
            'Tudo do Kit Financeiro + Precificador',
            'Relatorios completos do negocio',
            'Economize mais de 50% vs. comprar separado',
            'Suporte prioritario',
        ], JSON_UNESCAPED_UNICODE),
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
                'Leve tambem o Kit Financeiro + Precificador com 33% OFF',
                'Voce ja monta orcamentos como um profissional. Agora descubra o preco ideal de cada servico e controle seu dinheiro sem planilha. So nesta tela: de R$ 29,90 por R$ 19,90 (pagamento unico).',
                19.90, 10.00, 1, now(), now(),
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
$btn = fn(string $url, string $label) => '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="' . $url . '" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">' . $label . '</a></td></tr></table>';
$muted = fn(string $t) => '<p style="color:#94a3b8;font-size:13px;margin-top:24px">' . $t . '</p>';

$emailTemplates = [
    ['welcome', 'Boas-vindas', 'Bem-vindo(a) ao {{app_name}}! 🎉',
        '<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Bem-vindo(a), {{name}}! 👋</h2>'
        . '<p>Que bom ter você aqui. A partir de agora, criar orçamentos profissionais, descobrir o preço certo de cobrar e organizar suas finanças vai ficar muito mais simples.</p>'
        . '<p><strong>Seu primeiro passo:</strong> crie um orçamento em menos de 2 minutos e envie pelo WhatsApp.</p>'
        . $btn('{{dashboard_url}}', 'Acessar meu painel')
        . '<p>Qualquer dúvida, é só responder este e-mail. Estamos por aqui. 🙂</p>'],

    ['email_confirmation', 'Confirmação de e-mail', 'Confirme seu e-mail no {{app_name}}',
        '<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Falta só um passo, {{name}}</h2>'
        . '<p>Confirme seu e-mail para deixar sua conta 100% ativa e segura.</p>'
        . $btn('{{confirm_url}}', 'Confirmar meu e-mail')
        . $muted('Se você não criou uma conta no {{app_name}}, pode ignorar este e-mail com segurança.')],

    ['password_reset', 'Recuperação de senha', 'Redefinição de senha — {{app_name}}',
        '<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Vamos redefinir sua senha</h2>'
        . '<p>Olá {{name}}, recebemos um pedido para redefinir a senha da sua conta. Clique no botão abaixo para criar uma nova senha:</p>'
        . $btn('{{reset_url}}', 'Criar nova senha')
        . $muted('Este link expira em 1 hora. Se não foi você que solicitou, ignore este e-mail — sua senha continua a mesma.')],

    ['purchase_approved', 'Compra aprovada', '✅ Pagamento aprovado — acesso liberado!',
        '<h2 style="margin:0 0 12px;color:#16a34a;font-size:22px">Pagamento aprovado! 🎉</h2>'
        . '<p>Obrigado, {{name}}! Seu pagamento foi confirmado e seu acesso já está liberado. Aproveite todos os recursos agora mesmo.</p>'
        . $btn('{{dashboard_url}}', 'Começar a usar agora')
        . '<p>Bom trabalho e boas vendas! 🚀</p>'],

    ['payment_failed', 'Pagamento recusado', 'Não conseguimos confirmar seu pagamento',
        '<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Ops, algo deu errado no pagamento</h2>'
        . '<p>Olá {{name}}, não conseguimos confirmar seu pagamento. Isso costuma ser algo simples (limite, dados do cartão ou instabilidade). Você pode tentar de novo em segundos:</p>'
        . $btn('{{checkout_url}}', 'Tentar novamente')
        . $muted('Nenhuma cobrança foi confirmada. Se precisar de ajuda, é só responder este e-mail.')],

    ['quote_sent', 'Orçamento enviado (cliente)', 'Você recebeu um orçamento 📄',
        '<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Olá {{customer_name}}, seu orçamento está pronto</h2>'
        . '<p>Preparamos um orçamento no valor de <strong style="color:#6366f1">{{quote_total}}</strong> para você. É rápido: abra, confira os detalhes e responda com um clique.</p>'
        . $btn('{{quote_url}}', 'Ver meu orçamento')
        . $muted('Você poderá aprovar ou recusar direto na página do orçamento.')],

    ['quote_approved', 'Orçamento aprovado (prestador)', '🎉 Orçamento {{quote_number}} aprovado!',
        '<h2 style="margin:0 0 12px;color:#16a34a;font-size:22px">Boa notícia, {{name}}!</h2>'
        . '<p>O orçamento <strong>{{quote_number}}</strong> acabou de ser <strong>aprovado</strong> pelo cliente. 🙌</p>'
        . '<p>Que tal já registrar essa receita e agendar o serviço?</p>'
        . $btn('{{dashboard_url}}', 'Ir para o painel')],

    ['quote_refused', 'Orçamento recusado (prestador)', 'Orçamento {{quote_number}} foi recusado',
        '<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Atualização do orçamento {{quote_number}}</h2>'
        . '<p>Olá {{name}}, o cliente recusou o orçamento <strong>{{quote_number}}</strong>. Acontece! Que tal revisar o valor ou as condições e reenviar uma nova proposta?</p>'
        . $btn('{{dashboard_url}}', 'Revisar e reenviar')],

    ['upsell', 'Oferta especial', '🎁 Uma oferta especial pra você, {{name}}',
        '<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">{{upsell_title}}</h2>'
        . '<p>Preparamos uma condição exclusiva para turbinar ainda mais o seu dia a dia. Dá uma olhada antes que expire:</p>'
        . $btn('{{upsell_url}}', 'Ver oferta especial')],

    ['reminder', 'Lembrete', '🔔 Um lembrete do {{app_name}}',
        '<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Olá {{name}}, passando pra lembrar</h2>'
        . '<p>{{reminder_body}}</p>'
        . $btn('{{dashboard_url}}', 'Acessar o painel')],

    ['contact', 'Contato (interno)', '📬 Nova mensagem de contato',
        '<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Nova mensagem pelo site</h2>'
        . '<p><strong>De:</strong> {{from_name}} &lt;{{from_email}}&gt;</p>'
        . '<div style="background:#f8fafc;border-left:3px solid #6366f1;padding:12px 16px;border-radius:8px;margin-top:12px">{{message}}</div>'],
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
    ['quote_created', 'Orcamento criado', "Ola, {{customer_name}}! 👋\n\nPreparei um orcamento especialmente pra voce, no valor de *{{quote_total}}*.\n\nQualquer duvida, e so me chamar por aqui. 😉"],
    ['quote_sent', 'Orcamento enviado', "Ola, {{customer_name}}! 📄\n\nSeu orcamento no valor de *{{quote_total}}* ja esta pronto. Da uma olhada e me avisa o que achou:\n\n👉 {{quote_url}}\n\nQualquer coisa, estou por aqui! 🙌"],
    ['quote_approved', 'Orcamento aprovado', "Que otima noticia! 🎉\n\nO orcamento *{{quote_number}}* foi aprovado. Ja vou dar andamento. Obrigado pela confianca! 🤝"],
    ['payment_approved', 'Pagamento aprovado', "Ola, {{name}}! ✅\n\nSeu pagamento foi *aprovado* e seu acesso ja esta liberado. Bom trabalho e boas vendas! 🚀"],
    ['checkout_recovery', 'Recuperacao de checkout', "Oi, {{name}}! 👀\n\nVi que voce comecou sua compra mas nao finalizou. Ta a um passo de destravar tudo!\n\nFinalize aqui em 1 minuto: 👉 {{checkout_url}}\n\nSe precisar de ajuda, e so responder. 🙂"],
    ['reminder', 'Lembrete', "Oi, {{name}}! 🔔\n\nSo passando pra lembrar: {{reminder_body}}"],
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
