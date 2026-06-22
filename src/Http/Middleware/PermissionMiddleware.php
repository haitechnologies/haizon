<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

class PermissionMiddleware implements MiddlewareInterface
{
    private string $moduleName;
    private string $action;

    public function __construct(string $moduleName, string $action = 'view')
    {
        $this->moduleName = $moduleName;
        $this->action = $action;
    }

    public function process(Request $request, callable $next): Response
    {
        if (!function_exists('granted_') || !granted_($this->action, $this->moduleName)) {
            return new Response('Forbidden', 403);
        }

        return $next($request);
    }
}
