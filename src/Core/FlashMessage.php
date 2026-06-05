<?php

declare(strict_types=1);

namespace App\Core;

/**
 * FlashMessage Class - Centralized Session-based Flash Messages
 *
 * Handles setting and retrieving success, error, warning, and informational
 * messages across page redirects.
 *
 * @package App\Core
 */
class FlashMessage
{
    private const SESSION_KEY = '__flash_messages';

    /**
     * Set a success flash message.
     */
    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    /**
     * Set an error flash message.
     */
    public static function error(string $message): void
    {
        self::add('danger', $message);
    }

    /**
     * Set an info flash message.
     */
    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    /**
     * Set a warning flash message.
     */
    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    /**
     * Add a raw flash message of specific type.
     */
    public static function add(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION[self::SESSION_KEY][] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => time()
        ];
    }

    /**
     * Fetch all queued flash messages and clear them from session.
     *
     * @return array Array of messages [['type' => ..., 'message' => ..., 'timestamp' => ...]]
     */
    public static function all(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);
        return is_array($messages) ? $messages : [];
    }
}
