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

    public function account()
    {
        return $this->belongsTo(Account::class, 'bankinfo_id');
    }

    // Alias برای سازگاری با کد قدیمی
    public function bankInfo()
    {
        return $this->account();
    }

    public function baseSalaryInfo()
    {
        return $this->belongsTo(Information::class, 'base_salary');
    }
}
