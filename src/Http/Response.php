<?php

declare(strict_types=1);

namespace App\Http;

class Response
{
    private int $statusCode;
    private array $headers;
    private mixed $body;

    public function __construct(mixed $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public static function json(mixed $data, int $statusCode = 200): self
    {
        return new self(json_encode($data), $statusCode, ['Content-Type' => 'application/json']);
    }

    public static function html(string $html, int $statusCode = 200): self
    {
        return new self($html, $statusCode, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        if ($this->body !== '' && $this->body !== null) {
            echo $this->body;
        }
        exit;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function withStatus(int $code): self
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        return $clone;
    }

    public function getStatusCode(): int { return $this->statusCode; }
    public function getBody(): mixed { return $this->body; }
    public function getHeaders(): array { return $this->headers; }
}
