<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Lấy thông báo của chính mình
    public function getMyNotifications()
    {
        $userId = Auth::id();

        $notifications = Notification::where('UserID', $userId)
            ->orderBy('created_at', 'desc') 
            ->take(50) // Giới hạn 50 tin mới nhất
            ->get();

        return response()->json($notifications);
    }

    // Đánh dấu đã đọc
    public function markAsRead($id)
    {
        $notification = Notification::where('UserID', Auth::id())
            ->where('NotificationID', $id)
            ->first();

        if ($notification) {
            $notification->Status = 'Read';
            $notification->save();
        }

        return response()->json(['message' => 'Đã đánh dấu đã đọc']);
    }
    public function destroy($id)
    {
        $userId = Auth::id();
        $notification = Notification::where('NotificationID', $id)
            ->where('UserID', $userId)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Không tìm thấy hoặc bạn không có quyền xóa'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Đã xóa thông báo'], 200);
    }
    public function destroyAll()
    {
        Notification::query()->delete();
        return response()->json(['message' => 'Đã xóa toàn bộ lịch sử thông báo']);
    }
}