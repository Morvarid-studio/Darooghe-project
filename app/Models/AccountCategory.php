<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    // رابطه با حساب‌ها
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    // متد برای دریافت نقش‌های مجاز
    public function getAllowedRoles(): array
    {
        return \Illuminate\Support\Facades\DB::table('account_category_role')
            ->where('account_category_id', $this->id)
            ->pluck('role')
            ->toArray();
    }
    
    // متد برای اضافه کردن role به دسته‌بندی
    public function attachRole(string $role)
    {
        \Illuminate\Support\Facades\DB::table('account_category_role')->insertOrIgnore([
            'account_category_id' => $this->id,
            'role' => $role,
        ]);
    }
    
    // متد برای حذف role از دسته‌بندی
    public function detachRole(string $role)
    {
        \Illuminate\Support\Facades\DB::table('account_category_role')
            ->where('account_category_id', $this->id)
            ->where('role', $role)
            ->delete();
    }
    
    // متد برای sync کردن نقش‌ها (حذف همه و اضافه کردن جدید)
    public function syncRoles(array $roles)
    {
        \Illuminate\Support\Facades\DB::table('account_category_role')
            ->where('account_category_id', $this->id)
            ->delete();
        
        foreach ($roles as $role) {
            $this->attachRole($role);
        }
    }
    
    // متد برای بررسی دسترسی یک role
    public function hasRole(string $role): bool
    {
        return \Illuminate\Support\Facades\DB::table('account_category_role')
            ->where('account_category_id', $this->id)
            ->where('role', $role)
            ->exists();
    }
}


