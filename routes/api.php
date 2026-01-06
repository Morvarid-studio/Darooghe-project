<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CompanyAccountController;
use App\Http\Controllers\AccountCategoryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\WorklogController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaySlipController;
use App\Http\Controllers\SalaryController;


Route::post('/register', [AuthController::class, 'registerPost'])
    ->name('api_registerPost'); // ثبت نام کاربر
Route::post('/login', [AuthController::class, 'loginPost'])
    ->name('api_login'); // ورود کاربر

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dashboard/information', [InformationController::class, 'store'])
        ->name('api_information'); // ثبت اطلاعات کاربر
    Route::get('/dashboard/information', [InformationController::class, 'show'])
        ->name('api_information'); //برگرداندن اطلاعات کاربر
    Route::get('/dashboard/information/latest', [InformationController::class, 'getLatest'])
        ->name('api_information_latest'); // دریافت آخرین اطلاعات کاربر (برای فرم تکمیل اطلاعات)
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('api_logout');// خروج کاربر
    Route::post('/dashboard/update_auth', [AuthController::class, 'update'])
        ->name('api_update_auth');// بروزرسانی رمز و نام کاربری و ایمیل
    Route::post('/updateinformation', [InformationController::class, 'update'])
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

    // Route‌های فیش حقوقی
    // ⚠️ مهم: روت‌های خاص (مثل preview) باید قبل از روت‌های پارامتری (مثل {id}) تعریف شوند
    Route::get('/payslips/preview', [PaySlipController::class, 'preview'])
        ->name('payslips_preview'); // پیش‌نمایش فیش قبل از ثبت
    Route::get('/payslips', [PaySlipController::class, 'index'])
        ->name('payslips_index'); // لیست فیش‌های کاربر
    Route::get('/payslips/{id}', [PaySlipController::class, 'show'])
        ->name('payslips_show'); // جزئیات یک فیش
    Route::post('/payslips', [PaySlipController::class, 'store'])
        ->name('payslips_store'); // ثبت فیش جدید

    // Route‌های حساب‌ها (قبلاً bankinfo)
    Route::get('/accounts/for-transaction', [AccountController::class, 'getAccountsForTransaction'])
        ->name('accounts_for_transaction'); // دریافت لیست حساب‌ها برای ثبت تراکنش (با فیلتر role-based)
    Route::post('/accounts', [AccountController::class, 'store'])
        ->name('accounts_store'); // ثبت حساب جدید (برای کاربران عادی)
    Route::get('/accounts', [AccountController::class, 'show'])
        ->name('accounts_show'); // دریافت حساب‌های کاربر
    Route::post('/accounts/update', [AccountController::class, 'update'])
        ->name('accounts_update'); // به‌روزرسانی حساب کاربر

    Route::post('/dashboard/transactions', [TransactionController::class, 'store'])
        ->name('transactions_store'); //ثبت تراکنش
    Route::get('/dashboard//transactions', [TransactionController::class, 'show'])
        ->name('transactions_show'); //نمایش تراکنش های کاربر به کاربر
    Route::patch('/dashboard/transactions/archive', [TransactionController::class, 'archive'])
        ->name('transactions_archive'); // آرشیو تراکنش کاربر توسط خود کاربر
    Route::patch('/dashboard/transactions/restore', [TransactionController::class, 'restore'])
        ->name('transactions_restore'); //بازیابی تراکنش کاربر توسط خود کاربر

    // Route‌های مدیریت حساب تنخواه (برای کاربران عادی)
    Route::get('/petty-cash/account', [PettyCashController::class, 'getMyPettyCashAccount'])
        ->name('petty_cash_account'); // دریافت حساب تنخواه کاربر
    Route::get('/petty-cash/transactions', [PettyCashController::class, 'getMyPettyCashTransactions'])
        ->name('petty_cash_transactions'); // دریافت تراکنش‌های حساب تنخواه
    Route::post('/petty-cash/transactions', [PettyCashController::class, 'storeTransaction'])
        ->name('petty_cash_store_transaction'); // ثبت تراکنش برای حساب تنخواه
    Route::get('/petty-cash/balance', [PettyCashController::class, 'getMyPettyCashBalance'])
        ->name('petty_cash_balance'); // دریافت موجودی حساب تنخواه

    // ✅ Route‌های Admin (نیاز به نقش Admin دارند)
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/pending-profiles', [AdminController::class, 'getPendingProfiles'])
            ->name('admin_pending_profiles'); // لیست اطلاعات تایید نشده
        Route::get('/approved-profiles', [AdminController::class, 'getApprovedProfiles'])
            ->name('admin_approved_profiles'); // لیست اطلاعات تایید شده
        Route::get('/profiles/{userId}', [AdminController::class, 'getUserProfile'])
            ->name('admin_user_profile'); // جزئیات اطلاعات یک کاربر
        Route::post('/profiles/{userId}/approve', [AdminController::class, 'approveProfile'])
            ->name('admin_approve_profile'); // تایید اطلاعات کاربر
        Route::post('/profiles/{userId}/reject', [AdminController::class, 'rejectProfile'])
            ->name('admin_reject_profile'); // رد اطلاعات کاربر
        Route::post('/profiles/{userId}/archive', [AdminController::class, 'archiveApprovedProfile'])
            ->name('admin_archive_profile'); // آرشیو کردن پروفایل تایید شده
        
        // Route‌های داشبورد مالی حساب اصلی شرکت
        Route::get('/company-account/transactions', [CompanyAccountController::class, 'getCompanyTransactions'])
            ->name('admin_company_transactions'); // دریافت تراکنش‌های حساب اصلی
        Route::post('/company-account/transactions', [CompanyAccountController::class, 'storeTransaction'])
            ->name('admin_company_store_transaction'); // ثبت تراکنش برای حساب اصلی
        Route::patch('/company-account/transactions/{id}/archive', [CompanyAccountController::class, 'archiveTransaction'])
            ->name('admin_company_archive_transaction'); // آرشیو کردن تراکنش حساب اصلی
        Route::get('/company-account/balance', [CompanyAccountController::class, 'getCompanyBalance'])
            ->name('admin_company_balance'); // دریافت موجودی حساب اصلی
        
        // Route‌های مدیریت دسته‌بندی حساب‌ها
        Route::get('/account-categories', [AccountCategoryController::class, 'index'])
            ->name('admin_account_categories_index'); // دریافت لیست دسته‌بندی‌ها
        Route::post('/account-categories', [AccountCategoryController::class, 'store'])
            ->name('admin_account_categories_store'); // ایجاد دسته‌بندی جدید
        Route::put('/account-categories/{id}', [AccountCategoryController::class, 'update'])
            ->name('admin_account_categories_update'); // به‌روزرسانی دسته‌بندی
        Route::post('/account-categories/{id}/sync-roles', [AccountCategoryController::class, 'syncRoles'])
            ->name('admin_account_categories_sync_roles'); // sync کردن نقش‌های دسته‌بندی
        Route::delete('/account-categories/{id}', [AccountCategoryController::class, 'destroy'])
            ->name('admin_account_categories_destroy'); // حذف دسته‌بندی
        
        // Route‌های مدیریت حساب‌ها (برای Admin)
        Route::get('/accounts', [AccountController::class, 'index'])
            ->name('admin_accounts_index'); // دریافت لیست همه حساب‌ها با فیلتر
        Route::post('/accounts', [AccountController::class, 'storeAdmin'])
            ->name('admin_accounts_store'); // ایجاد حساب جدید توسط Admin
        Route::get('/accounts/{id}', [AccountController::class, 'showAccount'])
            ->name('admin_accounts_show'); // دریافت اطلاعات یک حساب خاص
        Route::get('/accounts/{id}/transactions', [AccountController::class, 'getAccountTransactions'])
            ->name('admin_accounts_transactions'); // دریافت تراکنش‌های یک حساب
        Route::get('/accounts/{id}/balance', [AccountController::class, 'getAccountBalance'])
            ->name('admin_accounts_balance'); // دریافت موجودی یک حساب
        Route::post('/accounts/{id}/transactions', [AccountController::class, 'storeAccountTransaction'])
            ->name('admin_accounts_store_transaction'); // ثبت تراکنش برای یک حساب
        Route::patch('/accounts/{id}/transactions/{transactionId}/archive', [AccountController::class, 'archiveTransaction'])
            ->name('admin_accounts_archive_transaction'); // آرشیو کردن تراکنش یک حساب
        Route::post('/accounts/{id}/sync-roles', [AccountController::class, 'syncRoles'])
            ->name('admin_accounts_sync_roles'); // sync کردن نقش‌های حساب
        Route::patch('/accounts/{id}/archive', [AccountController::class, 'archive'])
            ->name('admin_accounts_archive'); // آرشیو کردن حساب
        
        // Route‌های مدیریت تگ‌ها (برای Admin)
        Route::get('/tags', [TagController::class, 'index'])
            ->name('admin_tags_index'); // دریافت لیست تگ‌ها
        Route::post('/tags', [TagController::class, 'store'])
            ->name('admin_tags_store'); // ایجاد تگ جدید
        Route::put('/tags/{id}', [TagController::class, 'update'])
            ->name('admin_tags_update'); // به‌روزرسانی تگ
        Route::delete('/tags/{id}', [TagController::class, 'destroy'])
            ->name('admin_tags_destroy'); // حذف تگ
        
        // Route‌های مدیریت Role ها (برای Admin)
        Route::get('/roles', [RoleController::class, 'index'])
            ->name('admin_roles_index'); // دریافت لیست role ها
        Route::get('/roles/{id}', [RoleController::class, 'show'])
            ->name('admin_roles_show'); // دریافت یک role خاص
        Route::post('/roles', [RoleController::class, 'store'])
            ->name('admin_roles_store'); // ایجاد role جدید
        Route::put('/roles/{id}', [RoleController::class, 'update'])
            ->name('admin_roles_update'); // به‌روزرسانی role
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])
            ->name('admin_roles_destroy'); // حذف (غیرفعال کردن) role
        
        // Route برای دریافت لیست کاربران
        Route::get('/users', [AdminController::class, 'getAllUsers'])
            ->name('admin_users_list'); // دریافت لیست تمام کاربران
        
        // Route‌های مدیریت حقوق (برای Admin)
        Route::get('/users/{userId}/salaries', [SalaryController::class, 'getUserSalaries'])
            ->name('admin_user_salaries'); // دریافت حقوق‌های یک کاربر
        Route::post('/salaries', [SalaryController::class, 'store'])
            ->name('admin_salaries_store'); // ثبت حقوق جدید
        Route::put('/salaries/{id}', [SalaryController::class, 'update'])
            ->name('admin_salaries_update'); // به‌روزرسانی حقوق
        
        // Route‌های مدیریت فیش حقوقی (برای Admin)
        Route::get('/payslips', [PaySlipController::class, 'indexAdmin'])
            ->name('admin_payslips_index'); // لیست همه فیش‌ها با فیلتر
        Route::get('/payslips/user/{user_id}', [PaySlipController::class, 'indexByUser'])
            ->name('admin_payslips_by_user'); // فیش‌های یک کاربر خاص
        Route::get('/payslips/month/{month}', [PaySlipController::class, 'indexByMonth'])
            ->name('admin_payslips_by_month'); // فیش‌های یک ماه خاص
    });
    
    // Route عمومی برای دریافت لیست role ها (برای dropdown ها)
    Route::get('/roles', [RoleController::class, 'index'])
        ->name('roles_index');
});


