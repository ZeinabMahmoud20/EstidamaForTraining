<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows the request when the authenticated user holds ANY of the given permissions.
 * (Laravel's "can" middleware treats extra arguments as Gate models, not as alternatives.)
 */
class RequireAnyPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        abort_unless($request->user()?->canAny($permissions), 403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');

        return $next($request);
    }
}
