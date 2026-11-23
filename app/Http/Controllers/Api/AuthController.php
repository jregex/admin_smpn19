<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function getProfile(Request $request)
    {
        $user = $request->user()->load(['siswa', 'sekolah']);

        return response()->json([
            'status' => 'success',
            'user' => new UserResource($user),
            'access_token' => $request->bearerToken(),
        ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email tidak boleh kosong.',
            'email.email' => 'Format email salah.',
            'password.required' => 'Password tidak boleh kosong.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password, 'role_id' => 4])) {

            $token = Auth::user()->createToken('access_token', ['*']);
            /* $user = Auth::user()->load(['siswa', 'sekolah']); */
            $user = new UserResource(Auth::user()->load(['siswa', 'sekolah']));

            return response()->json(['data' => $user, 'access_token' => $token->plainTextToken]);
        } else {
            return response()->json(['message' => 'Email atau password salah atau anda bukan siswa!!'], 401);
        }

    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed|different:current_password',
        ], [
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_password.min' => 'Password baru harus lebih dari 8 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password lama Anda salah.',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diperbarui.',
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            $request->user()->update([
                'fcm_token' => null,
            ]);
            /* $request->user()->currentAccessToken()->delete(); */

            return response()->json([
                'message' => 'Anda berhasil logout',
            ], 200);
        } catch (\Exception $ex) {
            return response()->json([
                'message' => $ex->getMessage(),
            ], 500);
        }

    }
}
