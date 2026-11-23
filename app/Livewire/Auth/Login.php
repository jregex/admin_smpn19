<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Enums\Position;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class Login extends Component
{
    public $email = '';

    public $password = '';

    public $remember = false;

    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ];
    }

    protected $validationAttributes = [
        'email' => 'Email',
        'password' => 'Kata Sandi',
    ];

    public function messages()
    {
        return [
            'email.required' => 'Alamat email wajib diisi, jangan dikosongkan.',
            'email.email' => 'Format email yang Anda masukkan tidak valid.',
            'password.required' => 'Kata sandi tidak boleh kosong.',
            'password.min' => 'Kata sandi terlalu pendek, minimal :min karakter.',
        ];
    }

    public function login()
    {
        $this->validate();
        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
        ];
        if (Auth::attempt($credentials, $this->remember)) {
            $user = Auth::user();

            if ($user->is_active != 1 || $user->isSiswa()) {
                Auth::logout();
                LivewireAlert::title('Perhatian')
                    ->text('User belum diverifikasi atau Siswa tidak bisa login')
                    ->warning()
                    ->position(Position::Center)
                    ->show();

                return;
            }
            session()->put('login-user', $user);

            return redirect()->intended(route('admin.dashboard'));
        } else {
            LivewireAlert::title('Akses ditolak')
                ->text('Email atau password salah')
                ->timer(4000)
                ->error()
                ->show();
            $this->reset(['password']);

            return back();
        }
    }

    public function render()
    {
        return view('auth.login')->layout('layouts.app_login');
    }
}
