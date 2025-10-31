<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register()
    {
        //return view('auth.register');
    }

    public function login()
    {
         if(auth()->check())
         {
             return redirect()->route('home');
         }

        //return view('auth.login');
    }
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
    public function registerpost (Request $request)
    {
        $request->validate([
            'user_name' => 'required' ,
            'email' => 'required|email|unique:users' ,
            'password' => 'required|min:5|confirmed'
        ]);



        $user = User::create([
            'user_name' => $request->user_name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        if(!$user)
        {
            return redirect()->back()->with('error' , 'Registration failed, try again');
        }

        return redirect()->route('home')->with('Success' , 'Registration Success, login to access');
    }

    public function loginpost (Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email|exists:users' ,
            'password' => 'required|min:5'
        ]);

        if(Auth::attempt($credentials))
        {
            return redirect()->route('home');
        }

        return redirect()->route('login')->with('erorr' , 'Login details are not valid');
    }


}
