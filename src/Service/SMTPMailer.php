<?php

declare(strict_types=1);

namespace App\Service;

use mysqli;
use PDO;
use PDOException;
use App\Core\Container;
use App\Core\Database;
use App\Core\DB;
use Exception;
use Throwable;

/**
 * SMTP Mailer Class
 * Supports multiple email providers: Custom SMTP, Gmail, SendGrid, Mailgun
 */
class SMTPMailer
{
    private string $provider;
    private string $from_address;
    private string $from_name;
    // SMTP Configuration
    private string $smtp_host;
    private int $smtp_port;
    private string $smtp_encryption;
    private string $smtp_username;
    private string $smtp_password;
    private ?EmailProviderManager $providerManager = null;
    private ?array $activeProvider = null;
    private string $lastError = '';
    private string $lastSMTPCode = '';
    private string $lastSMTPResponse = '';
    private bool $skipHistoryLog = false;

    public function __construct()
    {
        $this->provider = 'database';
        $this->from_address = '';
        $this->from_name = '';
        $this->smtp_host = '';
        $this->smtp_port = 0;
        $this->smtp_encryption = 'tls';
        $this->smtp_username = '';
        $this->smtp_password = '';

        $dbConn = $this->resolveDbConnection();
        $this->providerManager = new EmailProviderManager($dbConn);
    }

    /**
     * Send email via configured provider
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML or plain text)
     * @param array $headers Additional headers (Reply-To, CC, BCC, etc.)
     * @return bool Success status
     */
    public function send(string $to, string $subject, string $body, array $headers = []): bool
    {
        try {
            $this->lastError = '';
            $this->lastSMTPCode = '';
            $this->lastSMTPResponse = '';
            $this->skipHistoryLog = !empty($headers['skip_history_log']);

            // Validate email address
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $this->lastError = "Invalid recipient email: $to";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            if (!$this->resolveProviderConfig($headers)) {
                return false;
            }

            // Log sending attempt
            $providerRef = !empty($this->activeProvider['id']) ? ('#' . $this->activeProvider['id']) : $this->from_address;
            error_log("[SMTPMailer] Sending email to $to via database provider {$providerRef}");

            return $this->sendViaSMTP($to, $subject, $body, $headers);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("[SMTPMailer] Exception: " . $this->lastError);
            return false;
        }
    }

