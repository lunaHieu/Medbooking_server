<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    /**
     * Lấy danh sách Dịch vụ (Hỗ trợ tìm kiếm ?search=...)
     * GET /api/services
     */
    public function index(Request $request)
    {

        $query = Service::query()->with('specialty');

        if ($request->has('specialty_id') && $request->input('specialty_id') != null) {
            $query->where('SpecialtyID', $request->input('specialty_id'));
        }

        if ($request->has('search') && $request->input('search') != null) {
            $searchTerm = $request->input('search');
            $query->where('ServiceName', 'like', '%' . $searchTerm . '%');
        }

        $query->orderBy('ServiceID', 'desc');

        $services = $query->get();

        //Trả về JSON (Giữ nguyên JSON_UNESCAPED_UNICODE để tiếng Việt không bị lỗi font)
        return response()->json($services, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Xem chi tiết 1 dịch vụ
     * GET /api/services/{id}
     */
    public function show($id)
    {
        $service = Service::with('specialty')->findOrFail($id);
        return response()->json($service, 200, [], JSON_UNESCAPED_UNICODE);
    }
}