<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriberController extends Controller
{
    public function showSubscribers(Request $request): JsonResponse
    {
        try {
            // Get dynamic pagination count or default to 10
            $perPage = $request->input('paginate_count', 10);

            // Fetch subscribers with id and email, paginated
            $subscribers = Subscriber::select('id', 'email','created_at')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $subscribers->items(),
                'meta' => [
                    'current_page' => $subscribers->currentPage(),
                    'per_page' => $subscribers->perPage(),
                    'total' => $subscribers->total(),
                    'last_page' => $subscribers->lastPage(),
                ]
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
