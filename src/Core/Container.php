<?php

declare(strict_types=1);

namespace App\Core;

use Psr\Container\ContainerInterface;
use App\Exception\NotFoundException;
use RuntimeException;

/**
 * Standard Dependency Injection Container (PSR-11)
 *
 * Manages service instantiation and lifecycle in the Haipulse application.
 */
class Container implements ContainerInterface
{
    /** @var Container|null Singleton instance for legacy/global accessor */
    private static ?Container $instance = null;

    /** @var array<string, mixed> Cached service instances */
    private array $instances = [];

    /** @var array<string, callable> Service definitions */
    private array $definitions = [];

    /**
     * Get the global singleton container instance
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set the global singleton container instance
     */
    public static function setInstance(?Container $container): void
    {
        self::$instance = $container;
    }

    /**
     * Bind a service instance directly
     *
     * @param string $id
     * @param mixed $instance
     */
    public function set(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Register a lazy service factory definition
     *
     * @param string $id
     * @param callable $definition Callback accepting ContainerInterface and returning the service
     */
    public function register(string $id, callable $definition): void
    {
        $this->definitions[$id] = $definition;
        unset($this->instances[$id]); // Invalidate cached instance if definition changes
    }

    /**
     * Get a service by ID
     *
     * @param string $id
     * @return mixed
     * @throws RuntimeException if service cannot be resolved
     */
    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            try {
                $service = $this->definitions[$id]($this);
                $this->instances[$id] = $service;
                return $service;
            } catch (\Throwable $e) {
                throw new RuntimeException(
                    "Container: Failed to resolve service '{$id}': " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        throw new class ("Service '{$id}' not found in the container.") extends RuntimeException implements \Psr\Container\NotFoundExceptionInterface {
        };
    }

    /**
     * Check if service is registered or bound
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->definitions[$id]);
    }
}
