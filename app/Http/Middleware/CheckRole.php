<?php
// Tên file: app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth; // <-- Thêm dòng này

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  ...$roles (Các vai trò được phép, ví dụ: 'BacSi', 'Admin')
     */
    public function handle($request, Closure $next, ...$roles)
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    
    // Sửa từ 'role' thành 'Role' để match với database
    $userRole = strtolower($user->Role);
    $allowed = array_map('strtolower', $roles);
    
    if (!in_array($userRole, $allowed)) {
        return response()->json(['message' => 'Forbidden'], 403);
    }
    
    return $next($request);
}
}