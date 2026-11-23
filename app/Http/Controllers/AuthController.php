<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class AuthController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Login',
        ];

        return view('admin.login', $data);
    }

    public function check_login(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email',
                'password' => 'required|string',
            ],
            [
                'email.required' => 'Email tidak boleh kosong',
                'password.required' => 'Password tidak boleh kosong',
            ]
        );
        $user = User::where('email', $request->email)->first();
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'is_active' => 1])) {
            $request->session()->put('login-user', $user);

            return to_route('dashboard');
        } else {
            LivewireAlert::title('Gagal')
                ->text('Email atau password salah, atau user belum diverifikasi')
                ->error()
                ->show();

            return back();
        }
    }

    public function logout(Request $request)
    {
        $request->session()->flush();

        return to_route('login');
    }
}
