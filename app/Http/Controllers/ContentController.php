<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;  // Add this at the top of your controller
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    public function showContents()
    {
        $perPage = 10;

        $contents = Content::latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Latest contents fetched successfully.',
            'data' => $contents->items(),
            'meta' => [
                'current_page' => $contents->currentPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
                'last_page' => $contents->lastPage(),
            ]
        ]);
    }

    public function landingPage()
    {
        $latestActiveContent = Content::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestActiveContent) {
            $latestActiveContent->makeHidden(['user_id', 'category_id', 'subcategory_id', 'status']);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'latest' => $latestActiveContent,
            ],
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        // Validate the status field
        $request->validate([
            'status' => 'required|string|in:active,pending',  // adjust allowed values as needed
        ]);

        // Find the content by ID
        $content = Content::find($id);

        if (!$content) {
            return response()->json([
                'status' => false,
                'message' => 'Content not found.',
            ], 404);
        }

        // Update the status
        $content->status = $request->input('status');
        $content->save();

        return response()->json([
            'status' => true,
            'message' => 'Content status updated successfully.',
            'data' => $content
        ], 200);
    }

    public function relatedContents($cat_id, $sub_id, $contentId)
    {
        try {
            // Get latest 10 related contents (same category and subcategory, excluding current)
            $contents = Content::with(['category', 'subcategory'])
                ->where('category_id', $cat_id)
                ->where('subcategory_id', $sub_id)
                ->where('id', '!=', $contentId)
                ->latest()
                ->take(10)
                ->get();

            if ($contents->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No related content found.',
                    'data' => [],
                ], 404);
            }

            // Add full image URLs
            $contents->transform(function ($content) {
                $content->image1_url = $content->image1 ? url($content->image1) : null;
                $content->advertising_image_url = $content->advertising_image ? url($content->advertising_image) : null;
                return $content;
            });

            return response()->json([
                'status' => true,
                'message' => 'Related contents fetched successfully.',
                'data' => $contents,
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching related contents failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch related contents.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexFrontend($cat_id)
    {
        try {
            // Get latest 4 contents for the given category
            $contents = Content::with(['category', 'subcategory'])
                ->where('category_id', $cat_id)
                ->latest()
                ->take(10)
                ->get();

            // Check if contents are empty
            if ($contents->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No Content Found.',
                    'data' => [],
                ], 404);
            }

            // Add full image URLs to each content
            $contents->transform(function ($content) {
                $content->image1_url = $content->image1 ? url($content->image1) : null;
                $content->advertising_image_url = $content->advertising_image ? url($content->advertising_image) : null;
                return $content;
            });

            return response()->json([
                'status' => true,
                'message' => 'Contents fetched successfully.',
                'data' => $contents,
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching contents failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch contents.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index($cat_id, $sub_id, $id)
    {
        try {
            $content = Content::where('category_id', $cat_id)
                ->where('subcategory_id', $sub_id)
                ->where('id', $id)
                ->first();

            // If content not found
            if (!$content) {
                return response()->json([
                    'status' => false,
                    'message' => 'No Content Found.',
                    'data' => null,
                ], 404);
            }

            // Add full URLs for images
            $content->image1_url = $content->image1 ? url($content->image1) : null;
            $content->advertising_image_url = $content->advertising_image ? url($content->advertising_image) : null;

            return response()->json([
                'status' => true,
                'message' => 'Content fetched successfully.',
                'data' => $content,
            ]);
        } catch (\Exception $e) {
            Log::error('Content fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function indexForSubCategory($cat_id, $sub_id, Request $request)
    {
        try {
            // Validate query parameters
            $validated = $request->validate([
                'paginate_count' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
            ]);

            $paginate_count = $validated['paginate_count'] ?? 10;
            $search = $validated['search'] ?? null;

            // Base query with relationships
            $query = Content::with(['category', 'subcategory'])
                ->where('category_id', $cat_id)
                ->where('subcategory_id', $sub_id);

            // Optional search on heading
            if ($search) {
                $query->where('heading', 'like', '%' . $search . '%');
            }

            // Apply pagination
            $contents = $query->orderBy('id', 'desc')->paginate($paginate_count);

            // Transform only the items in the paginated result
            $transformedData = $contents->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'heading' => $item->heading,
                    'sub_heading' => $item->sub_heading,
                    'author' => $item->author,
                    'date' => $item->date,
                    'body1' => $item->body1,
                    'tags' => $item->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $item->tags) : null,
                    'category_name' => optional($item->category)->category_name,
                    'sub_category_name' => optional($item->subcategory)->name,
                    'image1' => $item->image1 ? url($item->image1) : null,
                    'advertising_image' => $item->advertising_image ? url($item->advertising_image) : null,
                    'advertisingLink' => $item->advertisingLink ? url($item->advertisingLink) : null,
                    'imageLink' => $item->imageLink ? url($item->imageLink) : null,
                ];
            });

            // Replace the collection with transformed data
            $contents->setCollection($transformedData);

            // Return response matching the example format
            return response()->json([
                'success' => true,
                'data' => $contents,
                'current_page' => $contents->currentPage(),
                'total_pages' => $contents->lastPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Fetching contents by category and subcategory failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contents.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function store(Request $request)
    // {
    //     // Validate everything except tags (which we'll handle separately)
    //     $validated = $request->validate([
    //         'category_id' => 'required|exists:categories,id',
    //         'subcategory_id' => 'required|exists:sub_categories,id',
    //         'heading' => 'nullable|string',
    //         'author' => 'nullable|string',
    //         'date' => 'nullable|date',
    //         'sub_heading' => 'nullable|string',
    //         'body1' => 'nullable|string',
    //         'image1' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
    //         'advertising_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
    //         // omit tags here intentionally
    //     ]);

    //     try {
    //         // Handle image1 upload
    //         if ($request->hasFile('image1')) {
    //             $file = $request->file('image1');
    //             $image1Name = time() . '_image1.' . $file->getClientOriginalExtension();
    //             $file->move(public_path('uploads/Blogs'), $image1Name);
    //             $validated['image1'] = 'uploads/Blogs/' . $image1Name;
    //         } else {
    //             $validated['image1'] = null;  // Ensure image1 is set to null if not provided
    //         }

    //         // Handle advertising_image upload
    //         if ($request->hasFile('advertising_image')) {
    //             $file = $request->file('advertising_image');
    //             $advertisingImageName = time() . '_advertising.' . $file->getClientOriginalExtension();
    //             $file->move(public_path('uploads/Blogs'), $advertisingImageName);
    //             $validated['advertising_image'] = 'uploads/Blogs/' . $advertisingImageName;
    //         } else {
    //             $validated['advertising_image'] = null;  // Ensure advertising_image is set to null if not provided
    //         }

    //         // Handle tags separately outside validation
    //         $tagsInput = $request->input('tags');

    //         if (is_string($tagsInput)) {
    //             // if tags come as a comma-separated string, convert to array
    //             $tagsArray = array_filter(array_map('trim', explode(',', $tagsInput)));
    //         } elseif (is_array($tagsInput)) {
    //             $tagsArray = $tagsInput;
    //         } else {
    //             $tagsArray = null;
    //         }

    //         $validated['tags'] = $tagsArray;

    //         $content = Content::create($validated);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Content created successfully.',
    //             'data' => $content,
    //         ], 201);
    //     } catch (\Exception $e) {
    //         Log::error('Content creation failed: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to create content.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function update(Request $request, $id)
    // {
    //     // Find the content or fail
    //     $content = Content::findOrFail($id);

    //     // Validate all except tags
    //     $validated = $request->validate([
    //         'category_id' => 'required|exists:categories,id',
    //         'subcategory_id' => 'required|exists:sub_categories,id',
    //         'heading' => 'nullable|string',
    //         'author' => 'nullable|string',
    //         'date' => 'nullable|date',
    //         'sub_heading' => 'nullable|string',
    //         'body1' => 'nullable|string',
    //         'image1' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
    //         'advertising_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
    //         // omit tags intentionally
    //     ]);

    //     try {
    //         // Handle image1 upload
    //         if ($request->hasFile('image1')) {
    //             $file = $request->file('image1');
    //             $image1Name = time() . '_image1.' . $file->getClientOriginalExtension();
    //             $file->move(public_path('uploads/Blogs'), $image1Name);
    //             $validated['image1'] = 'uploads/Blogs/' . $image1Name;

    //             // Optionally delete old image
    //             // if ($content->image1) File::delete(public_path($content->image1));
    //         }

    //         // Handle advertising_image upload
    //         if ($request->hasFile('advertising_image')) {
    //             $file = $request->file('advertising_image');
    //             $advertisingImageName = time() . '_advertising.' . $file->getClientOriginalExtension();
    //             $file->move(public_path('uploads/Blogs'), $advertisingImageName);
    //             $validated['advertising_image'] = 'uploads/Blogs/' . $advertisingImageName;

    //             // Optionally delete old image
    //             // if ($content->advertising_image) File::delete(public_path($content->advertising_image));
    //         }

    //         // Handle tags separately outside validation
    //         $tagsInput = $request->input('tags');

    //         if (is_string($tagsInput)) {
    //             $tagsArray = array_filter(array_map('trim', explode(',', $tagsInput)));
    //         } elseif (is_array($tagsInput)) {
    //             $tagsArray = $tagsInput;
    //         } else {
    //             $tagsArray = null;
    //         }

    //         $validated['tags'] = $tagsArray;

    //         // Update the content with validated data
    //         $content->update($validated);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Content updated successfully.',
    //             'data' => $content,
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Content update failed: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to update content.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function store(Request $request)
    {
        // Validate everything except tags (which we'll handle separately)
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:sub_categories,id',
            'heading' => 'nullable|string',
            'author' => 'nullable|string',
            'date' => 'nullable|date',
            'sub_heading' => 'nullable|string',
            'body1' => 'nullable|string',
            'image1' => 'nullable',
            'advertising_image' => 'nullable',
            'imageLink' => 'nullable|string',  // ✅ added
            'advertisingLink' => 'nullable|string',  // ✅ added
            // omit tags here intentionally
        ]);

        try {
            // Handle image1 upload
            if ($request->hasFile('image1')) {
                $file = $request->file('image1');
                $image1Name = time() . '_image1.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Blogs'), $image1Name);
                $validated['image1'] = 'uploads/Blogs/' . $image1Name;
            } else {
                $validated['image1'] = null;
            }

            // Handle advertising_image upload
            if ($request->hasFile('advertising_image')) {
                $file = $request->file('advertising_image');
                $advertisingImageName = time() . '_advertising.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Blogs'), $advertisingImageName);
                $validated['advertising_image'] = 'uploads/Blogs/' . $advertisingImageName;
            } else {
                $validated['advertising_image'] = null;
            }

            // Handle tags separately
            $tagsInput = $request->input('tags');

            if (is_string($tagsInput)) {
                $tagsArray = array_filter(array_map('trim', explode(',', $tagsInput)));
            } elseif (is_array($tagsInput)) {
                $tagsArray = $tagsInput;
            } else {
                $tagsArray = null;
            }

            $validated['tags'] = $tagsArray;

            // ✅ Add authenticated user ID
            $validated['user_id'] = auth()->id();

            $content = Content::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Content created successfully.',
                'data' => $content,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Content creation failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to create content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $content = Content::find($id);

        if (!$content) {
            return response()->json([
                'status' => false,
                'message' => 'No Available Content Found.',
            ], 404);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:sub_categories,id',
            'heading' => 'nullable|string',
            'author' => 'nullable|string',
            'date' => 'nullable|date',
            'sub_heading' => 'nullable|string',
            'body1' => 'nullable|string',
            'image1' => 'nullable',
            'advertising_image' => 'nullable',
            'imageLink' => 'nullable|string',
            'advertisingLink' => 'nullable|string',
        ]);

        try {
            // Handle image1 upload
            if ($request->hasFile('image1')) {
                if ($content->image1 && File::exists(public_path($content->image1))) {
                    File::delete(public_path($content->image1));
                }

                $file = $request->file('image1');
                $image1Name = time() . '_image1.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Blogs'), $image1Name);
                $validated['image1'] = 'uploads/Blogs/' . $image1Name;
            }

            // Handle advertising_image upload
            if ($request->hasFile('advertising_image')) {
                if ($content->advertising_image && File::exists(public_path($content->advertising_image))) {
                    File::delete(public_path($content->advertising_image));
                }

                $file = $request->file('advertising_image');
                $advertisingImageName = time() . '_advertising.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Blogs'), $advertisingImageName);
                $validated['advertising_image'] = 'uploads/Blogs/' . $advertisingImageName;
            }

            // Handle tags
            $tagsInput = $request->input('tags');
            if (is_string($tagsInput)) {
                $tagsArray = array_filter(array_map('trim', explode(',', $tagsInput)));
            } elseif (is_array($tagsInput)) {
                $tagsArray = $tagsInput;
            } else {
                $tagsArray = null;
            }

            $validated['tags'] = $tagsArray;

            // Map camelCase to snake_case for DB fields
            $validated['image_link'] = $validated['imageLink'] ?? $content->image_link;
            $validated['advertising_link'] = $validated['advertisingLink'] ?? $content->advertising_link;
            unset($validated['imageLink'], $validated['advertisingLink']);

            // ✅ Add authenticated user ID
            $validated['user_id'] = auth()->id();

            $content->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Content updated successfully.',
                'data' => $content,
            ]);
        } catch (\Exception $e) {
            \Log::error('Content update failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to update content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $content = Content::findOrFail($id);

            // Delete image1 if exists
            if ($content->image1 && File::exists(public_path($content->image1))) {
                File::delete(public_path($content->image1));
            }

            // Delete advertising_image if exists
            if ($content->advertising_image && File::exists(public_path($content->advertising_image))) {
                File::delete(public_path($content->advertising_image));
            }

            // Delete content
            $content->delete();

            return response()->json([
                'status' => true,
                'message' => 'Content deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Content deletion failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function update(Request $request, $id)
    // {
    //     // Find the content by ID
    //     $content = Content::find($id);

    //     // If content is not found, return custom message
    //     if (!$content) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No Available Content Found.',
    //         ], 404);
    //     }

    //     // Validate all except tags
    //     $validated = $request->validate([
    //         'category_id' => 'required|exists:categories,id',
    //         'subcategory_id' => 'required|exists:sub_categories,id',
    //         'heading' => 'nullable|string',
    //         'author' => 'nullable|string',
    //         'date' => 'nullable|date',
    //         'sub_heading' => 'nullable|string',
    //         'body1' => 'nullable|string',
    //         'image1' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
    //         'advertising_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
    //     ]);

    //     try {
    //         // Handle image1 upload
    //         if ($request->hasFile('image1')) {
    //             $file = $request->file('image1');
    //             $image1Name = time() . '_image1.' . $file->getClientOriginalExtension();
    //             $file->move(public_path('uploads/Blogs'), $image1Name);
    //             $validated['image1'] = 'uploads/Blogs/' . $image1Name;
    //         }

    //         // Handle advertising_image upload
    //         if ($request->hasFile('advertising_image')) {
    //             $file = $request->file('advertising_image');
    //             $advertisingImageName = time() . '_advertising.' . $file->getClientOriginalExtension();
    //             $file->move(public_path('uploads/Blogs'), $advertisingImageName);
    //             $validated['advertising_image'] = 'uploads/Blogs/' . $advertisingImageName;
    //         }

    //         // Handle tags
    //         $tagsInput = $request->input('tags');
    //         if (is_string($tagsInput)) {
    //             $tagsArray = array_filter(array_map('trim', explode(',', $tagsInput)));
    //         } elseif (is_array($tagsInput)) {
    //             $tagsArray = $tagsInput;
    //         } else {
    //             $tagsArray = null;
    //         }

    //         $validated['tags'] = $tagsArray;

    //         // Update content
    //         $content->update($validated);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Content updated successfully.',
    //             'data' => $content,
    //         ]);
    //     } catch (\Exception $e) {
    //         \Log::error('Content update failed: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to update content.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function destroy($id)
    // {
    //     try {
    //         $content = Content::findOrFail($id);

    //         // Delete image1 from public path if exists
    //         if ($content->image1 && File::exists(public_path($content->image1))) {
    //             File::delete(public_path($content->image1));
    //         }

    //         // Delete advertising_image from public path if exists
    //         if ($content->advertising_image && File::exists(public_path($content->advertising_image))) {
    //             File::delete(public_path($content->advertising_image));
    //         }

    //         // Delete content from DB
    //         $content->delete();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Content deleted successfully.',
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Content deletion failed: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to delete content.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function vote(Request $request, $commentId)
    {
        $request->validate([
            'vote' => 'required|in:1,-1',
        ]);

        $vote = CommentVote::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'comment_id' => $commentId,
            ],
            [
                'vote' => $request->vote,
            ]
        );

        return response()->json([
            'message' => 'Vote saved',
            'vote' => $vote,
        ]);
    }

    public function getVotes(Comment $comment)
    {
        return response()->json([
            'comment_id' => $comment->id,
            'upvotes' => $comment->votes()->where('vote', 1)->count(),
            'downvotes' => $comment->votes()->where('vote', -1)->count(),
            'user_vote' => auth()->check()
                ? $comment->votes()->where('user_id', auth()->id())->value('vote')
                : null
        ]);
    }
}
