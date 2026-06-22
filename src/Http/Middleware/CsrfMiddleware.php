<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        if ($request->isPost()) {
            $token = $request->post('csrf_token', '');
            if (!function_exists('validate_csrf_token') || !validate_csrf_token($token)) {
                return new Response('Invalid CSRF token', 403);
            }
        }

        return $next($request);
    }
}
