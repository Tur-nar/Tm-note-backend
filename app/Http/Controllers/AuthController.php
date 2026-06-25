<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    private function authTokenCookie(string $token)
    {
        return Cookie::make(
            config('app.auth_token_cookie.name'),
            $token,
            config('app.auth_token_cookie.ttl_minutes'),
            '/',
            config('app.auth_token_cookie.domain'),
            config('app.auth_token_cookie.secure') ?? request()->isSecure(),
            true,
            false,
            config('app.auth_token_cookie.same_site')
        );
    }

    private function forgetAuthTokenCookie()
    {
        return Cookie::make(
            config('app.auth_token_cookie.name'),
            '',
            -1,
            '/',
            config('app.auth_token_cookie.domain'),
            config('app.auth_token_cookie.secure') ?? request()->isSecure(),
            true,
            false,
            config('app.auth_token_cookie.same_site')
        );
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255"],
            "email" => ["required", "email", "unique:users"],
            "password" => ["required", "string", "confirmed", Password::defaults()],
        ]);

        $user = User::create($validated);

        event(new Registered($user));

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            'message' => 'Registeration successful. Please check your email for verification',
            "data" => $user,
        ], 201)->withCookie($this->authTokenCookie($token));
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'     => ['required', 'string', 'email'],
            'password'  => ['required', 'string']
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.'
            ], 401);
        }

        $token = $user->createToken("auth_token")->plainTextToken;

        return response()->json([
            "message" => 'Login successful',
            "data" => $user,
        ], 200)->withCookie($this->authTokenCookie($token));
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if( !$user ) {
            return response()->json([
                'message' => 'Unauthenticated user.'
            ], 401);
        }
        $token = $user->createToken('')->plainTextToken;
        return response()->json([
            'message' => 'Profile fetched successfully.',
            'data' => $user,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ], 200)->withCookie($this->forgetAuthTokenCookie());
    }

    public function verifyEmail(string $id, string $hash)
    {
        $user = User::findOrFail($id);
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->away("{$frontendUrl}/verify-email?status=already-verified");
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->away("{$frontendUrl}/verify-email?status=verified");
    }

    public function resendVerificationEmail(Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'You are already verified.',
            ], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email resent successfully.',
        ], 200);
    }

    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
