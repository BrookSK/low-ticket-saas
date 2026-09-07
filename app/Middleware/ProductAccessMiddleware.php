<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\AuthService;

/**
 * Verifica se o usuario possui acesso ao modulo/produto exigido pela rota.
 * Uso: 'product:orcamentos'. Admins tem acesso liberado.
 * Sem acesso -> redireciona para a pagina de compra do produto correspondente.
 */
class ProductAccessMiddleware implements Middleware
{
    protected AuthService $auth;
    protected string $module = '';

    /** Mapeia modulo -> slug do produto de venda. */
    protected array $moduleToProduct = [
        'orcamentos' => 'gerador-orcamentos',
        'financeiro' => 'kit-financeiro',
        'precificador' => 'kit-financeiro',
        'relatorios' => 'plano-completo',
    ];

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function setParams(array $params): void
    {
        $this->module = $params[0] ?? '';
    }

    public function handle(Request $request, callable $next): Response
    {
        $userId = $this->auth->id();
        if (!$userId) {
            return Response::redirect(url('/login'));
        }

        if (User::hasModule($userId, $this->module)) {
            return $next($request);
        }

        $productSlug = $this->moduleToProduct[$this->module] ?? 'plano-completo';
        Session::flash('info', 'Voce ainda nao tem acesso a este recurso. Conheca o plano.');
        return Response::redirect(url('/checkout/' . $productSlug));
    }
}
