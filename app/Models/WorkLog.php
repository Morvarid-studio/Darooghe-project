<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkLog extends Model
{
    use HasFactory;

    // نام جدول
    protected $table = 'worklogs';

    // ستون‌هایی که می‌توان با mass assignment مقداردهی کرد
    protected $fillable = [
        'user_id',
        'work_date',
        'work_hours',
        'description',
        'archived',
    ];

    // ستون archived به صورت boolean
    protected $casts = [
        'archived' => 'boolean',
        'work_date' => 'date',
        'work_hours' => 'decimal:2',
    ];

    // رابطه با کاربر
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // scope برای رکوردهای فعال
    public function scopeActive($query)
    {
        return $query->where('archived', false);
    }

    // scope برای رکوردهای آرشیو شده
    public function scopeArchived($query)
    {
        return $query->where('archived', true);
    }
}
