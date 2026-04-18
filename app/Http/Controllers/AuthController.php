<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Mail\PasswordResetOtp;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    use ApiResponse;
public function __construct(private AuthService $authService) {}

    /**
     * Register a new user and return Sanctum token
     */
    public function register(RegisterRequest $request)
    {


        try {
            $result = $this->authService->register($request->validated());

            return $this->success(
                data: $result,
                message: 'Registered successfully',
                status: 201
            );
        }  catch (\Exception $e) {
              return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Login user and return Sanctum token
     */
    public function login(LoginRequest  $request)
    {
         try {
            $result = $this->authService->login($request->only('email', 'password'));

            return $this->success(
                data: $result,
                message: 'Logged in successfully'
            );
        } catch (\App\Exceptions\ApiException  $e) {
            return $this->error(
                message: $e->getMessage(),
                status: (int) $e->getCode() ?: 400
            );
        }catch (\Exception $e) {
             Log::error($e->getMessage());
        return $this->error('Something went wrong', 500);     //  unknown errors → 500
    }
    }

    /**
     * Logout from current device only
     */
    public function logout(Request $request)
    {
      try {
            $this->authService->logout($request);

            return $this->success(message: 'Logged out successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Logout from all devices
     */
     public function logoutAll(Request $request)
    {
        try {
            $this->authService->logoutAll($request);

            return $this->success(message: 'Logged out from all devices');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
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

 public function forgot_password(ForgotPasswordRequest  $request)
{
    try {
            $this->authService->forgotPassword($request->email);

            // always success — never reveal if email exists
            return $this->success(message: 'You will receive an OTP only if this email exists');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
}

 public function reset_password(ResetPasswordRequest $request)
{
    try {
            $this->authService->resetPassword($request->validated());

            return $this->success(message: 'Password reset successfully');
        } catch (\App\Exceptions\ApiException  $e) {
            return $this->error(
                message: $e->getMessage(),
                status: (int) $e->getCode() ?: 400
            );
        }catch (\Exception $e) {
        return $this->error('Something went wrong', 500);     //  unknown errors → 500
    }
    }

}
