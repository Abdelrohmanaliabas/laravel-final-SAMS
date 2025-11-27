<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone'    => $validated['phone'],
            'status'   => 'active',
        ]);

        $token = $user->createToken('sams-app')->plainTextToken;

        return $this->success(
            data: [
                'user'  => $user->only(['id', 'name', 'email', 'phone', 'status']),
                'token' => $token,
            ],
            message: 'Registration successful.'
        );
    }

    public function login(LoginRequest $request)
    {
        $user = Auth::attempt($request->only('email', 'password'));

        if (! $user) {
            return $this->error(
                message: 'Invalid credentials.',
                status: 401
            );
        }

        $user = User::find(Auth::id());

        if ($user->status !== 'active') {
            return $this->error(
                message: 'Account is not active.',
                status: 403
            );
        }

        if ($user->tokens()->count()) {
            return $this->error(
                message: 'User is already logged in from another device.',
                status: 403
            );
        }

        $token = $user->createToken('sams-app')->plainTextToken;

        return $this->success(
            data: [
                'user'  => $user->only(['id', 'name', 'email', 'phone', 'status']),
                'token' => $token,
            ],
            message: 'Login successful.'
        );
    }

    public function logout()
    {
        $user = User::findOrFail(Auth::id());
        $user->tokens()->delete();

        return $this->success(
            message: 'Logout successful.'
        );
    }
    public function me()
    {
        return $this->success(
            data: Auth::user(),
            message: 'User retrieved successfully.'
        );
    }
    }
