<?php

declare(strict_types=1);

namespace App\Http;

class Request
{
    private array $query;
    private array $body;
    private array $files;
    private array $server;
    private array $cookies;

    public function __construct(
        array $query = [],
        array $body = [],
        array $files = [],
        array $server = [],
        array $cookies = []
    ) {
        $this->query = $query;
        $this->body = $body;
        $this->files = $files;
        $this->server = $server;
        $this->cookies = $cookies;
    }

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_FILES, $_SERVER, $_COOKIE);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return ($this->server['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getUri(): string
    {
        return parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int)($this->get($key, $default));
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);
        return is_string($value) ? trim($value) : $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function getServer(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function getFile(string $key): ?array
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE
            ? $this->files[$key]
            : null;
    }

    public function getCookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function getParsedBody(): array
    {
        return $this->body;
    }

    public function getQueryParams(): array
    {
        return $this->query;
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function isAjax(): bool
    {
        return strtolower($this->getServer('HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest';
    }

    public function getClientIp(): string
    {
        return $this->getServer('REMOTE_ADDR', '0.0.0.0');
    }

    public function getArrayItem(string $key, int $index, mixed $default = null): mixed
    {
        $value = $this->post($key, []);
        if (is_array($value) && isset($value[$index])) {
            return $value[$index];
        }
        return $default;
    }
}
