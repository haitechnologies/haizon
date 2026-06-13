<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public const EMERGENCY = 'emergency';
    public const ALERT     = 'alert';
    public const CRITICAL  = 'critical';
    public const ERROR     = 'error';
    public const WARNING   = 'warning';
    public const NOTICE    = 'notice';
    public const INFO      = 'info';
    public const DEBUG     = 'debug';

    private string $logDir;
    private string $environment;
    private static array $requestSignatures = [];

    public function __construct(?string $logDir = null, string $environment = 'development')
    {
        $this->logDir = $logDir ?? (dirname(__DIR__, 2) . '/logs');
        $this->environment = $environment;
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        if ($this->environment === 'production') {
            return;
        }
        $this->log(self::DEBUG, $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $interpolated = $this->interpolate($message, $context);

        $uri = (string)($_SERVER['REQUEST_URI'] ?? 'CLI');
        $method = (string)($_SERVER['REQUEST_METHOD'] ?? 'N/A');
        $signature = sha1($level . '|' . $interpolated . '|' . $uri . '|' . $method);
        if (isset(self::$requestSignatures[$signature])) {
            return;
        }
        self::$requestSignatures[$signature] = true;

        $file = $context['file'] ?? 'unknown';
        $line = $context['line'] ?? 0;
        unset($context['file'], $context['line']);

        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0755, true);
        }

        $logFile = $this->logDir . '/application.log';
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);

        $entry = "[$timestamp] [$levelUpper] [$file:$line]\n";
        $entry .= "Message: " . $interpolated . "\n";
        $entry .= "Context: " . json_encode($context + ['uri' => $uri, 'method' => $method]) . "\n";
        $entry .= "---\n";

        $fp = @fopen($logFile, 'a');
        if ($fp) {
            if (@flock($fp, LOCK_EX)) {
                fwrite($fp, $entry);
                @flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string)$val;
            }
        }
        return strtr($message, $replace);
    }
}
