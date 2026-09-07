<?php

/**
 * Dados de demonstracao para ambiente de desenvolvimento.
 * CLARAMENTE SEPARADO da producao. Rode com: php database/seed.php --demo
 */

use App\Core\Database;

function seedDemo(Database $db): void
{
    echo "Populando dados de DEMONSTRACAO...\n";

    // Usuario demo.
    $email = 'demo@lowticket.local';
    $existing = $db->selectOne('SELECT id FROM users WHERE email = ?', [$email]);
    if ($existing) {
        $userId = (int) $existing['id'];
    } else {
        $userId = $db->insert(
            'INSERT INTO users (name, email, phone, whatsapp, password, email_verified_at, business_type, goal, status, onboarding_done, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,1,?,?)',
            ['Joao Eletricista', $email, '11999990000', '11999990000',
             password_hash('Demo@12345', PASSWORD_DEFAULT), now(), 'eletricista', 'todos', 'active', now(), now()]
        );
        $userRole = $db->selectOne('SELECT id FROM roles WHERE slug = ?', ['user']);
        if ($userRole) {
            $db->insert('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)', [$userId, $userRole['id']]);
        }
        // Concede acesso a todos os modulos para a demo.
        foreach (['orcamentos', 'financeiro', 'precificador', 'clientes', 'servicos', 'relatorios'] as $mod) {
            $db->insert('INSERT INTO user_product_access (user_id, module, source, granted_at) VALUES (?,?,?,?)',
                [$userId, $mod, 'admin', now()]);
        }
        echo "  usuario demo criado: {$email} / Demo@12345\n";
    }

    // Clientes demo.
    $customers = [
        ['Maria Souza', '123.456.789-00', 'maria@exemplo.com', '11988887777'],
        ['Construtora ABC Ltda', '12.345.678/0001-90', 'contato@abc.com', '1133334444'],
    ];
    $customerIds = [];
    foreach ($customers as [$name, $doc, $mail, $phone]) {
        $exists = $db->selectOne('SELECT id FROM customers WHERE user_id = ? AND name = ?', [$userId, $name]);
        if ($exists) {
            $customerIds[] = (int) $exists['id'];
            continue;
        }
        $customerIds[] = $db->insert(
            'INSERT INTO customers (user_id, name, document, email, phone, whatsapp, city, state, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$userId, $name, $doc, $mail, $phone, $phone, 'Sao Paulo', 'SP', now(), now()]
        );
    }

    // Servicos demo.
    $services = [
        ['Instalacao eletrica residencial', 'service', 150.00, 250.00, 'h'],
        ['Troca de disjuntor', 'service', 30.00, 80.00, 'un'],
        ['Cabo flexivel 2.5mm', 'product', 2.50, 4.00, 'm'],
    ];
    foreach ($services as [$name, $kind, $cost, $price, $unit]) {
        if (!$db->selectOne('SELECT id FROM services WHERE user_id = ? AND name = ?', [$userId, $name])) {
            $db->insert(
                'INSERT INTO services (user_id, name, kind, cost, suggested_price, unit, is_active, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,1,?,?)',
                [$userId, $name, $kind, $cost, $price, $unit, now(), now()]
            );
        }
    }

    // Orcamento demo.
    if (!empty($customerIds) && !$db->selectOne('SELECT id FROM quotes WHERE user_id = ? LIMIT 1', [$userId])) {
        $token = bin2hex(random_bytes(16));
        $quoteId = $db->insert(
            'INSERT INTO quotes (user_id, customer_id, number, public_token, title, status, subtotal, discount_type, discount_value, surcharge, total, payment_terms, valid_until, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$userId, $customerIds[0], 'ORC-0001', $token, 'Instalacao eletrica', 'sent',
             500.00, 'value', 50.00, 0, 450.00, '50% entrada, 50% na entrega',
             date('Y-m-d', strtotime('+15 days')), now(), now()]
        );
        $db->insert('INSERT INTO quote_items (quote_id, description, quantity, unit_price, total, sort_order) VALUES (?,?,?,?,?,0)',
            [$quoteId, 'Instalacao eletrica residencial', 2, 250.00, 500.00]);
        $db->insert('INSERT INTO quote_status_history (quote_id, from_status, to_status, actor, created_at) VALUES (?,?,?,?,?)',
            [$quoteId, 'draft', 'sent', 'user', now()]);
    }

    // Financeiro demo.
    if (!$db->selectOne('SELECT id FROM revenues WHERE user_id = ? LIMIT 1', [$userId])) {
        $db->insert('INSERT INTO revenues (user_id, customer_id, description, amount, date, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)',
            [$userId, $customerIds[0] ?? null, 'Servico eletrico - Maria', 450.00, date('Y-m-d'), 'received', now(), now()]);
        $db->insert('INSERT INTO revenues (user_id, description, amount, date, due_date, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)',
            [$userId, 'Manutencao mensal', 300.00, date('Y-m-d'), date('Y-m-d', strtotime('+10 days')), 'pending', now(), now()]);
    }
    if (!$db->selectOne('SELECT id FROM expenses WHERE user_id = ? LIMIT 1', [$userId])) {
        $db->insert('INSERT INTO expenses (user_id, supplier, description, amount, date, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)',
            [$userId, 'Loja de Materiais', 'Compra de cabos e disjuntores', 180.00, date('Y-m-d'), 'paid', now(), now()]);
    }

    echo "  dados de demonstracao aplicados.\n";
}
