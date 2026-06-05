<?php
/**
 * SMTP Mailer Class
 * Supports multiple email providers: Custom SMTP, Gmail, SendGrid, Mailgun
 * 
 * @package HAI\Email
 * @version 2.0
 * @date March 5, 2026
 * 
 * Usage:
 *   $mailer = new SMTPMailer();
 *   $mailer->send('user@example.com', 'Subject', 'Body', ['Reply-To' => 'support@haipulse.com']);
 */

class SMTPMailer {
    private $provider;
    private $from_address;
    private $from_name;
    // SMTP Configuration
    private $smtp_host;
    private $smtp_port;
    private $smtp_encryption;
    private $smtp_username;
    private $smtp_password;
    // Provider-specific
    private $gmail_address;
    private $gmail_app_password;
    private $sendgrid_api_key;
    private $mailgun_domain;
    private $mailgun_secret;
    private $providerManager;
    private $activeProvider;
    private $lastError = '';
    private $lastSMTPCode = '';
    private $lastSMTPResponse = '';
    private $skipHistoryLog = false;

    public function __construct() {
        $this->provider = 'database';
        $this->from_address = '';
        $this->from_name = '';
        $this->smtp_host = '';
        $this->smtp_port = 0;
        $this->smtp_encryption = 'tls';
        $this->smtp_username = '';
        $this->smtp_password = '';
        $this->gmail_address = '';
        $this->gmail_app_password = '';
        $this->sendgrid_api_key = '';
        $this->mailgun_domain = '';
        $this->mailgun_secret = '';

        if (!class_exists('EmailProviderManager')) {
            $providerManagerPath = __DIR__ . '/EmailProviderManager.php';
            if (file_exists($providerManagerPath)) {
                require_once $providerManagerPath;
            }
        }

        $dbConn = $this->resolveDbConnection();
        if (class_exists('EmailProviderManager') && $dbConn instanceof mysqli) {
            $this->providerManager = new EmailProviderManager($dbConn);
        }
    }
    
