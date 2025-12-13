<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Specialty;
use Illuminate\Support\Facades\Storage; // <-- Quan trọng: Để xử lý ảnh

class SpecialtyController extends Controller
{
    /**
     * Admin Thêm Chuyên khoa (POST)
     */
    public function store(Request $request)
    {
        $request->validate([
            'SpecialtyName' => 'required|string|max:255|unique:specialties',
            'Description' => 'nullable|string',
            'imageURL' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('imageURL')) {
            $path = $request->file('imageURL')->store('uploads/specialties', 'public');
        }

        $specialty = new Specialty();
        $specialty->SpecialtyName = $request->SpecialtyName;
        $specialty->Description = $request->Description;
        $specialty->imageURL = $path ? Storage::url($path) : null;
        $specialty->save();

        return response()->json(['message' => 'Tạo chuyên khoa thành công!', 'specialty' => $specialty], 201);
    }

    /**
     * Admin Sửa Chuyên khoa (PUT)
     */
    public function update(Request $request, $id)
    {
        $specialty = Specialty::findOrFail($id);

        $request->validate([
            'SpecialtyName' => 'required|string|max:255|unique:specialties,SpecialtyName,' . $id . ',SpecialtyID',
            'Description' => 'nullable|string',
            'imageURL' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if ($request->hasFile('imageURL')) {
            // Xóa ảnh cũ nếu có
            if ($specialty->imageURL) {
                $oldPath = str_replace('/storage/', '', $specialty->imageURL);
                Storage::disk('public')->delete($oldPath);
            }
            // Lưu ảnh mới
            $path = $request->file('imageURL')->store('uploads/specialties', 'public');
            $specialty->imageURL = Storage::url($path);
        }

        $specialty->SpecialtyName = $request->SpecialtyName;
        $specialty->Description = $request->Description;
        $specialty->save();

        return response()->json(['message' => 'Cập nhật thành công!', 'specialty' => $specialty], 200);
    }

    /**
     * Admin Xóa Chuyên khoa (DELETE)
     */
    public function destroy($id)
    {
        $specialty = Specialty::findOrFail($id);

        if ($specialty->doctors()->count() > 0) {
            return response()->json(['message' => 'Không thể xoá, chuyên khoa này đang có bác sĩ.'], 422);
        }

        if ($specialty->imageURL) {
            $oldPath = str_replace('/storage/', '', $specialty->imageURL);
            Storage::disk('public')->delete($oldPath);
        }

        $specialty->delete();
        return response()->json(['message' => 'Xoá thành công.'], 200);
    }
}