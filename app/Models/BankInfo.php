<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankInfo extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'branch_name',
        'branch_code',
        'account_number',
        'sheba',
        'status',        // pending, approved, rejected
        'is_active',     // رکورد فعال یا قدیمی
        'deactivated_at',
        'approved_by',   // user_id ادمینی که تأیید کرده
        'admin_note',    // توضیح رد یا یادداشت HR
    ];

    protected $casts = [
        'account_number' => 'encrypted',  // رمزنگاری Laravel
        'sheba' => 'encrypted',
        'is_active' => 'boolean',
    ];

    // رابطه با کارمند
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
