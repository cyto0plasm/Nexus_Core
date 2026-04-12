<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    /**
     * Register a new user and return Sanctum token
     */
    public function register(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255', 'unique:users,name'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'numeric', 'digits_between:10,15'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            // Create user in database
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
            ]);

            // Generate API token (Sanctum)
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => $this->formatUser($user),
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
            ], 500);
        }
    }

    /**
     * Login user and return Sanctum token
     */
    public function login(Request $request)
    {
        // Validate credentials format
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Create token for API access

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    /**
     * Logout from current device only
     */
    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request)
    {
        /** @var \App\Models\User $user */
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices'
        ]);
    }

    /**
     * Logout all devices except current one
     */
    public function logoutOthers(Request $request)
    {
        /** @var \App\Models\User $user */
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $request->user()->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();

        return response()->json([
            'message' => 'Logged out from other devices'
        ]);
    }

 public function forgot_password(Request $request)
{
    $request->validate([
        'email' => ['required', 'email']
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    $token = random_int(100000, 999999);

    DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        [
            'email' => $request->email,
            'token' => $token,
            'created_at' => now()
        ]
    );
    Mail::to($request->email)->send(new PasswordResetOtp($token));

    return response()->json([
        'message' => 'Password reset token generated',

    ]);
}

 public function reset_password(Request $request)
{
    $request->validate([
        'email' => ['required', 'email'],
        'token' => ['required'],
        'password' => ['required', 'confirmed', 'min:8'],
    ]);

    $reset = DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->where('token', $request->token)
        ->first();

    if (!$reset) {
        return response()->json([
            'message' => 'Invalid token'
        ], 400);
    }

    // optional expiry (60 minutes)
    if (now()->diffInMinutes($reset->created_at) > config('otp.expires_minutes')) {
        return response()->json([
            'message' => 'Token expired'
        ], 400);
    }

    $user = User::where('email', $request->email)->first();

    $user->update([
        'password' => Hash::make($request->password)
    ]);

    // delete token after use
    DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->delete();

    return response()->json([
        'message' => 'Password reset successfully'
    ]);
}
    /**
     * Centralized user response formatting
     * (avoids repetition across methods)
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
