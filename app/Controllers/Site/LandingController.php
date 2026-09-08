<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Paginas de venda focadas em UM produto cada.
 *
 * - Home (/) e paginas de campanha por nicho vendem o produto de ENTRADA
 *   (Gerador de Orcamentos), com CTA unico para o checkout.
 * - Paginas dedicadas (/precificador-de-servicos, /controle-financeiro)
 *   vendem os outros produtos, para trafego pago segmentado.
 *
 * O conteudo de cada pagina vem de um mapa declarativo (pageConfig), o que
 * permite criar novas paginas de venda sem duplicar layout.
 */
class LandingController extends Controller
{
    protected Database $db;

    /** Copy do hero por nicho (paginas de campanha do Gerador de Orcamentos). */
    protected array $niches = [
        'eletricista' => 'eletricista',
        'marceneiro' => 'marceneiro',
        'pintor' => 'pintor',
        'pedreiro' => 'pedreiro',
        'autonomo' => 'autonomo',
        'fotografo' => 'fotografo',
        'designer' => 'designer',
        'tecnico' => 'tecnico',
    ];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /* ---- Home: vende o Gerador de Orcamentos ---- */
    public function index(): Response
    {
        return $this->renderSales('gerador-orcamentos');
    }

    /* ---- Campanhas por nicho: mesmo produto, copy do hero ajustada ---- */
    public function campaign(Request $request): Response
    {
        $nicho = (string) $request->param('nicho', '');
        $label = $this->nicheLabel($nicho);
        $overrides = $label
            ? [
                'hero_title' => "Orcamentos profissionais para {$label} em minutos",
                'hero_sub' => "Monte, envie pelo WhatsApp e feche mais servicos. Feito para {$label} que querem parecer profissionais e cobrar certo.",
            ]
            : [];
        return $this->renderSales('gerador-orcamentos', $overrides, $nicho ?: 'orcamento');
    }

    /* ---- Pagina de venda do Precificador ---- */
    public function pricingPage(): Response
    {
        return $this->renderSales('kit-financeiro', [
            'focus' => 'pricing',
        ], 'precificador');
    }

    /* ---- Pagina de venda do Controle Financeiro ---- */
    public function financePage(): Response
    {
        return $this->renderSales('kit-financeiro', [
            'focus' => 'finance',
        ], 'financeiro');
    }

    /* -------------------------------------------------------------------- */

    protected function renderSales(string $slug, array $overrides = [], ?string $campaign = null): Response
    {
        $product = $this->db->selectOne('SELECT * FROM products WHERE slug = ? AND is_active = 1', [$slug]);
        // Fallback caso o banco ainda nao tenha o produto (nao quebra a home).
        if (!$product) {
            $product = [
                'id' => 0, 'name' => 'Gerador de Orcamentos', 'slug' => $slug,
                'price' => 39.90, 'promo_price' => 19.90, 'features' => '[]',
                'description' => '',
            ];
        }

        $page = $this->pageConfig($slug, $overrides);
        $price = $product['promo_price'] && (float) $product['promo_price'] > 0
            ? (float) $product['promo_price'] : (float) $product['price'];
        $oldPrice = ($product['promo_price'] && (float) $product['promo_price'] > 0 && (float) $product['promo_price'] < (float) $product['price'])
            ? (float) $product['price'] : null;

        return $this->view('site.sales', [
            'title' => $page['seo_title'],
            'metaDescription' => $page['seo_desc'],
            'product' => $product,
            'page' => $page,
            'price' => $price,
            'oldPrice' => $oldPrice,
            'checkoutUrl' => url('/checkout/' . $product['slug']),
            'campaign' => $campaign,
        ]);
    }

    protected function nicheLabel(string $nicho): ?string
    {
        $labels = [
            'eletricista' => 'eletricistas', 'marceneiro' => 'marceneiros', 'pintor' => 'pintores',
            'pedreiro' => 'pedreiros', 'autonomo' => 'autonomos', 'fotografo' => 'fotografos',
            'designer' => 'designers', 'tecnico' => 'tecnicos',
        ];
        return $labels[$nicho] ?? null;
    }

