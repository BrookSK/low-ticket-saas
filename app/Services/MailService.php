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
        $body = $this->interpolate($template['body'], $vars);

        return $this->send($to, $subject, $body);
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        if (!$this->isConfigured()) {
            logger()->warning('mail', 'SMTP nao configurado. E-mail nao enviado.', ['to' => $to, 'subject' => $subject]);
            return false;
        }

        $fromAddress = (string) $this->settings->get('mail.from_address');
        $fromName = (string) $this->settings->get('mail.from_name', 'LowTicket SaaS');

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
            'app_name' => (string) $this->settings->get('app.name', 'LowTicket SaaS'),
            'app_url' => rtrim((string) $this->settings->get('app.url', ''), '/'),
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
