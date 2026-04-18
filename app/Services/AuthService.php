<?php

namespace App\Services;

use App\Mail\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthService
{
    /**
     * Register a new user — no role, no shop yet
     * Role is assigned during onboarding
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $this->formatUser($user),
            'token' => $token,
        ];
    }

    /**
     * Login — returns user + token + shop + role
     * Flutter uses shop/role to decide where to navigate
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw new \App\Exceptions\ApiException('Invalid credentials', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // load their shop membership if exists
        // returns the FIRST shop — extend later for multi-shop
        $membership = $user->shops()->first();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $this->formatUser($user),
            'token' => $token,
            'shop'  => $membership ? [
                'id'   => $membership->id,
                'name' => $membership->name,
            ] : null,
            'role'  => $membership ? $membership->pivot->role : null,
        ];
    }

    /**
     * Logout from current device only
     */
    public function logout($request): void
    {
        $request->user()->currentAccessToken()->delete();
    }

    /**
     * Logout from all devices
     */
    public function logoutAll($request): void
    {
        $request->user()->tokens()->delete();
    }

    /**
     * Generate OTP and send to email
     */
    public function forgotPassword(string $email): void
    {
        $user = User::where('email', $email)->first();

        // always return success — never reveal if email exists (security)
        if (!$user) return;

        $otp = random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token'      => $otp,
                'created_at' => now(),
            ]
        );

        Mail::to($email)->send(new PasswordResetOtp($otp));
    }

    /**
     * Verify OTP and reset password
     */
    public function resetPassword(array $data): void
    {
        $reset = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->where('token', $data['token'])
            ->first();

        if (!$reset) {
            throw new \App\Exceptions\ApiException('Invalid token', 400);
        }

        $expiredMinutes = config('otp.expires_minutes', 60);
        if (now()->diffInMinutes($reset->created_at) > $expiredMinutes) {
            throw new \App\Exceptions\ApiException('Token expired', 400);
        }

        User::where('email', $data['email'])->update([
            'password' => Hash::make($data['password']),
        ]);

        // delete after use — one time only
        DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->delete();
    }

    /**
     * Centralized user formatting
     * Single place to change what the API exposes
     */
    private function formatUser(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
    }
}