    /**
     * Send email via configured provider
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML or plain text)
     * @param array $headers Additional headers (Reply-To, CC, BCC, etc.)
     * @return bool Success status
     */
    public function send($to, $subject, $body, $headers = []) {
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
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("[SMTPMailer] Exception: " . $this->lastError);
            return false;
        }
    }

    private function resolveProviderConfig($headers = []) {
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
            $conn = $GLOBALS['conn'] ?? null;
            if ($conn instanceof mysqli) {
                $emailHistoryTable = class_exists('DB') ? DB::EMAIL_HISTORY : 'erp_email_history';
                $sentRow = $conn->query(
                    "SELECT COUNT(*) AS cnt FROM `" . $emailHistoryTable . "`
                     WHERE provider_id = $providerId
                       AND status = 'sent'
                       AND DATE(COALESCE(sent_at, created_at)) = CURDATE()"
                );
                $sentToday = $sentRow ? (int)($sentRow->fetch_assoc()['cnt'] ?? 0) : 0;
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
     * @return bool
     */
    private function sendViaSMTP($to, $subject, $body, $headers = []) {
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
        
        // Add custom headers â€” default Reply-To to support@haipulse.com
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
        if (!empty($this->smtp_username) && !empty($this->smtp_password)) {
            return $this->sendViaSMTPConnection($to, $subject, $body, $mail_headers);
        }

        // No SMTP credentials: do NOT fallback to mail(), log error and return false
        $this->lastError = "No SMTP credentials provided. Email not sent. (host: {$this->smtp_host}, user: {$this->smtp_username})";
        error_log("[SMTPMailer] " . $this->lastError);
        return false;
    }
    
    /**
     * Read a complete SMTP response (handles multi-line responses)
     * SMTP responses can span multiple lines:
     * - Lines ending with "-" (e.g., "250-") indicate more lines follow
     * - The last line uses " " (e.g., "250 ") to indicate end of response
     * @param resource $socket
     * @return string The response code (3 digits)
     */
    private function readSMTPResponse($socket) {
        $response = '';
        $responseCode = '';
        
        while (true) {
            $line = fgets($socket, 1024);
            if ($line === false || empty($line)) {
                break;
            }
            
            $response .= $line;
            
            // Extract code from first line
            if (empty($responseCode)) {
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
        $this->lastSMTPCode = $responseCode ?: '';

        return $responseCode ? substr($response, 0, 3) : $response;
    }

    /**
     * Send via SMTP connection (TCP socket)
     * Uses stream socket for direct SMTP communication
     * @return bool
     */
    private function sendViaSMTPConnection($to, $subject, $body, $headers) {
        try {
            // Build connection URL with scheme
            $hostname = ($this->smtp_encryption === 'ssl') 
                ? "ssl://{$this->smtp_host}"
                : "tcp://{$this->smtp_host}";
            
            // Connect to SMTP server
            // Note: When using a scheme (ssl://, tcp://), the port must not be included in hostname
            $socket = @fsockopen(
                $hostname,
                (int)$this->smtp_port,
                $errno,
                $errstr,
                10
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

            $success = is_string($code) && strpos($code, '250') !== false;

            // Log successful send to email history so quota/stats are accurate.
            if ($success && !$this->skipHistoryLog) {
                $this->logToHistory($to, $subject);
            }

            return $success;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log("[SMTPMailer] SMTP connection error: " . $this->lastError);
            return false;
        }
    }

    /**
    * Insert a sent-email record into the email history table.
     * Silently skips if $GLOBALS['conn'] is unavailable (e.g. CLI without DB).
     */
    private function logToHistory($to, $subject) {
        $conn = $this->resolveDbConnection();
        if (!$conn instanceof mysqli) {
            return;
        }
        $providerId = isset($this->activeProvider['id']) ? (int)$this->activeProvider['id'] : null;
        $fromName   = (string)($this->from_name ?? '');
        $fromEmail  = (string)($this->from_address ?? '');
        $subjectEsc = substr((string)$subject, 0, 500);
        $emailHistoryTable = class_exists('DB') ? DB::EMAIL_HISTORY : 'erp_email_history';

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
    }

    /**
     * Resolve active DB connection from commonly used global handles.
     */
    private function resolveDbConnection() {
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            return $GLOBALS['conn'];
        }

        if (isset($GLOBALS['DB']['MSQLI']) && $GLOBALS['DB']['MSQLI'] instanceof mysqli) {
            return $GLOBALS['DB']['MSQLI'];
        }

        if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) {
            return $GLOBALS['mysqli'];
        }

        return null;
    }
    
    /**
     * Send via Gmail SMTP
     * Requires App Password: https://myaccount.google.com/apppasswords
     * @return bool
     */
    private function sendViaGmail($to, $subject, $body, $headers = []) {
        if (empty($this->gmail_address) || empty($this->gmail_app_password)) {
            error_log("[SMTPMailer] Gmail credentials not configured");
            return false;
        }
        
        // Use custom SMTP with Gmail configuration
        $this->smtp_host = 'smtp.gmail.com';
        $this->smtp_port = 587;
        $this->smtp_encryption = 'tls';
        $this->smtp_username = $this->gmail_address;
        $this->smtp_password = $this->gmail_app_password;
        $this->from_address = $this->gmail_address;
        
        return $this->sendViaSMTPConnection($to, $subject, $body, 
            $this->buildHeaders($headers));
    }
    
    /**
     * Send via SendGrid API
     * Requires API key: https://app.sendgrid.com/settings/api_keys
     * @return bool
     */
    private function sendViaSendGrid($to, $subject, $body, $headers = []) {
        if (empty($this->sendgrid_api_key)) {
            error_log("[SMTPMailer] SendGrid API key not configured");
            return false;
        }
        
        $payload = [
            'personalizations' => [
                [
                    'to' => [['email' => $to]],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => $this->from_address,
                'name' => $this->from_name
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $body
                ]
            ]
        ];
        
        // Add reply-to if provided
        if (!empty($headers['Reply-To'])) {
            $payload['reply_to'] = [
                'email' => $headers['Reply-To']
            ];
        }
        // Email threading headers (Message-ID, In-Reply-To, References)
        $customHeaders = [];
        foreach (['Message-ID', 'In-Reply-To', 'References'] as $_hdr) {
            if (!empty($headers[$_hdr])) {
                $customHeaders[$_hdr] = $headers[$_hdr];
            }
        }
        if (!empty($customHeaders)) {
            $payload['headers'] = $customHeaders;
        }
        
        // Make API request
        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->sendgrid_api_key,
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!empty($error)) {
            error_log("[SMTPMailer] SendGrid curl error: $error");
            return false;
        }
        
        if ($http_code !== 202) {
            error_log("[SMTPMailer] SendGrid API error ($http_code): $response");
            return false;
        }
        
        return true;
    }
    
    /**
     * Send via Mailgun API
     * Requires domain and secret: https://app.mailgun.com/app/domains
     * @return bool
     */
    private function sendViaMailgun($to, $subject, $body, $headers = []) {
        if (empty($this->mailgun_domain) || empty($this->mailgun_secret)) {
            error_log("[SMTPMailer] Mailgun credentials not configured");
            return false;
        }
        
        $postData = [
            'from' => "{$this->from_name} <{$this->from_address}>",
            'to' => $to,
            'subject' => $subject,
            'html' => $body
        ];
        
        // Add reply-to if provided
        if (!empty($headers['Reply-To'])) {
            $postData['h:Reply-To'] = $headers['Reply-To'];
        }
        // Email threading headers (Message-ID, In-Reply-To, References)
        foreach (['Message-ID', 'In-Reply-To', 'References'] as $_hdr) {
            if (!empty($headers[$_hdr])) {
                $postData['h:' . $_hdr] = $headers[$_hdr];
            }
        }
        
        // Make API request
        $ch = curl_init("https://api.mailgun.net/v3/{$this->mailgun_domain}/messages");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_USERPWD => "api:{$this->mailgun_secret}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!empty($error)) {
            error_log("[SMTPMailer] Mailgun curl error: $error");
            return false;
        }
        
        if ($http_code !== 200) {
            error_log("[SMTPMailer] Mailgun API error ($http_code): $response");
            return false;
        }
        
        return true;
    }
    
    /**
     * Build mail headers from array
     * @return string
     */
    private function buildHeaders($headers = []) {
        $result = "X-Mailer: HAI-SMTP-Mailer/2.0\r\n";
        $result .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Forwarded headers including Reply-To and email threading (RFC 2822)
        foreach (['Reply-To', 'CC', 'BCC', 'Message-ID', 'In-Reply-To', 'References'] as $key) {
            if (!empty($headers[$key])) {
                $result .= "$key: {$headers[$key]}\r\n";
            }
        }
        
        return $result;
    }
    
    /**
     * Encode subject for non-ASCII characters
     * @return string
     */
    private function encodeSubject($subject) {
        if (preg_match('/[\x80-\xFF]/', $subject)) {
            return '=?UTF-8?B?' . base64_encode($subject) . '?=';
        }
        return $subject;
    }

    /**
     * Test raw SMTP connectivity/auth for a provider configuration.
     *
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $encryption tls|ssl
     * @return array{success:bool,message:string}
     */
    public function testConnection($host, $port, $username, $password, $encryption = 'tls') {
        $host = trim((string)$host);
        $port = (int)$port;
        $username = trim((string)$username);
        $password = (string)$password;
        $encryption = strtolower(trim((string)$encryption));

        if ($host === '' || $port <= 0 || $username === '' || $password === '') {
            return ['success' => false, 'message' => 'Incomplete SMTP configuration'];
        }

        $hostname = ($encryption === 'ssl') ? "ssl://{$host}" : "tcp://{$host}";

        try {
            $socket = @fsockopen($hostname, $port, $errno, $errstr, 10);
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
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get current provider
     * @return string
     */
    public function getProvider() {
        return $this->provider;
    }
    
    /**
     * Get configuration summary
     * @return array
     */
    public function getConfig() {
        return [
            'provider' => $this->provider,
            'from_address' => $this->from_address,
            'from_name' => $this->from_name,
            'provider_id' => $this->activeProvider['id'] ?? null
        ];
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function getLastSMTPCode() {
        return $this->lastSMTPCode;
    }

    public function getLastSMTPResponse() {
        return $this->lastSMTPResponse;
    }
}
?>

