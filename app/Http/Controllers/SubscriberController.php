<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Validator;

class SubscriberController extends Controller
{
    public function store(Request $request)
    {
        // Validate only email
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

        $subscriber = Subscriber::create([
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscribed successfully.',
            'data' => $subscriber
        ], 201);
    }
}
