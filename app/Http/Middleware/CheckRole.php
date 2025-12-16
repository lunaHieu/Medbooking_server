<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // 1. Nếu chưa đăng nhập -> Chặn luôn
        if (!$user) {
            return response()->json(['message' => 'Unauthorized: Bạn chưa đăng nhập.'], 401);
        }

        // 2. Lấy Role của User từ DB
        // Ép kiểu (string) để tránh lỗi nếu Role bị null trong DB (PHP 8.1+ rất kỵ null vào hàm string)
        $userRole = strtolower((string)($user->Role ?? '')); 

        // 3. Chuẩn hóa danh sách Role được phép (Allowed Roles)
        // Xử lý cả trường hợp middleware viết liền 'Admin,Staff' hoặc tách rời
        $allowedRoles = [];
        foreach ($roles as $role) {
            // Tách dấu phẩy nếu có (đề phòng)
            $parts = explode(',', $role);
            foreach ($parts as $part) {
                // Xóa khoảng trắng và chuyển về chữ thường
                $allowedRoles[] = strtolower(trim($part));
            }
        }

        // 4. Kiểm tra: Nếu Role của user nằm trong danh sách cho phép -> OK
        if (in_array($userRole, $allowedRoles)) {
            return $next($request);
        }

        // 5. Nếu không khớp -> Lỗi 403
        // Debug nhẹ để bạn biết tại sao lỗi (Xóa dòng debug_info khi chạy thật)
        return response()->json([
            'message' => 'Forbidden: Tài khoản không đủ quyền truy cập.',
            'debug_info' => [
                'role_cua_ban' => $userRole, // Xem nó đang đọc là gì
                'role_yeu_cau' => $allowedRoles // Xem nó yêu cầu gì
            ]
        ], 403);
    }
}