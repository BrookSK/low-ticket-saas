# LowTicket SaaS

Plataforma SaaS para MEIs, autonomos e pequenos negocios, com tres solucoes integradas:

- **Gerador de Orcamentos** — crie orcamentos profissionais, gere PDF, compartilhe por link/WhatsApp e acompanhe status.
- **Kit Financeiro** — controle receitas e despesas com dashboard e comparativo mensal.
- **Precificador** — descubra quanto cobrar com base nos seus custos e margem.

Inclui painel administrativo completo, checkout com abstracao de gateways (Mercado Pago, Stripe, Asaas), upsell, cupons, webhooks, tracking de marketing (UTM/atribuicao), e-mail (SMTP) e WhatsApp.

> **Importante:** este projeto **NAO usa `.env`**. Todas as configuracoes (nome, URL, gateways, e-mail, WhatsApp, Google/Meta, etc.) sao gerenciadas pelo **Painel Administrativo** e ficam na tabela `settings`. O unico arquivo de configuracao sensivel e `config/database.php`, indispensavel para conectar ao banco.

---

## Requisitos

- PHP 8.1+ com extensoes: `pdo_mysql`, `mbstring`, `openssl`, `json`, `curl`
- MySQL 5.7+ ou MariaDB 10.3+
- Servidor Apache (com `mod_rewrite`) ou Nginx
- (Opcional) Composer, para instalar `dompdf` (PDF nativo) e `phpmailer` (SMTP robusto)

Sem Composer o sistema ainda funciona: ha um autoloader interno de fallback, o PDF cai em modo "imprimir" e o e-mail usa `mail()`.

---

## Instalacao

### 1. Clonar e configurar o banco

```bash
git clone <repo> lowticket-saas
cd lowticket-saas
```

Crie o banco:

```sql
CREATE DATABASE lowticket_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Copie e ajuste as credenciais:

```bash
cp config/database.example.php config/database.php
# edite config/database.php com host, database, username, password
```

### 2. Definir a chave de criptografia

Edite `config/app.php` e troque `app.key` por uma chave aleatoria de 32 bytes. Gere uma com:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Essa chave protege (criptografa) as credenciais sensiveis salvas no painel.

### 3. Dependencias (opcional, recomendado)

```bash
composer install
```

### 4. Migrations e seed

**Opcao A — Terminal (SSH/CLI):**

```bash
php database/migrate.php          # cria as tabelas (executa database/schema.sql)
php database/seed.php             # dados iniciais + Super Admin
php database/seed.php --demo      # (dev) inclui dados de demonstracao
```

**Opcao B — Instalador web (hospedagem sem SSH):**

Acesse no navegador: `https://SEU_DOMINIO/install.php`. O assistente testa a conexao,
cria as tabelas e popula os dados iniciais (voce informa o e-mail e a senha do Super Admin).
Ao terminar, clique em **Apagar install.php** — o proprio instalador se remove por seguranca.

**Opcao C — Importar o SQL manualmente (phpMyAdmin ou mysql):**

```bash
mysql -u USUARIO -p lowticket_saas < database/schema.sql
```
Depois rode o seed (Opcao A ou B) para criar o Super Admin e os produtos. Somente
importar o SQL cria as tabelas vazias, sem login administrativo nem produtos.

O seed cria um **Super Admin** padrao

Personalize com variaveis de ambiente antes de rodar o seed:

```bash
ADMIN_EMAIL="voce@dominio.com" ADMIN_PASSWORD="SenhaForte123" php database/seed.php
```

Ou crie/atualize um Super Admin a qualquer momento:

```bash
php database/create_admin.php "Seu Nome" voce@dominio.com "SenhaForte123"
```

> **Altere a senha padrao no primeiro acesso.**

### 5. Servidor web

Aponte o virtual host para a **raiz do projeto** (o `index.php` da raiz delega para `public/index.php`) ou diretamente para `public/`.

**Apache:** o `.htaccess` ja esta incluso (raiz e `public/`). Garanta `AllowOverride All` e `mod_rewrite` ativo.

**Nginx (apontando para /public):**

```nginx
server {
    listen 80;
    server_name meuorcamento.lrvweb.com.br;
    root /caminho/lowticket-saas/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    # Bloqueia acesso direto a pastas internas
    location ~ ^/(app|config|database|storage|resources|routes)/ { deny all; }
}
```

### 6. Permissoes

```bash
chmod -R 775 storage public/uploads
chmod 600 config/database.php
```

### 7. Cron (automacoes)

Adicione ao crontab para rodar a cada minuto:

```cron
* * * * * php /caminho/lowticket-saas/bin/cron.php >> /caminho/lowticket-saas/storage/logs/cron.log 2>&1
```

O cron processa: automacoes agendadas, recuperacao de checkout, contas vencidas e expiracao de orcamentos.

---

