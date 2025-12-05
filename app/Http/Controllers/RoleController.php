<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * دریافت لیست تمام role ها
     */
    public function index()
    {
        $roles = Role::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    /**
     * دریافت یک role خاص
     */
    public function show($id)
    {
        $role = Role::findOrFail($id);
        return response()->json($role);
    }

    /**
     * ایجاد role جدید (فقط برای Admin)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز. فقط مدیران می‌توانند role ایجاد کنند.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->input('is_active', true);

        $role = Role::create($validated);

        return response()->json([
            'message' => 'Role با موفقیت ایجاد شد.',
            'data' => $role
        ], 201);
    }

    /**
     * به‌روزرسانی role (فقط برای Admin)
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز.'
            ], 403);
        }

        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
            'display_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $role->update($validated);

        return response()->json([
            'message' => 'Role با موفقیت به‌روزرسانی شد.',
            'data' => $role
        ]);
    }

    /**
     * حذف role (فقط برای Admin)
     * توجه: اگر role به کاربران متصل باشد، نمی‌توان آن را حذف کرد
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز.'
            ], 403);
        }

        $role = Role::findOrFail($id);

        // بررسی اینکه آیا کاربرانی با این role وجود دارند
        if ($role->users()->count() > 0) {
            return response()->json([
                'message' => 'نمی‌توان این role را حذف کرد زیرا به کاربران متصل است. لطفاً ابتدا role کاربران را تغییر دهید.'
            ], 422);
        }

        // به جای حذف، role را غیرفعال می‌کنیم
        $role->update(['is_active' => false]);

        return response()->json([
            'message' => 'Role با موفقیت غیرفعال شد.',
        ]);
    }
}


