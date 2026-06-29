<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'applicant',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Account locked check — reveal no information about whether email exists
        if ($user && $user->isLocked()) {
            $remaining = $user->lockoutRemainingSeconds();

            return response()->json([
                'error' => [
                    'code' => 'ACCOUNT_LOCKED',
                    'message' => 'Akun Anda terkunci sementara. Silakan coba lagi dalam '.ceil($remaining / 60).' menit.',
                    'retry_after_seconds' => $remaining,
                ],
            ], Response::HTTP_LOCKED);
        }

        // Validate credentials
        if (! $user || ! Hash::check($request->password, $user->password)) {
            if ($user) {
                $user->recordFailedLoginAttempt();

                if ($user->isLocked()) {
                    return response()->json([
                        'error' => [
                            'code' => 'ACCOUNT_LOCKED',
                            'message' => 'Akun Anda terkunci sementara setelah beberapa kali percobaan gagal.',
                            'retry_after_seconds' => $user->lockoutRemainingSeconds(),
                        ],
                    ], Response::HTTP_LOCKED);
                }
            }

            return response()->json([
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'Email atau password salah.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->clearFailedLoginAttempts();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Delete the current token if available (Bearer token), otherwise revoke all
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } else {
            $request->user()->tokens()->delete();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        // Always attempt — Password::sendResetLink returns the same status string
        // regardless of whether the email exists, so the response is always identical.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Jika email terdaftar, link reset password telah dikirimkan.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password]);
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password berhasil diperbarui.']);
        }

        return response()->json([
            'error' => [
                'code' => 'RESET_FAILED',
                'message' => 'Link reset password tidak valid atau sudah kadaluarsa.',
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
