<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * دریافت لیست حساب‌ها برای ثبت تراکنش (با فیلتر role-based)
     */
    public function getAccountsForTransaction(Request $request)
    {
        $user = Auth::user();
        
        if ($user->isAdmin()) {
            // Admin: همه حساب‌های فعال
            $accounts = Account::with(['tags', 'user'])
                ->where('is_active', true)
                ->orderBy('account_type')
                ->orderBy('display_name')
                ->get()
                ->map(function ($account) {
                    $account->allowed_roles = $account->getAllowedRoles();
                    return $account;
                });
        } else {
            // کاربر عادی: فقط حساب‌هایی که role او در allowed_roles است + حساب خودش
            $userRoleName = $user->role ? $user->role->name : 'user';
            $accounts = Account::with(['tags', 'user'])
                ->where('is_active', true)
                ->where(function($q) use ($user, $userRoleName) {
                    $q->where('user_id', $user->id) // حساب خودش
                      ->orWhereHas('allowedRoles', function($query) use ($userRoleName) {
                          $query->where('role', $userRoleName);
                      });
                })
                ->orderBy('account_type')
                ->orderBy('display_name')
                ->get()
                ->map(function ($account) {
                    $account->allowed_roles = $account->getAllowedRoles();
                    return $account;
                });
        }
        
        return response()->json($accounts);
    }

    /**
     * ثبت حساب جدید (برای کاربران عادی - حساب خودشان)
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:60',
            'sheba' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['user_id'] = $user->id;
        $validated['account_type'] = 'employee';
        $validated['is_active'] = $request->input('is_active', true);

        $account = Account::create($validated);

        return response()->json([
            'message' => 'اطلاعات با موفقیت ثبت شد.',
            'data' => $account
        ], 201);
    }

    /**
     * دریافت حساب‌های کاربر
     */
    public function show(Request $request)
    {
        $user = Auth::user();

        $accounts = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->with('tags')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($accounts);
    }

    /**
     * به‌روزرسانی حساب کاربر
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $oldAccount = Account::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $validated = $request->validate([
            'bank_name' => 'sometimes|required|string|max:255',
            'account_number' => 'sometimes|required|string|max:60',
            'sheba' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($oldAccount) {
            $oldAccount->update([
                'is_active' => false,
            ]);
        }

        $newAccount = Account::create(array_merge(
            $validated,
            [
                'user_id' => $user->id,
                'account_type' => 'employee',
                'is_active' => true,
            ]
        ));

        return response()->json([
            'message' => 'اطلاعات جدید ثبت شد.',
            'data' => $newAccount
        ]);
    }

    /**
     * دریافت لیست همه حساب‌ها برای مدیریت (با فیلتر)
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز.'
            ], 403);
        }

        $query = Account::with(['tags', 'user']);

        // فیلتر بر اساس account_type
        if ($request->has('account_type') && $request->account_type) {
            $query->where('account_type', $request->account_type);
        }

        // فیلتر بر اساس tags
        if ($request->has('tag_ids') && is_array($request->tag_ids) && count($request->tag_ids) > 0) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->whereIn('account_tags.id', $request->tag_ids);
            });
        }

        $accounts = $query->orderBy('account_type')
            ->orderBy('display_name')
            ->get()
            ->map(function ($account) {
                $account->allowed_roles = $account->getAllowedRoles();
                return $account;
            });

        return response()->json($accounts);
    }

    /**
     * ایجاد حساب جدید توسط Admin (برای حساب‌های company و external)
     */
    public function storeAdmin(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز. فقط مدیران می‌توانند حساب ایجاد کنند.'
            ], 403);
        }

        $validated = $request->validate([
            'account_type' => 'required|in:employee,company,external,petty_cash',
            'display_name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:60',
            'sheba' => 'required|string',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id', // برای حساب‌های employee و petty_cash
            'tags' => 'nullable|array',
            'tags.*' => 'exists:account_tags,id',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
        ]);

        // اگر account_type = employee یا petty_cash باشد، user_id الزامی است
        if (in_array($validated['account_type'], ['employee', 'petty_cash']) && !isset($validated['user_id'])) {
            return response()->json([
                'message' => 'برای حساب کارمند و حساب تنخواه، user_id الزامی است.'
            ], 422);
        }

        // اگر account_type = company یا external باشد، user_id باید null باشد
        if (in_array($validated['account_type'], ['company', 'external'])) {
            $validated['user_id'] = null;
        }

        $validated['is_active'] = true;

        $account = Account::create($validated);

        // sync کردن tags
        if ($request->has('tags') && is_array($request->input('tags'))) {
            $account->tags()->sync($request->input('tags'));
        }

        // اگر roles برای دسترسی تعریف شده باشند، آنها را sync می‌کنیم
        if ($request->has('roles') && is_array($request->input('roles'))) {
            $account->syncRoles($request->input('roles'));
        }

        $account->allowed_roles = $account->getAllowedRoles();

        return response()->json([
            'message' => 'حساب با موفقیت ایجاد شد.',
            'data' => $account->load(['tags', 'user'])
        ], 201);
    }

    /**
     * آرشیو کردن حساب (برای Admin)
     */
    public function archive(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز.'
            ], 403);
        }

        $account = Account::findOrFail($id);
        $account->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'حساب با موفقیت آرشیو شد.',
            'data' => $account
        ]);
    }

    /**
     * Sync کردن نقش‌های حساب
     */
    public function syncRoles(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز.'
            ], 403);
        }

        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string',
        ]);

        $account->syncRoles($validated['roles']);

        $account->allowed_roles = $account->getAllowedRoles();

        return response()->json([
            'message' => 'نقش‌های حساب با موفقیت به‌روزرسانی شد.',
            'data' => $account->load(['tags', 'user'])
        ]);
    }
}
