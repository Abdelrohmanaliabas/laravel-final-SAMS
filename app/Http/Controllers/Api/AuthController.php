<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Mail\ActivationCodeMail;
use App\Mail\IncompleteProfileWarningMail;
use App\Mail\ResetCodeMail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $activationCode = (string) Str::uuid();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'activation_code' => $activationCode,
            'is_data_complete' => false,
            'status' => 'pending', // Or active but unverified
        ]);

        // Send Activation Code
        Mail::to($user->email)->send(new ActivationCodeMail($user, $activationCode));

        // Send Incomplete Profile Warning
        Mail::to($user->email)->send(new IncompleteProfileWarningMail($user));

        $token = $user->createToken('sams-app')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please check your email for the activation code.',
            'user' => $user->only(['id', 'name', 'email', 'role', 'status', 'is_data_complete']),
            'token' => $token,
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = User::where('activation_code', $request->code)->first();

        if (!$user) {
            return response()->json(['message' => 'Activation link is invalid or has already been used.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified.']);
        }

        $user->email_verified_at = now();
        $user->activation_code = null;
        $user->status = 'active';
        $user->save();

        return response()->json([
            'message' => 'Email verified successfully.',
            'user' => $user->only(['id', 'name', 'email', 'status']),
        ]);
    }

    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Google login failed.'], 400);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(16)), // Random password
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
                'status' => 'active',
                'is_data_complete' => false,
            ]);

            // Send Incomplete Profile Warning
            Mail::to($user->email)->send(new IncompleteProfileWarningMail($user));
        } else {
            // Update google_id if not set
            if (!$user->google_id) {
                $user->google_id = $googleUser->getId();
                $user->save();
            }
        }

        $token = $user->createToken('sams-app')->plainTextToken;

        // SECURE: Generate a short-lived code to exchange for the token
        // This prevents the JWT from being exposed in the URL
        $exchangeCode = Str::random(40);
        Cache::put('auth_exchange_' . $exchangeCode, [
            'token' => $token,
            'user_id' => $user->id
        ], now()->addMinutes(1)); // Valid for 1 minute

        // Redirect to frontend with the exchange code
        $frontendUrl = config('app.frontend_url', 'http://localhost:4200');
        $queryParams = http_build_query(['code' => $exchangeCode]);

        return redirect()->to("{$frontendUrl}/login?{$queryParams}");
    }

    public function exchangeToken(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $data = Cache::pull('auth_exchange_' . $request->code);

        if (!$data) {
            return response()->json(['message' => 'Invalid or expired exchange code.'], 400);
        }

        $user = User::find($data['user_id']);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $data['token'],
            'user' => $user->only(['id', 'name', 'email', 'role', 'status', 'is_data_complete']),
        ]);
    }

    public function completeProfile(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'role' => 'required|string|in:student,parent,teacher,center_admin,assistant',
            // Add other required fields here
        ]);

        $user = Auth::user();
        $user->update([
            'phone' => $request->phone,
            'role' => $request->role,
            'is_data_complete' => true,
        ]);

        return response()->json(['message' => 'Profile completed successfully.', 'user' => $user]);
    }

    public function login(LoginRequest $request)
    {
        $user = Auth::attempt($request->only('email', 'password'));

        if (!$user) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 401);
        }

        $user = User::find(Auth::id());

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account is not active.'], 403);
        }

        $token = $user->createToken('sams-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->only(['id', 'name', 'email', 'phone', 'status']),
            'token' => $token,
        ]);
    }

    public function logout()
    {
        $user = User::findOrFail(Auth::id());
        $user->tokens()->delete();

        return response()->json(['message' => 'Logout successful.']);
    }
    public function me()
    {
        $user = Auth::user();
        return response()->json($user->only(['id', 'name', 'email', 'phone', 'status', 'role', 'is_data_complete']));
    }

    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $plainToken = (string) Str::uuid();

        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        // SECURE: Generate a short-lived exchange code
        $exchangeCode = Str::random(40);
        Cache::put('password_reset_exchange_' . $exchangeCode, [
            'email' => $request->email,
            'token' => $plainToken
        ], now()->addMinutes(30));

        Mail::to($request->email)->send(new ResetCodeMail($exchangeCode));

        return response()->json(['message' => 'Password reset link has been emailed to you.']);
    }

    public function validateResetCode(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $data = Cache::get('password_reset_exchange_' . $request->code);

        if (!$data) {
            return response()->json(['message' => 'Invalid or expired reset link.'], 400);
        }

        return response()->json([
            'email' => $data['email'],
            'token' => $data['token']
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $record = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset link.'], 400);
        }

        if (now()->diffInMinutes($record->created_at) > 30) {
            return response()->json(['message' => 'Reset link expired.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete token
        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
