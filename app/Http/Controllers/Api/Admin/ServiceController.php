<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    /**
     * Admin Lấy danh sách Dịch vụ (Có tìm kiếm & kèm tên Chuyên khoa)
     * GET /api/admin/services
     */
    public function index(Request $request)
    {
        $query = Service::with('specialty');

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('ServiceName', 'like', '%' . $searchTerm . '%')
                    ->orWhere('Description', 'like', '%' . $searchTerm . '%');
            });
        }

        $services = $query->orderBy('ServiceID', 'desc')->get();

        return response()->json($services, 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Admin Xem chi tiết 1 Dịch vụ
     * GET /api/admin/services/{id}
     */
    public function show($id)
    {
        $service = Service::with('specialty')->findOrFail($id);
        return response()->json($service, 200, [], JSON_UNESCAPED_UNICODE);
    }
    /**
     * Admin Thêm Dịch vụ
     * POST /api/admin/services
     */
    public function store(Request $request)
    {
        $request->validate([
            'ServiceName' => 'required|string|max:255|unique:services',
            'SpecialtyID' => 'required|integer|exists:specialties,SpecialtyID',
            'Description' => 'nullable|string',
            'EstimatedDuration' => 'required|integer|min:0',
            'Price' => 'required|numeric|min:0',
            'imageURL' => 'nullable|image|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('imageURL')) {
            $path = $request->file('imageURL')->store('uploads/services', 'public');
        }

        $service = new Service();
        $service->ServiceName = $request->ServiceName;
        $service->SpecialtyID = $request->SpecialtyID;
        $service->Description = $request->Description;
        $service->EstimatedDuration = $request->EstimatedDuration;
        $service->Price = $request->Price;
        $service->imageURL = $path ? Storage::url($path) : null;
        $service->save();

        return response()->json(['message' => 'Tạo dịch vụ thành công!', 'service' => $service], 201);
    }

    /**
     * Admin Sửa Dịch vụ
     * PUT /api/admin/services/{id}
     */
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'ServiceName' => 'required|string|max:255|unique:services,ServiceName,' . $id . ',ServiceID',
            'SpecialtyID' => 'required|integer|exists:specialties,SpecialtyID',
            'Description' => 'nullable|string',
            'EstimatedDuration' => 'required|integer|min:0',
            'Price' => 'required|numeric|min:0',
            'imageURL' => 'nullable|image|max:10240',
        ]);

        if ($request->hasFile('imageURL')) {
            if ($service->imageURL) {
                $oldPath = str_replace('/storage/', '', $service->imageURL);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('imageURL')->store('uploads/services', 'public');
            $service->imageURL = Storage::url($path);
        }

        $service->ServiceName = $request->ServiceName;
        $service->SpecialtyID = $request->SpecialtyID;
        $service->Description = $request->Description;
        $service->EstimatedDuration = $request->EstimatedDuration;
        $service->Price = $request->Price;
        $service->save();

        return response()->json(['message' => 'Cập nhật dịch vụ thành công!', 'service' => $service], 200);
    }

    /**
     * Admin Xóa Dịch vụ
     * DELETE /api/admin/services/{id}
     */
    public function destroy($id)
    {
        $service = Service::findOrFail($id);

        if ($service->appointments()->count() > 0) {
            return response()->json(['message' => 'Không thể xoá dịch vụ này, đang có lịch hẹn.'], 422);
        }

        if ($service->imageURL) {
            $oldPath = str_replace('/storage/', '', $service->imageURL);
            Storage::disk('public')->delete($oldPath);
        }

        $service->delete();
        return response()->json(['message' => 'Xoá dịch vụ thành công.'], 200);
    }
}