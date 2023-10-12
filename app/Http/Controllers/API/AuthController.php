<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    //

    public function login(Request $request){
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string',
        ];

        // Validate the incoming request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        $credentials = $request->only(['email', 'password']);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $access_token = $user->createToken('authToken')->plainTextToken;
            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'token' => $access_token
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Username or password are incorrect'
            ]);
        }
    }


    public function register(Request $request) {

        //dd($request->all());
        // Define validation rules
        $rules = [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ];

        // Validate the incoming request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Check if user already exists
        $user = User::where('email', $request->email)->first();
        if (is_null($user)) {
            // User does not exist, create the user
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->save();

            $token = $user->createToken('authToken')->plainTextToken;
            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'token' => $token
            ]);
        } else {
            // User already exists
            return response()->json([
                'status' => false,
                'message' => 'User already exists'
            ]);
        }
    }


    public function rememberMe(Request $request)
    {
        // Check if the user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // Set a new token expiration time (for example, a longer period)
            $user->tokens->each(function ($token) {
                $token->forceFill([
                    'expires_at' => now()->addDays(30), // Extend token expiration time by 30 days
                ])->save();
            });

            // Return the existing token
            $token = $user->currentAccessToken();

            return response()->json(['access_token' => $token->plainTextToken]);
        }

        return response()->json(['message' => 'User not authenticated'], 401);
    }


    public function setToken(Request $request)
    {
        $user = Auth::user();
        $newToken = $request->input('new_token');
    
        // Update the user's token with the provided value
        $user->remember_token = $newToken;
        $user->save();
    
        return response()->json(['message' => 'Token updated successfully']);
    }

}

