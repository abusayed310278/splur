<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriberController extends Controller
{

    public function store(Request $request)
    {
        // Validate only the email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 400);
        }

        // Create user with subscriber flag, and default values for required fields
        $subscriber = User::create([
            'email' => $request->email,
            'subscriber' => true,
            'password' => bcrypt(''),  // required by the users table
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscribed successfully.',
            'data' => $subscriber
        ], 201);
    }
}
