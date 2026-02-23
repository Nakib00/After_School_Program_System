<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponse;

/**
 * Class RoleMiddleware
 * Handles role-based authorization for API routes.
 */
class RoleMiddleware
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @param string ...$roles
     * @return Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('Unauthorized access.', 401);
        }

        if (!in_array($user->role, $roles)) {
            return $this->error('Forbidden. Your role (' . $user->role . ') does not have access to this resource.', 403);
        }

        return $next($request);
    }
}
