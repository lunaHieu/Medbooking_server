<?php
// Tên file: app/Http/Controllers/Api/Admin/NotificationController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
        //Gửi all không cần where
        $query = User::query();
        if ($request->TargetGroup === 'patients') {
            $query->where('Role', 'BenhNhan');
        } elseif ($request->TargetGroup === 'doctors') {
            $query->where('Role', 'BacSi');
        } elseif ($request->TargetGroup === 'staff') {
            $query->where('Role', 'NhanVien');
        }

        $users = $query->get();

        // Gửi thông báo (Tạo bản ghi trong DB)
        // Transaction để giảm thiểu khả năng bị treo DB
        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                $notification = new Notification();
                $notification->UserID = $user->UserID;
                $notification->Title = $request->Title;
                $notification->Content = $request->Content;
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
    /**
     * Xóa một thông báo cụ thể
     */
    public function destroy($id)
}