<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

/**
 * Exige usuario autenticado. Redireciona para /login se nao estiver.
 */
class AuthMiddleware implements Middleware
{
    protected AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            if ($request->wantsJson()) {
                return Response::json(['error' => true, 'message' => 'Nao autenticado.'], 401);
            }
            Session::flash('intended', $request->path());
            return Response::redirect(url('/login'));
        }
        return $next($request);
    }
}
