<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;  // ✅ Import Validator

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Ensure user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please log in to comment.'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'content_id' => 'required|exists:contents,id',
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 400);
        }

        $comment = Comment::create([
            'user_id' => auth()->id(),  // ✅ Safe, logged-in user ID
            'content_id' => $request->content_id,
            'comment' => $request->comment,
        ]);

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
}
