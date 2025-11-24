<?php
// Tên file: app/Http/Controllers/Api/Admin/NotificationController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Lấy danh sách tất cả thông báo đã gửi.
     * GET /api/admin/notifications
     */
    public function index()
    {
        $notifications = Notification::with('user') // Lấy tên người nhận
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Gửi thông báo mới (Hàng loạt).
     * POST /api/admin/notifications/send
     */
    public function send(Request $request)
    {
        $request->validate([
            'Title' => 'required|string|max:255', // (Lưu ý: DB cần cột Title nếu chưa có)
            'Content' => 'required|string',
            'TargetGroup' => 'required|in:all,patients,doctors,staff',
            'Channel' => 'required|in:in_app,email', // Hiện tại chỉ support in_app
        ]);

        // 1. Xác định nhóm người nhận
        $query = User::query();
        if ($request->TargetGroup === 'patients') {
            $query->where('Role', 'BenhNhan');
        } elseif ($request->TargetGroup === 'doctors') {
            $query->where('Role', 'BacSi');
        } elseif ($request->TargetGroup === 'staff') {
            $query->where('Role', 'NhanVien');
        }
        // 'all' thì không cần where

        $users = $query->get();

        // 2. Gửi thông báo (Tạo bản ghi trong DB)
        // (Vì gửi hàng loạt có thể lâu, nên dùng Transaction)
        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                $notification = new Notification();
                $notification->UserID = $user->UserID;
                // $notification->Title = $request->Title; // (Nếu DB chưa có Title thì bỏ qua hoặc thêm cột)
                $notification->Content = $request->Content; // Nội dung: "Tiêu đề: Nội dung"
                $notification->NotificationType = 'SystemAlert';
                $notification->Channel = $request->Channel;
                $notification->Status = 'Sent';
                $notification->save();
            }
            DB::commit();
            
            return response()->json([
                'message' => 'Đã gửi thông báo thành công cho ' . $users->count() . ' người dùng.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi gửi thông báo.', 'error' => $e->getMessage()], 500);
        }
    }
}