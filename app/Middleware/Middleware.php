<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Contrato de middleware. O metodo handle deve chamar $next($request)
 * para continuar a pipeline, ou retornar uma Response para interromper.
 */
interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
