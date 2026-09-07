<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Exige usuario autenticado com papel administrativo. Caso contrario,
 * redireciona para o login do admin.
 */
class AdminMiddleware implements Middleware
{
    protected AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->auth->check() || !$this->auth->isAdmin()) {
            if ($request->wantsJson()) {
                return Response::json(['error' => true, 'message' => 'Acesso negado.'], 403);
            }
            return Response::redirect(url('/admin/login'));
        }
        return $next($request);
    }
}
