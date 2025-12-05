<?php

namespace App\Http\Controllers;

use App\Models\AccountTag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * دریافت لیست همه تگ‌ها
     */
    public function index()
    {
        $tags = AccountTag::orderBy('name')->get();
        return response()->json($tags);
    }

    /**
     * ایجاد تگ جدید
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:account_tags,name',
            'color' => 'nullable|string|max:7',
        ]);

        $tag = AccountTag::create($validated);

        return response()->json([
            'message' => 'تگ با موفقیت ایجاد شد.',
            'data' => $tag
        ], 201);
    }

    /**
     * به‌روزرسانی تگ
     */
    public function update(Request $request, $id)
    {
        $tag = AccountTag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:account_tags,name,' . $id,
            'color' => 'nullable|string|max:7',
        ]);

        $tag->update($validated);

        return response()->json([
            'message' => 'تگ با موفقیت به‌روزرسانی شد.',
            'data' => $tag
        ]);
    }

    /**
     * حذف تگ
     */
    public function destroy($id)
    {
        $tag = AccountTag::findOrFail($id);
        $tag->delete();

        return response()->json([
            'message' => 'تگ با موفقیت حذف شد.'
        ]);
    }
}

