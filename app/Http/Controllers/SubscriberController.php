<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriberController extends Controller
{
    public function showSubscribers(): JsonResponse
    {
        try {
            $emails = Subscriber::pluck('email');

            return response()->json([
                'success' => true,
                'emails' => $emails
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscribers.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Validate only the email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 400);
        }

        // Create user with subscriber flag, and default values for required fields
        $subscriber = Subscriber::create([
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
