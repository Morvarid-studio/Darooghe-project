<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaySlip extends Model
{
    use HasFactory;

    protected $table = 'pay_slips';

    /**
     * فقط فیلدهایی که کاربر اجازه پر کردن دارد
     */
    protected $fillable = [
        'month',
        'payment_amount',
        'description',
    ];

    /**
     * روابط مدل
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankInfo()
    {
        return $this->belongsTo(BankInfo::class, 'bankinfo_id');
    }

    public function baseSalaryInfo()
    {
        return $this->belongsTo(Information::class, 'base_salary');
    }
}
