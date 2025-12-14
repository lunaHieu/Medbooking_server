<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // ❗ BẮT BUỘC dùng auth()
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        // So sánh đúng field Role (hoa R)
        if (!in_array($user->Role, $roles)) {
            return response()->json([
                'message' => 'Forbidden',
                'current_role' => $user->Role,
                'allowed_roles' => $roles
            ], 403);
        }

        return $next($request);
    }
}
