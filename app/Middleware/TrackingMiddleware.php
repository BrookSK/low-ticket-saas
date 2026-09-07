<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AttributionService;

/**
 * Captura parametros de marketing (UTM, gclid, fbclid), define/le o cookie
 * de visitante e delega a persistencia da atribuicao (first/last touch)
 * ao AttributionService.
 */
class TrackingMiddleware implements Middleware
{
    protected AttributionService $attribution;

    public function __construct(AttributionService $attribution)
    {
        $this->attribution = $attribution;
    }

    public function handle(Request $request, callable $next): Response
    {
        // Apenas em GET de paginas (nao em assets/webhooks).
        if ($request->method() === 'GET') {
            try {
                $this->attribution->capture($request);
            } catch (\Throwable $e) {
                // Nunca deve quebrar a requisicao principal.
            }
        }
        return $next($request);
    }
}
