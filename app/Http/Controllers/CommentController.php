<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Content;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;  // ✅ Import Validator

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index($content_id)
    // {
    //     // Validate content_id exists
    //     if (!\App\Models\Content::where('id', $content_id)->exists()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Invalid content ID.',
    //         ], 404);
    //     }
    //     $comments = Comment::where('content_id', $content_id)
    //         ->with(['user:id,first_name,last_name,email'])
    //         ->latest()
    //         ->get()
    //         ->map(function ($comment) {
    //             $first = $comment->user->first_name;
    //             $last = $comment->user->last_name;
    //             $name = trim("$first $last");
    //             if (empty($name)) {
    //                 $name = $comment->user->email;
    //             }
    //             return [
    //                 'name' => $name,
    //                 'comment' => $comment->comment,
    //                 'created_at' => $comment->created_at->toDateTimeString(),
    //             ];
    //         });
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Comments fetched successfully.',
    //         'data' => $comments
    //     ]);
    // }
    public function index($content_id)
    {
        // Validate content_id exists
        if (!Content::where('id', $content_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid content ID.',
            ], 404);
        }

        $comments = Comment::where('content_id', $content_id)
            ->with(['user:id,first_name,last_name,email'])
            ->latest()
            ->get()
            ->map(function ($comment) {
                $first = trim($comment->user->first_name);
                $last = trim($comment->user->last_name);
                $name = trim("$first $last");

                if (empty($name)) {
                    $email = $comment->user->email;
                    $name = ucfirst(str_replace('.', ' ', strstr($email, '@', true)));
                }

                // Count upvotes and downvotes
                $upvotes = $comment->votes->where('vote', 1)->count();
                $downvotes = $comment->votes->where('vote', -1)->count();

                return [
                    'id' => $comment->id,
                    'name' => $name,
                    'comment' => $comment->comment,
                    'upvotes' => $upvotes,
                    'downvotes' => $downvotes,
                    'created_at' => $comment->created_at->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Comments fetched successfully.',
            'data' => $comments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Validate input
        $validator = Validator::make($request->all(), [
            'content_id' => 'required|exists:contents,id',
            'comment' => 'required|string',
            'email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 400);
        }

        // Case 1: Authenticated user
        if ($user) {
            $comment = Comment::create([
                'user_id' => $user->id,
                'content_id' => $request->content_id,
                'comment' => $request->comment,
            ]);
        }
        // Case 2: Subscriber (user with email and subscriber = true)
        else if ($request->filled('email')) {
            $subscriberUser = User::where('email', $request->email)
                ->where('subscriber', true)
                ->first();

            if ($subscriberUser) {
                $comment = Comment::create([
                    'user_id' => $subscriberUser->id,
                    'content_id' => $request->content_id,
                    'comment' => $request->comment,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Only logged-in users or users marked as subscribers can comment.'
                ], 403);
            }
        }
        // Case 3: Not allowed
        else {
            return response()->json([
                'success' => false,
                'message' => 'Only logged-in users or users marked as subscribers can comment.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment created successfully.',
            'data' => $comment
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
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

    public function storeForSubscriber(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'content_id' => 'required|exists:contents,id',
            'comment' => 'required|string',
            'email' => 'required|email|exists:users,email',  // Validate directly against users table
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 400);
        }

        // Fetch the user using email
        $user = User::where('email', $request->email)->first();

        // Create comment using the authenticated user
        $comment = Comment::create([
            'user_id' => $user->id,
            'content_id' => $request->content_id,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment posted successfully.',
            'data' => $comment,
        ], 201);
    }
}
