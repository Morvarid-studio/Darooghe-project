<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankInfoController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\WorklogController;


Route::post('/register', [AuthController::class, 'registerPost'])
    ->name('api_registerPost'); // ثبت نام کاربر
Route::post('/login', [AuthController::class, 'loginPost'])
    ->name('api_login'); // ورود کاربر

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dashboard/information', [InformationController::class, 'store'])
        ->name('api_information'); // ثبت اطلاعات کاربر
    Route::get('/dashboard/information', [InformationController::class, 'show'])
        ->name('api_information'); //برگرداندن اطلاعات کاربر
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('api_logout');// خروج کاربر
    Route::post('/dashboard/update_auth', [AuthController::class, 'update'])
        ->name('api_update_auth');// بروزرسانی رمز و نام کاربری و ایمیل
    Route::post('/dashboard/updateinformation', [InformationController::class, 'update'])
        ->name('api_update_information');// بروزرسانی اطلاعات

    Route::get('/worklogs', [WorklogController::class, 'show'])
        ->name('worklogs_index'); //برگرداندن ساعات کاری
    Route::post('/worklogs', [WorklogController::class, 'store'])
        ->name('worklogs_store'); // ثبت ساعات کاری
    Route::patch('/worklogs/archive', [WorklogController::class, 'archive'])
        ->name('worklogs_archive'); //ارشیو ساعت کاری
    Route::patch('/worklogs/restore', [WorklogController::class, 'restore'])
        ->name('worklogs_restore'); // بازیابی رکورد ارشیو شده
    Route::get('/worklogs/monthly_report', [WorklogController::class, 'MonthlyWorkHours'])
        ->name('worklogs_monthly_report');
    Route::get('/worklogs/weekly_report', [WorklogController::class, 'WeeklyWorkHours'])
        ->name('worklogs_weekly_report');
    Route::get('/worklogs/last_seven_days', [WorklogController::class, 'LastSevenDaysWorkHours'])
        ->name('worklogs_last_seven_days_report');

    Route::post('/bankinfo', [BankInfoController::class, 'store'])
        ->name('bankinfo_store');
    Route::get('/bankinfo', [BankInfoController::class, 'show'])
        ->name('bankinfo_show');
    Route::post('/bankinfo', [BankInfoController::class, 'update'])
        ->name('bankinfo_update');

    Route::post('/dashboard/transactions', [TransactionController::class, 'store'])
        ->name('transactions_store'); //ثبت تراکنش
    Route::get('/dashboard//transactions', [TransactionController::class, 'show'])
        ->name('transactions_show'); //نمایش تراکنش های کاربر به کاربر
    Route::patch('/dashboard/transactions/archive', [TransactionController::class, 'archive'])
        ->name('transactions_archive'); // آرشیو تراکنش کاربر توسط خود کاربر
    Route::patch('/dashboard/transactions/restore', [TransactionController::class, 'restore'])
        ->name('transactions_restore'); //بازیابی تراکنش کاربر توسط خود کاربر
});


