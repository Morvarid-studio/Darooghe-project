<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    use HasFactory;

    protected $table = 'salaries';

    protected $fillable = [
        'user_id',
        'hourly_wage',
        'monthly_salary',
        'is_active',
        'effective_from',
        'effective_to',
        'notes',
    ];

    protected $casts = [
        'hourly_wage' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * رابطه با کاربر
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope برای دریافت حقوق فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope برای دریافت حقوق فعال در تاریخ مشخص
     */
    public function scopeActiveAt($query, $date = null)
    {
        $date = $date ?? now();
        
        return $query->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            });
    }

    /**
     * Scope برای دریافت آخرین حقوق فعال
     */
    public function scopeLatestActive($query)
    {
        return $query->where('is_active', true)
            ->orderBy('effective_from', 'desc');
    }
}

