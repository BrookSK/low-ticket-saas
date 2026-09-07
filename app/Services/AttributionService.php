<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Request;

/**
 * Gerencia atribuicao de marketing: first-touch e last-touch por visitante.
 * Captura UTMs, gclid, fbclid, landing page, referrer, dispositivo e browser.
 */
class AttributionService
{
    protected Database $db;
    protected string $cookieName = 'lt_vid';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Recupera (ou cria) o id anonimo de visitante.
     */
    public function visitorId(): string
    {
        $vid = $_COOKIE[$this->cookieName] ?? null;
        if (!$vid) {
            $vid = bin2hex(random_bytes(16));
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            // Cookie de 1 ano.
            @setcookie($this->cookieName, $vid, [
                'expires' => time() + 31536000,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            $_COOKIE[$this->cookieName] = $vid;
        }
        return $vid;
    }

    public function capture(Request $request): void
    {
        $utm = [
            'source' => $request->query('utm_source'),
            'medium' => $request->query('utm_medium'),
            'campaign' => $request->query('utm_campaign'),
            'content' => $request->query('utm_content'),
            'term' => $request->query('utm_term'),
            'gclid' => $request->query('gclid'),
            'fbclid' => $request->query('fbclid'),
        ];

        $hasUtm = array_filter($utm) !== [];
        $vid = $this->visitorId();

        $existing = $this->db->selectOne('SELECT id FROM marketing_attribution WHERE visitor_id = ? ORDER BY id DESC LIMIT 1', [$vid]);

        // Se nao ha UTM e ja existe registro, nada a fazer (visita direta recorrente).
        if (!$hasUtm && $existing) {
            return;
        }

        $device = $this->detectDevice($request->userAgent());
        $browser = $this->detectBrowser($request->userAgent());
        $landing = $request->path();
        $referrer = $request->referer();

        $source = $utm['source'] ?: ($referrer ? 'referral' : 'direct');
        $medium = $utm['medium'] ?: ($utm['gclid'] ? 'cpc' : ($referrer ? 'referral' : 'none'));

        if (!$existing) {
            // First touch.
            $this->db->insert(
                'INSERT INTO marketing_attribution
                 (visitor_id, first_source, first_medium, first_campaign, first_content, first_term,
                  first_landing, first_referrer, first_gclid, first_fbclid, first_at,
                  last_source, last_medium, last_campaign, last_content, last_term, last_gclid, last_fbclid, last_at,
                  device, browser, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?,?,?,?,?,?,?, ?,?,?,?)',
                [
                    $vid, $source, $medium, $utm['campaign'], $utm['content'], $utm['term'],
                    $landing, $referrer, $utm['gclid'], $utm['fbclid'], now(),
                    $source, $medium, $utm['campaign'], $utm['content'], $utm['term'], $utm['gclid'], $utm['fbclid'], now(),
                    $device, $browser, now(), now(),
                ]
            );
        } elseif ($hasUtm) {
            // Last touch (atualiza apenas se houver nova origem identificada).
            $this->db->execute(
                'UPDATE marketing_attribution SET
                    last_source = ?, last_medium = ?, last_campaign = ?, last_content = ?, last_term = ?,
                    last_gclid = ?, last_fbclid = ?, last_at = ?, updated_at = ?
                 WHERE id = ?',
                [
                    $source, $medium, $utm['campaign'], $utm['content'], $utm['term'],
                    $utm['gclid'], $utm['fbclid'], now(), now(), $existing['id'],
                ]
            );
        }
    }

    /**
     * Associa a atribuicao do visitante a um usuario (chamado no cadastro).
     */
    public function linkUser(int $userId): void
    {
        $vid = $_COOKIE[$this->cookieName] ?? null;
        if (!$vid) {
            return;
        }
        $this->db->execute(
            'UPDATE marketing_attribution SET user_id = ?, updated_at = ? WHERE visitor_id = ? AND user_id IS NULL',
            [$userId, now(), $vid]
        );
    }

    public function currentAttributionId(): ?int
    {
        $vid = $_COOKIE[$this->cookieName] ?? null;
        if (!$vid) {
            return null;
        }
        $row = $this->db->selectOne('SELECT id FROM marketing_attribution WHERE visitor_id = ? ORDER BY id DESC LIMIT 1', [$vid]);
        return $row ? (int) $row['id'] : null;
    }

    protected function detectDevice(string $ua): string
    {
        if (preg_match('/mobile|android|iphone|ipod/i', $ua)) {
            return 'mobile';
        }
        if (preg_match('/ipad|tablet/i', $ua)) {
            return 'tablet';
        }
        return 'desktop';
    }

    protected function detectBrowser(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/edg/i', $ua) => 'Edge',
            (bool) preg_match('/chrome/i', $ua) => 'Chrome',
            (bool) preg_match('/firefox/i', $ua) => 'Firefox',
            (bool) preg_match('/safari/i', $ua) => 'Safari',
            default => 'Outro',
        };
    }
}
