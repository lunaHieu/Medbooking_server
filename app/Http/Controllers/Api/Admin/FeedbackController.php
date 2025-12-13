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
        // Eager load 'user' (người gửi) và 'appointment.doctor.user' (bác sĩ được nhận xét)
        $feedbacks = Feedback::with(['user', 'appointment.doctor.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $feedbacks->map(function ($fb) {

            $reviewerName = $fb->user ? $fb->user->FullName : 'Ẩn danh';
            $reviewerAvatar = $fb->user ? $fb->user->avatar_url : null;

            $targetName = 'Hệ thống';
            $type = 'System';
            //Phải đặt lịch mới có thể đánh giá bác sĩ
            if ($fb->TargetType === 'Doctor' || $fb->AppointmentID) {
                $type = 'Doctor';
                // Cố gắng lấy tên bác sĩ
                if ($fb->appointment && $fb->appointment->doctor && $fb->appointment->doctor->user) {
                    $targetName = "BS. " . $fb->appointment->doctor->user->FullName;
                } else {
                    $targetName = "Bác sĩ (Đã ẩn)";
                }
            }

            return [
                'FeedbackID' => $fb->FeedbackID,
                'Rating' => $fb->Rating,
                'Comment' => $fb->Comment,
                'CreatedAt' => $fb->created_at->format('d/m/Y H:i'),

                // Thông tin người gửi (Đã hiện cho cả Hệ thống & Bác sĩ)
                'ReviewerName' => $reviewerName,
                'ReviewerAvatar' => $reviewerAvatar,

                'TargetName' => $targetName,
                'Type' => $type,
            ];
        });

        return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function getTopFeedbacks()
    {
        $feedbacks = Feedback::with('appointment.patient')
            ->where('Rating', 5)
            ->whereNotNull('Comment')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
        $data = $feedbacks->map(function ($fb) {
            $patient = $fb->appointment->patient;
            return [
                'FeedbackID' => $fb->FeedbackID,
                'Rating' => $fb->Rating,
                'Comment' => $fb->Comment,
                'FullName' => $patient ? $patient->FullName : 'Ẩn danh',
                'avatar_url' => $patient ? $patient->avatar_url : null,
            ];
        });
        return response()->json($data);
    }
    /**
     * 
     */
    // (Chúng ta có thể thêm hàm store, update, destroy ở đây sau)
}