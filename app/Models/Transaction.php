<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',          // کسی که این تراکنش را ثبت کرده
        'payment_date',
        'amount_decimal',
        'category',
        'handled_by',
        'from_account_id',   // foreign key → accounts.id
        'to_account_id',    // foreign key → accounts.id
        'description',
        'invoice',
        'archived',
    ];

    protected $casts = [
        'archived' => 'boolean',
        'payment_date' => 'date',
        'amount_decimal' => 'decimal:2',
    ];

    // رابطه تراکنش با کاربر (ثبت‌کننده)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // رابطه با حساب مبدا
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    // رابطه با حساب مقصد
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    // scope برای رکوردهای فعال
    public function scopeActive($query)
    {
        return $query->where('archived', false);
    }
}
