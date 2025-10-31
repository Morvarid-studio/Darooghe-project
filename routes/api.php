<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Route::get('/register' , [AuthController::class, 'register'])-> name('register');
Route::post('/register' , [AuthController::class, 'registerPost'])-> name('api.register');

//Route::get('/login' , [AuthController::class, 'login'])-> name('login');
Route::post('/login' , [AuthController::class, 'loginPost'])-> name('api.login');
//Route::get('/logout' , [AuthController::class, 'logout'])-> name('logout');

Route::middleware('auth:scantum')->get('/dashboard' , [AuthController::class, 'dashboard'])-> name('api.dashboard');
