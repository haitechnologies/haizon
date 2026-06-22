<?php

declare(strict_types=1);

namespace App\Service;

/**
 * ZKTecoClient — Lightweight ZK protocol implementation over TCP sockets.
 *
 * Connects to ZKTeco biometric devices (e.g. BioPro SA30) on port 4370,
 * authenticates, and retrieves attendance logs. No external dependencies.
 *
 * Protocol reference: open-source ZK implementations
 */
class ZKTecoClient
{
    private const DEFAULT_PORT = 4370;
    private const CONNECT_TIMEOUT = 5;
    private const READ_TIMEOUT = 10;

    // ZK command codes
    private const CMD_CONNECT = 1000;
    private const CMD_EXIT = 1101;
    private const CMD_ENABLEDEVICE = 1102;
    private const CMD_DISABLEDEVICE = 1103;
    private const CMD_GET_TIME = 1104;
    private const CMD_SET_TIME = 1105;
    private const CMD_VERIFY = 1200;
    private const CMD_ATTLOG_RRQ = 1500;
    private const CMD_CLEAR_ATTLOG = 1501;
    private const CMD_USERS_RRQ = 1504;
    private const CMD_GET_DEVICE_INFO = 1522;

    private const USHR_SIZE = 8;

    private ?\Socket $socket = null;
    private int $sessionId = 0;
    private int $replyId = 0;

    private string $ip = '';
    private int $port = 4370;
    private string $password = '0';

    private array $lastError = [];

    public function __construct()
    {
    }

    /**
     * Connect to a ZKTeco device.
     */
    public function connect(string $ip, int $port = self::DEFAULT_PORT, string $password = '0'): bool
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->password = $password;

        $errno = 0;
        $errstr = '';
        $resource = @fsockopen($ip, $port, $errno, $errstr, self::CONNECT_TIMEOUT);
        if ($resource === false) {
            $this->lastError = [
                'code' => $errno,
                'message' => "Connection failed: $errstr",
            ];
            return false;
        }

        $this->socket = $resource;
        stream_set_timeout($this->socket, self::READ_TIMEOUT);

        // Read the USHR (user header) packet
        $ushr = @fread($this->socket, self::USHR_SIZE);
        if ($ushr === false || strlen($ushr) < self::USHR_SIZE) {
            $this->lastError = ['code' => -1, 'message' => 'Failed to read USHR packet'];
            $this->disconnect();
            return false;
        }

        // First connect command
        $this->sendCommand(self::CMD_CONNECT, '');
        $response = $this->readResponse();
        if ($response === null || $response['returnCode'] !== 0) {
            $this->lastError = ['code' => -2, 'message' => 'Connect command failed'];
            $this->disconnect();
            return false;
        }

        // Extract session ID from connect response
        $this->sessionId = $response['sessionId'];

        // Verify device password (send password command)
        $passwordData = str_pad(substr($this->password, 0, 8), 8, "\0");
        $this->sendCommand(self::CMD_VERIFY, $passwordData);
        $verifyResponse = $this->readResponse();

        if ($verifyResponse === null) {
            $this->lastError = ['code' => -3, 'message' => 'No response to password verification'];
            $this->disconnect();
            return false;
        }

        if ($verifyResponse['returnCode'] !== 0 && $verifyResponse['returnCode'] !== 1) {
            $this->lastError = ['code' => -4, 'message' => 'Password verification failed (wrong password?)'];
            $this->disconnect();
            return false;
        }

