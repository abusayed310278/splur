<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File; // Add this at the top of your controller



class ContentController extends Controller
{

    // public function indexFrontend($cat_id)
    // {
    //     try {
    //         // Get all contents where category_id matches
    //         $contents = Content::with(['category', 'subcategory'])
    //             ->where('category_id', $cat_id)
    //             ->latest()
    //             ->take(4)
    //             ->get();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Contents fetched successfully.',
    //             'data' => $contents,
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Fetching contents failed: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to fetch contents.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function indexFrontend($cat_id)
    {
        try {
            // Get latest 4 contents for the given category
            $contents = Content::with(['category', 'subcategory'])
                ->where('category_id', $cat_id)
                ->latest()
                ->take(4)
                ->get();

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




    // public function index($cat_id, $sub_id, $id)
    // {
    //     try {
    //         $content = Content::where('category_id', $cat_id)
    //             ->where('subcategory_id', $sub_id)
    //             ->where('id', $id)
    //             ->firstOrFail();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Content fetched successfully.',
    //             'data' => $content,
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Content fetch failed: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to fetch content.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function index($cat_id, $sub_id, $id)
    {
        try {
            $content = Content::where('category_id', $cat_id)
                ->where('subcategory_id', $sub_id)
                ->where('id', $id)
                ->firstOrFail();

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






    // public function indexForSubCategory($cat_id, $sub_id)
    // {
    //     try {
    //         // Fetch paginated content
    //         $contents = Content::where('category_id', $cat_id)
    //             ->where('subcategory_id', $sub_id)
    //             ->orderBy('date', 'desc')
    //             ->paginate(10); // paginate with 10 items per page

    //         return response()->json([
    //             'status' => true,
    //             'data' => $contents->items(),
    //             'meta' => [
    //                 'current_page' => $contents->currentPage(),
    //                 'per_page' => $contents->perPage(),
    //                 'total_items' => $contents->total(),
    //                 'total_pages' => $contents->lastPage(),
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Fetching contents by category and subcategory failed: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to fetch contents.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function indexForSubCategory($cat_id, $sub_id)
    {
        try {
            $contents = Content::where('category_id', $cat_id)
                ->where('subcategory_id', $sub_id)
                ->orderBy('date', 'desc')
                ->paginate(10);

            // Map over items to add full URLs to image fields
            $data = $contents->getCollection()->transform(function ($item) {
                $item->image1_url = $item->image1 ? url($item->image1) : null;
                $item->advertising_image_url = $item->advertising_image ? url($item->advertising_image) : null;
                return $item;
            });

            return response()->json([
                'status' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $contents->currentPage(),
                    'per_page' => $contents->perPage(),
                    'total_items' => $contents->total(),
                    'total_pages' => $contents->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching contents by category and subcategory failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch contents.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





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
            'image1' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
            'advertising_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
            // omit tags here intentionally
        ]);

        try {
            // Handle image1 upload
            if ($request->hasFile('image1')) {
                $file = $request->file('image1');
                $image1Name = time() . '_image1.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Blogs'), $image1Name);
                $validated['image1'] = 'uploads/Blogs/' . $image1Name;
            }

            // Handle advertising_image upload
            if ($request->hasFile('advertising_image')) {
                $file = $request->file('advertising_image');
                $advertisingImageName = time() . '_advertising.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Blogs'), $advertisingImageName);
                $validated['advertising_image'] = 'uploads/Blogs/' . $advertisingImageName;
            }

            // Handle tags separately outside validation
            $tagsInput = $request->input('tags');

            if (is_string($tagsInput)) {
                // if tags come as a comma-separated string, convert to array
                $tagsArray = array_filter(array_map('trim', explode(',', $tagsInput)));
            } elseif (is_array($tagsInput)) {
                $tagsArray = $tagsInput;
            } else {
                $tagsArray = null;
            }

            $validated['tags'] = $tagsArray;

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
        // Find the content or fail
        $content = Content::findOrFail($id);

        // Validate all except tags
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:sub_categories,id',
            'heading' => 'nullable|string',
            'author' => 'nullable|string',
            'date' => 'nullable|date',
            'sub_heading' => 'nullable|string',
            'body1' => 'nullable|string',
            'image1' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
            'advertising_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10248',
            // omit tags intentionally
        ]);

        try {
            // Handle image1 upload
            if ($request->hasFile('image1')) {
                $file = $request->file('image1');
                $image1Name = time() . '_image1.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Blogs'), $image1Name);
                $validated['image1'] = 'uploads/Blogs/' . $image1Name;

                // Optionally delete old image
                // if ($content->image1) File::delete(public_path($content->image1));
            }

            // Handle advertising_image upload
            if ($request->hasFile('advertising_image')) {
                $file = $request->file('advertising_image');
                $advertisingImageName = time() . '_advertising.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/Blogs'), $advertisingImageName);
                $validated['advertising_image'] = 'uploads/Blogs/' . $advertisingImageName;

                // Optionally delete old image
                // if ($content->advertising_image) File::delete(public_path($content->advertising_image));
            }

            // Handle tags separately outside validation
            $tagsInput = $request->input('tags');

            if (is_string($tagsInput)) {
                $tagsArray = array_filter(array_map('trim', explode(',', $tagsInput)));
            } elseif (is_array($tagsInput)) {
                $tagsArray = $tagsInput;
            } else {
                $tagsArray = null;
            }

            $validated['tags'] = $tagsArray;

            // Update the content with validated data
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

            // Delete image1 from public path if exists
            if ($content->image1 && File::exists(public_path($content->image1))) {
                File::delete(public_path($content->image1));
            }

            // Delete advertising_image from public path if exists
            if ($content->advertising_image && File::exists(public_path($content->advertising_image))) {
                File::delete(public_path($content->advertising_image));
            }

            // Delete content from DB
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
}
