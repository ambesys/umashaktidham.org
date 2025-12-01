<?php
namespace App\Services;

/**
 * NotificationService
 *
 * Sends email notifications for user events (registration, forgot password, password change,
 * and event registration). Uses Mailer::getInstance() and templates under src/Views/emails.
 */
class NotificationService
{
    private $mailer;
    private $defaultFrom;

    /**
     * Constructor accepts either a mail config array, or a provider name string, or empty.
     * If empty, it will use get_mail_provider() and get_mail_config() from config.
     *
     * @param array|string|null $mailConfigOrProvider
     */
    public function __construct($mailConfigOrProvider = null)
    {
        // Resolve mail config
        $config = [];
        if (is_array($mailConfigOrProvider) && !empty($mailConfigOrProvider)) {
            $config = $mailConfigOrProvider;
        } elseif (is_string($mailConfigOrProvider) && !empty($mailConfigOrProvider)) {
            if (function_exists('get_mail_config')) {
                $config = get_mail_config($mailConfigOrProvider);
            }
        } else {
            // No explicit provider/config passed - use environment-driven provider
            if (function_exists('get_mail_provider') && function_exists('get_mail_config')) {
                $provider = get_mail_provider();
                $config = get_mail_config($provider);
            }
        }

        $this->mailer = \App\Services\Mailer::getInstance($config ?: []);
        $this->defaultFrom = $config['from_address'] ?? ($config['from'] ?? 'noreply@umashaktidham.org');
    }

    public function sendRegistration(array $user): bool
    {
        $template = __DIR__ . '/../Views/emails/welcome.php';
        $context = ['user' => $user, 'to' => $user['email'] ?? $user['u_email'] ?? null, 'name' => $user['name'] ?? $user['u_name'] ?? ''];
        return $this->mailer->sendTemplate($template, $context);
    }

    public function sendForgotPassword(array $user, string $token): bool
    {
        $template = __DIR__ . '/../Views/emails/forgot-password.php';
        $resetUrl = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000'))) . '/reset-password?token=' . urlencode($token);
        $context = ['user' => $user, 'token' => $token, 'reset_url' => $resetUrl, 'to' => $user['email'] ?? $user['u_email'] ?? null, 'name' => $user['name'] ?? $user['u_name'] ?? ''];
        return $this->mailer->sendTemplate($template, $context);
    }

    public function sendPasswordChanged(array $user): bool
    {
        $template = __DIR__ . '/../Views/emails/password-changed.php';
        $context = ['user' => $user, 'to' => $user['email'] ?? $user['u_email'] ?? null, 'name' => $user['name'] ?? $user['u_name'] ?? ''];
        return $this->mailer->sendTemplate($template, $context);
    }

    public function sendEventRegistration(array $user, array $event): bool
    {
        $template = __DIR__ . '/../Views/emails/event-registration.php';
        $context = ['user' => $user, 'event' => $event, 'to' => $user['email'] ?? $user['u_email'] ?? null, 'name' => $user['name'] ?? $user['u_name'] ?? ''];
        return $this->mailer->sendTemplate($template, $context);
    }

    // Placeholder for WhatsApp integration (can be implemented later)
    public function sendWhatsApp(array $user, string $templateName, array $params = []): bool
    {
        // For now, log the action and return false (no-op)
        error_log('WhatsApp: would send to ' . ($user['mobile'] ?? $user['phone'] ?? 'unknown') . ' template=' . $templateName);
        return false;
    }
}
