<?php

class Mailer {
    private $fromAddress;
    private $fromName;
    private $resendApiKey;

    public function __construct() {
        $this->fromAddress = getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@example.com';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'Expense Tracker';
        $this->resendApiKey = getenv('RESEND_API_KEY') ?: '';
    }

    public function sendVerificationEmail($toEmail, $toName, $token) {
        $activationLink = $this->buildActivationLink($token);
        $subject = 'Activate your Expense Tracker account';

        $body = "Hello {$toName},\n\n"
            . "Please activate your account by clicking the link below:\n"
            . "{$activationLink}\n\n"
            . "This link expires in 24 hours.\n\n"
            . "If you did not create this account, please ignore this email.\n\n"
            . "Expense Tracker";

        if (!empty($this->resendApiKey)) {
            $resendResult = $this->sendViaResendApi($toEmail, $toName, $subject, $body);
            if ($resendResult['success']) {
                return ['success' => true, 'provider' => 'resend'];
            }
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromAddress . '>',
            'Reply-To: ' . $this->fromAddress,
            'X-Mailer: PHP/' . phpversion()
        ];

        $sent = @mail($toEmail, $subject, $body, implode("\r\n", $headers));

        if ($sent) {
            return ['success' => true];
        }

        return [
            'success' => false,
            'message' => 'Could not send verification email. Configure RESEND_API_KEY or PHP SMTP mail settings.',
            'activation_link' => $activationLink
        ];
    }

    private function sendViaResendApi($toEmail, $toName, $subject, $textBody) {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'cURL extension is required for Resend API'];
        }

        $payload = [
            'from' => $this->fromName . ' <' . $this->fromAddress . '>',
            'to' => [$toEmail],
            'subject' => $subject,
            'text' => $textBody
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->resendApiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            return ['success' => false, 'message' => $curlError];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'response' => $response];
        }

        return ['success' => false, 'message' => 'Resend API returned HTTP ' . $httpCode];
    }

    private function buildActivationLink($token) {
        $baseUrl = rtrim($this->getPublicBaseUrl(), '/');
        return $baseUrl . '/auth.html?verify_token=' . urlencode($token);
    }

    private function getPublicBaseUrl() {
        $appUrl = getenv('APP_URL');
        if (!empty($appUrl)) {
            return rtrim($appUrl, '/');
        }

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
        $scriptDir = str_replace('\\', '/', $scriptDir);

        if ($scriptDir === '/' || $scriptDir === '\\') {
            $scriptDir = '';
        }

        return $scheme . '://' . $host . rtrim($scriptDir, '/');
    }
}
