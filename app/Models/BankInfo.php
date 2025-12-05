<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Account extends Model
{
    protected $table = 'accounts';

    protected $fillable = [
        'user_id',
        'account_category_id',
        'account_type',      // 'employee', 'company', 'external'
        'name',              // برای حساب‌های خارجی مثل "سوپرمارکت علی"
        'bank_name',
        'branch_name',
        'branch_code',
        'account_number',
        'sheba',
        'status',            // pending, approved, rejected
        'is_active',         // رکورد فعال یا قدیمی
        'deactivated_at',
        'approved_by',       // user_id ادمینی که تأیید کرده
        'admin_note',        // توضیح رد یا یادداشت HR
    ];

    protected $casts = [
        'account_number' => 'encrypted',  // رمزنگاری Laravel
        'sheba' => 'encrypted',
        'is_active' => 'boolean',
    ];

    // رابطه با کارمند (nullable برای حساب‌های خارجی و شرکت)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // رابطه با دسته‌بندی حساب
    public function accountCategory(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class);
    }

    // رابطه many-to-many با roles (برای دسترسی)
    public function allowedRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'account_role',
            'account_id',
            'role',
            'id',
            'role'
        )->withPivot('role');
    }

    // Scope برای حساب‌های فعال
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope برای فیلتر بر اساس account_type
    public function scopeType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    // Scope برای حساب‌هایی که یک role خاص می‌تواند ببیند
    public function scopeAccessibleByRole($query, string $role)
    {
        return $query->whereHas('allowedRoles', function($q) use ($role) {
            $q->where('role', $role);
        })->orWhere(function($q) {
            // حساب‌های خود کاربر همیشه قابل دسترسی هستند
            $q->whereNotNull('user_id');
        });
    }
}
