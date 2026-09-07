<?php

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\MailService;

/**
 * Paginas institucionais: precos, FAQ, contato, termos, privacidade, cookies.
 */
class PageController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function pricing(): Response
    {
        $products = $this->db->select('SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order');
        return $this->view('site.pricing', [
            'title' => 'Precos - ' . setting('app.name', 'LowTicket SaaS'),
            'metaDescription' => 'Planos e precos acessiveis para criar orcamentos e controlar suas financas.',
            'products' => $products,
        ]);
    }

    public function faq(): Response
    {
        return $this->view('site.faq', [
            'title' => 'Perguntas frequentes - ' . setting('app.name', 'LowTicket SaaS'),
            'metaDescription' => 'Tire suas duvidas sobre a plataforma de orcamentos e financeiro.',
            'faqs' => $this->faqs(),
        ]);
    }

    public function contact(): Response
    {
        return $this->view('site.contact', [
            'title' => 'Contato - ' . setting('app.name', 'LowTicket SaaS'),
            'metaDescription' => 'Fale com a nossa equipe.',
        ]);
    }

    public function sendContact(Request $request): Response
    {
        $data = [
            'name' => trim((string) $request->input('name')),
            'email' => trim((string) $request->input('email')),
            'message' => trim((string) $request->input('message')),
        ];
        $errors = $this->validate($data, [
            'name' => 'required|max:191',
            'email' => 'required|email',
            'message' => 'required|min:5',
        ]);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/contato');
        }

        $to = (string) setting('app.support_email', setting('mail.from_address', ''));
        if ($to !== '') {
            try {
                app(MailService::class)->sendTemplate('contact', $to, [
                    'from_name' => $data['name'],
                    'from_email' => $data['email'],
                    'message' => $data['message'],
                ]);
            } catch (\Throwable $e) {}
        }

        $this->withFlash('success', 'Mensagem enviada! Retornaremos em breve.');
        return $this->redirect('/contato');
    }

    public function terms(): Response
    {
        return $this->legal('terms', 'Termos de Uso');
    }

    public function privacy(): Response
    {
        return $this->legal('privacy', 'Politica de Privacidade');
    }

    public function cookies(): Response
    {
        return $this->legal('cookies', 'Politica de Cookies');
    }

    public function sitemap(): Response
    {
        $urls = ['/', '/precos', '/faq', '/contato', '/termos-de-uso', '/politica-de-privacidade', '/politica-de-cookies'];
        foreach (['orcamento', 'orcamento-eletricista', 'orcamento-marceneiro', 'orcamento-pintor', 'orcamento-autonomo'] as $c) {
            $urls[] = '/' . $c;
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $path) {
            $xml .= '  <url><loc>' . e(url($path)) . '</loc><changefreq>weekly</changefreq></url>' . "\n";
        }
        $xml .= '</urlset>';
        return Response::make($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    protected function legal(string $type, string $heading): Response
    {
        return $this->view('site.legal', [
            'title' => $heading . ' - ' . setting('app.name', 'LowTicket SaaS'),
            'metaDescription' => $heading,
            'heading' => $heading,
            'type' => $type,
        ]);
    }

    protected function faqs(): array
    {
        return [
            ['Preciso instalar algo?', 'Nao. Tudo funciona pelo navegador, no computador ou no celular.'],
            ['Consigo enviar o orcamento pelo WhatsApp?', 'Sim. Com um clique voce compartilha um link profissional do orcamento pelo WhatsApp.'],
            ['Como funciona o precificador?', 'Voce informa seus custos e a margem desejada, e o sistema calcula o preco ideal para cobrar.'],
            ['Meus dados estao seguros?', 'Sim. Usamos boas praticas de seguranca e voce pode excluir sua conta e dados quando quiser.'],
            ['Posso cancelar quando quiser?', 'Sim. Voce compra o que precisa e continua com acesso ao que adquiriu.'],
        ];
    }
}
