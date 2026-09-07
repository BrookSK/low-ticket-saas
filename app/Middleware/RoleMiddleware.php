<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Autorizacao por papel. Uso na rota: 'role:super_admin' ou 'role:admin,super_admin'.
 */
class RoleMiddleware implements Middleware
{
    protected AuthService $auth;
    protected array $allowedRoles = [];

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function setParams(array $params): void
    {
        $this->allowedRoles = $params;
    }

    public function handle(Request $request, callable $next): Response
    {
        $roles = $this->auth->roles();
        $hasRole = !empty(array_intersect($this->allowedRoles, $roles));

        if (!$this->auth->check() || !$hasRole) {
            if ($request->wantsJson()) {
                return Response::json(['error' => true, 'message' => 'Permissao insuficiente.'], 403);
            }
            return Response::make('Acesso negado.', 403);
        }
        return $next($request);
    }
}
