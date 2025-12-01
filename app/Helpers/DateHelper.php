<?php

namespace App\Helpers;

use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class DateHelper
{
    /**
     * تبدیل تاریخ شمسی به میلادی
     * ورودی: تاریخ شمسی به فرمت Y/m/d یا Y-m-d (مثلاً 1403/07/15)
     * خروجی: تاریخ میلادی به فرمت Y-m-d (مثلاً 2024-10-06)
     */
    public static function shamsiToMiladi(string $shamsiDate): string
    {
        // حذف فاصله‌ها و تبدیل جداکننده‌ها
        $shamsiDate = str_replace(['/', '-', ' '], '/', trim($shamsiDate));
        
        // تبدیل به آرایه
        $parts = explode('/', $shamsiDate);
        
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('فرمت تاریخ شمسی نامعتبر است. فرمت صحیح: Y/m/d (مثلاً 1403/07/15)');
        }
        
        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $day = (int) $parts[2];
        
        // ایجاد تاریخ شمسی و تبدیل به میلادی
        // استفاده از constructor مستقیم برای ساخت Jalalian از سال، ماه و روز شمسی
        $jalali = new Jalalian($year, $month, $day);
        $miladi = $jalali->toCarbon();
        
        return $miladi->format('Y-m-d');
    }

    /**
     * تبدیل تاریخ میلادی به شمسی
     * ورودی: تاریخ میلادی (Carbon, DateTime, یا string به فرمت Y-m-d)
     * خروجی: تاریخ شمسی به فرمت Y/m/d (مثلاً 1403/07/15)
     */
    public static function miladiToShamsi($miladiDate, string $format = 'Y/m/d'): string
    {
        // اگر string است، تبدیل به Carbon
        if (is_string($miladiDate)) {
            $miladiDate = Carbon::parse($miladiDate);
        }
        
        // تبدیل به Jalalian
        $jalali = Jalalian::fromDateTime($miladiDate);
        
        return $jalali->format($format);
    }

    /**
     * اعتبارسنجی تاریخ شمسی
     * بررسی می‌کند که آیا تاریخ شمسی معتبر است یا نه
     */
    public static function isValidShamsiDate(string $shamsiDate): bool
    {
        try {
            $parts = explode('/', str_replace(['-', ' '], '/', trim($shamsiDate)));
            
            if (count($parts) !== 3) {
                return false;
            }
            
            $year = (int) $parts[0];
            $month = (int) $parts[1];
            $day = (int) $parts[2];
            
            // بررسی محدوده‌های معقول
            if ($year < 1300 || $year > 1500) {
                return false;
            }
            
            if ($month < 1 || $month > 12) {
                return false;
            }
            
            if ($day < 1 || $day > 31) {
                return false;
            }
            
            // تلاش برای تبدیل به میلادی برای بررسی اعتبار
            $jalali = new Jalalian($year, $month, $day);
            $miladi = $jalali->toCarbon();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

