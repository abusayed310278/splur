<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterApiController extends Controller
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
        try {
            $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'nullable|string',
                'file' => 'nullable|file|mimes:pdf,html,txt',
            ]);

            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('newsletters');
            }

            $newsletter = Newsletter::create([
                'subject' => $request->subject,
                'message' => $request->message,
                'file_path' => $filePath,
            ]);

            // Send to all subscribers
            // return 1;
            $subscribers = Subscriber::all();
            foreach ($subscribers as $subscriber) {
                
                Mail::to($subscriber->email)->queue(new NewsletterMail($newsletter));
            
            }
                // Mail::to('abusayed310278@gmail.com')->queue(new NewsletterMail($newsletter));

            return response()->json([
                'success' => true,
                'message' => 'Newsletter sent successfully.',
                'data' => $newsletter,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Newsletter store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send newsletter.',
                'error' => $e->getMessage()
            ], 500);
        }
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
