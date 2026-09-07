<?php

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\PricingService;

/**
 * Precificador: calcula quanto cobrar por um servico.
 */
class PricingController extends Controller
{
    protected Database $db;
    protected PricingService $pricing;

    public function __construct(Database $db, PricingService $pricing)
    {
        $this->db = $db;
        $this->pricing = $pricing;
    }

    public function index(): Response
    {
        $recent = $this->db->select(
            'SELECT * FROM pricing_calculations WHERE user_id = ? ORDER BY created_at DESC LIMIT 10',
            [$this->auth()->id()]
        );
        return $this->view('app.pricing.index', ['title' => 'Precificador', 'recent' => $recent]);
    }

    public function calculate(Request $request): Response
    {
        $input = $this->input($request);
        $result = $this->pricing->calculate($input);

        if ($request->wantsJson()) {
            return $this->json(['result' => $result]);
        }

        return $this->view('app.pricing.index', [
            'title' => 'Precificador',
            'result' => $result,
            'input' => $input,
            'recent' => $this->db->select('SELECT * FROM pricing_calculations WHERE user_id = ? ORDER BY created_at DESC LIMIT 10', [$this->auth()->id()]),
        ]);
    }

    public function save(Request $request): Response
    {
        $input = $this->input($request);
        $result = $this->pricing->calculate($input);

        $this->db->insert(
            'INSERT INTO pricing_calculations
             (user_id, name, material_cost, labor_cost, hours, travel_cost, fixed_cost, taxes_percent, fees_percent, margin_percent, other_costs, total_cost, recommended_price, result, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $this->auth()->id(), trim((string) $request->input('name')) ?: 'Calculo',
                $input['material_cost'], $input['labor_cost'], $input['hours'], $input['travel_cost'],
                $input['fixed_cost'], $input['taxes_percent'], $input['fees_percent'], $input['margin_percent'],
                $input['other_costs'], $result['total_cost'], $result['recommended_price'],
                json_encode($result, JSON_UNESCAPED_UNICODE), now(),
            ]
        );

        $this->withFlash('success', 'Calculo salvo.');
        return $this->redirect('/precificador');
    }

    protected function input(Request $request): array
    {
        $num = fn($k) => (float) str_replace(',', '.', (string) $request->input($k, '0'));
        return [
            'material_cost' => $num('material_cost'),
            'labor_cost' => $num('labor_cost'),
            'hours' => $num('hours'),
            'travel_cost' => $num('travel_cost'),
            'fixed_cost' => $num('fixed_cost'),
            'taxes_percent' => $num('taxes_percent'),
            'fees_percent' => $num('fees_percent'),
            'margin_percent' => $num('margin_percent'),
            'other_costs' => $num('other_costs'),
        ];
    }
}
