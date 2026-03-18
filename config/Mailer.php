<?php

class Mailer {
    private $config;

    public function __construct() {
        if (file_exists(dirname(__FILE__) . '/mail.php')) {
            $this->config = require dirname(__FILE__) . '/mail.php';
        } else {
            $this->config = ['enabled' => false];
        }
    }

    public function sendVerificationEmail($toEmail, $toName, $token) {
        if (empty($this->config['enabled'])) {
            return ['success' => false, 'message' => 'Mailer not configured/enabled.'];
        }

        require_once dirname(__FILE__) . '/../vendor/PHPMailer/src/Exception.php';
        require_once dirname(__FILE__) . '/../vendor/PHPMailer/src/PHPMailer.php';
        require_once dirname(__FILE__) . '/../vendor/PHPMailer/src/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'] === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['port'];

            // Recipients
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($toEmail, $toName);

            // Content
            $mail->isHTML(true);
            $activationLink = $this->buildActivationLink($token);
            $mail->Subject = 'Activate your Expense Tracker account';
            
            $htmlBody = "<h2>Hello {$toName},</h2>"
                . "<p>Welcome to Expense Tracker! Please verify your email address to activate your account.</p>"
                . "<p><a href='{$activationLink}' style='display:inline-block;padding:10px 20px;background-color:#4f46e5;color:#fff;text-decoration:none;border-radius:6px;'>Verify Email</a></p>"
                . "<p>Or copy this link to your browser: <br> <a href='{$activationLink}'>{$activationLink}</a></p>"
                . "<p>If you did not create this account, please ignore this email.</p>";
                
            $textBody = "Hello {$toName},\n\n"
                . "Please verify your email by opening the link below:\n"
                . "{$activationLink}\n\n"
                . "If you did not create this account, please ignore this email.";

            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();
            return ['success' => true];
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            return [
                'success' => false,
                'message' => 'Mailer Error: ' . $mail->ErrorInfo,
                'activation_link' => $this->buildActivationLink($token)
            ];
        }
    }

    private function buildActivationLink($token) {
        $baseUrl = rtrim($this->getPublicBaseUrl(), '/');
        // We'll point this to verify.html, which calls the verify API endpoint
        return $baseUrl . '/verify.html?token=' . urlencode($token);
    }

    private function getPublicBaseUrl() {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
        $scriptDir = str_replace('\\', '/', $scriptDir);
        if ($scriptDir === '/' || $scriptDir === '\\') {
            $scriptDir = '';
        }
        return $scheme . '://' . $host . rtrim($scriptDir, '/');
    }
}
