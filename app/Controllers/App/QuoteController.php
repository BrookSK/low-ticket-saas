<?php

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\EventService;
use App\Services\MailService;
use App\Services\PdfService;
use App\Services\QuoteService;
use App\Services\WhatsAppService;

/**
 * Gerador de Orcamentos: CRUD, envio, PDF, duplicacao.
 */
class QuoteController extends Controller
{
    protected Database $db;
    protected QuoteService $quotes;
    protected EventService $events;

    public function __construct(Database $db, QuoteService $quotes, EventService $events)
    {
        $this->db = $db;
        $this->quotes = $quotes;
        $this->events = $events;
    }

    public function index(Request $request): Response
    {
        $userId = $this->auth()->id();
        $status = (string) $request->query('status', '');
        $where = 'q.user_id = ?';
        $bindings = [$userId];
        if ($status !== '') {
            $where .= ' AND q.status = ?';
            $bindings[] = $status;
        }
        $quotes = $this->db->select(
            "SELECT q.*, c.name AS customer_name FROM quotes q
             LEFT JOIN customers c ON c.id = q.customer_id
             WHERE {$where} ORDER BY q.created_at DESC",
            $bindings
        );
        return $this->view('app.quotes.index', ['title' => 'Orcamentos', 'quotes' => $quotes, 'status' => $status]);
    }

    public function create(Request $request): Response
    {
        return $this->view('app.quotes.form', [
            'title' => 'Novo orcamento',
            'quote' => null,
            'items' => [],
            'customers' => $this->customers(),
            'services' => $this->services(),
            'selectedCustomer' => (int) $request->query('customer_id', 0),
        ]);
    }

    public function store(Request $request): Response
    {
        $userId = $this->auth()->id();
        $data = $this->payload($request);
        $items = $this->parseItems($request);

        if (empty($items)) {
            $this->withErrors(['items' => 'Adicione ao menos um item ao orcamento.'], $data);
            return $this->redirect('/orcamentos/novo');
        }

        $quoteId = $this->quotes->create($userId, $data, $items);

        $this->events->dispatch('QuoteCreated', ['user_id' => $userId, 'quote_id' => $quoteId]);
        $this->withFlash('success', 'Orcamento criado.');
        return $this->redirect('/orcamentos/' . $quoteId);
    }

    public function show(Request $request): Response
    {
        $quote = $this->ownedWithItems((int) $request->param('id'));
        $customer = $quote['customer_id'] ? $this->db->selectOne('SELECT * FROM customers WHERE id = ?', [$quote['customer_id']]) : null;
        $history = $this->db->select('SELECT * FROM quote_status_history WHERE quote_id = ? ORDER BY created_at DESC', [$quote['id']]);

        return $this->view('app.quotes.show', [
            'title' => 'Orcamento ' . $quote['number'],
            'quote' => $quote,
            'customer' => $customer,
            'history' => $history,
        ]);
    }

