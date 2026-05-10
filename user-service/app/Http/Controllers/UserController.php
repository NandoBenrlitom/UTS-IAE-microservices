<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($validated);

        $token = Str::random(60);
        $user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return response()->json([
            'message' => 'Register berhasil',
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $token = Str::random(60);
        $user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user('api');

        return response()->json([
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user('api');
        $user->forceFill([
            'api_token' => null,
        ])->save();

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function show(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($this->userPayload($user));
    }

    public function history(int $id)
    {
        try {
            $response = Http::get("http://127.0.0.1:8003/api/orders/user/{$id}");

            if ($response->failed()) {
                return response()->json(['message' => 'Gagal menarik histori'], 502);
            }

            return response()->json([
                'message' => 'Berhasil menarik histori',
                'data' => $response->json(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal terhubung ke OrderService'], 500);
        }
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
