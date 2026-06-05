<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

/**
 * Simple Captcha Utility
 *
 * Generates and validates simple session-based CAPTCHAs.
 */
class SimpleCaptcha
{
    private const SESSION_ROOT = 'hai_simple_captcha';
    private const DEFAULT_LENGTH = 5;
    private const DEFAULT_TTL = 900;
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Ensure challenge code exists for the context.
     *
     * @param string $context
     * @param int $length
     * @return void
     */
    public static function ensureChallenge(string $context, int $length = self::DEFAULT_LENGTH): void
    {
        self::ensureSessionBucket();

        if (!isset($_SESSION[self::SESSION_ROOT][$context]['code'])) {
            self::refreshChallenge($context, $length);
        }
    }

    /**
     * Refresh challenge code for the context.
     *
     * @param string $context
     * @param int $length
     * @return void
     */
    public static function refreshChallenge(string $context, int $length = self::DEFAULT_LENGTH): void
    {
        self::ensureSessionBucket();

        $_SESSION[self::SESSION_ROOT][$context] = [
            'code' => self::generateCode($length),
            'generated_at' => time(),
        ];
    }

    /**
     * Validate captcha input against stored code.
     *
     * @param string $context
     * @param string $input
     * @param int $ttl
     * @return bool
     */
    public static function validate(string $context, string $input, int $ttl = self::DEFAULT_TTL): bool
    {
        self::ensureSessionBucket();

        $challenge = $_SESSION[self::SESSION_ROOT][$context] ?? null;
        if (!is_array($challenge) || empty($challenge['code']) || empty($challenge['generated_at'])) {
            self::refreshChallenge($context);
            return false;
        }

        $generatedAt = (int)$challenge['generated_at'];
        if ((time() - $generatedAt) > $ttl) {
            self::refreshChallenge($context);
            return false;
        }

        $normalizedInput = strtoupper(trim($input));
        $normalizedCode = strtoupper((string)$challenge['code']);
        $isValid = ($normalizedInput !== '') && hash_equals($normalizedCode, $normalizedInput);

        self::refreshChallenge($context);

        return $isValid;
    }

    /**
     * Render SVG representation of the CAPTCHA.
     *
     * @param string $context
     * @return string SVG code
     */
    public static function renderSvg(string $context): string
    {
        self::ensureChallenge($context);
        $code = (string)($_SESSION[self::SESSION_ROOT][$context]['code'] ?? 'ERROR');

        $width = 180;
        $height = 56;
        $chars = preg_split('//u', $code, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $charCount = max(1, count($chars));
        $step = 28;
        $startX = 18;

        $svg = [];
        $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Captcha code">';
        $svg[] = '<rect width="100%" height="100%" rx="8" fill="#f7fafc" stroke="#d4dde8"/>';

        for ($i = 0; $i < 6; $i++) {
            $x1 = 8 + (($i * 29) % ($width - 16));
            $y1 = 10 + (($i * 11) % ($height - 20));
            $x2 = 20 + (($i * 31) % ($width - 24));
            $y2 = 12 + (($i * 17) % ($height - 24));
            $svg[] = '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="#c8d5e6" stroke-width="1.2" opacity="0.7"/>';
        }

        for ($i = 0; $i < 18; $i++) {
            $cx = 10 + (($i * 19) % ($width - 20));
            $cy = 8 + (($i * 13) % ($height - 16));
            $svg[] = '<circle cx="' . $cx . '" cy="' . $cy . '" r="1.1" fill="#9db3cc" opacity="0.65"/>';
        }

        foreach ($chars as $index => $char) {
            $x = $startX + ($index * $step);
            $y = 36 + (($index % 2) * 3);
            $rotation = (($index % 2) === 0) ? -8 : 9;
            $safeChar = htmlspecialchars($char, ENT_QUOTES, 'UTF-8');
            $svg[] = '<text x="' . $x . '" y="' . $y . '" font-family="monospace" font-size="28" font-weight="700" fill="#18324d" transform="rotate(' . $rotation . ' ' . $x . ' ' . $y . ')">' . $safeChar . '</text>';
        }

        $svg[] = '</svg>';

        return implode('', $svg);
    }

    /**
     * Ensure session is active and captcha bucket is set.
     *
     * @return void
     * @throws RuntimeException
     */
    private static function ensureSessionBucket(): void
    {
        if (!isset($_SESSION)) {
            throw new RuntimeException('Session is not available for captcha handling.');
        }

        if (!isset($_SESSION[self::SESSION_ROOT]) || !is_array($_SESSION[self::SESSION_ROOT])) {
            $_SESSION[self::SESSION_ROOT] = [];
        }
    }

    /**
     * Generate secure random CAPTCHA code.
     *
     * @param int $length
     * @return string
     */
    private static function generateCode(int $length): string
    {
        $length = max(4, min(7, $length));
        $alphabet = self::ALPHABET;
        $maxIndex = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $maxIndex)];
        }

        return $code;
    }
}
