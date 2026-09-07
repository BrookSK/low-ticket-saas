<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\EventService;

/**
 * Recebe eventos de tracking do front-end e os registra via EventService.
 */
class TrackingController extends Controller
{
    protected EventService $events;

    public function __construct(EventService $events)
    {
        $this->events = $events;
    }

    public function event(Request $request): Response
    {
        $event = trim((string) $request->input('event'));
        $allowed = [
            'page_view', 'signup', 'begin_checkout', 'purchase', 'upsell_view',
            'upsell_accept', 'upsell_decline', 'lead', 'generate_quote',
            'download_pdf', 'whatsapp_click',
        ];
        if ($event === '' || !in_array($event, $allowed, true)) {
            return $this->json(['ok' => false, 'message' => 'Evento invalido.'], 422);
        }

        $this->events->dispatch($event, [
            'user_id' => $this->auth()->id(),
            'value' => $request->input('value'),
            'currency' => $request->input('currency', 'BRL'),
            'transaction_id' => $request->input('transaction_id'),
            'properties' => $request->input('properties'),
        ]);

        return $this->json(['ok' => true]);
    }
}
