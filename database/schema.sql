-- =============================================================================
-- LowTicket SaaS - Schema completo do banco de dados
-- MySQL / MariaDB (utf8mb4). Idempotente (CREATE TABLE IF NOT EXISTS).
-- Executado pelo runner database/migrate.php.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- CONFIGURACOES (substitui .env)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group`      VARCHAR(64)  NOT NULL DEFAULT 'general',
    `key`        VARCHAR(191) NOT NULL,
    `value`      LONGTEXT     NULL,
    `type`       VARCHAR(20)  NOT NULL DEFAULT 'string',
    `encrypted`  TINYINT(1)   NOT NULL DEFAULT 0,
    `is_public`  TINYINT(1)   NOT NULL DEFAULT 0,
    description  VARCHAR(255) NULL,
    created_at   DATETIME     NULL,
    updated_at   DATETIME     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (`key`),
    KEY idx_settings_group (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- RBAC: roles, permissions, users
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(64)  NOT NULL,
    slug        VARCHAR(64)  NOT NULL,
    description VARCHAR(191) NULL,
    created_at  DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(64)  NOT NULL,
    slug        VARCHAR(96)  NOT NULL,
    created_at  DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permissions_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id       BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Empresas / tenants (preparacao para multi-tenancy futura).
CREATE TABLE IF NOT EXISTS companies (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(191) NOT NULL,
    document      VARCHAR(32)  NULL,
    email         VARCHAR(191) NULL,
    phone         VARCHAR(32)  NULL,
    logo_path     VARCHAR(255) NULL,
    address       VARCHAR(255) NULL,
    city          VARCHAR(96)  NULL,
    state         VARCHAR(8)   NULL,
    zipcode       VARCHAR(16)  NULL,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id         BIGINT UNSIGNED NULL,
    name               VARCHAR(191) NOT NULL,
    email              VARCHAR(191) NOT NULL,
    phone              VARCHAR(32)  NULL,
    whatsapp           VARCHAR(32)  NULL,
    password           VARCHAR(255) NOT NULL,
    email_verified_at  DATETIME NULL,
    business_type      VARCHAR(64)  NULL,
    goal               VARCHAR(64)  NULL,
    status             VARCHAR(20)  NOT NULL DEFAULT 'active',  -- active | blocked | pending
    onboarding_done    TINYINT(1)   NOT NULL DEFAULT 0,
    remember_token     VARCHAR(100) NULL,
    last_login_at      DATETIME NULL,
    last_login_ip      VARCHAR(45)  NULL,
    created_at         DATETIME NULL,
    updated_at         DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_company (company_id),
    KEY idx_users_status (status),
    CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- SEGURANCA / AUTENTICACAO
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email      VARCHAR(191) NOT NULL,
    token      VARCHAR(191) NOT NULL,
    created_at DATETIME NULL,
    expires_at DATETIME NULL,
    used_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_pr_email (email),
    KEY idx_pr_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_confirmations (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    BIGINT UNSIGNED NOT NULL,
    token      VARCHAR(191) NOT NULL,
    created_at DATETIME NULL,
    expires_at DATETIME NULL,
    used_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_ec_token (token),
    CONSTRAINT fk_ec_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    identifier   VARCHAR(191) NOT NULL,  -- email ou ip
    ip_address   VARCHAR(45)  NULL,
    successful   TINYINT(1)   NOT NULL DEFAULT 0,
    context      VARCHAR(32)  NOT NULL DEFAULT 'user', -- user | admin
    created_at   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_la_identifier (identifier),
    KEY idx_la_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting generico (por chave).
CREATE TABLE IF NOT EXISTS rate_limits (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rl_key      VARCHAR(191) NOT NULL,
    hits        INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at  DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rl_key (rl_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- CATALOGO COMERCIAL (produtos vendaveis, planos, upsells)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name             VARCHAR(191) NOT NULL,
    slug             VARCHAR(191) NOT NULL,
    description      TEXT NULL,
    price            DECIMAL(10,2) NOT NULL DEFAULT 0,
    promo_price      DECIMAL(10,2) NULL,
    type             VARCHAR(32) NOT NULL DEFAULT 'one_time', -- one_time | subscription | bundle
    grants_access    VARCHAR(255) NULL,  -- csv de modulos: orcamentos,financeiro,precificador
    features         LONGTEXT NULL,      -- json de recursos
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    sort_order       INT NOT NULL DEFAULT 0,
    created_at       DATETIME NULL,
    updated_at       DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plans (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(96) NOT NULL,
    slug          VARCHAR(96) NOT NULL,
    price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_yearly  DECIMAL(10,2) NULL,
    features      LONGTEXT NULL,
    grants_access VARCHAR(255) NULL,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_plans_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS upsells (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    trigger_product_id BIGINT UNSIGNED NOT NULL, -- comprou este
    offer_product_id   BIGINT UNSIGNED NOT NULL, -- oferece este
    title         VARCHAR(191) NOT NULL,
    description   TEXT NULL,
    price         DECIMAL(10,2) NULL,   -- preco especial do upsell
    discount      DECIMAL(10,2) NULL,
    image_path    VARCHAR(255) NULL,
    sort_order    INT NOT NULL DEFAULT 0,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_upsell_trigger (trigger_product_id),
    CONSTRAINT fk_upsell_trigger FOREIGN KEY (trigger_product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_upsell_offer   FOREIGN KEY (offer_product_id)   REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Acesso concedido a modulos por usuario (resultado de compras).
CREATE TABLE IF NOT EXISTS user_product_access (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    product_id  BIGINT UNSIGNED NULL,
    module      VARCHAR(64) NOT NULL,  -- orcamentos | financeiro | precificador ...
    source      VARCHAR(32) NOT NULL DEFAULT 'purchase', -- purchase | admin | trial
    granted_at  DATETIME NULL,
    expires_at  DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_upa (user_id, module),
    KEY idx_upa_user (user_id),
    CONSTRAINT fk_upa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_upa_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- CUPONS
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code            VARCHAR(64) NOT NULL,
    type            VARCHAR(16) NOT NULL DEFAULT 'percent', -- percent | fixed
    percent         DECIMAL(5,2) NULL,
    amount          DECIMAL(10,2) NULL,
    valid_from      DATETIME NULL,
    valid_until     DATETIME NULL,
    max_uses        INT UNSIGNED NULL,
    used_count      INT UNSIGNED NOT NULL DEFAULT 0,
    applicable_products VARCHAR(255) NULL, -- csv de product_id, vazio = todos
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NULL,
    updated_at      DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_coupons_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupon_usages (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    coupon_id  BIGINT UNSIGNED NOT NULL,
    user_id    BIGINT UNSIGNED NULL,
    order_id   BIGINT UNSIGNED NULL,
    discount   DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_cu_coupon (coupon_id),
    CONSTRAINT fk_cu_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PEDIDOS / PAGAMENTOS
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference     VARCHAR(40) NOT NULL,  -- codigo publico do pedido
    user_id       BIGINT UNSIGNED NULL,
    email         VARCHAR(191) NULL,
    subtotal      DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount      DECIMAL(10,2) NOT NULL DEFAULT 0,
    total         DECIMAL(10,2) NOT NULL DEFAULT 0,
    coupon_id     BIGINT UNSIGNED NULL,
    gateway       VARCHAR(32) NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|approved|refused|canceled|refunded|expired
    is_upsell     TINYINT(1) NOT NULL DEFAULT 0,
    parent_order_id BIGINT UNSIGNED NULL,
    attribution_id  BIGINT UNSIGNED NULL,
    meta          LONGTEXT NULL,
    paid_at       DATETIME NULL,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_reference (reference),
    KEY idx_orders_user (user_id),
    KEY idx_orders_status (status),
    KEY idx_orders_created (created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id    BIGINT UNSIGNED NOT NULL,
    product_id  BIGINT UNSIGNED NULL,
    name        VARCHAR(191) NOT NULL,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantity    INT NOT NULL DEFAULT 1,
    created_at  DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_oi_order (order_id),
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id       BIGINT UNSIGNED NOT NULL,
    gateway        VARCHAR(32) NOT NULL,
    transaction_id VARCHAR(191) NULL,
    method         VARCHAR(32) NULL,   -- pix | credit_card | boleto
    amount         DECIMAL(10,2) NOT NULL DEFAULT 0,
    status         VARCHAR(20) NOT NULL DEFAULT 'pending',
    gateway_response LONGTEXT NULL,
    created_at     DATETIME NULL,
    updated_at     DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_pay_order (order_id),
    KEY idx_pay_txid (transaction_id),
    CONSTRAINT fk_pay_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_webhooks (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    gateway        VARCHAR(32) NOT NULL,
    event_id       VARCHAR(191) NULL,  -- id do evento no gateway (idempotencia)
    event_type     VARCHAR(96) NULL,
    order_id       BIGINT UNSIGNED NULL,
    payload        LONGTEXT NULL,
    headers        LONGTEXT NULL,
    signature_valid TINYINT(1) NOT NULL DEFAULT 0,
    processed      TINYINT(1) NOT NULL DEFAULT 0,
    processed_at   DATETIME NULL,
    created_at     DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_webhook_event (gateway, event_id),
    KEY idx_wh_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assinaturas (preparacao para planos recorrentes futuros).
CREATE TABLE IF NOT EXISTS subscriptions (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        BIGINT UNSIGNED NOT NULL,
    plan_id        BIGINT UNSIGNED NULL,
    gateway        VARCHAR(32) NULL,
    gateway_subscription_id VARCHAR(191) NULL,
    status         VARCHAR(20) NOT NULL DEFAULT 'inactive', -- active|inactive|canceled|past_due|trialing
    current_period_start DATETIME NULL,
    current_period_end   DATETIME NULL,
    created_at     DATETIME NULL,
    updated_at     DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_sub_user (user_id),
    CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recuperacao de checkout abandonado.
CREATE TABLE IF NOT EXISTS checkout_abandonment (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       BIGINT UNSIGNED NULL,
    email         VARCHAR(191) NULL,
    product_id    BIGINT UNSIGNED NULL,
    amount        DECIMAL(10,2) NULL,
    attribution_id BIGINT UNSIGNED NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'started', -- started|recovered|lost
    recovered_order_id BIGINT UNSIGNED NULL,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_ab_status (status),
    KEY idx_ab_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- CLIENTES (do usuario)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(191) NOT NULL,
    document    VARCHAR(32)  NULL,   -- CPF/CNPJ
    email       VARCHAR(191) NULL,
    phone       VARCHAR(32)  NULL,
    whatsapp    VARCHAR(32)  NULL,
    address     VARCHAR(255) NULL,
    city        VARCHAR(96)  NULL,
    state       VARCHAR(8)   NULL,
    zipcode     VARCHAR(16)  NULL,
    notes       TEXT NULL,
    created_at  DATETIME NULL,
    updated_at  DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_customers_user (user_id),
    KEY idx_customers_name (name),
    CONSTRAINT fk_customers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categorias (financeiro e catalogo).
CREATE TABLE IF NOT EXISTS categories (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NULL,   -- null = categoria global
    name        VARCHAR(96) NOT NULL,
    type        VARCHAR(20) NOT NULL DEFAULT 'general', -- revenue | expense | service | general
    color       VARCHAR(16) NULL,
    created_at  DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_categories_user (user_id),
    KEY idx_categories_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Servicos / produtos reutilizaveis nos orcamentos.
CREATE TABLE IF NOT EXISTS services (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(191) NOT NULL,
    description    TEXT NULL,
    category_id    BIGINT UNSIGNED NULL,
    kind           VARCHAR(16) NOT NULL DEFAULT 'service', -- service | product
    cost           DECIMAL(10,2) NULL,
    suggested_price DECIMAL(10,2) NULL,
    unit           VARCHAR(20) NULL,  -- un | h | m2 | ...
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NULL,
    updated_at     DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_services_user (user_id),
    KEY idx_services_active (is_active),
    CONSTRAINT fk_services_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_services_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- ORCAMENTOS
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quotes (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           BIGINT UNSIGNED NOT NULL,
    customer_id       BIGINT UNSIGNED NULL,
    number            VARCHAR(32) NOT NULL,  -- numero sequencial exibido
    public_token      VARCHAR(64) NOT NULL,  -- link publico seguro
    title             VARCHAR(191) NULL,
    status            VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft|sent|viewed|approved|refused|expired|canceled
    subtotal          DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_type     VARCHAR(10) NOT NULL DEFAULT 'value', -- value | percent
    discount_value    DECIMAL(10,2) NOT NULL DEFAULT 0,
    surcharge         DECIMAL(10,2) NOT NULL DEFAULT 0,
    total             DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes             TEXT NULL,
    payment_terms     VARCHAR(255) NULL,
    execution_deadline VARCHAR(191) NULL,
    valid_until       DATE NULL,
    sent_at           DATETIME NULL,
    viewed_at         DATETIME NULL,
    responded_at      DATETIME NULL,
    response_ip       VARCHAR(45) NULL,
    created_at        DATETIME NULL,
    updated_at        DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quotes_token (public_token),
    KEY idx_quotes_user (user_id),
    KEY idx_quotes_customer (customer_id),
    KEY idx_quotes_status (status),
    CONSTRAINT fk_quotes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_quotes_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quote_items (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quote_id    BIGINT UNSIGNED NOT NULL,
    service_id  BIGINT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    quantity    DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
    total       DECIMAL(10,2) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_qi_quote (quote_id),
    CONSTRAINT fk_qi_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    CONSTRAINT fk_qi_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quote_status_history (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quote_id    BIGINT UNSIGNED NOT NULL,
    from_status VARCHAR(20) NULL,
    to_status   VARCHAR(20) NOT NULL,
    note        VARCHAR(255) NULL,
    actor       VARCHAR(32) NULL,  -- user | customer | system
    ip_address  VARCHAR(45) NULL,
    created_at  DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_qsh_quote (quote_id),
    CONSTRAINT fk_qsh_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- FINANCEIRO
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS revenues (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       BIGINT UNSIGNED NOT NULL,
    customer_id   BIGINT UNSIGNED NULL,
    category_id   BIGINT UNSIGNED NULL,
    quote_id      BIGINT UNSIGNED NULL,
    description   VARCHAR(191) NOT NULL,
    amount        DECIMAL(10,2) NOT NULL DEFAULT 0,
    date          DATE NULL,
    due_date      DATE NULL,
    received_at   DATE NULL,
    payment_method VARCHAR(32) NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|received|overdue|canceled
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_rev_user (user_id),
    KEY idx_rev_status (status),
    KEY idx_rev_date (date),
    CONSTRAINT fk_rev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    CONSTRAINT fk_rev_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expenses (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       BIGINT UNSIGNED NOT NULL,
    supplier      VARCHAR(191) NULL,
    category_id   BIGINT UNSIGNED NULL,
    description   VARCHAR(191) NOT NULL,
    amount        DECIMAL(10,2) NOT NULL DEFAULT 0,
    date          DATE NULL,
    due_date      DATE NULL,
    paid_at       DATE NULL,
    payment_method VARCHAR(32) NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending|paid|overdue|canceled
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_exp_user (user_id),
    KEY idx_exp_status (status),
    KEY idx_exp_date (date),
    CONSTRAINT fk_exp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_exp_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PRECIFICADOR
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pricing_calculations (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           BIGINT UNSIGNED NOT NULL,
    name              VARCHAR(191) NULL,
    material_cost     DECIMAL(10,2) NOT NULL DEFAULT 0,
    labor_cost        DECIMAL(10,2) NOT NULL DEFAULT 0,
    hours             DECIMAL(10,2) NOT NULL DEFAULT 0,
    travel_cost       DECIMAL(10,2) NOT NULL DEFAULT 0,
    fixed_cost        DECIMAL(10,2) NOT NULL DEFAULT 0,
    taxes_percent     DECIMAL(5,2) NOT NULL DEFAULT 0,
    fees_percent      DECIMAL(5,2) NOT NULL DEFAULT 0,
    margin_percent    DECIMAL(5,2) NOT NULL DEFAULT 0,
    other_costs       DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_cost        DECIMAL(10,2) NOT NULL DEFAULT 0,
    recommended_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    result            LONGTEXT NULL, -- json com os demais precos calculados
    created_at        DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_pc_user (user_id),
    CONSTRAINT fk_pc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- MARKETING / TRACKING / ATRIBUICAO
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marketing_attribution (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    visitor_id     VARCHAR(64) NULL,  -- cookie anonimo
    user_id        BIGINT UNSIGNED NULL,
    first_source   VARCHAR(96) NULL,
    first_medium   VARCHAR(96) NULL,
    first_campaign VARCHAR(191) NULL,
    first_content  VARCHAR(191) NULL,
    first_term     VARCHAR(191) NULL,
    first_landing  VARCHAR(255) NULL,
    first_referrer VARCHAR(255) NULL,
    first_gclid    VARCHAR(191) NULL,
    first_fbclid   VARCHAR(191) NULL,
    first_at       DATETIME NULL,
    last_source    VARCHAR(96) NULL,
    last_medium    VARCHAR(96) NULL,
    last_campaign  VARCHAR(191) NULL,
    last_content   VARCHAR(191) NULL,
    last_term      VARCHAR(191) NULL,
    last_gclid     VARCHAR(191) NULL,
    last_fbclid    VARCHAR(191) NULL,
    last_at        DATETIME NULL,
    device         VARCHAR(32) NULL,
    browser        VARCHAR(64) NULL,
    created_at     DATETIME NULL,
    updated_at     DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_attr_visitor (visitor_id),
    KEY idx_attr_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_events (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event        VARCHAR(64) NOT NULL, -- page_view, signup, purchase, ...
    user_id      BIGINT UNSIGNED NULL,
    visitor_id   VARCHAR(64) NULL,
    attribution_id BIGINT UNSIGNED NULL,
    value        DECIMAL(10,2) NULL,
    currency     VARCHAR(8) NULL,
    transaction_id VARCHAR(191) NULL,
    properties   LONGTEXT NULL,
    url          VARCHAR(255) NULL,
    created_at   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_ae_event (event),
    KEY idx_ae_user (user_id),
    KEY idx_ae_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- COMUNICACAO / AUTOMACOES / NOTIFICACOES
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_templates (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug         VARCHAR(64) NOT NULL,
    name         VARCHAR(191) NOT NULL,
    subject      VARCHAR(255) NOT NULL,
    body         LONGTEXT NOT NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NULL,
    updated_at   DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_et_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug         VARCHAR(64) NOT NULL,
    name         VARCHAR(191) NOT NULL,
    body         LONGTEXT NOT NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   DATETIME NULL,
    updated_at   DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wt_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS automation_rules (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(191) NOT NULL,
    event         VARCHAR(64) NOT NULL,  -- checkout_abandoned, quote_sent, ...
    channel       VARCHAR(20) NOT NULL,  -- email | whatsapp | internal
    template_slug VARCHAR(64) NULL,
    delay_minutes INT NOT NULL DEFAULT 0,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    config        LONGTEXT NULL,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_ar_event (event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS automation_logs (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id      BIGINT UNSIGNED NULL,
    event        VARCHAR(64) NULL,
    channel      VARCHAR(20) NULL,
    recipient    VARCHAR(191) NULL,
    status       VARCHAR(20) NOT NULL DEFAULT 'queued', -- queued|sent|failed
    error        VARCHAR(255) NULL,
    scheduled_at DATETIME NULL,
    sent_at      DATETIME NULL,
    created_at   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_al_status (status),
    KEY idx_al_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      BIGINT UNSIGNED NOT NULL,
    type         VARCHAR(64) NOT NULL,
    title        VARCHAR(191) NOT NULL,
    body         TEXT NULL,
    link         VARCHAR(255) NULL,
    read_at      DATETIME NULL,
    created_at   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_notif_user (user_id),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- LOGS ADMINISTRATIVOS
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_logs (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      BIGINT UNSIGNED NULL,
    action       VARCHAR(96) NOT NULL,
    severity     VARCHAR(16) NOT NULL DEFAULT 'info',
    description  VARCHAR(255) NULL,
    meta         LONGTEXT NULL,
    ip_address   VARCHAR(45) NULL,
    created_at   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_alog_action (action),
    KEY idx_alog_severity (severity),
    KEY idx_alog_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documentos anexados a clientes (para historico).
CREATE TABLE IF NOT EXISTS customer_documents (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id  BIGINT UNSIGNED NOT NULL,
    name         VARCHAR(191) NOT NULL,
    path         VARCHAR(255) NOT NULL,
    created_at   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_cd_customer (customer_id),
    CONSTRAINT fk_cd_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