    /**
     * Configuracao declarativa de cada pagina de venda.
     */
    protected function pageConfig(string $slug, array $overrides): array
    {
        $configs = [
            'gerador-orcamentos' => [
                'seo_title' => setting('app.name', 'Meu Orçamento') . ' — Orcamentos profissionais em 2 minutos',
                'seo_desc' => 'Monte orcamentos profissionais, gere PDF com a sua marca e envie pelo WhatsApp. Acompanhe quando o cliente aprova.',
                'eyebrow' => 'Gerador de Orcamentos',
                'hero_title' => 'Envie orcamentos que <span class="grad">fecham negocio</span>',
                'hero_sub' => 'Monte um orcamento profissional em 2 minutos, mande pelo WhatsApp e acompanhe quando o cliente aprova. Sem planilha, sem complicacao.',
                'hero_cta' => 'Quero fechar mais orcamentos',
                'stats' => [
                    ['2 min', 'para criar e enviar'],
                    ['1 clique', 'para o cliente aprovar'],
                    ['+30%', 'mais chance de fechar'],
                ],
                'benefits_title' => 'Tudo para voce fechar mais e trabalhar menos',
                'benefits' => [
                    ['file-text', 'Orcamento pronto em minutos', 'Escolha os servicos, ajuste valores e o total se calcula sozinho. Simples assim.'],
                    ['clipboard', 'PDF com a sua marca', 'Um documento limpo e profissional que passa confianca e valoriza o seu trabalho.'],
                    ['send', 'Envio pelo WhatsApp', 'Compartilhe um link em 1 clique. O cliente abre no celular e responde na hora.'],
                    ['eye', 'Saiba quando abriram', 'Status em tempo real: enviado, visualizado, aprovado ou recusado.'],
                    ['users', 'Clientes organizados', 'Histórico de cada cliente e orcamentos reaproveitaveis. Refaca em segundos.'],
                    ['check-circle', 'Aprovacao com 1 toque', 'O cliente aprova ou recusa direto no link, sem atrito. Voce recebe o aviso.'],
                ],
                'feature_title' => 'Do zero ao orcamento enviado, sem fricao',
                'feature_points' => [
                    'Modelos prontos e itens reaproveitaveis',
                    'Desconto, acrescimo, validade e condicoes de pagamento',
                    'Link publico com botoes de aprovar e recusar',
                ],
            ],
            'kit-financeiro' => [
                'seo_title' => 'Precificador e Controle Financeiro — ' . setting('app.name', 'Meu Orçamento'),
                'seo_desc' => 'Descubra o preco certo de cobrar e controle o dinheiro do seu negocio sem planilha.',
                // Foco muda entre "pricing" e "finance".
                'eyebrow' => ($overrides['focus'] ?? '') === 'finance' ? 'Controle Financeiro' : 'Precificador Inteligente',
                'hero_title' => ($overrides['focus'] ?? '') === 'finance'
                    ? 'Saiba <span class="grad">quanto voce realmente ganha</span>'
                    : 'Descubra o <span class="grad">preco certo de cobrar</span>',
                'hero_sub' => ($overrides['focus'] ?? '') === 'finance'
                    ? 'Controle receitas, despesas e veja seu lucro real na hora. Nunca mais esqueca uma conta ou pague algo em atraso.'
                    : 'Informe seus custos e a margem desejada. Em segundos voce ve o preco minimo para nao ter prejuizo e o ideal para lucrar.',
                'hero_cta' => ($overrides['focus'] ?? '') === 'finance' ? 'Quero organizar meu dinheiro' : 'Quero cobrar o preco certo',
                'stats' => ($overrides['focus'] ?? '') === 'finance'
                    ? [['R$', 'lucro real na tela'], ['0', 'planilhas'], ['2 min', 'para lancar contas']]
                    : [['R$ 500', 'preco ideal em 1 clique'], ['30%', 'de margem garantida'], ['0', 'chute no preco']],
                'benefits_title' => ($overrides['focus'] ?? '') === 'finance'
                    ? 'Seu financeiro no controle, sem esforco'
                    : 'Pare de cobrar no escuro',
                'benefits' => ($overrides['focus'] ?? '') === 'finance'
                    ? [
                        ['wallet', 'Receitas e despesas', 'Lance tudo em segundos e veja o saldo do mes em tempo real.'],
                        ['pie-chart', 'Lucro real', 'Dashboard que mostra quanto sobra de verdade, sem achismo.'],
                        ['bell', 'Alertas de vencimento', 'Contas a receber e a pagar sempre na sua frente. Nada passa batido.'],
                        ['bar-chart', 'Comparativo mensal', 'Enxergue a evolucao do seu negocio mes a mes.'],
                    ]
                    : [
                        ['calculator', 'Calculo em segundos', 'Materiais, mao de obra, deslocamento, impostos e margem. Tudo considerado.'],
                        ['target', 'Preco minimo e ideal', 'Saiba o piso para nao ter prejuizo e o valor que da lucro de verdade.'],
                        ['trending-up', 'Margem garantida', 'Defina a margem desejada e o sistema faz a conta certa por voce.'],
                        ['dollar-sign', 'Mais lucro por servico', 'Descubra quanto realmente sobra no seu bolso em cada trabalho.'],
                    ],
                'feature_title' => ($overrides['focus'] ?? '') === 'finance'
                    ? 'O dinheiro do seu negocio, claro e sob controle'
                    : 'O preco certo, com base em numeros',
                'feature_points' => ($overrides['focus'] ?? '') === 'finance'
                    ? ['Contas a receber e a pagar', 'Alertas de vencimento', 'Lucro real do mes num painel']
                    : ['Custo por hora e por servico', 'Preco minimo, ideal e agressivo', 'Margem que voce escolhe'],
            ],
        ];

        $config = $configs[$slug] ?? $configs['gerador-orcamentos'];
        // Aplica overrides simples do hero (usado por campanhas de nicho).
        if (!empty($overrides['hero_title'])) {
            $config['hero_title'] = e($overrides['hero_title']);
        }
        if (!empty($overrides['hero_sub'])) {
            $config['hero_sub'] = e($overrides['hero_sub']);
        }
        return $config;
    }
}
