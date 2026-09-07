<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\WhatsAppService;

/**
 * Configuracao de WhatsApp: templates e teste de envio.
 * As credenciais ficam em Configuracoes > WhatsApp.
 */
class WhatsappController extends Controller
{
    protected Database $db;
    protected WhatsAppService $whatsapp;

    public function __construct(Database $db, WhatsAppService $whatsapp)
    {
        $this->db = $db;
        $this->whatsapp = $whatsapp;
    }

    public function index(): Response
    {
        $templates = $this->db->select('SELECT * FROM whatsapp_templates ORDER BY name');
        return $this->view('admin.whatsapp', [
            'title' => 'WhatsApp',
            'templates' => $templates,
            'configured' => $this->whatsapp->isConfigured(),
        ]);
    }

    public function storeTemplate(Request $request): Response
    {
        $slug = trim((string) $request->input('slug'));
        $id = $request->input('id');
        if ($id) {
            $this->db->execute('UPDATE whatsapp_templates SET name=?, body=?, is_active=?, updated_at=? WHERE id=?', [
                trim((string) $request->input('name')),
                (string) $request->input('body'),
                $request->input('is_active') ? 1 : 0,
                now(), (int) $id,
            ]);
        } else {
            $this->db->insert('INSERT INTO whatsapp_templates (slug, name, body, is_active, created_at, updated_at) VALUES (?,?,?,?,?,?)', [
                $slug ?: ('tpl_' . time()),
                trim((string) $request->input('name')),
                (string) $request->input('body'),
                $request->input('is_active') ? 1 : 0,
                now(), now(),
            ]);
        }
        $this->withFlash('success', 'Template salvo.');
        return $this->redirect('/admin/whatsapp');
    }

    public function test(Request $request): Response
    {
        $to = trim((string) $request->input('to'));
        if (!$this->whatsapp->isConfigured()) {
            $this->withFlash('warning', 'WhatsApp nao configurado. Configure em Configuracoes > WhatsApp.');
            return $this->redirect('/admin/whatsapp');
        }
        $result = $this->whatsapp->send($to, 'Teste de integracao do ' . setting('app.name', 'sistema') . '.');
        if ($result['success']) {
            $this->withFlash('success', 'Mensagem de teste enviada.');
        } else {
            $this->withFlash('error', 'Falha ao enviar: ' . ($result['error'] ?? 'desconhecida'));
        }
        return $this->redirect('/admin/whatsapp');
    }
}
