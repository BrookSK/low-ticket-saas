<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Editor de templates de e-mail (assunto e conteudo).
 */
class EmailTemplatesController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(): Response
    {
        $templates = $this->db->select('SELECT * FROM email_templates ORDER BY name');
        return $this->view('admin.emails.index', ['title' => 'Templates de e-mail', 'templates' => $templates]);
    }

    public function edit(Request $request): Response
    {
        $template = $this->db->selectOne('SELECT * FROM email_templates WHERE id = ?', [$request->param('id')]);
        if (!$template) {
            $this->abort(404);
        }
        return $this->view('admin.emails.edit', ['title' => 'Editar template', 'template' => $template]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->db->execute(
            'UPDATE email_templates SET subject = ?, body = ?, is_active = ?, updated_at = ? WHERE id = ?',
            [
                trim((string) $request->input('subject')),
                (string) $request->input('body'),
                $request->input('is_active') ? 1 : 0,
                now(), $id,
            ]
        );
        $this->withFlash('success', 'Template atualizado.');
        return $this->redirect('/admin/emails/' . $id . '/editar');
    }
}
