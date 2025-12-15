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
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Lấy user đã đăng nhập (đã qua 'auth:sanctum')
        $user = Auth::user();

        // 2. Lặp qua các vai trò được phép mà chúng ta định nghĩa ở Route
        foreach ($roles as $role) {
            // 3. Nếu user CÓ vai trò này
            if ($user->Role == $role) {
                // -> Cho phép đi tiếp
                return $next($request);
            }
        }

        // 4. Nếu user không có vai trò nào được phép
        // -> Trả về lỗi 403 Forbidden (Cấm)
        return response()->json(['message' => 'Bạn không có quyền truy cập chức năng này.'], 403);
    }
}