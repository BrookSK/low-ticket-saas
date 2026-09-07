<?php

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Rate limiting simples baseado em banco. Uso: 'throttle:60,1' = 60 req por 1 minuto.
 * A chave combina rota + IP.
 */
class ThrottleMiddleware implements Middleware
{
    protected Database $db;
    protected int $maxAttempts = 60;
    protected int $decayMinutes = 1;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function setParams(array $params): void
    {
        $this->maxAttempts = (int) ($params[0] ?? 60);
        $this->decayMinutes = (int) ($params[1] ?? 1);
    }

    public function handle(Request $request, callable $next): Response
    {
        $key = 'throttle:' . sha1($request->path() . '|' . $request->ip());

        $row = $this->db->selectOne('SELECT hits, expires_at FROM rate_limits WHERE rl_key = ?', [$key]);
        $nowTs = time();

        if ($row && $row['expires_at'] !== null && strtotime($row['expires_at']) > $nowTs) {
            if ((int) $row['hits'] >= $this->maxAttempts) {
                $retry = strtotime($row['expires_at']) - $nowTs;
                if ($request->wantsJson()) {
                    return Response::json(['error' => true, 'message' => 'Muitas tentativas. Aguarde.'], 429)
                        ->header('Retry-After', (string) $retry);
                }
                return Response::make('Muitas tentativas. Tente novamente em ' . $retry . ' segundos.', 429)
                    ->header('Retry-After', (string) $retry);
            }
            $this->db->execute('UPDATE rate_limits SET hits = hits + 1 WHERE rl_key = ?', [$key]);
        } else {
            $expiresAt = date('Y-m-d H:i:s', $nowTs + $this->decayMinutes * 60);
            $this->db->execute(
                'INSERT INTO rate_limits (rl_key, hits, expires_at) VALUES (?, 1, ?)
                 ON DUPLICATE KEY UPDATE hits = 1, expires_at = VALUES(expires_at)',
                [$key, $expiresAt]
            );
        }

        return $next($request);
    }
}
