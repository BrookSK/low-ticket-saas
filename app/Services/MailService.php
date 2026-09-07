<?php

namespace App\Services;

use App\Core\Database;

/**
 * Servico de envio de e-mails. Usa SMTP configurado no painel (settings)
 * e templates da tabela email_templates com substituicao de variaveis.
 *
 * Se o PHPMailer estiver disponivel (via Composer), usa-o; caso contrario,
 * cai no mail() nativo. Se o SMTP nao estiver configurado, registra em log
 * e nao interrompe o fluxo (deixando claro que a integracao precisa de config).
 */
class MailService
{
    protected Database $db;
    protected SettingsService $settings;

    public function __construct(Database $db, SettingsService $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    public function isConfigured(): bool
    {
        return (bool) $this->settings->get('mail.host') && (bool) $this->settings->get('mail.from_address');
    }

    /**
     * Envia um e-mail a partir de um template (slug).
     */
    public function sendTemplate(string $slug, string $to, array $vars = []): bool
    {
        $template = $this->db->selectOne('SELECT * FROM email_templates WHERE slug = ? AND is_active = 1', [$slug]);
        if (!$template) {
            logger()->warning('mail', "Template de e-mail nao encontrado: {$slug}");
            return false;
        }

        $vars = array_merge($this->defaultVars(), $vars);
        $subject = $this->interpolate($template['subject'], $vars);
        $inner = $this->interpolate($template['body'], $vars);

        // Envolve o conteudo num layout de e-mail profissional (a menos que o
        // template ja seja um documento HTML completo).
        $body = str_contains(strtolower($inner), '<!doctype') || str_contains(strtolower($inner), '<html')
            ? $inner
            : $this->wrapHtml($subject, $inner, $vars);

        return $this->send($to, $subject, $body);
    }

    /**
     * Layout HTML responsivo para e-mails (tabelas + estilos inline para
     * maxima compatibilidade com clientes de e-mail). O conteudo dos templates
     * fica no miolo; header, botao e rodape sao padronizados aqui.
     */
    protected function wrapHtml(string $title, string $innerHtml, array $vars): string
    {
        $appName = e($vars['app_name'] ?? 'Meu Orçamento');
        $appUrl = $vars['app_url'] ?? url('/');
        $year = date('Y');
        $support = (string) $this->settings->get('app.support_email', '');
        $logo = (string) $this->settings->get('app.logo', '');

        $logoHtml = $logo !== ''
            ? '<img src="' . e($logo) . '" alt="' . $appName . '" height="40" style="height:40px;display:inline-block">'
            : '<span style="display:inline-block;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-.5px">' . $appName . '</span>';

        $supportLine = $support !== ''
            ? '<br>Precisa de ajuda? Fale com a gente: <a href="mailto:' . e($support) . '" style="color:#6366f1;text-decoration:none">' . e($support) . '</a>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{$title}</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
    <tr><td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,.08);">
        <tr>
          <td style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:26px 32px;text-align:center;">
            <a href="{$appUrl}" style="text-decoration:none;">{$logoHtml}</a>
          </td>
        </tr>
        <tr>
          <td style="padding:32px;color:#334155;font-size:16px;line-height:1.65;">
            {$innerHtml}
          </td>
        </tr>
        <tr>
          <td style="padding:20px 32px;background:#0f172a;color:#94a3b8;font-size:12px;line-height:1.6;text-align:center;">
            © {$year} {$appName}. Todos os direitos reservados.{$supportLine}
          </td>
        </tr>
      </table>
      <p style="color:#94a3b8;font-size:11px;margin:16px 0 0;">Você recebeu este e-mail porque tem uma conta no {$appName}.</p>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        if (!$this->isConfigured()) {
            logger()->warning('mail', 'SMTP nao configurado. E-mail nao enviado.', ['to' => $to, 'subject' => $subject]);
            return false;
        }

        $fromAddress = (string) $this->settings->get('mail.from_address');
        $fromName = (string) $this->settings->get('mail.from_name', 'Meu Orçamento');

        // Usa PHPMailer se disponivel.
        if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return $this->sendWithPhpMailer($to, $subject, $htmlBody, $fromAddress, $fromName);
        }

        // Fallback: mail() nativo.
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromAddress . '>',
        ];
        try {
            return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
        } catch (\Throwable $e) {
            logger()->error('mail', 'Falha no mail(): ' . $e->getMessage());
            return false;
        }
    }

    protected function sendWithPhpMailer(string $to, string $subject, string $body, string $fromAddress, string $fromName): bool
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string) $this->settings->get('mail.host');
            $mail->Port = (int) $this->settings->get('mail.port', 587);
            $mail->SMTPAuth = true;
            $mail->Username = (string) $this->settings->get('mail.username');
            $mail->Password = (string) $this->settings->get('mail.password');
            $encryption = (string) $this->settings->get('mail.encryption', 'tls');
            if ($encryption !== '' && $encryption !== 'none') {
                $mail->SMTPSecure = $encryption;
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            logger()->error('mail', 'Falha no PHPMailer: ' . $e->getMessage(), ['to' => $to]);
            return false;
        }
    }

    protected function defaultVars(): array
    {
        return [
            'app_name' => (string) $this->settings->get('app.name', 'Meu Orçamento'),
            'app_url' => rtrim((string) $this->settings->get('app.url', ''), '/') ?: url('/'),
            'dashboard_url' => url('/dashboard'),
        ];
    }

    protected function interpolate(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($vars) {
            return (string) ($vars[$m[1]] ?? '');
        }, $text);
    }
}
