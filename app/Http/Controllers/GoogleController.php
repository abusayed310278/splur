<?php


namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;


class GoogleController extends Controller
{
    public function process(Request $request)
    {
  
        $validated = $request->validate([
            'email' => 'required|email',
            'googleAuthentication' => 'required|boolean',
            'name' => 'required|string|max:255',
        ]);
         

        // If googleAuthentication is true, proceed with login/create
        if ($validated['googleAuthentication']) {

            // Check if user exists
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                // Create user
                $user = User::create([
                    'first_name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => bcrypt(Str::random(16)), // dummy password
                ]);
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'status' => true,
                'token' => $token,
                'user' => $user,
            ]);
        }

        // If googleAuthentication is false
        return response()->json([
            'status' => false,
            'message' => 'Google login not requested.',
        ], 400);
    }
}

