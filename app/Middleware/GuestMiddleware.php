<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Permite apenas visitantes (nao autenticados).
 */
class GuestMiddleware implements Middleware
{
    protected AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->auth->check()) {
            return Response::redirect(url('/dashboard'));
        }
        return $next($request);
    }
}
