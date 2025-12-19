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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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

        // حذف أي session قديم قبل تسجيل الدخول الجديد
        // هذا يضمن عدم وجود تعارض مع sessions قديمة
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        // استخدام Laravel session-based authentication بدلاً من token
        Auth::login($user);

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
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

        // استخدام Laravel session-based authentication بدلاً من token
        Auth::login($user);

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
        ], ['Login successful.']);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        // مع session-based authentication، نحتاج فقط لحذف session
        // Auth::logout() لا يعمل مع RequestGuard في Sanctum
        if ($request->hasSession()) {
            // حذف جميع بيانات session
            $request->session()->flush();
        }

        return $this->success(null, ['Logged out successfully.']);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // إذا لم يكن هناك مستخدم مسجل دخول، يعيد 401
        if (!$user) {
            return $this->error(['Unauthenticated. Please login to continue.'], 401);
        }
        
        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'birthday' => $user->birthday,
                'address' => $user->address,
                'country' => $user->country,
                'zip_code' => $user->zip_code,
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    /**
     * Update profile of authenticated user
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female,other'],
            'birthday' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        $data = $validator->validated();

        // Update full name if first_name / last_name provided
        if (isset($data['first_name']) || isset($data['last_name'])) {
            $first = $data['first_name'] ?? $user->first_name ?? '';
            $last = $data['last_name'] ?? $user->last_name ?? '';
            $fullName = trim($first . ' ' . $last);
            if ($fullName !== '') {
                $data['name'] = $fullName;
            }
        }

        $user->update($data);

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'birthday' => $user->birthday,
                'address' => $user->address,
                'country' => $user->country,
                'zip_code' => $user->zip_code,
                'email_verified_at' => $user->email_verified_at,
            ],
        ], ['Profile updated successfully.']);
    }

    /**
     * Upload and update user avatar (profile image)
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all(), 422);
        }

        // Delete old avatar file if it exists and is stored in /storage
        if ($user->avatar && str_starts_with($user->avatar, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $user->avatar);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $url = '/storage/' . $path;

        $user->avatar = $url;
        $user->save();

        return $this->success([
            'avatar' => $url,
        ], ['Avatar updated successfully.']);
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

        
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

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

        // التأكد من أن session نظيف بعد verify email
        // هذا يضمن عدم وجود تعارض عند تسجيل الدخول
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

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
