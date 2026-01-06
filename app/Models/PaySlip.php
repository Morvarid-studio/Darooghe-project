<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaySlip extends Model
{
    use HasFactory;

    protected $table = 'pay_slips';

    /**
     * فیلدهایی که می‌توانند mass assigned شوند
     */
    protected $fillable = [
        'user_id',
        'bankinfo',
        'base_salary',
        'month',
        'total_work_hours',
        'salary',
        'payment_amount',
        'remaining_salary_of_this_month',
        'remaining_salary_total',
        'balance',
        'total_balance',
        'receipt',
        'description',
        'insurance',
        'financial_facilities',
        'remaining_facilities',
        'monthly_facility_payment',
        'total_facility_payment',
    ];

    /**
     * فیلدهایی که باید به صورت عددی cast شوند
     */
    protected $casts = [
        'base_salary' => 'decimal:2',
        'total_work_hours' => 'decimal:2',
        'salary' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'remaining_salary_of_this_month' => 'decimal:2',
        'remaining_salary_total' => 'decimal:2',
        'balance' => 'decimal:2',
        'total_balance' => 'decimal:2',
        'insurance' => 'decimal:2',
        'financial_facilities' => 'decimal:2',
        'remaining_facilities' => 'decimal:2',
        'monthly_facility_payment' => 'decimal:2',
        'total_facility_payment' => 'decimal:2',
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

    /**
     * رابطه با worklog هایی که در این فیش حقوقی استفاده شده‌اند
     */
    public function workLogs()
    {
        return $this->hasMany(WorkLog::class);
    }

    /**
     * Accessor برای فرمت کردن حقوق
     */
    public function getFormattedSalaryAttribute()
    {
        return number_format($this->salary, 0);
    }

    /**
     * Accessor برای فرمت کردن مبلغ پرداختی
     */
    public function getFormattedPaymentAmountAttribute()
    {
        return number_format($this->payment_amount, 0);
    }

    /**
     * Accessor برای فرمت کردن باقیمانده حقوق
     */
    public function getFormattedRemainingSalaryAttribute()
    {
        return number_format($this->remaining_salary_total, 0);
    }

    /**
     * Accessor برای دریافت نام ماه شمسی
     */
    public function getMonthNameAttribute()
    {
        $parts = explode('-', $this->month);
        $year = (int)$parts[0];
        $monthNum = (int)$parts[1];
        
        $monthNames = [
            1 => 'فروردین',
            2 => 'اردیبهشت',
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];
        
        return $monthNames[$monthNum] ?? $monthNum;
    }

    /**
     * Accessor برای دریافت تاریخ کامل ماه
     */
    public function getFullMonthAttribute()
    {
        return $this->month_name . ' ' . explode('-', $this->month)[0];
    }
}