    /**
     * Resolve email provider configuration.
     *
     * @param array $headers
     * @return bool
     */
    private function resolveProviderConfig(array $headers = []): bool
    {
        if (!$this->providerManager) {
            $this->lastError = 'EmailProviderManager is unavailable. DB provider resolution failed.';
            error_log('[SMTPMailer] ' . $this->lastError);
            return false;
        }

        $provider = null;
        $providerId = (int)($headers['provider_id'] ?? 0);
        $fromHeader = trim((string)($headers['from'] ?? $headers['From'] ?? ''));

        if ($providerId > 0) {
            $provider = $this->providerManager->getById($providerId);
        }

        if (!$provider && $fromHeader !== '') {
            $provider = $this->providerManager->getByEmail($fromHeader);
        }

        if (!$provider) {
            $provider = $this->providerManager->getDefault();
        }

        if (!$provider) {
            // No provider found via hint; try any available provider with quota
            $provider = $this->providerManager->getAvailableWithQuota();
        }

        if (!$provider) {
            $this->lastError = 'No active email provider found in email_providers table.';
            error_log('[SMTPMailer] ' . $this->lastError);
            return false;
        }

        // Check daily quota for the selected provider; failover if exhausted.
        $providerId = (int)($provider['id'] ?? 0);
        if ($providerId > 0) {
            $dailyLimit = (int)($provider['daily_limit'] ?? 0);
            if ($dailyLimit <= 0) {
                $dailyLimit = 100; // default
            }
            $conn = $this->resolveDbConnection();
            if ($conn !== null) {
                $sentToday = 0;
                $emailHistoryTable = class_exists('DB') && defined('DB::EMAIL_HISTORY') ? (string)constant('DB::EMAIL_HISTORY') : 'erp_email_history';
                if ($conn instanceof mysqli) {
                    $sentRow = $conn->query(
                        "SELECT COUNT(*) AS cnt FROM `" . $emailHistoryTable . "`
                         WHERE provider_id = $providerId
                           AND status = 'sent'
                           AND DATE(COALESCE(sent_at, created_at)) = CURDATE()"
                    );
                    $sentToday = $sentRow ? (int)($sentRow->fetch_assoc()['cnt'] ?? 0) : 0;
                } elseif ($conn instanceof PDO) {
                    try {
                        $stmt = $conn->prepare(
                            "SELECT COUNT(*) AS cnt FROM `" . $emailHistoryTable . "`
                             WHERE provider_id = ?
                               AND status = 'sent'
                               AND DATE(COALESCE(sent_at, created_at)) = CURDATE()"
                        );
                        $stmt->execute([$providerId]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $sentToday = $row ? (int)($row['cnt'] ?? 0) : 0;
                    } catch (PDOException $e) {
                        $sentToday = 0;
                    }
                }

                if ($sentToday >= $dailyLimit) {
                    error_log("[SMTPMailer] Provider #$providerId has reached daily limit ($sentToday/$dailyLimit). Switching to next available provider.");
                    $fallback = $this->providerManager->getAvailableWithQuota([$providerId]);
                    if (!$fallback) {
                        $this->lastError = "All email providers have reached their daily sending limit for today.";
                        error_log('[SMTPMailer] ' . $this->lastError);
                        return false;
                    }
                    $provider = $fallback;
                    error_log('[SMTPMailer] Switched to provider #' . (int)$provider['id'] . ' (' . ($provider['email'] ?? '') . ').');
                }
            }
        }

        $this->activeProvider = $provider;
        $this->from_address = trim((string)($provider['email'] ?? ''));
        $this->from_name = trim((string)($headers['from_name'] ?? $provider['provider_name'] ?? 'HAIPULSE'));
        $this->smtp_host = trim((string)($provider['smtp_host'] ?? ''));
        $this->smtp_port = (int)($provider['smtp_port'] ?? 0);
        $this->smtp_encryption = strtolower(trim((string)($provider['email_encryption'] ?? 'tls')));
        $this->smtp_username = trim((string)($provider['smtp_username'] ?? $this->from_address));
        $this->smtp_password = (string)($provider['smtp_password_decrypted'] ?? $provider['smtp_password'] ?? '');

        if (stripos($this->smtp_host, 'titan.email') !== false) {
            $this->smtp_port = 465;
            $this->smtp_encryption = 'ssl';
        }

        if (
            $this->from_address === '' ||
            $this->smtp_host === '' ||
            $this->smtp_port <= 0 ||
            $this->smtp_username === '' ||
            $this->smtp_password === ''
        ) {
            $this->lastError = 'Selected email provider has incomplete SMTP configuration.';
            error_log('[SMTPMailer] ' . $this->lastError);
            return false;
        }

        return true;
    }

    /**
     * Send via custom SMTP server
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $headers
     * @return bool
     */
    private function sendViaSMTP(string $to, string $subject, string $body, array $headers = []): bool
    {
        // Prepare headers
        $mail_headers = "Date: " . date('r') . "\r\n";
        $mail_headers .= "MIME-Version: 1.0\r\n";
        $mail_headers .= "X-Mailer: HAI-SMTP-Mailer/2.0\r\n";
        $mail_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        if (!empty($headers['Message-ID'])) {
            $mail_headers .= "Message-ID: {$headers['Message-ID']}\r\n";
        } else {
            $domain = preg_replace('/^.*@/', '', (string)$this->from_address);
            if ($domain === '' || strpos($domain, '.') === false) {
                $domain = 'haipulse.local';
            }
            $mail_headers .= "Message-ID: <" . uniqid('hai-', true) . "@{$domain}>\r\n";
        }

        // Add custom headers
        $replyTo = !empty($headers['Reply-To']) ? $headers['Reply-To'] : 'support@haipulse.com';
        $mail_headers .= "Reply-To: {$replyTo}\r\n";
        if (!empty($headers['CC'])) {
            $mail_headers .= "CC: {$headers['CC']}\r\n";
        }
        if (!empty($headers['BCC'])) {
            $mail_headers .= "BCC: {$headers['BCC']}\r\n";
        }
        // Email threading headers (RFC 2822)
        foreach (['Message-ID', 'In-Reply-To', 'References'] as $_hdr) {
            if (!empty($headers[$_hdr])) {
                $mail_headers .= "$_hdr: {$headers[$_hdr]}\r\n";
            }
        }

        // Try SMTP connection if credentials provided
        if ($this->smtp_username !== '' && $this->smtp_password !== '') {
            return $this->sendViaSMTPConnection($to, $subject, $body, $mail_headers);
        }

        $this->lastError = "No SMTP credentials provided. Email not sent. (host: {$this->smtp_host}, user: {$this->smtp_username})";
        error_log("[SMTPMailer] " . $this->lastError);
        return false;
    }

    /**
     * Read a complete SMTP response (handles multi-line responses)
     *
     * @param resource $socket
     * @return string The response code (3 digits)
     */
    private function readSMTPResponse($socket): string
    {
        $response = '';
        $responseCode = '';

        while (true) {
            $line = fgets($socket, 1024);
            if ($line === false || empty($line)) {
                break;
            }

            $response .= $line;

            // Extract code from first line
            if ($responseCode === '') {
                preg_match('/^(\d{3})/', $line, $matches);
                $responseCode = $matches[1] ?? '';
            }

            // Check if this is the last line (ends with space, not dash)
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }

        $response = trim($response);
        $this->lastSMTPResponse = $response;
        $this->lastSMTPCode = $responseCode;

        return $responseCode !== '' ? substr($response, 0, 3) : $response;
    }

    /**
     * Send via SMTP connection (TCP socket)
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $headers
     * @return bool
     */
    private function sendViaSMTPConnection(string $to, string $subject, string $body, string $headers): bool
    {
        try {
            // Build connection URL with scheme
            $hostname = ($this->smtp_encryption === 'ssl')
                ? "ssl://{$this->smtp_host}"
                : "tcp://{$this->smtp_host}";

            // Connect to SMTP server
            $socket = @fsockopen(
                $hostname,
                (int)$this->smtp_port,
                $errno,
                $errstr,
                10.0
            );

            if (!$socket) {
                $this->lastError = "SMTP connection failed: [$errno] $errstr";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            stream_set_timeout($socket, 10);

            // Read SMTP greeting (220)
            $code = $this->readSMTPResponse($socket);
            if ($code !== '220') {
                fclose($socket);
                $this->lastError = "Invalid SMTP greeting: $code";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            // Send EHLO
            fwrite($socket, "EHLO haipulse.local\r\n");
            $code = $this->readSMTPResponse($socket);
            if (strpos($code, '2') === false) {
                fclose($socket);
                $this->lastError = "EHLO failed: $code";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            // Upgrade to TLS if required
            if ($this->smtp_encryption === 'tls') {
                fwrite($socket, "STARTTLS\r\n");
                $code = $this->readSMTPResponse($socket);

                if (strpos($code, '220') === false) {
                    fclose($socket);
                    $this->lastError = "STARTTLS failed: $code";
                    error_log("[SMTPMailer] " . $this->lastError);
                    return false;
                }

                // Enable encryption
                stream_context_set_option($socket, 'ssl', 'verify_peer', false);
                stream_context_set_option($socket, 'ssl', 'verify_peer_name', false);
                $tlsEnabled = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($tlsEnabled !== true) {
                    fclose($socket);
                    $this->lastError = "TLS negotiation failed";
                    error_log("[SMTPMailer] " . $this->lastError);
                    return false;
                }

                // Send EHLO again after TLS
                fwrite($socket, "EHLO haipulse.local\r\n");
                $code = $this->readSMTPResponse($socket);
                if (strpos($code, '2') === false) {
                    fclose($socket);
                    $this->lastError = "EHLO after STARTTLS failed: $code";
                    error_log("[SMTPMailer] " . $this->lastError);
                    return false;
                }
            }

            // Authenticate
            fwrite($socket, "AUTH LOGIN\r\n");
            $code = $this->readSMTPResponse($socket);

            if ($code !== '334') {
                fclose($socket);
                $this->lastError = "AUTH LOGIN failed: $code";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            // Send username (base64 encoded)
            fwrite($socket, base64_encode($this->smtp_username) . "\r\n");
            $code = $this->readSMTPResponse($socket);

            if ($code !== '334') {
                fclose($socket);
                $this->lastError = "Username submission failed: $code";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            // Send password (base64 encoded)
            fwrite($socket, base64_encode($this->smtp_password) . "\r\n");
            $code = $this->readSMTPResponse($socket);

            if ($code !== '235') {
                fclose($socket);
                $this->lastError = "Authentication failed: $code";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            // Send MAIL FROM
            fwrite($socket, "MAIL FROM: <{$this->from_address}>\r\n");
            $code = $this->readSMTPResponse($socket);
            if (strpos($code, '2') === false) {
                fclose($socket);
                $this->lastError = "MAIL FROM failed: $code";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            // Send RCPT TO
            fwrite($socket, "RCPT TO: <$to>\r\n");
            $code = $this->readSMTPResponse($socket);
            if (strpos($code, '2') === false) {
                fclose($socket);
                $this->lastError = "RCPT TO failed: $code";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            // Send DATA
            fwrite($socket, "DATA\r\n");
            $code = $this->readSMTPResponse($socket);
            if ($code !== '354') {
                fclose($socket);
                $this->lastError = "DATA failed: $code";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            // Prepare message
            $message = "To: $to\r\n";
            $message .= "From: {$this->from_name} <{$this->from_address}>\r\n";
            $message .= "Subject: " . $this->encodeSubject($subject) . "\r\n";
            $message .= $headers;
            $message .= "\r\n" . $body;
            $message .= "\r\n.\r\n";

            // Send message
            fwrite($socket, $message);
            $code = $this->readSMTPResponse($socket);
            if (strpos($code, '2') === false) {
                fclose($socket);
                $this->lastError = "Message send failed: $code";
                error_log("[SMTPMailer] " . $this->lastError);
                return false;
            }

            // Close connection
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            $success = strpos($code, '250') !== false;

            // Log successful send to email history
            if ($success && !$this->skipHistoryLog) {
                $this->logToHistory($to, $subject);
            }

            return $success;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("[SMTPMailer] SMTP connection error: " . $this->lastError);
            return false;
        }
    }

    /**
     * Encode subject for non-ASCII characters
     *
     * @param string $subject
     * @return string
     */
    private function encodeSubject(string $subject): string
    {
        if (preg_match('/[\x80-\xFF]/', $subject)) {
            return '=?UTF-8?B?' . base64_encode($subject) . '?=';
        }
        return $subject;
    }

    /**
     * Insert a sent-email record into the email history table.
     */
    private function logToHistory(string $to, string $subject): void
    {
        $conn = $this->resolveDbConnection();
        if ($conn === null) {
            return;
        }
        $providerId = isset($this->activeProvider['id']) ? (int)$this->activeProvider['id'] : null;
        $fromName   = $this->from_name;
        $fromEmail  = $this->from_address;
        $subjectEsc = substr($subject, 0, 500);
        $emailHistoryTable = class_exists('DB') && defined('DB::EMAIL_HISTORY') ? (string)constant('DB::EMAIL_HISTORY') : 'erp_email_history';

        if ($conn instanceof mysqli) {
            $stmt = $conn->prepare(
                "INSERT INTO `" . $emailHistoryTable . "`
                 (recipient_email, subject, provider_id, status, sent_at, from_name, from_email)
                 VALUES (?, ?, ?, 'sent', NOW(), ?, ?)"
            );
            if (!$stmt) {
                error_log('[SMTPMailer] logToHistory prepare failed: ' . $conn->error);
                return;
            }
            $stmt->bind_param('ssiss', $to, $subjectEsc, $providerId, $fromName, $fromEmail);
            $stmt->execute();
            $stmt->close();
        } elseif ($conn instanceof PDO) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO `" . $emailHistoryTable . "`
                     (recipient_email, subject, provider_id, status, sent_at, from_name, from_email)
                     VALUES (?, ?, ?, 'sent', NOW(), ?, ?)"
                );
                $stmt->execute([$to, $subjectEsc, $providerId, $fromName, $fromEmail]);
            } catch (PDOException $e) {
                error_log('[SMTPMailer] logToHistory execute failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Resolve active DB connection from commonly used handles or DI container.
     */
    private function resolveDbConnection(): mixed
    {
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            return $GLOBALS['conn'];
        }

        if (isset($GLOBALS['DB']['MSQLI']) && $GLOBALS['DB']['MSQLI'] instanceof mysqli) {
            return $GLOBALS['DB']['MSQLI'];
        }

        if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) {
            return $GLOBALS['mysqli'];
        }

        try {
            $container = Container::getInstance();
            if ($container->has(Database::class)) {
                $resolved = $container->get(Database::class);
                if ($resolved instanceof Database) {
                    return $resolved->getConnection(); // returns raw PDO object
                }
            }
        } catch (Throwable $e) {
            // Ignore container errors
        }

        return null;
    }

    /**
     * Test SMTP connectivity/auth for a provider configuration.
     *
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $encryption tls|ssl
     * @return array{success:bool,message:string}
     */
    public function testConnection(string $host, int $port, string $username, string $password, string $encryption = 'tls'): array
    {
        $host = trim($host);
        $username = trim($username);
        $encryption = strtolower(trim($encryption));

        if ($host === '' || $port <= 0 || $username === '' || $password === '') {
            return ['success' => false, 'message' => 'Incomplete SMTP configuration'];
        }

        $hostname = ($encryption === 'ssl') ? "ssl://{$host}" : "tcp://{$host}";

        try {
            $socket = @fsockopen($hostname, $port, $errno, $errstr, 10.0);
            if (!$socket) {
                return ['success' => false, 'message' => "Connection failed: [$errno] $errstr"];
            }

            stream_set_timeout($socket, 10);

            $code = $this->readSMTPResponse($socket);
            if ($code !== '220') {
                fclose($socket);
                return ['success' => false, 'message' => "Invalid SMTP greeting: $code"];
            }

            fwrite($socket, "EHLO haipulse.local\r\n");
            $code = $this->readSMTPResponse($socket);
            if (strpos($code, '2') !== 0) {
                fclose($socket);
                return ['success' => false, 'message' => "EHLO failed: $code"];
            }

            if ($encryption === 'tls') {
                fwrite($socket, "STARTTLS\r\n");
                $code = $this->readSMTPResponse($socket);
                if (strpos($code, '220') !== 0) {
                    fclose($socket);
                    return ['success' => false, 'message' => "STARTTLS failed: $code"];
                }

                stream_context_set_option($socket, 'ssl', 'verify_peer', false);
                stream_context_set_option($socket, 'ssl', 'verify_peer_name', false);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

                fwrite($socket, "EHLO haipulse.local\r\n");
                $code = $this->readSMTPResponse($socket);
                if (strpos($code, '2') !== 0) {
                    fclose($socket);
                    return ['success' => false, 'message' => "EHLO after STARTTLS failed: $code"];
                }
            }

            fwrite($socket, "AUTH LOGIN\r\n");
            $code = $this->readSMTPResponse($socket);
            if ($code !== '334') {
                fclose($socket);
                return ['success' => false, 'message' => "AUTH LOGIN failed: $code"];
            }

            fwrite($socket, base64_encode($username) . "\r\n");
            $code = $this->readSMTPResponse($socket);
            if ($code !== '334') {
                fclose($socket);
                return ['success' => false, 'message' => "Username rejected: $code"];
            }

            fwrite($socket, base64_encode($password) . "\r\n");
            $code = $this->readSMTPResponse($socket);
            if ($code !== '235') {
                fclose($socket);
                return ['success' => false, 'message' => "Authentication failed: $code"];
            }

            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            return ['success' => true, 'message' => 'SMTP connection and authentication successful'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get current provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get configuration summary
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'provider' => $this->provider,
            'from_address' => $this->from_address,
            'from_name' => $this->from_name,
            'provider_id' => $this->activeProvider['id'] ?? null
        ];
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getLastSMTPCode(): string
    {
        return $this->lastSMTPCode;
    }

    public function getLastSMTPResponse(): string
    {
        return $this->lastSMTPResponse;
    }
}
