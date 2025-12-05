<?php

namespace App\Http\Controllers;

use App\Models\AccountCategory;
use Illuminate\Http\Request;

class AccountCategoryController extends Controller
{
    /**
     * دریافت لیست دسته‌بندی‌ها
     */
    public function index()
    {
        $categories = AccountCategory::with('accounts')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                $category->allowed_roles = $category->getAllowedRoles();
                return $category;
            });

        return response()->json($categories);
    }

    /**
     * ایجاد دسته‌بندی جدید
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
        ]);

        $category = AccountCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // اگر roles ارسال شده باشد، آنها را sync می‌کنیم
        if (isset($validated['roles'])) {
            $category->syncRoles($validated['roles']);
        }

        $category->allowed_roles = $category->getAllowedRoles();

        return response()->json([
            'message' => 'دسته‌بندی با موفقیت ایجاد شد.',
            'data' => $category
        ], 201);
    }

    /**
     * به‌روزرسانی دسته‌بندی
     */
    public function update(Request $request, $id)
    {
        $category = AccountCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
        ]);

        $category->update([
            'name' => $validated['name'] ?? $category->name,
            'description' => $validated['description'] ?? $category->description,
        ]);

        // اگر roles ارسال شده باشد، آنها را sync می‌کنیم
        if (isset($validated['roles'])) {
            $category->syncRoles($validated['roles']);
        }

        $category->allowed_roles = $category->getAllowedRoles();

        return response()->json([
            'message' => 'دسته‌بندی با موفقیت به‌روزرسانی شد.',
            'data' => $category
        ]);
    }

    /**
     * حذف دسته‌بندی
     */
    public function destroy($id)
    {
        $category = AccountCategory::findOrFail($id);

        // بررسی اینکه آیا حساب‌هایی با این دسته‌بندی وجود دارد
        if ($category->accounts()->count() > 0) {
            return response()->json([
                'message' => 'نمی‌توان این دسته‌بندی را حذف کرد زیرا حساب‌هایی با این دسته‌بندی وجود دارد.'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'دسته‌بندی با موفقیت حذف شد.'
        ]);
    }

    /**
     * Sync کردن نقش‌های دسته‌بندی
     */
    public function syncRoles(Request $request, $id)
    {
        $category = AccountCategory::findOrFail($id);

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string',
        ]);

        $category->syncRoles($validated['roles']);

        $category->allowed_roles = $category->getAllowedRoles();

        return response()->json([
            'message' => 'نقش‌های دسته‌بندی با موفقیت به‌روزرسانی شد.',
            'data' => $category
        ]);
    }
}