        return true;
    }

    /**
     * Disconnect from device.
     */
    public function disconnect(): void
    {
        try {
            $this->sendCommand(self::CMD_EXIT, '');
        } catch (\Throwable $e) {
            // Best effort
        }

        if ($this->socket !== null) {
            @fclose($this->socket);
            $this->socket = null;
        }
        $this->sessionId = 0;
        $this->replyId = 0;
    }

    /**
     * Disable device (prevents real-time events during data transfer).
     */
    public function disableDevice(): bool
    {
        $this->sendCommand(self::CMD_DISABLEDEVICE, '');
        $response = $this->readResponse();
        return $response !== null && $response['returnCode'] === 0;
    }

    /**
     * Enable device after data transfer.
     */
    public function enableDevice(): bool
    {
        $this->sendCommand(self::CMD_ENABLEDEVICE, '');
        $response = $this->readResponse();
        return $response !== null && $response['returnCode'] === 0;
    }

    /**
     * Get all attendance log records from the device.
     *
     * Returns array of arrays with keys: user_id, timestamp, type, verification_mode, status
     * timestamp is returned as a formatted datetime string.
     */
    public function getAttendance(): array
    {
        $this->sendCommand(self::CMD_ATTLOG_RRQ, '');
        $response = $this->readResponse();

        if ($response === null || $response['returnCode'] !== 0) {
            return [];
        }

        $data = $response['data'] ?? '';

        // Parse attendance records
        // Each record is 8 bytes: 2 bytes user_id, 4 bytes DOS timestamp, 1 byte status, 1 byte verification_mode
        return $this->parseAttendanceRecords($data);
    }

    /**
     * Get device information (serial, firmware, etc.).
     */
    public function getDeviceInfo(): array
    {
        $this->sendCommand(self::CMD_GET_DEVICE_INFO, '');
        $response = $this->readResponse();

        if ($response === null || $response['returnCode'] !== 0) {
            return [];
        }

        $data = $response['data'] ?? '';
        $info = [];

        // Parse device info string (newline separated key:value pairs)
        foreach (explode("\n", $data) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $info[trim($key)] = trim($value);
            }
        }

        return $info;
    }

    /**
     * Get the last error that occurred.
     */
    public function getLastError(): array
    {
        return $this->lastError;
    }

    /**
     * Check if currently connected.
     */
    public function isConnected(): bool
    {
        return $this->socket !== null;
    }

    // ========================================================================
    //  Protocol internals
    // ========================================================================

    /**
     * Build and send a ZK command packet.
     */
    private function sendCommand(int $command, string $data): void
    {
        if ($this->socket === null) {
            throw new \RuntimeException('Not connected');
        }

        $this->replyId++;

        // Build packet according to ZK protocol:
        // [2 bytes checksum] [2 bytes command_id] [2 bytes session_id] [2 bytes reply_id] [data...]
        $commandCode = $command + 2000; // Request command base offset
        $header = pack('vvvv', 0, $commandCode, $this->sessionId, $this->replyId);
        $packet = $header . $data;

        // Calculate checksum
        $checksum = $this->calculateChecksum($packet);
        $packet[0] = chr($checksum & 0xFF);
        $packet[1] = chr(($checksum >> 8) & 0xFF);

        $length = strlen($packet);
        $written = @fwrite($this->socket, $packet, $length);

        if ($written === false || $written !== $length) {
            throw new \RuntimeException('Failed to send command');
        }
    }

    /**
     * Read response from device and parse it.
     */
    private function readResponse(): ?array
    {
        if ($this->socket === null) {
            return null;
        }

        // Read 8-byte header
        $header = @fread($this->socket, 8);
        if ($header === false || strlen($header) < 8) {
            $this->lastError = ['code' => -10, 'message' => 'Failed to read response header'];
            return null;
        }

        $parsed = unpack('vchecksum/vcommand/vsessionId/vreplyId', $header);
        if ($parsed === false) {
            return null;
        }

        $command = $parsed['command'];
        $responseCode = $command & 0xFF; // Return code is in low byte of command
        $sessionId = $parsed['sessionId'];

        // Update session ID from response
        if ($sessionId > 0) {
            $this->sessionId = $sessionId;
        }

        // Determine data size from command (top byte indicates size flag)
        // For attendance records, need to read remaining data based on content
        $data = '';

        // Try to read more data - ZK sends data with size in the header
        // The actual data length is determined by the command
        $remaining = @fread($this->socket, 1024 * 64); // Max 64KB response
        if ($remaining !== false && strlen($remaining) > 0) {
            $data = $remaining;
        }

        return [
            'command' => $command,
            'returnCode' => $responseCode,
            'sessionId' => $sessionId,
            'replyId' => $parsed['replyId'],
            'data' => $data,
        ];
    }

    /**
     * Calculate ZK packet checksum.
     */
    private function calculateChecksum(string $packet): int
    {
        $sum = 0;
        $length = strlen($packet);
        for ($i = 0; $i < $length; $i++) {
            $sum += ord($packet[$i]);
        }
        return $sum & 0xFFFF;
    }

    /**
     * Parse binary attendance record data from device.
     *
     * Record format:
     * - Byte 0-1: User ID (uint16 LE)
     * - Byte 2-3: Reserved / Status
     * - Byte 4-7: DOS timestamp (uint32 LE)
     * - Byte 8:   Verification mode
     * - Byte 9:   Status / Work code
     */
    private function parseAttendanceRecords(string $data): array
    {
        $records = [];
        $recordSize = 8;
        $len = strlen($data);

        if ($len < $recordSize) {
            return [];
        }

        // Often ZK wraps data with a header - skip first 4 bytes if present
        $offset = 0;
        if ($len % $recordSize !== 0) {
            // Might have a sub-header, try to find data start
            $mod = $len % $recordSize;
            // Common: 4-byte sub-header
            if ($mod === 4) {
                $offset = 4;
            }
        }

        for ($i = $offset; $i + $recordSize <= $len; $i += $recordSize) {
            $record = substr($data, $i, $recordSize);
            if (strlen($record) < $recordSize) {
                break;
            }

            $parsed = unpack('vuserId/vstatus/Vtimestamp/Cverification/CworkCode', $record);
            if ($parsed === false) {
                continue;
            }

            $userId = $parsed['userId'];
            $rawTimestamp = $parsed['timestamp'];

            // Convert DOS/Unix timestamp to datetime
            // ZK uses 32-bit uint seconds since 1970-01-01 or a custom offset
            $dateStr = $this->parseZkTimestamp($rawTimestamp);

            $records[] = [
                'user_id' => (string)$userId,
                'timestamp' => $dateStr,
                'type' => $parsed['status'] & 0xFF,
                'verification_mode' => $parsed['verification'] & 0xFF,
                'status' => $parsed['workCode'] & 0xFF,
            ];
        }

        return $records;
    }

    /**
     * Parse ZKTeco timestamp.
     *
     * ZK typically stores seconds since 2000-01-01 or standard Unix timestamp.
     * We detect and handle both.
     */
    private function parseZkTimestamp(int $timestamp): string
    {
        // ZK timestamp is seconds from 2000-01-01 if value is in a reasonable range
        $unixTs = $timestamp;

        // If timestamp is less than ~20 years from 2000, it's relative to 2000
        if ($timestamp < 946684800 && $timestamp > 10000) {
            // Relative to year 2000
            $unixTs = $timestamp + 946684800;
        } elseif ($timestamp < 10000) {
            // DOS date format: days since some epoch
            // Fall back to current date
            return date('Y-m-d H:i:s');
        }

        // Validate range (2000-01-01 to 2100-01-01)
        if ($unixTs < 946684800 || $unixTs > 4102444800) {
            return date('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', $unixTs);
    }
}
