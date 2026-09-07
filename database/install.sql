-- =============================================================================
-- LowTicket SaaS - INSTALACAO COMPLETA (estrutura + dados iniciais)
--
-- Rode este arquivo UMA VEZ no seu banco (phpMyAdmin > Importar, ou:
--   mysql -u USUARIO -p NOME_DO_BANCO < database/install.sql
--
-- Contem: todas as tabelas + roles, permissions, settings padrao, produtos,
-- upsell, planos, categorias, templates de e-mail/WhatsApp e o Super Admin.
--
-- SUPER ADMIN PADRAO:
--   e-mail:  admin@lowticket.local
--   senha:   Admin@12345
--   >>> TROQUE A SENHA no primeiro acesso (ou edite o INSERT no final).
--
-- Idempotente: pode rodar novamente sem duplicar (usa IF NOT EXISTS / INSERT IGNORE).
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- =============================================================================
-- ESTRUTURA (tabelas)
-- =============================================================================

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
    status             VARCHAR(20)  NOT NULL DEFAULT 'active',
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
    identifier   VARCHAR(191) NOT NULL,
    ip_address   VARCHAR(45)  NULL,
    successful   TINYINT(1)   NOT NULL DEFAULT 0,
    context      VARCHAR(32)  NOT NULL DEFAULT 'user',
    created_at   DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_la_identifier (identifier),
    KEY idx_la_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rl_key      VARCHAR(191) NOT NULL,
    hits        INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at  DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rl_key (rl_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name             VARCHAR(191) NOT NULL,
    slug             VARCHAR(191) NOT NULL,
    description      TEXT NULL,
    price            DECIMAL(10,2) NOT NULL DEFAULT 0,
    promo_price      DECIMAL(10,2) NULL,
    type             VARCHAR(32) NOT NULL DEFAULT 'one_time',
    grants_access    VARCHAR(255) NULL,
    features         LONGTEXT NULL,
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
    trigger_product_id BIGINT UNSIGNED NOT NULL,
    offer_product_id   BIGINT UNSIGNED NOT NULL,
    title         VARCHAR(191) NOT NULL,
    description   TEXT NULL,
    price         DECIMAL(10,2) NULL,
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

CREATE TABLE IF NOT EXISTS user_product_access (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    product_id  BIGINT UNSIGNED NULL,
    module      VARCHAR(64) NOT NULL,
    source      VARCHAR(32) NOT NULL DEFAULT 'purchase',
    granted_at  DATETIME NULL,
    expires_at  DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_upa (user_id, module),
    KEY idx_upa_user (user_id),
    CONSTRAINT fk_upa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_upa_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupons (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code            VARCHAR(64) NOT NULL,
    type            VARCHAR(16) NOT NULL DEFAULT 'percent',
    percent         DECIMAL(5,2) NULL,
    amount          DECIMAL(10,2) NULL,
    valid_from      DATETIME NULL,
    valid_until     DATETIME NULL,
    max_uses        INT UNSIGNED NULL,
    used_count      INT UNSIGNED NOT NULL DEFAULT 0,
    applicable_products VARCHAR(255) NULL,
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

CREATE TABLE IF NOT EXISTS orders (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference     VARCHAR(40) NOT NULL,
    user_id       BIGINT UNSIGNED NULL,
    email         VARCHAR(191) NULL,
    subtotal      DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount      DECIMAL(10,2) NOT NULL DEFAULT 0,
    total         DECIMAL(10,2) NOT NULL DEFAULT 0,
    coupon_id     BIGINT UNSIGNED NULL,
    gateway       VARCHAR(32) NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'pending',
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
    method         VARCHAR(32) NULL,
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
    event_id       VARCHAR(191) NULL,
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

CREATE TABLE IF NOT EXISTS subscriptions (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        BIGINT UNSIGNED NOT NULL,
    plan_id        BIGINT UNSIGNED NULL,
    gateway        VARCHAR(32) NULL,
    gateway_subscription_id VARCHAR(191) NULL,
    status         VARCHAR(20) NOT NULL DEFAULT 'inactive',
    current_period_start DATETIME NULL,
    current_period_end   DATETIME NULL,
    created_at     DATETIME NULL,
    updated_at     DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_sub_user (user_id),
    CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checkout_abandonment (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       BIGINT UNSIGNED NULL,
    email         VARCHAR(191) NULL,
    product_id    BIGINT UNSIGNED NULL,
    amount        DECIMAL(10,2) NULL,
    attribution_id BIGINT UNSIGNED NULL,
    status        VARCHAR(20) NOT NULL DEFAULT 'started',
    recovered_order_id BIGINT UNSIGNED NULL,
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_ab_status (status),
    KEY idx_ab_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(191) NOT NULL,
    document    VARCHAR(32)  NULL,
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

CREATE TABLE IF NOT EXISTS categories (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     BIGINT UNSIGNED NULL,
    name        VARCHAR(96) NOT NULL,
    type        VARCHAR(20) NOT NULL DEFAULT 'general',
    color       VARCHAR(16) NULL,
    created_at  DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_categories_user (user_id),
    KEY idx_categories_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id        BIGINT UNSIGNED NOT NULL,
    name           VARCHAR(191) NOT NULL,
    description    TEXT NULL,
    category_id    BIGINT UNSIGNED NULL,
    kind           VARCHAR(16) NOT NULL DEFAULT 'service',
    cost           DECIMAL(10,2) NULL,
    suggested_price DECIMAL(10,2) NULL,
    unit           VARCHAR(20) NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NULL,
    updated_at     DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_services_user (user_id),
    KEY idx_services_active (is_active),
    CONSTRAINT fk_services_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_services_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotes (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           BIGINT UNSIGNED NOT NULL,
    customer_id       BIGINT UNSIGNED NULL,
    number            VARCHAR(32) NOT NULL,
    public_token      VARCHAR(64) NOT NULL,
    title             VARCHAR(191) NULL,
    status            VARCHAR(20) NOT NULL DEFAULT 'draft',
    subtotal          DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_type     VARCHAR(10) NOT NULL DEFAULT 'value',
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
    actor       VARCHAR(32) NULL,
    ip_address  VARCHAR(45) NULL,
    created_at  DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_qsh_quote (quote_id),
    CONSTRAINT fk_qsh_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    status        VARCHAR(20) NOT NULL DEFAULT 'pending',
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
    status        VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at    DATETIME NULL,
    updated_at    DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_exp_user (user_id),
    KEY idx_exp_status (status),
    KEY idx_exp_date (date),
    CONSTRAINT fk_exp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_exp_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    result            LONGTEXT NULL,
    created_at        DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_pc_user (user_id),
    CONSTRAINT fk_pc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketing_attribution (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    visitor_id     VARCHAR(64) NULL,
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
    event        VARCHAR(64) NOT NULL,
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
    event         VARCHAR(64) NOT NULL,
    channel       VARCHAR(20) NOT NULL,
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
    status       VARCHAR(20) NOT NULL DEFAULT 'queued',
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

-- =============================================================================
-- DADOS INICIAIS (seed)
-- =============================================================================

-- Roles
INSERT IGNORE INTO roles (name, slug, description, created_at) VALUES
('Super Admin', 'super_admin', 'Acesso total ao sistema', NOW()),
('Admin', 'admin', 'Administrador', NOW()),
('Usuario', 'user', 'Usuario final da plataforma', NOW());

-- Permissions
INSERT IGNORE INTO permissions (name, slug, created_at) VALUES
('Manage Settings', 'manage_settings', NOW()),
('Manage Users', 'manage_users', NOW()),
('Manage Products', 'manage_products', NOW()),
('Manage Orders', 'manage_orders', NOW()),
('Manage Coupons', 'manage_coupons', NOW()),
('Manage Marketing', 'manage_marketing', NOW()),
('View Reports', 'view_reports', NOW()),
('View Logs', 'view_logs', NOW()),
('Manage Automations', 'manage_automations', NOW()),
('Manage Upsells', 'manage_upsells', NOW());

-- Settings padrao (credenciais sensiveis ficam vazias; preencha pelo painel).
-- Observacao: settings sensiveis serao criptografadas automaticamente quando
-- voce salva-las pelo painel; aqui entram vazias e nao criptografadas.
INSERT IGNORE INTO settings (`group`,`key`,`value`,`type`,`encrypted`,`is_public`,created_at,updated_at) VALUES
('general','app.name','Meu Orçamento','string',0,1,NOW(),NOW()),
('general','app.url','https://meuorcamento.lrvweb.com.br','string',0,1,NOW(),NOW()),
('general','app.currency','BRL','string',0,1,NOW(),NOW()),
('general','app.timezone','America/Sao_Paulo','string',0,0,NOW(),NOW()),
('general','app.logo','','string',0,1,NOW(),NOW()),
('general','app.favicon','','string',0,1,NOW(),NOW()),
('general','app.support_email','','string',0,1,NOW(),NOW()),
('payment','payment.provider','','string',0,0,NOW(),NOW()),
('payment','payment.mode','sandbox','string',0,0,NOW(),NOW()),
('payment','payment.mercadopago.access_token','','string',0,0,NOW(),NOW()),
('payment','payment.mercadopago.public_key','','string',0,0,NOW(),NOW()),
('payment','payment.mercadopago.webhook_secret','','string',0,0,NOW(),NOW()),
('payment','payment.stripe.secret_key','','string',0,0,NOW(),NOW()),
('payment','payment.stripe.publishable_key','','string',0,0,NOW(),NOW()),
('payment','payment.stripe.webhook_secret','','string',0,0,NOW(),NOW()),
('payment','payment.asaas.api_key','','string',0,0,NOW(),NOW()),
('payment','payment.asaas.webhook_token','','string',0,0,NOW(),NOW()),
('mail','mail.host','','string',0,0,NOW(),NOW()),
('mail','mail.port','587','int',0,0,NOW(),NOW()),
('mail','mail.username','','string',0,0,NOW(),NOW()),
('mail','mail.password','','string',0,0,NOW(),NOW()),
('mail','mail.encryption','tls','string',0,0,NOW(),NOW()),
('mail','mail.from_address','','string',0,0,NOW(),NOW()),
('mail','mail.from_name','Meu Orçamento','string',0,0,NOW(),NOW()),
('whatsapp','whatsapp.provider','','string',0,0,NOW(),NOW()),
('whatsapp','whatsapp.token','','string',0,0,NOW(),NOW()),
('whatsapp','whatsapp.phone_number','','string',0,0,NOW(),NOW()),
('whatsapp','whatsapp.phone_number_id','','string',0,0,NOW(),NOW()),
('whatsapp','whatsapp.api_url','','string',0,0,NOW(),NOW()),
('whatsapp','whatsapp.webhook_verify_token','','string',0,0,NOW(),NOW()),
('google','google.analytics_id','','string',0,1,NOW(),NOW()),
('google','google.ads_conversion_id','','string',0,1,NOW(),NOW()),
('google','google.ads_conversion_label','','string',0,1,NOW(),NOW()),
('google','google.tag_manager_id','','string',0,1,NOW(),NOW()),
('google','google.analytics_enabled','0','bool',0,1,NOW(),NOW()),
('google','google.ads_enabled','0','bool',0,1,NOW(),NOW()),
('google','google.gtm_enabled','0','bool',0,1,NOW(),NOW()),
('google','google.ads_api_configured','0','bool',0,0,NOW(),NOW()),
('meta','meta.pixel_id','','string',0,1,NOW(),NOW()),
('meta','meta.pixel_enabled','0','bool',0,1,NOW(),NOW()),
('security','security.max_login_attempts','5','int',0,0,NOW(),NOW()),
('security','security.lockout_minutes','15','int',0,0,NOW(),NOW()),
('security','security.session_lifetime','120','int',0,0,NOW(),NOW()),
('marketing','marketing.cookie_consent_enabled','1','bool',0,1,NOW(),NOW()),
('marketing','marketing.attribution_window_days','30','int',0,0,NOW(),NOW()),
('automation','automation.recovery_email_1_minutes','30','int',0,0,NOW(),NOW()),
('automation','automation.recovery_whatsapp_hours','4','int',0,0,NOW(),NOW()),
('automation','automation.recovery_email_2_hours','24','int',0,0,NOW(),NOW());

-- Produtos comerciais
INSERT IGNORE INTO products (name, slug, description, price, promo_price, type, grants_access, features, is_active, sort_order, created_at, updated_at) VALUES
('Gerador de Orcamentos','gerador-orcamentos','Crie orcamentos profissionais em 2 minutos, gere PDF com a sua marca e envie pelo WhatsApp. Acompanhe quando o cliente visualiza e aprova.',39.90,19.90,'one_time','orcamentos,clientes,servicos','["Orcamentos ilimitados","PDF profissional com a sua marca","Link publico com aprovar/recusar","Envio em 1 clique pelo WhatsApp","Status em tempo real (enviado, visto, aprovado)","Cadastro de clientes e servicos reutilizaveis"]',1,1,NOW(),NOW()),
('Kit Financeiro + Precificador','kit-financeiro','Saiba exatamente quanto cobrar e para onde vai o seu dinheiro. Controle receitas, despesas e descubra o preco ideal dos seus servicos.',49.90,29.90,'one_time','financeiro,precificador','["Precificador inteligente (preco minimo e ideal)","Controle de receitas e despesas","Dashboard com lucro real do mes","Alertas de contas a receber e a pagar","Comparativo mes a mes"]',1,2,NOW(),NOW()),
('Plano Completo','plano-completo','A caixa de ferramentas completa do seu negocio: orcamentos, financeiro, precificador, clientes e relatorios num so lugar, com o melhor custo-beneficio.',89.80,39.90,'bundle','orcamentos,financeiro,precificador,clientes,servicos,relatorios','["Tudo do Gerador de Orcamentos","Tudo do Kit Financeiro + Precificador","Relatorios completos do negocio","Economize mais de 50% vs. comprar separado","Suporte prioritario"]',1,3,NOW(),NOW());

-- Upsell: comprou Gerador de Orcamentos -> oferece Kit Financeiro
INSERT IGNORE INTO upsells (trigger_product_id, offer_product_id, title, description, price, discount, sort_order, is_active, created_at, updated_at)
SELECT tp.id, op.id,
       'Leve tambem o Kit Financeiro + Precificador com 33% OFF',
       'Voce ja monta orcamentos como um profissional. Agora descubra o preco ideal de cada servico e controle seu dinheiro sem planilha. So nesta tela: de R$ 29,90 por R$ 19,90 (pagamento unico).',
       19.90, 10.00, 1, 1, NOW(), NOW()
FROM products tp, products op
WHERE tp.slug = 'gerador-orcamentos' AND op.slug = 'kit-financeiro'
AND NOT EXISTS (SELECT 1 FROM upsells u WHERE u.trigger_product_id = tp.id AND u.offer_product_id = op.id);

-- Planos (assinatura futura)
INSERT IGNORE INTO plans (name, slug, price_monthly, grants_access, is_active, sort_order, created_at, updated_at) VALUES
('Gratuito','free',0,'orcamentos',1,1,NOW(),NOW()),
('Inicial','starter',19.90,'orcamentos,clientes,servicos',1,2,NOW(),NOW()),
('Profissional','pro',39.90,'orcamentos,financeiro,precificador,clientes,servicos,relatorios',1,3,NOW(),NOW());

-- Categorias globais (user_id NULL)
INSERT IGNORE INTO categories (user_id, name, type, created_at) VALUES
(NULL,'Servicos','revenue',NOW()),
(NULL,'Vendas','revenue',NOW()),
(NULL,'Outros','revenue',NOW()),
(NULL,'Materiais','expense',NOW()),
(NULL,'Impostos','expense',NOW()),
(NULL,'Transporte','expense',NOW()),
(NULL,'Marketing','expense',NOW()),
(NULL,'Outros','expense',NOW());

-- Templates de e-mail
INSERT IGNORE INTO email_templates (slug, name, subject, body, is_active, created_at, updated_at) VALUES
('welcome','Boas-vindas','Bem-vindo(a) ao {{app_name}}! 🎉','<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Bem-vindo(a), {{name}}! 👋</h2><p>Que bom ter você aqui. A partir de agora, criar orçamentos profissionais, descobrir o preço certo de cobrar e organizar suas finanças vai ficar muito mais simples.</p><p><strong>Seu primeiro passo:</strong> crie um orçamento em menos de 2 minutos e envie pelo WhatsApp.</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Acessar meu painel</a></td></tr></table><p>Qualquer dúvida, é só responder este e-mail. Estamos por aqui. 🙂</p>',1,NOW(),NOW()),
('email_confirmation','Confirmação de e-mail','Confirme seu e-mail no {{app_name}}','<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Falta só um passo, {{name}}</h2><p>Confirme seu e-mail para deixar sua conta 100% ativa e segura.</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{confirm_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Confirmar meu e-mail</a></td></tr></table><p style="color:#94a3b8;font-size:13px;margin-top:24px">Se você não criou uma conta no {{app_name}}, pode ignorar este e-mail com segurança.</p>',1,NOW(),NOW()),
('password_reset','Recuperação de senha','Redefinição de senha — {{app_name}}','<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Vamos redefinir sua senha</h2><p>Olá {{name}}, recebemos um pedido para redefinir a senha da sua conta. Clique no botão abaixo para criar uma nova senha:</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{reset_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Criar nova senha</a></td></tr></table><p style="color:#94a3b8;font-size:13px;margin-top:24px">Este link expira em 1 hora. Se não foi você que solicitou, ignore este e-mail — sua senha continua a mesma.</p>',1,NOW(),NOW()),
('purchase_approved','Compra aprovada','✅ Pagamento aprovado — acesso liberado!','<h2 style="margin:0 0 12px;color:#16a34a;font-size:22px">Pagamento aprovado! 🎉</h2><p>Obrigado, {{name}}! Seu pagamento foi confirmado e seu acesso já está liberado. Aproveite todos os recursos agora mesmo.</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Começar a usar agora</a></td></tr></table><p>Bom trabalho e boas vendas! 🚀</p>',1,NOW(),NOW()),
('payment_failed','Pagamento recusado','Não conseguimos confirmar seu pagamento','<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Ops, algo deu errado no pagamento</h2><p>Olá {{name}}, não conseguimos confirmar seu pagamento. Isso costuma ser algo simples (limite, dados do cartão ou instabilidade). Você pode tentar de novo em segundos:</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{checkout_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Tentar novamente</a></td></tr></table><p style="color:#94a3b8;font-size:13px;margin-top:24px">Nenhuma cobrança foi confirmada. Se precisar de ajuda, é só responder este e-mail.</p>',1,NOW(),NOW()),
('quote_sent','Orçamento enviado (cliente)','Você recebeu um orçamento 📄','<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Olá {{customer_name}}, seu orçamento está pronto</h2><p>Preparamos um orçamento no valor de <strong style="color:#6366f1">{{quote_total}}</strong> para você. É rápido: abra, confira os detalhes e responda com um clique.</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{quote_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Ver meu orçamento</a></td></tr></table><p style="color:#94a3b8;font-size:13px;margin-top:24px">Você poderá aprovar ou recusar direto na página do orçamento.</p>',1,NOW(),NOW()),
('quote_approved','Orçamento aprovado (prestador)','🎉 Orçamento {{quote_number}} aprovado!','<h2 style="margin:0 0 12px;color:#16a34a;font-size:22px">Boa notícia, {{name}}!</h2><p>O orçamento <strong>{{quote_number}}</strong> acabou de ser <strong>aprovado</strong> pelo cliente. 🙌</p><p>Que tal já registrar essa receita e agendar o serviço?</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Ir para o painel</a></td></tr></table>',1,NOW(),NOW()),
('quote_refused','Orçamento recusado (prestador)','Orçamento {{quote_number}} foi recusado','<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Atualização do orçamento {{quote_number}}</h2><p>Olá {{name}}, o cliente recusou o orçamento <strong>{{quote_number}}</strong>. Acontece! Que tal revisar o valor ou as condições e reenviar uma nova proposta?</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Revisar e reenviar</a></td></tr></table>',1,NOW(),NOW()),
('upsell','Oferta especial','🎁 Uma oferta especial pra você, {{name}}','<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">{{upsell_title}}</h2><p>Preparamos uma condição exclusiva para turbinar ainda mais o seu dia a dia. Dá uma olhada antes que expire:</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{upsell_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Ver oferta especial</a></td></tr></table>',1,NOW(),NOW()),
('reminder','Lembrete','🔔 Um lembrete do {{app_name}}','<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Olá {{name}}, passando pra lembrar</h2><p>{{reminder_body}}</p><table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="border-radius:10px;background:#6366f1"><a href="{{dashboard_url}}" style="display:inline-block;padding:13px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:15px;border-radius:10px">Acessar o painel</a></td></tr></table>',1,NOW(),NOW()),
('contact','Contato (interno)','📬 Nova mensagem de contato','<h2 style="margin:0 0 12px;color:#0f172a;font-size:22px">Nova mensagem pelo site</h2><p><strong>De:</strong> {{from_name}} &lt;{{from_email}}&gt;</p><div style="background:#f8fafc;border-left:3px solid #6366f1;padding:12px 16px;border-radius:8px;margin-top:12px">{{message}}</div>',1,NOW(),NOW());

-- Templates de WhatsApp
INSERT IGNORE INTO whatsapp_templates (slug, name, body, is_active, created_at, updated_at) VALUES
('quote_created','Orcamento criado','Ola, {{customer_name}}! 👋\n\nPreparei um orcamento especialmente pra voce, no valor de *{{quote_total}}*.\n\nQualquer duvida, e so me chamar por aqui. 😉',1,NOW(),NOW()),
('quote_sent','Orcamento enviado','Ola, {{customer_name}}! 📄\n\nSeu orcamento no valor de *{{quote_total}}* ja esta pronto. Da uma olhada e me avisa o que achou:\n\n👉 {{quote_url}}\n\nQualquer coisa, estou por aqui! 🙌',1,NOW(),NOW()),
('quote_approved','Orcamento aprovado','Que otima noticia! 🎉\n\nO orcamento *{{quote_number}}* foi aprovado. Ja vou dar andamento. Obrigado pela confianca! 🤝',1,NOW(),NOW()),
('payment_approved','Pagamento aprovado','Ola, {{name}}! ✅\n\nSeu pagamento foi *aprovado* e seu acesso ja esta liberado. Bom trabalho e boas vendas! 🚀',1,NOW(),NOW()),
('checkout_recovery','Recuperacao de checkout','Oi, {{name}}! 👀\n\nVi que voce comecou sua compra mas nao finalizou. Ta a um passo de destravar tudo!\n\nFinalize aqui em 1 minuto: 👉 {{checkout_url}}\n\nSe precisar de ajuda, e so responder. 🙂',1,NOW(),NOW()),
('reminder','Lembrete','Oi, {{name}}! 🔔\n\nSo passando pra lembrar: {{reminder_body}}',1,NOW(),NOW());

-- =============================================================================
-- SUPER ADMIN
-- E-mail: admin@lowticket.local  |  Senha: Admin@12345
-- O hash abaixo corresponde a "Admin@12345" (bcrypt). TROQUE apos o 1o acesso.
-- Para usar outro e-mail/senha, gere um hash com:
--   php -r "echo password_hash('SuaSenha', PASSWORD_DEFAULT);"
-- e substitua o valor abaixo.
-- =============================================================================
INSERT IGNORE INTO users (name, email, password, email_verified_at, status, onboarding_done, created_at, updated_at)
VALUES ('Super Admin', 'admin@lowticket.local',
        '$2y$12$bLP2ccflBp2L1aDDM5/lzex/mlpf0HDgXpVL8Tl7Ngf00fQxA0Coa',
        NOW(), 'active', 1, NOW(), NOW());

-- Vincula o Super Admin ao papel super_admin
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r
WHERE u.email = 'admin@lowticket.local' AND r.slug = 'super_admin';

SET FOREIGN_KEY_CHECKS = 1;

-- Fim. Acesse /admin/login com admin@lowticket.local / Admin@12345