    public function edit(Request $request): Response
    {
        $quote = $this->ownedWithItems((int) $request->param('id'));
        return $this->view('app.quotes.form', [
            'title' => 'Editar orcamento',
            'quote' => $quote,
            'items' => $quote['items'],
            'customers' => $this->customers(),
            'services' => $this->services(),
            'selectedCustomer' => (int) $quote['customer_id'],
        ]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->ownedWithItems($id);
        $data = $this->payload($request);
        $items = $this->parseItems($request);
        if (empty($items)) {
            $this->withErrors(['items' => 'Adicione ao menos um item.'], $data);
            return $this->redirect('/orcamentos/' . $id . '/editar');
        }
        $this->quotes->update($id, $data, $items);
        $this->withFlash('success', 'Orcamento atualizado.');
        return $this->redirect('/orcamentos/' . $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->ownedWithItems($id);
        $this->db->execute('DELETE FROM quotes WHERE id = ?', [$id]);
        $this->withFlash('success', 'Orcamento removido.');
        return $this->redirect('/orcamentos');
    }

    /**
     * Marca como enviado e dispara notificacoes (e-mail/WhatsApp) ao cliente.
     */
    public function send(Request $request): Response
    {
        $id = (int) $request->param('id');
        $quote = $this->ownedWithItems($id);
        $customer = $quote['customer_id'] ? $this->db->selectOne('SELECT * FROM customers WHERE id = ?', [$quote['customer_id']]) : null;

        $this->quotes->changeStatus($id, 'sent', 'user');

        $publicUrl = url('/orcamento/' . $quote['public_token']);
        $vars = [
            'customer_name' => $customer['name'] ?? 'cliente',
            'quote_number' => $quote['number'],
            'quote_total' => money($quote['total']),
            'quote_url' => $publicUrl,
        ];

        if ($customer && !empty($customer['email'])) {
            try { app(MailService::class)->sendTemplate('quote_sent', $customer['email'], $vars); } catch (\Throwable $e) {}
        }
        if ($customer && !empty($customer['whatsapp'])) {
            try { app(WhatsAppService::class)->sendTemplate('quote_sent', $customer['whatsapp'], $vars); } catch (\Throwable $e) {}
        }

        $this->events->dispatch('QuoteSent', ['user_id' => $this->auth()->id(), 'quote_id' => $id]);
        $this->withFlash('success', 'Orcamento enviado. Link publico: ' . $publicUrl);
        return $this->redirect('/orcamentos/' . $id);
    }

    public function pdf(Request $request): Response
    {
        $quote = $this->ownedWithItems((int) $request->param('id'));
        return $this->renderPdf($quote);
    }

    public function duplicate(Request $request): Response
    {
        $id = (int) $request->param('id');
        $quote = $this->ownedWithItems($id);
        $items = array_map(fn($it) => [
            'service_id' => $it['service_id'],
            'description' => $it['description'],
            'quantity' => $it['quantity'],
            'unit_price' => $it['unit_price'],
        ], $quote['items']);

        $newId = $this->quotes->create((int) $this->auth()->id(), [
            'customer_id' => $quote['customer_id'],
            'title' => $quote['title'],
            'discount_type' => $quote['discount_type'],
            'discount_value' => $quote['discount_value'],
            'surcharge' => $quote['surcharge'],
            'notes' => $quote['notes'],
            'payment_terms' => $quote['payment_terms'],
            'execution_deadline' => $quote['execution_deadline'],
            'valid_until' => $quote['valid_until'],
        ], $items);

        $this->withFlash('success', 'Orcamento duplicado.');
        return $this->redirect('/orcamentos/' . $newId);
    }

    // ---- Helpers ----

    protected function renderPdf(array $quote): Response
    {
        $user = $this->user();
        $company = $user['company_id'] ? $this->db->selectOne('SELECT * FROM companies WHERE id = ?', [$user['company_id']]) : null;
        $customer = $quote['customer_id'] ? $this->db->selectOne('SELECT * FROM customers WHERE id = ?', [$quote['customer_id']]) : null;

        return app(PdfService::class)->fromView('pdf.quote', [
            'quote' => $quote,
            'company' => $company,
            'customer' => $customer,
            'user' => $user,
        ], 'orcamento-' . $quote['number'] . '.pdf');
    }

    protected function ownedWithItems(int $id): array
    {
        $quote = $this->quotes->findWithItems($id);
        if (!$quote || (int) $quote['user_id'] !== (int) $this->auth()->id()) {
            $this->abort(404, 'Orcamento nao encontrado.');
        }
        return $quote;
    }

    protected function customers(): array
    {
        return $this->db->select('SELECT id, name FROM customers WHERE user_id = ? ORDER BY name', [$this->auth()->id()]);
    }

    protected function services(): array
    {
        return $this->db->select('SELECT id, name, suggested_price, unit FROM services WHERE user_id = ? AND is_active = 1 ORDER BY name', [$this->auth()->id()]);
    }

    protected function payload(Request $request): array
    {
        $discountType = $request->input('discount_type') === 'percent' ? 'percent' : 'value';
        return [
            'customer_id' => (int) $request->input('customer_id') ?: null,
            'title' => trim((string) $request->input('title')),
            'discount_type' => $discountType,
            'discount_value' => (float) str_replace(',', '.', (string) $request->input('discount_value', '0')),
            'surcharge' => (float) str_replace(',', '.', (string) $request->input('surcharge', '0')),
            'notes' => (string) $request->input('notes'),
            'payment_terms' => trim((string) $request->input('payment_terms')),
            'execution_deadline' => trim((string) $request->input('execution_deadline')),
            'valid_until' => $request->input('valid_until') ?: null,
        ];
    }

    protected function parseItems(Request $request): array
    {
        $descriptions = (array) $request->input('item_description', []);
        $quantities = (array) $request->input('item_quantity', []);
        $prices = (array) $request->input('item_unit_price', []);
        $serviceIds = (array) $request->input('item_service_id', []);

        $items = [];
        foreach ($descriptions as $i => $desc) {
            $desc = trim((string) $desc);
            if ($desc === '') {
                continue;
            }
            $items[] = [
                'service_id' => isset($serviceIds[$i]) && $serviceIds[$i] ? (int) $serviceIds[$i] : null,
                'description' => $desc,
                'quantity' => (float) str_replace(',', '.', (string) ($quantities[$i] ?? 1)),
                'unit_price' => (float) str_replace(',', '.', (string) ($prices[$i] ?? 0)),
            ];
        }
        return $items;
    }
}
