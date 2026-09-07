<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Landing page principal e paginas de campanha por nicho.
 * As paginas de campanha reutilizam a mesma view, variando apenas a copy
 * do hero conforme o nicho (evita duplicacao de codigo).
 */
class LandingController extends Controller
{
    protected Database $db;

    /** Personalizacoes de copy por nicho (para trafego pago segmentado). */
    protected array $niches = [
        'eletricista' => ['Eletricista', 'eletricos'],
        'marceneiro' => ['Marceneiro', 'de marcenaria'],
        'pintor' => ['Pintor', 'de pintura'],
        'pedreiro' => ['Pedreiro', 'de obras'],
        'autonomo' => ['Autonomo', 'do seu negocio'],
        'fotografo' => ['Fotografo', 'de fotografia'],
        'designer' => ['Designer', 'de design'],
    ];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(): Response
    {
        return $this->view('site.landing', $this->data());
    }

    public function campaign(Request $request): Response
    {
        $nicho = (string) $request->param('nicho', '');
        $niche = $this->niches[$nicho] ?? null;

        $hero = $niche
            ? [
                'title' => "Orcamentos profissionais para {$niche[0]}s em minutos",
                'subtitle' => "Crie orcamentos {$niche[1]}, descubra quanto cobrar e organize suas financas sem complicacao.",
            ]
            : [
                'title' => 'Faca orcamentos profissionais e tenha mais controle sobre o seu negocio',
                'subtitle' => 'Crie orcamentos, descubra quanto cobrar e organize suas financas em poucos minutos.',
            ];

        return $this->view('site.landing', array_merge($this->data(), [
            'hero' => $hero,
            'campaign' => $nicho ?: 'orcamento',
        ]));
    }

    protected function data(): array
    {
        $products = $this->db->select('SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order');
        return [
            'title' => setting('app.name', 'LowTicket SaaS') . ' - Orcamentos, financeiro e precificador',
            'metaDescription' => 'Crie orcamentos profissionais, descubra quanto cobrar e organize suas financas. Feito para MEIs, autonomos e pequenos negocios.',
            'products' => $products,
            'hero' => [
                'title' => 'Faca orcamentos profissionais e tenha mais controle sobre o seu negocio',
                'subtitle' => 'Crie orcamentos, descubra quanto cobrar e organize suas financas em poucos minutos.',
            ],
            'campaign' => null,
        ];
    }
}
