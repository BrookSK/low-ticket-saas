<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Gestao de regras de automacao (evento -> canal -> template com atraso).
 */
class AutomationsController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(): Response
    {
        $rules = $this->db->select('SELECT * FROM automation_rules ORDER BY event, id');
        $emailTemplates = $this->db->select('SELECT slug, name FROM email_templates ORDER BY name');
        $waTemplates = $this->db->select('SELECT slug, name FROM whatsapp_templates ORDER BY name');

        return $this->view('admin.automations', [
            'title' => 'Automacoes',
            'rules' => $rules,
            'emailTemplates' => $emailTemplates,
            'waTemplates' => $waTemplates,
            'events' => $this->availableEvents(),
        ]);
    }

    public function store(Request $request): Response
    {
        $this->db->insert(
            'INSERT INTO automation_rules (name, event, channel, template_slug, delay_minutes, is_active, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?)',
            [
                trim((string) $request->input('name')),
                (string) $request->input('event'),
                (string) $request->input('channel'),
                $request->input('template_slug') ?: null,
                (int) $request->input('delay_minutes', 0),
                $request->input('is_active') ? 1 : 0,
                now(), now(),
            ]
        );
        $this->withFlash('success', 'Automacao criada.');
        return $this->redirect('/admin/automacoes');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->db->execute(
            'UPDATE automation_rules SET name=?, event=?, channel=?, template_slug=?, delay_minutes=?, is_active=?, updated_at=? WHERE id=?',
            [
                trim((string) $request->input('name')),
                (string) $request->input('event'),
                (string) $request->input('channel'),
                $request->input('template_slug') ?: null,
                (int) $request->input('delay_minutes', 0),
                $request->input('is_active') ? 1 : 0,
                now(), $id,
            ]
        );
        $this->withFlash('success', 'Automacao atualizada.');
        return $this->redirect('/admin/automacoes');
    }

    public function toggle(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->db->execute('UPDATE automation_rules SET is_active = 1 - is_active, updated_at = ? WHERE id = ?', [now(), $id]);
        return $this->redirect('/admin/automacoes');
    }

    protected function availableEvents(): array
    {
        return [
            'UserRegistered' => 'Usuario cadastrado',
            'CheckoutStarted' => 'Checkout iniciado',
            'checkout_abandoned' => 'Checkout abandonado',
            'PaymentApproved' => 'Pagamento aprovado',
            'PaymentFailed' => 'Pagamento recusado',
            'QuoteCreated' => 'Orcamento criado',
            'QuoteSent' => 'Orcamento enviado',
            'QuoteApproved' => 'Orcamento aprovado',
            'QuoteRefused' => 'Orcamento recusado',
        ];
    }
}
