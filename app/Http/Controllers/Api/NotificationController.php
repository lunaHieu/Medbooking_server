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
            ->orderBy('created_at', 'desc') // Đã sửa lỗi chính tả ở đây
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
}