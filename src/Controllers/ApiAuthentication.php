<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiAuthentication extends Controller
{


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:15|unique:users',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $request->phone, $otp, now()->addMinutes(10));

        // $this->twilio->messages->create($request->phone, [
        //     'from' => env('TWILIO_FROM'),
        //     'body' => "Your OTP code is $otp"
        // ]);

        return response()->json(['message' => 'OTP sent to your phone.']);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:15',
            'otp' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $cachedOtp = Cache::get('otp_' . $request->phone);

        if ($cachedOtp && $cachedOtp == $request->otp) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            Cache::forget('otp_' . $request->phone);

            return response()->json(['message' => 'User registered successfully.', 'user' => $user]);
        }

        return response()->json(['message' => 'Invalid OTP.'], 422);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->accessToken;

            return response()->json(['token' => $token, 'user' => $user]);
        }

        return response()->json(['message' => 'Invalid credentials.'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Successfully logged out.']);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $otp = rand(100000, 999999);
        Cache::put('otp_' . $user->phone, $otp, now()->addMinutes(10));

        // $this->twilio->messages->create($user->phone, [
        //     'from' => env('TWILIO_FROM'),
        //     'body' => "Your OTP code is $otp"
        // ]);

        return response()->json(['message' => 'OTP sent to your phone.']);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:15',
            'otp' => 'required|integer',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $cachedOtp = Cache::get('otp_' . $request->phone);

        if ($cachedOtp && $cachedOtp == $request->otp) {
            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            Cache::forget('otp_' . $request->phone);

            return response()->json(['message' => 'Password reset successfully.']);
        }

        return response()->json(['message' => 'Invalid OTP.'], 422);
    }
}