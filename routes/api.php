<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InformationController;


Route::post('/register', [AuthController::class, 'registerPost'])->name('api.registerPost');
Route::post('/login', [AuthController::class, 'loginPost'])->name('api.login');
//Route::get('/', function () {return view('welcome');});
Route::get('/register' , [AuthController::class, 'registerPost'])-> name('register');
//Route::get('/login' , [AuthController::class, 'login'])-> name('login');
Route::middleware('auth:sanctum')->group(function () {
    //Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('api.dashboard');
    Route::post('/dashboard/information', [InformationController::class, 'informationPost'])->name('api.information');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
});


