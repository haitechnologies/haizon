<?php

declare(strict_types=1);

namespace App\Security;

/**
 * TOTP Authenticator helper compatible with Google Authenticator apps.
 */
class TOTPAuthenticator
{
    private const DIGITS = 6;
    private const PERIOD = 30;
    private const DEFAULT_WINDOW = 1;
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a Base32 secret for TOTP enrollment.
     *
     * @param int|string $length
     * @return string
     */
    public static function generateSecret(mixed $length = 32): string
    {
        $length = max(16, (int)$length);
        $alphabet = self::BASE32_ALPHABET;
        $max = strlen($alphabet) - 1;
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, $max)];
        }

        return $secret;
    }

    /**
     * Build otpauth URI for QR code enrollment.
     *
     * @param string $issuer
     * @param string $accountName
     * @param string $secret
     * @return string
     */
    public static function getProvisioningUri(string $issuer, string $accountName, string $secret): string
    {
        $issuer = trim($issuer);
        $accountName = trim($accountName);
        $label = rawurlencode($issuer . ':' . $accountName);

        return 'otpauth://totp/' . $label
            . '?secret=' . rawurlencode($secret)
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS
            . '&period=' . self::PERIOD;
    }

    /**
     * Verify a user TOTP code.
     *
     * @param string $secret
     * @param string|int $code
     * @param int|string $window
     * @return bool
     */
    public static function verifyCode(string $secret, mixed $code, mixed $window = self::DEFAULT_WINDOW): bool
    {
        $codeStr = preg_replace('/\D+/', '', (string)$code) ?? '';
        if ($codeStr === '' || strlen($codeStr) !== self::DIGITS) {
            return false;
        }

        $counter = (int)floor(time() / self::PERIOD);
        $windowInt = max(0, (int)$window);

        for ($i = -$windowInt; $i <= $windowInt; $i++) {
            $expected = self::getCode($secret, $counter + $i);
            if (hash_equals($expected, $codeStr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate one-time recovery codes and return plain + hashed versions.
     *
     * @param int|string $count
     * @return array{plain: array<string>, hashed: array<string>}
     */
    public static function generateRecoveryCodes(mixed $count = 8): array
    {
        $countInt = max(1, (int)$count);
        $plainCodes = [];
        $hashedCodes = [];

        for ($i = 0; $i < $countInt; $i++) {
            $plain = strtoupper(bin2hex(random_bytes(4)));
            $plainCodes[] = $plain;
            $hashedCodes[] = password_hash($plain, PASSWORD_DEFAULT);
        }

        return [
            'plain' => $plainCodes,
            'hashed' => $hashedCodes,
        ];
    }

    /**
     * Validate and consume a recovery code from stored hashed code list.
     *
     * @param string $inputCode
     * @param array<string> $storedHashedCodes
     * @return array{valid: bool, remaining: array<string>}
     */
    public static function consumeRecoveryCode(string $inputCode, array $storedHashedCodes): array
    {
        $normalizedInput = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $inputCode) ?? '');
        if ($normalizedInput === '') {
            return [
                'valid' => false,
                'remaining' => $storedHashedCodes,
            ];
        }

        foreach ($storedHashedCodes as $index => $hash) {
            if ($hash === '') {
                continue;
            }

            if (password_verify($normalizedInput, $hash)) {
                unset($storedHashedCodes[$index]);

                return [
                    'valid' => true,
                    'remaining' => array_values($storedHashedCodes),
                ];
            }
        }

        return [
            'valid' => false,
            'remaining' => $storedHashedCodes,
        ];
    }

    /**
     * Encrypt TOTP secret for storage.
     *
     * @param string $secret
     * @return string
     */
    public static function encryptSecret(string $secret): string
    {
        if ($secret === '') {
            return '';
        }

        $key = self::getEncryptionKey();
        if ($key === '') {
            return $secret;
        }

        $iv = random_bytes(16);
        $cipherText = openssl_encrypt($secret, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipherText === false) {
            return $secret;
        }

        return 'enc:' . base64_encode($iv . $cipherText);
    }

    /**
     * Decrypt stored TOTP secret.
     *
     * @param string $storedValue
     * @return string
     */
    public static function decryptSecret(string $storedValue): string
    {
        if ($storedValue === '') {
            return '';
        }

        if (strpos($storedValue, 'enc:') !== 0) {
            return $storedValue;
        }

        $encoded = substr($storedValue, 4);
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) <= 16) {
            return '';
        }

        $key = self::getEncryptionKey();
        if ($key === '') {
            return '';
        }

        $iv = substr($raw, 0, 16);
        $cipherText = substr($raw, 16);
        $plain = openssl_decrypt($cipherText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return $plain === false ? '' : $plain;
    }

    /**
     * Generate code based on secret and counter.
     *
     * @param string $secret
     * @param int $counter
     * @return string
     */
    private static function getCode(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);
        if ($key === '') {
            return str_repeat('0', self::DIGITS);
        }

        $binaryCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $part = substr($hash, $offset, 4);
        $value = unpack('N', $part)[1] & 0x7FFFFFFF;
        $mod = 10 ** self::DIGITS;

        return str_pad((string)($value % $mod), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Helper to decode Base32.
     *
     * @param string $secret
     * @return string
     */
    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper(trim($secret));
        $secret = preg_replace('/[^A-Z2-7]/', '', $secret) ?? '';
        if ($secret === '') {
            return '';
        }

        $alphabet = array_flip(str_split(self::BASE32_ALPHABET));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        foreach (str_split($secret) as $char) {
            if (!isset($alphabet[$char])) {
                return '';
            }

            $buffer = ($buffer << 5) | $alphabet[$char];
            $bitsLeft += 5;

            while ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }

    /**
     * Retrieve MFA encryption key.
     *
     * @return string
     */
    private static function getEncryptionKey(): string
    {
        $rawKey = (string)($_ENV['MFA_TOTP_KEY'] ?? getenv('MFA_TOTP_KEY') ?: '');
        if ($rawKey === '') {
            $rawKey = (string)($_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: '');
        }

        if ($rawKey === '') {
            return '';
        }

        return hash('sha256', $rawKey, true);
    }
}
