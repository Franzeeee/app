<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use App\Mail\NewMail;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        // Validate request data
        $request->validate(
            [
                'email' => 'required|email|unique:users',
                'password' => ['required', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*\d).+$/']
            ],
            [
                'password.regex' => 'The password must be at least 8 characters long and contain at least one uppercase letter and one number.',
            ]
        );

        // Find the count of existing users
        $count = User::count();

        // Generate the name
        $name = 'user' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

        // Create user
        $user = User::create([
            'name' => $name, // Generated Random Name
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        //Send Email Verification
        $createdAt = Carbon::now();
        $id = $user->id;
        $encryptedData =  Crypt::encryptString(serialize([$id, $createdAt]));
        $verificationUrl = '/api/verify/' . $encryptedData;

        Mail::queue(new NewMail($user, $verificationUrl));
        

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $user = $request->user();
        $token = $user->createToken('Access Token')->plainTextToken;
        $user_type = $user->user_type;

        return response()->json([
            'token' => $token,
            'user_type' => $user_type,
            'user_id' => $user->id
        ])->withCookie("jwt_token", $token, 60 * 24)
        ->withCookie("user_type", $user_type, 60 * 24);
    }


    public function user()
    {
        return response([
            'user' => Auth::user(),
        ]);
    }

    public function logout()
    {
        $cookie = Cookie::forget('jwt_token');
        $user_type = Cookie::forget('user_type');

        return response([
            'message' => 'Success'
        ])->withCookie($cookie)->withCookie($user_type);
    }

    public function verify(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message', "Token not provided"], 401);
        }

        // Query the database to check if the token exists
        $tokenExists = DB::table('tokens')->where('token', $token)->exists();

        if ($tokenExists) {
            return response()->json(['message' => 'Token is valid'], 200);
        } else {
            return response()->json(['message' => 'Invalid token'], 401);
        }
    }

    
        
}
