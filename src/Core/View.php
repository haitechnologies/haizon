<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    private string $basePath;
    private array $shared = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function render(string $template, array $data = []): string
    {
        extract(array_merge($this->shared, $data), EXTR_SKIP);
        ob_start();
        include $this->basePath . '/' . ltrim($template, '/');
        return (string) ob_get_clean();
    }
}
