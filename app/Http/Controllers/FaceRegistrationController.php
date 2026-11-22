<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaceRegistrationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.face_registration', compact('user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|string',
        ]);

        $descriptor = json_decode($request->face_descriptor, true);

        if (!is_array($descriptor) || count($descriptor) !== 128) {
            return response()->json([
                'success' => false,
                'message' => 'Format face descriptor tidak valid (harus 128 float values).'
            ], 422);
        }

        $user = Auth::user();
        $user->update([
            'face_descriptor' => json_encode($descriptor),
            'face_registered_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wajah berhasil didaftarkan ke dalam sistem!',
            'redirect' => route('attendance.index')
        ]);
    }

    public function getDescriptor()
    {
        $user = Auth::user();
        return response()->json([
            'has_face' => !empty($user->face_descriptor),
            'descriptor' => $user->face_descriptor ? json_decode($user->face_descriptor) : null,
        ]);
    }
}
