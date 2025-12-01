<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Mailer - singleton wrapper around PHPMailer with fallback to mail()
 */
class Mailer
{
    private static $instance = null;
    private $config = [];
    private $phpmailer = null;
    private $agent = 'phpmail-fallback';

    private function __construct(array $config = [])
    {
        $this->config = $config;

        // Try to initialize PHPMailer if available
        if (class_exists(PHPMailer::class)) {
            try {
                $this->phpmailer = new PHPMailer(true);
                $this->phpmailer->isSMTP();
                $this->phpmailer->SMTPDebug = 0;
                $this->phpmailer->CharSet = 'UTF-8';

                // Apply SMTP config if provided
                if (!empty($config)) {
                    $this->phpmailer->Host = $config['host'] ?? 'localhost';
                    $this->phpmailer->SMTPAuth = !empty($config['username']);
                    $this->phpmailer->Username = $config['username'] ?? '';
                    $this->phpmailer->Password = $config['password'] ?? '';
                    $this->phpmailer->SMTPSecure = $config['encryption'] ?? '';
                    $this->phpmailer->Port = $config['port'] ?? 25;
                    $this->phpmailer->setFrom($config['from_address'] ?? 'noreply@localhost', $config['from_name'] ?? 'No Reply');
                    if (!empty($config['reply_to'])) {
                        $this->phpmailer->addReplyTo($config['reply_to']);
                    }
                    $this->agent = $config['agent'] ?? ($config['host'] ?? 'smtp');
                }
            } catch (Exception $e) {
                error_log('PHPMailer init failed: ' . $e->getMessage());
                $this->phpmailer = null;
            }
        }
    }

    public static function getInstance(array $config = [])
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Send an email. Accepts keys: to, name, subject, html, text, attachments
     */
    public function send(array $opts): bool
    {
    $to = $opts['to'] ?? null;
        $name = $opts['name'] ?? '';
        $subject = $opts['subject'] ?? '';
        $html = $opts['html'] ?? '';
        $text = $opts['text'] ?? strip_tags($html);

        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log('Mailer: invalid recipient ' . var_export($to, true));
            return false;
        }

        // If PHPMailer is available, use SMTP
        if ($this->phpmailer) {
            try {
                $this->phpmailer->clearAllRecipients();
                $this->phpmailer->addAddress($to, $name);
                $this->phpmailer->Subject = $subject;
                $this->phpmailer->Body = $html;
                $this->phpmailer->AltBody = $text;
                $this->phpmailer->isHTML(true);

                $result = $this->phpmailer->send();
                error_log("Mailer: sent to {$to} via {$this->agent}");
                return (bool)$result;
            } catch (Exception $e) {
                error_log('Mailer: PHPMailer send error: ' . $e->getMessage());
                // fall through to mail() fallback
            }
        }

        // Fallback to native mail()
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $from = $this->config['from_address'] ?? 'noreply@localhost';
        $headers .= "From: {$from}\r\n";

        $sent = mail($to, $subject, $html, $headers);
        error_log('Mailer: mail() fallback to ' . $to . ' result=' . ($sent ? 'true' : 'false'));
        return (bool)$sent;
    }

    /**
     * Render a template file and send using send()
     */
    public function sendTemplate(string $templatePath, array $context, array $opts = []): bool
    {
        if (!file_exists($templatePath)) {
            error_log('Mailer: template not found ' . $templatePath);
            return false;
        }

        extract($context);
        ob_start();
        include $templatePath;
        $body = ob_get_clean();

        // Expect first line to be Subject: ... if provided
        $lines = preg_split('/\r?\n/', $body);
        $subject = 'Notification';
        if (isset($lines[0]) && stripos($lines[0], 'Subject:') === 0) {
            $subject = trim(substr($lines[0], strlen('Subject:')));
            $body = implode("\n", array_slice($lines, 1));
        }

        $to = $opts['to'] ?? ($context['to'] ?? null);
        $name = $opts['name'] ?? ($context['name'] ?? '');
        // Wrap the email body into the common HTML frame if helper exists
        $finalHtml = $body;
        if (function_exists('\generateEmailHTML')) {
            try {
                $finalHtml = \generateEmailHTML($body, $to);
            } catch (\Throwable $e) {
                error_log('generateEmailHTML failed: ' . $e->getMessage());
            }
        }

        return $this->send(['to' => $to, 'name' => $name, 'subject' => $subject, 'html' => $finalHtml]);
    }
}