## Configuracao pelo painel (sem `.env`)

Acesse `/admin/login`, entre com o Super Admin e va em **Configuracoes**:

- **Geral:** nome, URL, moeda, timezone, logo/favicon, e-mail de suporte.
- **Pagamentos:** escolha o gateway (Mercado Pago / Stripe / Asaas), ambiente (sandbox/producao) e informe as credenciais. Configure o webhook no gateway apontando para `https://SEU_DOMINIO/api/webhooks/{gateway}` (ex: `/api/webhooks/mercadopago`).
- **E-mail (SMTP):** host, porta, usuario, senha, remetente.
- **WhatsApp:** provedor (WhatsApp Cloud API), token, numero.
- **Google:** GA4, Google Ads, Tag Manager (ative cada um e informe os IDs).
- **Meta:** Pixel.
- **Seguranca:** tentativas de login, bloqueio, sessao.
- **Marketing:** banner de cookies, janela de atribuicao.

Credenciais sensiveis (tokens, secret keys, senha SMTP) sao **armazenadas criptografadas**.

---

## Estrutura do projeto

```
/app
  /Controllers   (Admin, App, Auth, Checkout, Public, Webhook, Api, Site)
  /Core          (Application, Router, Request, Response, Database, View, ...)
  /Models
  /Services      (Auth, Settings, Order, Payment, Coupon, Quote, Pricing, ...)
  /Middleware
  /Payments      (PaymentGateway + MercadoPago/Stripe/Asaas/Null)
  /WhatsApp      (WhatsAppProvider + CloudApi/Null)
  /Support       (autoload, helpers)
/config          (app.php, database.php, session.php)  <- unica config em arquivo
/database        (schema.sql, migrate.php, seed.php, create_admin.php)
/public          (index.php, .htaccess, assets)         <- unica pasta exposta
/resources/views (layouts, site, app, admin, auth, checkout, public, pdf, partials)
/routes          (web.php, admin.php, api.php)
/storage         (logs, pdfs, cache)
/bin             (cron.php)
/tests           (Unit)
```

---

## Modelo comercial

Produtos e precos sao **configuraveis pelo painel** (nunca hardcoded). Seed inicial:

| Produto | Preco | Acesso liberado |
|---|---|---|
| Gerador de Orcamentos | R$ 19,90 | orcamentos, clientes, servicos |
| Kit Financeiro + Precificador | R$ 29,90 | financeiro, precificador |
| Plano Completo | R$ 39,90 | tudo |

**Upsell:** ao comprar o Gerador de Orcamentos, o sistema oferece o Kit Financeiro. O usuario pode aceitar, recusar ou seguir apenas com o produto inicial (nunca bloqueia o que ja comprou).

**Assinaturas:** a arquitetura (`plans`, `subscriptions`) ja esta preparada para planos recorrentes futuros.

---

## Fluxo de pagamento e webhooks

1. Usuario acessa `/checkout/{slug}` e confirma (com cupom opcional).
2. E criado um pedido `pending` e uma cobranca no gateway; o usuario e redirecionado.
3. O gateway notifica `POST /api/webhooks/{gateway}`.
4. O webhook e validado (assinatura), registrado (idempotente) e o pedido e atualizado.
5. Ao aprovar, o acesso aos modulos do produto e liberado automaticamente.

Se nenhum gateway estiver configurado, o checkout informa claramente que a configuracao e necessaria (nao simula aprovacao).

---

## Testes

```bash
composer test
# ou
vendor/bin/phpunit
```

Cobrem as regras criticas de calculo (orcamento e precificacao). Exemplo validado: custo R$ 350 + margem 30% = preco recomendado R$ 500.

---

## Seguranca

- PDO com prepared statements (sem SQL concatenado)
- Escape de saida (XSS), protecao CSRF em todos os formularios
- Rate limiting e protecao contra brute force no login
- Senhas com hash seguro; credenciais sensiveis criptografadas
- Sessao segura (httponly, samesite, regeneracao)
- Autorizacao por papel e por produto (modulos)
- Apenas `/public` exposto; pastas internas bloqueadas

---

## Rotas principais

Publico: `/`, `/precos`, `/faq`, `/contato`, `/termos-de-uso`, `/politica-de-privacidade`, `/politica-de-cookies`, `/orcamento-{nicho}`
Auth: `/login`, `/cadastro`, `/esqueci-senha`
App: `/dashboard`, `/orcamentos`, `/clientes`, `/servicos`, `/financeiro`, `/receitas`, `/despesas`, `/precificador`, `/configuracoes`
Checkout: `/checkout/{slug}`, `/upsell/{order}`
Admin: `/admin`, `/admin/vendas`, `/admin/usuarios`, `/admin/produtos`, `/admin/configuracoes`, `/admin/marketing`, `/admin/logs`
API: `/api/webhooks/{gateway}`, `/api/tracking/event`
