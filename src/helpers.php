<?php
// This file contains helper functions used throughout the application.

function redirect($url) {
    header("Location: $url");
    exit();
}

function flash($message) {
    $_SESSION['flash_message'] = $message;
}

function old($key, $default = '') {
    return isset($_SESSION['old'][$key]) ? $_SESSION['old'][$key] : $default;
}

function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_role() {
    return $_SESSION['user_role'] ?? 'guest';
}

/**
 * Wrap email bodies with a common HTML layout
 *
 * @param string $emailBody Raw HTML body fragment
 * @param string $recipientEmail Recipient email (used for unsubscribe links)
 * @return string Full HTML document
 */
function generateEmailHTML($emailBody, $recipientEmail)
{
    $emailBody = $emailBody ?? '<p>No email content generated. Please check the template name.</p>';
    $siteUrl = defined('BASE_URL') ? BASE_URL : ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000'));
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Uma Shakti Dham';
    $siteEmail = defined('SITE_EMAIL') ? SITE_EMAIL : 'noreply@umashaktidham.org';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title><?php echo htmlspecialchars($siteName); ?></title>
    </head>

    <body style="margin:0; padding:0; background-color:#f4f7fa; font-family:Arial, sans-serif; color:#333;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f7fa; padding:20px 0;">
            <tr>
                <td align="center">
                    <table cellpadding="0" cellspacing="0" border="0" width="700" style="width:100%; max-width:700px; background-color:#ffffff; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.06); border-bottom:4px solid #083e78;">
                        <!-- Top Bar -->
                        <tr>
                            <td style="background-color:#317cae; color:#ffffff; font-size:13px; padding:10px 20px;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="color:#ffffff; font-size:13px;">
                                            <!-- Optional info -->
                                        </td>
                                        <td align="right">
                                            <a href="mailto:<?php echo htmlspecialchars($siteEmail); ?>" style="color:#ffffff; text-decoration:none;"><?php echo htmlspecialchars($siteEmail); ?></a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Header -->
                        <tr>
                            <td style="padding:20px; border-bottom:2px solid #083e78;">
                                <table width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="width:80px;">
                                            <!-- optional logo -->
                                            <img src="<?php echo htmlspecialchars($siteUrl . '/assets/images/logo.png'); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" style="height:80px; width:80px; object-fit:contain;" />
                                        </td>
                                        <td style="padding-left:15px;">
                                            <h1 style="margin:0; font-size:22px; color:#083e78;"><?php echo htmlspecialchars($siteName); ?></h1>
                                            <p style="margin:5px 0 0; font-size:14px; color:#444;">Community • Culture • Connection</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Content -->
                        <tr>
                            <td style="padding:20px; font-size:15px; line-height:1.6; color:#333;">
                                <?php echo $emailBody; ?>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="padding:20px; font-size:12px; background-color:#f1f1f1; color:#555;">
                                <p>
                                    Communications from <?php echo htmlspecialchars($siteName); ?> are intended for community and organizational purposes. We respect your privacy. If you no longer wish to receive communications, you may unsubscribe.
                                </p>
                                <p>
                                    Contact: <a href="mailto:<?php echo htmlspecialchars($siteEmail); ?>"><?php echo htmlspecialchars($siteEmail); ?></a> |
                                    Visit: <a href="<?php echo htmlspecialchars($siteUrl); ?>"><?php echo htmlspecialchars($siteUrl); ?></a> |
                                    Unsubscribe: <a href="<?php echo htmlspecialchars($siteUrl . '/unsubscribe/' . urlencode(base64_encode($recipientEmail))); ?>">Unsubscribe</a>
                                </p>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
    </body>

    </html>
    <?php
    return ob_get_clean();
}
?>