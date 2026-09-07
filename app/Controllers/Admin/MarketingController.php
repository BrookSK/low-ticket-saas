<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Response;
use App\Services\SettingsService;

/**
 * Marketing / Analytics e Performance de Marketing.
 */
class MarketingController extends Controller
{
    protected Database $db;
    protected SettingsService $settings;

    public function __construct(Database $db, SettingsService $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    public function index(): Response
    {
        // Eventos de conversao recentes agregados.
        $events = $this->db->select(
            'SELECT event, COUNT(*) total, COALESCE(SUM(value),0) value
             FROM analytics_events
             WHERE created_at >= ?
             GROUP BY event ORDER BY total DESC',
            [date('Y-m-d 00:00:00', strtotime('-30 days'))]
        );

        $integrations = [
            'analytics' => (bool) $this->settings->get('google.analytics_enabled'),
            'ads' => (bool) $this->settings->get('google.ads_enabled'),
            'gtm' => (bool) $this->settings->get('google.gtm_enabled'),
            'meta' => (bool) $this->settings->get('meta.pixel_enabled'),
        ];

        return $this->view('admin.marketing.index', [
            'title' => 'Marketing / Analytics',
            'events' => $events,
            'integrations' => $integrations,
        ]);
    }

    public function performance(): Response
    {
        // A API do Google Ads exige configuracao/credenciais. Nao inventamos dados.
        $configured = (bool) $this->settings->get('google.ads_api_configured', false);

        return $this->view('admin.marketing.performance', [
            'title' => 'Performance de Marketing',
            'configured' => $configured,
        ]);
    }

    public function attribution(): Response
    {
        $rows = $this->db->select(
            'SELECT ma.first_source, ma.first_medium, ma.first_campaign, COUNT(DISTINCT ma.id) visitors,
                    COUNT(DISTINCT o.id) orders, COALESCE(SUM(CASE WHEN o.status = ? THEN o.total ELSE 0 END),0) revenue
             FROM marketing_attribution ma
             LEFT JOIN orders o ON o.attribution_id = ma.id
             GROUP BY ma.first_source, ma.first_medium, ma.first_campaign
             ORDER BY revenue DESC, visitors DESC
             LIMIT 100',
            ['approved']
        );

        return $this->view('admin.marketing.attribution', [
            'title' => 'Atribuicao',
            'rows' => $rows,
        ]);
    }
}
