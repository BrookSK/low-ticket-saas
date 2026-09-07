<?php

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\EventService;
use App\Services\MailService;
use App\Services\NotificationService;
use App\Services\PdfService;
use App\Services\QuoteService;
use App\Services\WhatsAppService;

/**
 * Visualizacao publica do orcamento (link seguro por token), com aprovacao/recusa.
 * Nao exige autenticacao. Registra IP e dispara notificacoes ao prestador.
 */
class PublicQuoteController extends Controller
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

    public function show(Request $request): Response
    {
        $quote = $this->byToken((string) $request->param('token'));
        $data = $this->presentation($quote);

        // Marca como visualizado (se ainda nao respondido).
        if (in_array($quote['status'], ['sent'], true)) {
            $this->quotes->changeStatus((int) $quote['id'], 'viewed', 'customer', $request->ip());
        }

        return $this->view('public.quote', $data);
    }

    public function approve(Request $request): Response
    {
        return $this->respond($request, 'approved');
    }

    public function reject(Request $request): Response
    {
        return $this->respond($request, 'refused');
    }

    public function pdf(Request $request): Response
    {
        $quote = $this->byToken((string) $request->param('token'));
        $data = $this->presentation($quote);
        return app(PdfService::class)->fromView('pdf.quote', $data, 'orcamento-' . $quote['number'] . '.pdf');
    }

    protected function respond(Request $request, string $status): Response
    {
        $token = (string) $request->param('token');
        $quote = $this->byToken($token);

        // So permite responder se ainda nao foi respondido/expirado/cancelado.
        if (!in_array($quote['status'], ['sent', 'viewed'], true)) {
            $this->withFlash('info', 'Este orcamento ja foi respondido.');
            return $this->redirect('/orcamento/' . $token);
        }

        $this->quotes->changeStatus((int) $quote['id'], $status, 'customer', $request->ip());

        // Notifica o prestador.
        $owner = User::find((int) $quote['user_id']);
        $eventName = $status === 'approved' ? 'QuoteApproved' : 'QuoteRefused';
        $templateSlug = $status === 'approved' ? 'quote_approved' : 'quote_refused';

        if ($owner) {
            $vars = ['name' => $owner['name'], 'quote_number' => $quote['number']];
            try { app(MailService::class)->sendTemplate($templateSlug, $owner['email'], $vars); } catch (\Throwable $e) {}
            try {
                app(NotificationService::class)->create(
                    (int) $owner['id'], $eventName,
                    'Orcamento ' . $quote['number'] . ($status === 'approved' ? ' aprovado' : ' recusado'),
                    'O cliente respondeu ao orcamento.',
                    url('/orcamentos/' . $quote['id'])
                );
            } catch (\Throwable $e) {}
            if (!empty($owner['whatsapp']) && $status === 'approved') {
                try { app(WhatsAppService::class)->sendTemplate('quote_approved', $owner['whatsapp'], $vars); } catch (\Throwable $e) {}
            }
        }

        $this->events->dispatch($eventName, ['user_id' => $quote['user_id'], 'quote_id' => $quote['id']]);

        $this->withFlash('success', $status === 'approved' ? 'Orcamento aprovado! O prestador foi avisado.' : 'Orcamento recusado.');
        return $this->redirect('/orcamento/' . $token);
    }

    protected function presentation(array $quote): array
    {
        $owner = User::find((int) $quote['user_id']);
        $company = $owner && $owner['company_id'] ? $this->db->selectOne('SELECT * FROM companies WHERE id = ?', [$owner['company_id']]) : null;
        $customer = $quote['customer_id'] ? $this->db->selectOne('SELECT * FROM customers WHERE id = ?', [$quote['customer_id']]) : null;

        return [
            'title' => 'Orcamento ' . $quote['number'],
            'quote' => $quote,
            'company' => $company,
            'customer' => $customer,
            'user' => $owner,
        ];
    }

    protected function byToken(string $token): array
    {
        $quote = $this->quotes->findByToken($token);
        if (!$quote) {
            $this->abort(404, 'Orcamento nao encontrado.');
        }
        return $quote;
    }
}
