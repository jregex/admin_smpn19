<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FcmController extends Controller
{
    public function update_token(Request $request)
    {
        try {
            $request->validate([
                'fcm_token' => 'required|string',
            ]);
            $request->user()->update([
                'fcm_token' => $request->fcm_token,
            ]);

            return response()->json(['status' => 'sukses', 'message' => 'Fcm Token berhasil diperbarui']);
        } catch (ValidationException $th) {
            return response()->json([
                'status' => 'gagal',
                'message' => $th->getMessage(),
            ], 422);
        }

    }
}
