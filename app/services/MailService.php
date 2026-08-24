<?php
// app/services/MailService.php

require_once __DIR__ . '/../../Core/Env.php';
require_once __DIR__ . '/../helpers/Settings.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private bool $enabled;
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private string $encryption;

    public function __construct()
    {
        Env::load();

        $this->enabled    = class_exists('Settings') ? (bool)Settings::get('notifications.email.enabled', true) : true;
        $this->host       = class_exists('Settings') ? Settings::get('notifications.email.smtp_host', Env::get('SMTP_HOST') ?: 'smtp.gmail.com') : (Env::get('SMTP_HOST') ?: 'smtp.gmail.com');
        $this->port       = class_exists('Settings') ? (int)Settings::get('notifications.email.smtp_port', (int)(Env::get('SMTP_PORT') ?: 587)) : (int)(Env::get('SMTP_PORT') ?: 587);
        $this->username   = class_exists('Settings') ? Settings::get('notifications.email.smtp_username', Env::get('SMTP_USER') ?: '') : (Env::get('SMTP_USER') ?: '');
        $this->password   = class_exists('Settings') ? Settings::get('notifications.email.smtp_password', Env::get('SMTP_PASS') ?: '') : (Env::get('SMTP_PASS') ?: '');
        $this->fromEmail  = class_exists('Settings') ? Settings::get('notifications.email.sender_email', Env::get('SMTP_FROM') ?: 'no-reply@caloocan.gov.ph') : (Env::get('SMTP_FROM') ?: 'no-reply@caloocan.gov.ph');
        $this->fromName   = class_exists('Settings') ? Settings::get('notifications.email.sender_name', Env::get('SMTP_FROM_NAME') ?: 'Caloocan City Health Office') : (Env::get('SMTP_FROM_NAME') ?: 'Caloocan City Health Office');

        $encSetting = class_exists('Settings') ? strtolower(trim(Settings::get('notifications.email.smtp_encryption', 'tls'))) : (Env::get('SMTP_ENCRYPTION') ?: 'tls');
        if ($encSetting === 'ssl') {
            $this->encryption = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encSetting === 'none') {
            $this->encryption = '';
        } else {
            $this->encryption = PHPMailer::ENCRYPTION_STARTTLS;
        }
    }

    /**
     * Sends a 6-digit OTP verification email to an employee.
     * If SMTP is not configured or disabled, logs the code locally to storage/cache/last_otp.log for dev testing.
     */
    public function sendOtpEmail(string $toEmail, string $recipientName, string $otpCode, int $expiresMinutes = 15): bool
    {
        // Dev / Fallback log
        $logDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logContent = date('Y-m-d H:i:s') . " | OTP for {$toEmail} ({$recipientName}): {$otpCode}\n";
        @file_put_contents($logDir . '/last_otp.log', $logContent, FILE_APPEND);

        if (!$this->enabled) {
            error_log("MailService: Email dispatch disabled in Settings. OTP {$otpCode} recorded in log for {$toEmail}.");
            return true;
        }

        // If no SMTP password or username is provided, treat local dev log as success
        if (empty($this->username) || empty($this->password)) {
            error_log("MailService: SMTP credentials not set. OTP {$otpCode} logged to storage/cache/last_otp.log for {$toEmail}.");
            return true;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = $this->encryption;
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $recipientName);

            $mail->isHTML(true);
            $mail->Subject = "{$otpCode} is your Employee Portal Security Code";
            $mail->Body    = $this->renderOtpTemplate($recipientName, $otpCode, $expiresMinutes);
            $mail->AltBody = "Hello {$recipientName},\nYour employee portal verification code is: {$otpCode}\nValid for {$expiresMinutes} minutes.";

            return $mail->send();
        } catch (Exception $e) {
            error_log("MailService Error sending to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Renders a responsive, modern HTML email template for the OTP code.
     */
    private function renderOtpTemplate(string $name, string $code, int $minutes): string
    {
        $year = date('Y');
        return "
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset='utf-8'>
          <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f8ff; margin: 0; padding: 20px; }
            .card { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #B4D4FF; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(23, 107, 135, 0.1); }
            .header { background: linear-gradient(135deg, #176B87 0%, #86B6F6 100%); color: #ffffff; padding: 28px 24px; text-align: center; }
            .header h1 { margin: 0; font-size: 22px; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; }
            .content { padding: 32px 24px; text-align: center; color: #475569; }
            .code-box { background-color: #EEF5FF; border: 2px dashed #86B6F6; border-radius: 14px; padding: 20px; margin: 24px 0; display: inline-block; width: 85%; }
            .code { font-size: 38px; font-weight: 900; letter-spacing: 12px; color: #176B87; font-family: 'Courier New', Courier, monospace; }
            .footer { background-color: #f8fafc; padding: 18px; text-align: center; font-size: 11px; color: #94a3b8; border-t: 1px solid #e2e8f0; }
          </style>
        </head>
        <body>
          <div class='card'>
            <div class='header'>
              <h1>Civentral</h1>
              <p style='margin:6px 0 0 0; font-size:12px; font-weight:600; opacity:0.9; text-transform:uppercase; letter-spacing:1px;'>Health & Sanitation Office · Caloocan LGU</p>
            </div>
            <div class='content'>
              <h2 style='margin-top:0; font-size:18px; font-weight:800; color:#176B87;'>Security Verification Code</h2>
              <p style='font-size:14px; color:#64748b;'>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
              <p style='font-size:14px; color:#64748b; margin-bottom:10px;'>Use the following 6-digit security code to complete your Employee Portal login:</p>
              <div class='code-box'>
                <span class='code'>{$code}</span>
              </div>
              <p style='font-size:12px; color:#94a3b8; margin-bottom:0;'>This code will expire in <strong style='color:#176B87;'>{$minutes} minutes</strong>.<br>If you did not request this verification, please contact system administration.</p>
            </div>
            <div class='footer'>
              &copy; {$year} Health & Sanitation Management Information System · Caloocan City
            </div>
          </div>
        </body>
        </html>
        ";
    }

    /**
     * Sends a generic system notification email using the configured SMTP settings.
     */
    public function sendNotificationEmail(string $toEmail, string $recipientName, string $subject, string $htmlBody, string $altBody = ''): bool
    {
        // Dev / Fallback log
        $logDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logContent = date('Y-m-d H:i:s') . " | Notification to {$toEmail} ({$recipientName}) | Subject: {$subject}\n";
        @file_put_contents($logDir . '/notifications.log', $logContent, FILE_APPEND);

        if (!$this->enabled) {
            error_log("MailService: Email notifications disabled in Settings. Skipped dispatching to {$toEmail}.");
            return true;
        }

        if (empty($this->username) || empty($this->password)) {
            error_log("MailService: SMTP credentials not set. Notification email to {$toEmail} logged to storage/cache/notifications.log.");
            return true;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            if (!empty($this->encryption)) {
                $mail->SMTPSecure = $this->encryption;
            }
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $recipientName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);

            return $mail->send();
        } catch (Exception $e) {
            error_log("MailService Error sending notification to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
