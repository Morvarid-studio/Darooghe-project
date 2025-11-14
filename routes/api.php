<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\WorklogController;


Route::post('/register', [AuthController::class, 'registerPost'])
    ->name('api_registerPost'); // ثبت نام کاربر
Route::post('/login', [AuthController::class, 'loginPost'])
    ->name('api_login'); // ورود کاربر

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dashboard/information', [InformationController::class, 'informationPost'])
        ->name('api_information'); // ثبت اطلاعات کاربر
    Route::get('/dashboard/information', [InformationController::class, 'showInformation'])
        ->name('api_information'); //برگرداندن اطلاعات کاربر
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('api_logout');// خروج کاربر

    Route::post('/dashboard/update_auth', [AuthController::class, 'update'])
        ->name('api_update_auth');// بروزرسانی رمز و نام کاربری و ایمیل
    Route::post('/dashboard/update_information', [InformationController::class, 'update'])
        ->name('api_update_information');// بروزرسانی اطلاعات

    Route::get('/worklogs', [WorklogController::class, 'index'])
        ->name('worklogs_index'); //برگرداندن ساعات کاری
    Route::post('/worklogs', [WorklogController::class, 'store'])
        ->name('worklogs_store'); // ثبت ساعات کاری
    Route::patch('/worklogs/archive', [WorklogController::class, 'archive'])
        ->name('worklogs_archive'); //ارشیو ساعت کاری
    Route::patch('/worklogs/restore', [WorklogController::class, 'restore'])
        ->name('worklogs_restore'); // بازیابی رکورد ارشیو شده
});


