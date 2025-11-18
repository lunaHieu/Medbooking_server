<?php
// Tên file: app/Http/Controllers/Api/Admin/FeedbackController.php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback; // <-- Thêm

class FeedbackController extends Controller
{
    /**
     * Lấy tất cả Phản hồi (mới nhất lên đầu).
     * Chạy khi gọi GET /api/admin/feedbacks hoặc /api/staff/feedbacks
     */
    public function index()
    {
        $feedbacks = Feedback::with('patient') // Lấy tên Bệnh nhân
                                  ->orderBy('created_at', 'desc')
                                  ->get();
        
        return response()->json($feedbacks, 200, [], JSON_UNESCAPED_UNICODE);
    }
    
    // (Chúng ta có thể thêm hàm store, update, destroy ở đây sau)
}