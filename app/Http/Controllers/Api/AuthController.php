<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Send verification OTP
        $this->generateAndSendOtp($user->email, 'verify_email');

        return $this->success(
            ['email' => $user->email],
            ['Registration successful. Please check your email for verification code.'],
            201
        );
    }

    /**
     * Send OTP to email
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $email = $request->email;

        // User must exist and have verified email before login
        $user = User::where('email', $email)->first();

        if (!$user) {
            return $this->error(['No account found with this email. Please register first.'], 404);
        }

        if (!$user->email_verified_at) {
            return $this->error(['Please verify your email before logging in.'], 403);
        }

        $this->generateAndSendOtp($email, 'login');

        return $this->success(
            ['email' => $email],
            ['Verification code has been sent to your email.']
        );
    }

    /**
     * Login with email & password (classic login)
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error(['These credentials do not match our records.'], 401);
        }

        if (!$user->email_verified_at) {
            $this->generateAndSendOtp($user->email, 'verify_email');
            
            return $this->error(
                ['Your email is not verified. We have sent you a verification code.'],
                403,
                ['email' => $user->email, 'needs_verification' => true]
            );
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
            'token' => $token,
        ], ['Login successful.']);
    }

    /**
     * Verify OTP and login
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:4'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->where('type', 'login')
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return $this->error(['These credentials do not match our records.'], 401);
        }

        // Mark OTP as used
        $otp->markAsUsed();

        // Find user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error(['User not found.'], 404);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
            'token' => $token,
        ], ['Login successful.']);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, ['Logged out successfully.']);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        return $this->success([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'email_verified_at' => $request->user()->email_verified_at,
            ],
        ]);
    }

    /**
     * Forgot password - send reset OTP
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $this->generateAndSendOtp($request->email, 'reset_password');

        return $this->success(
            ['email' => $request->email],
            ['Password reset code has been sent to your email.']
        );
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:4'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->where('type', 'reset_password')
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return $this->error(['Invalid or expired code.'], 401);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error(['User not found.'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $otp->markAsUsed();

        return $this->success(null, ['Password has been reset successfully.']);
    }

    /**
     * Verify email after registration (using OTP type verify_email)
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:4'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $otp = Otp::where('email', $request->email)
            ->where('code', $request->code)
            ->where('type', 'verify_email')
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return $this->error(['Invalid or expired code.'], 401);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error(['User not found.'], 404);
        }

        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        $otp->markAsUsed();

        return $this->success(
            [
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
            ['Email has been verified successfully. Please log in.']
        );
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'type' => ['required', 'in:verify_email,reset_password,login'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $this->generateAndSendOtp($request->email, $request->type);

        return $this->success(
            ['email' => $request->email],
            ['Verification code has been resent to your email.']
        );
    }

    /**
     * Helper: Generate and send OTP
     */
    private function generateAndSendOtp(string $email, string $type = 'login'): void
    {
        // Delete old unused OTPs for this email and type
        Otp::where('email', $email)
            ->where('type', $type)
            ->whereNull('used_at')
            ->delete();

        // Generate 4-digit code
        $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Create OTP record
        $otp = Otp::create([
            'email' => $email,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Send email (simple version - you can create a Mailable later)
        Mail::raw(
            "Your verification code is: {$code}\n\nThis code will expire in 10 minutes.",
            function ($message) use ($email) {
                $message->to($email)
                    ->subject('Your Verification Code - Tripto');
            }
        );
    }
}
