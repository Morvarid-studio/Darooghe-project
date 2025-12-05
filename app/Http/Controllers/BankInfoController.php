<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountCategory;
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
            $accounts = Account::with(['accountCategory', 'user'])
                ->where('is_active', true)
                ->orderBy('account_type')
                ->orderBy('name')
                ->get();
        } else {
            // کاربر عادی: فقط حساب‌هایی که role او در allowed_roles است + حساب خودش
            $accounts = Account::with(['accountCategory', 'user'])
                ->where('is_active', true)
                ->where(function($q) use ($user) {
                    $q->where('user_id', $user->id) // حساب خودش
                      ->orWhereHas('allowedRoles', function($query) use ($user) {
                          $query->where('role', $user->role);
                      });
                })
                ->orderBy('account_type')
                ->orderBy('name')
                ->get();
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
            'branch_name' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:50',
            'account_number' => 'required|string|max:60',
            'sheba' => 'required|string',
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
            ->with('accountCategory')
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
            'branch_name' => 'sometimes|nullable|string|max:255',
            'branch_code' => 'sometimes|nullable|string|max:50',
            'account_number' => 'sometimes|required|string|max:60',
            'sheba' => 'sometimes|required|string',
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
}
