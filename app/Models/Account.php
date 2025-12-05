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
        'account_type',      // 'employee', 'company', 'external', 'petty_cash'
        'display_name',      // نام نمایشی
        'owner_name',        // نام صاحب حساب
        'name',              // برای حساب‌های خارجی مثل "سوپرمارکت علی"
        'bank_name',
        'branch_name',
        'branch_code',
        'account_number',
        'sheba',
        'description',       // توضیحات
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

    // رابطه many-to-many با tags
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(AccountTag::class, 'account_tag', 'account_id', 'tag_id');
    }

    // رابطه many-to-many با roles (برای دسترسی)
    // این یک رابطه custom است که از جدول pivot account_role استفاده می‌کند
    // role در pivot table ذخیره می‌شود نه در جدول users
    public function allowedRoles()
    {
        // استفاده از DB برای دسترسی مستقیم به pivot table
        return $this->hasMany(\Illuminate\Support\Facades\DB::table('account_role')
            ->where('account_id', $this->id)
            ->get());
    }

    // متد برای دریافت نقش‌های مجاز
    public function getAllowedRoles(): array
    {
        return \Illuminate\Support\Facades\DB::table('account_role')
            ->where('account_id', $this->id)
            ->pluck('role')
            ->toArray();
    }
    
    // متد برای اضافه کردن role به حساب
    public function attachRole(string $role)
    {
        \Illuminate\Support\Facades\DB::table('account_role')->insertOrIgnore([
            'account_id' => $this->id,
            'role' => $role,
        ]);
    }
    
    // متد برای حذف role از حساب
    public function detachRole(string $role)
    {
        \Illuminate\Support\Facades\DB::table('account_role')
            ->where('account_id', $this->id)
            ->where('role', $role)
            ->delete();
    }

    // متد برای sync کردن نقش‌ها (حذف همه و اضافه کردن جدید)
    public function syncRoles(array $roles)
    {
        \Illuminate\Support\Facades\DB::table('account_role')
            ->where('account_id', $this->id)
            ->delete();
        
        foreach ($roles as $role) {
            $this->attachRole($role);
        }
    }
    
    // متد برای بررسی دسترسی یک role
    public function hasRole(string $role): bool
    {
        return \Illuminate\Support\Facades\DB::table('account_role')
            ->where('account_id', $this->id)
            ->where('role', $role)
            ->exists();
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

    // Scope برای فیلتر بر اساس tags
    public function scopeWithTags($query, array $tagIds)
    {
        return $query->whereHas('tags', function($q) use ($tagIds) {
            $q->whereIn('account_tags.id', $tagIds);
        });
    }

    // Scope برای حساب‌هایی که یک role خاص می‌تواند ببیند
    public function scopeAccessibleByRole($query, string $role)
    {
        return $query->whereExists(function($q) use ($role) {
            $q->select(\Illuminate\Support\Facades\DB::raw(1))
              ->from('account_role')
              ->whereColumn('account_role.account_id', 'accounts.id')
              ->where('account_role.role', $role);
        })->orWhere(function($q) {
            // حساب‌های خود کاربر همیشه قابل دسترسی هستند
            $q->whereNotNull('user_id');
        });
    }
}
