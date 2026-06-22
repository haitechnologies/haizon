<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\MiddlewareInterface;

class Kernel
{
    private array $middleware = [];
    private array $routes = [];

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function registerRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function handle(Request $request): Response
    {
        $response = new Response();

        $stack = $this->middleware;
        $next = function (Request $req): Response {
            foreach ($this->routes as $route) {
                if ($route['method'] === $req->getMethod()) {
                    $params = $this->matchPath($route['path'], $req->getUri());
                    if ($params !== null) {
                        return call_user_func($route['handler'], $req, ...$params);
                    }
                }
            }
            return new Response('Not Found', 404);
        };

        while ($mw = array_pop($stack)) {
            $mwNext = $next;
            $next = fn(Request $req) => $mw->process($req, $mwNext);
        }

        return $next($request);
    }

    private function matchPath(string $pattern, string $uri): ?array
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (preg_match($regex, $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }
        return null;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
