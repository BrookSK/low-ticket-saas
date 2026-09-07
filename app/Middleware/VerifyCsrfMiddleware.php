<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\HttpException;

/**
 * Verifica o token CSRF em requisicoes de escrita (POST/PUT/PATCH/DELETE).
 */
class VerifyCsrfMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $method = $request->method();
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
            if (!Session::verifyCsrf($token)) {
                if ($request->wantsJson()) {
                    return Response::json(['error' => true, 'message' => 'Token CSRF invalido.'], 419);
                }
                throw new HttpException(419, 'Sessao expirada. Recarregue a pagina e tente novamente.');
            }
        }
        return $next($request);
    }
}
