<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTag;
use App\Models\Transaction;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
            $userRoleName = $user->role ?? 'user';
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

    /**
     * دریافت اطلاعات یک حساب خاص (برای داشبورد حساب)
     */
    public function showAccount(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز.'
            ], 403);
        }

        $account = Account::with(['tags', 'user'])
            ->findOrFail($id);

        $account->allowed_roles = $account->getAllowedRoles();

        return response()->json($account);
    }

    /**
     * دریافت تراکنش‌های یک حساب خاص
     */
    public function getAccountTransactions(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز.'
            ], 403);
        }

        $account = Account::findOrFail($id);

        // دریافت تراکنش‌هایی که از این حساب خرج شده یا به این حساب وارد شده
        $transactions = Transaction::with(['fromAccount', 'toAccount', 'user'])
            ->where(function($q) use ($account) {
                $q->where('from_account_id', $account->id)
                  ->orWhere('to_account_id', $account->id);
            })
            ->active()
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // تبدیل تاریخ‌های میلادی به شمسی و اضافه کردن type
        $transactions->transform(function ($transaction) use ($account) {
            // تبدیل payment_date به string شمسی (فقط تاریخ بدون timestamp)
            $paymentDate = $transaction->payment_date;
            if ($paymentDate instanceof \Carbon\Carbon) {
                $paymentDate = $paymentDate->format('Y-m-d');
            }
            $transaction->payment_date = DateHelper::miladiToShamsi($paymentDate, 'Y/m/d');
            
            // تبدیل created_at به string شمسی (فقط تاریخ بدون timestamp)
            $createdAt = $transaction->created_at;
            if ($createdAt instanceof \Carbon\Carbon) {
                $createdAt = $createdAt->format('Y-m-d');
            }
            $transaction->created_at_shamsi = DateHelper::miladiToShamsi($createdAt, 'Y/m/d');
            
            // محاسبه type به صورت خودکار
            if ($transaction->from_account_id == $account->id) {
                $transaction->type = 'cost';
            } else {
                $transaction->type = 'receive';
            }
            
            return $transaction;
        });

        return response()->json([
            'account' => $account,
            'transactions' => $transactions
        ]);
    }

    /**
     * دریافت موجودی یک حساب خاص
     */
    public function getAccountBalance(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز.'
            ], 403);
        }

        $account = Account::findOrFail($id);

        // محاسبه موجودی از تراکنش‌ها
        $totalReceived = Transaction::where('to_account_id', $account->id)
            ->where('archived', false)
            ->sum('amount_decimal');

        $totalCosts = Transaction::where('from_account_id', $account->id)
            ->where('archived', false)
            ->sum('amount_decimal');

        $balance = $totalReceived - $totalCosts;

        return response()->json([
            'account' => $account,
            'total_received' => $totalReceived,
            'total_costs' => $totalCosts,
            'balance' => $balance
        ]);
    }

    /**
     * ثبت تراکنش برای یک حساب خاص
     */
    public function storeAccountTransaction(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز. فقط مدیران می‌توانند تراکنش ثبت کنند.'
            ], 403);
        }

        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'payment_date' => 'required|string', // دریافت به صورت شمسی
            'amount_decimal' => 'required|numeric|min:0',
            'category' => 'required|string',
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id',
            'description' => 'nullable|string',
            'invoice' => 'file|mimes:pdf,doc,docx|max:2048|nullable',
        ]);

        // بررسی اینکه یکی از حساب‌ها باید حساب مورد نظر باشد
        if ($validated['from_account_id'] != $account->id && $validated['to_account_id'] != $account->id) {
            return response()->json([
                'message' => 'یکی از حساب‌های مبدا یا مقصد باید حساب مورد نظر باشد.'
            ], 422);
        }

        // اعتبارسنجی و تبدیل تاریخ شمسی به میلادی
        if (!DateHelper::isValidShamsiDate($validated['payment_date'])) {
            return response()->json([
                'message' => 'تاریخ پرداخت نامعتبر است. فرمت صحیح: Y/m/d (مثلاً 1403/07/15)'
            ], 422);
        }

        // تبدیل تاریخ شمسی به میلادی برای ذخیره در دیتابیس
        $validated['payment_date'] = DateHelper::shamsiToMiladi($validated['payment_date']);

        $validated['user_id'] = $user->id; // ثبت‌کننده
        $validated['handled_by'] = $user->user_name;
        $validated['archived'] = false;

        $validated['invoice'] = null;
        if ($request->hasFile('invoice')) {
            $validated['invoice'] = $request->file('invoice')->store('invoices', 'public');
        }

        $transaction = Transaction::create($validated);

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت (فقط تاریخ بدون timestamp)
        $paymentDate = $transaction->payment_date;
        if ($paymentDate instanceof \Carbon\Carbon) {
            $paymentDate = $paymentDate->format('Y-m-d');
        }
        $transaction->payment_date = DateHelper::miladiToShamsi($paymentDate, 'Y/m/d');
        
        $createdAt = $transaction->created_at;
        if ($createdAt instanceof \Carbon\Carbon) {
            $createdAt = $createdAt->format('Y-m-d');
        }
        $transaction->created_at_shamsi = DateHelper::miladiToShamsi($createdAt, 'Y/m/d');
        
        $transaction->load(['fromAccount', 'toAccount', 'user']);

        // محاسبه type به صورت خودکار
        if ($transaction->from_account_id == $account->id) {
            $transaction->type = 'cost';
        } else {
            $transaction->type = 'receive';
        }

        return response()->json([
            'message' => 'تراکنش با موفقیت ثبت شد.',
            'data' => $transaction
        ], 201);
    }

    /**
     * آرشیو کردن تراکنش یک حساب خاص
     */
    public function archiveTransaction(Request $request, $accountId, $transactionId)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز. فقط مدیران می‌توانند تراکنش آرشیو کنند.'
            ], 403);
        }

        $account = Account::findOrFail($accountId);

        // پیدا کردن تراکنش که مربوط به حساب مورد نظر باشد
        $transaction = Transaction::where('id', $transactionId)
            ->where(function($q) use ($account) {
                $q->where('from_account_id', $account->id)
                  ->orWhere('to_account_id', $account->id);
            })
            ->firstOrFail();

        $transaction->archived = true;
        $transaction->save();
        $transaction->load(['fromAccount', 'toAccount', 'user']);

        // تبدیل تاریخ میلادی به شمسی برای ارسال به کلاینت
        $paymentDate = $transaction->payment_date;
        if ($paymentDate instanceof \Carbon\Carbon) {
            $paymentDate = $paymentDate->format('Y-m-d');
        }
        $transaction->payment_date = DateHelper::miladiToShamsi($paymentDate, 'Y/m/d');
        
        $createdAt = $transaction->created_at;
        if ($createdAt instanceof \Carbon\Carbon) {
            $createdAt = $createdAt->format('Y-m-d');
        }
        $transaction->created_at_shamsi = DateHelper::miladiToShamsi($createdAt, 'Y/m/d');
        
        // محاسبه type به صورت خودکار
        if ($transaction->from_account_id == $account->id) {
            $transaction->type = 'cost';
        } else {
            $transaction->type = 'receive';
        }

        return response()->json([
            'message' => 'تراکنش با موفقیت آرشیو شد.',
            'data' => $transaction
        ]);
    }
}
