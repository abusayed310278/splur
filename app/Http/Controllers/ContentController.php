<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Content;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;  // Add this at the top of your controller
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    public function viewPosts($user_id)
    {
        try {
            // Get all content for this user
            $contents = Content::where('user_id', $user_id)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($contents->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No posts found for this user.',
                    'data' => [],
                ], 404);
            }

            // Add full image URLs for each content record
            $contents->transform(function ($content) {
                $content->image1_url = $content->image1 ? url('uploads/content/' . $content->image1) : null;
                $content->advertising_image_url = $content->advertising_image ? url('uploads/content/' . $content->advertising_image) : null;

                // Add category and subcategory names directly to the content object
                $content->category_name = $content->category ? $content->category->name : null;
                $content->sub_category_name = $content->subcategory ? $content->subcategory->name : null;

                // Optionally hide original relationships
                unset($content->category, $content->subcategory);
                return $content;
            });

            return response()->json([
                'success' => true,
                'message' => 'User posts fetched successfully.',
                'data' => $contents,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user posts: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching posts.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function HomeCategoryContent($cat_name)
    {
        try {
            // Find the category by name (case-insensitive)
            $category = Category::where('category_name', 'like', $cat_name)->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.',
                ], 404);
            }

            // Get latest 15 contents for that category with related category and subcategory
            $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
                ->where('category_id', $category->id)
                ->where('status', 'active')
                ->latest()
                ->take(15)
                ->get()
                ->map(function ($content) {
                    return [
                        'id' => $content->id,
                        'category_id' => $content->category_id,
                        'subcategory_id' => $content->subcategory_id,
                        'category_name' => optional($content->category)->category_name,
                        'sub_category_name' => optional($content->subcategory)->name,
                        'heading' => $content->heading,
                        'author' => $content->author,
                        'date' => $content->date,
                        'sub_heading' => $content->sub_heading,
                        'body1' => $content->body1,
                        'image1' => $content->image1,
                        'advertising_image' => $content->advertising_image,
                        'tags' => $content->tags,
                        'created_at' => $content->created_at,
                        'updated_at' => $content->updated_at,
                        'imageLink' => $content->imageLink,
                        'advertisingLink' => $content->advertisingLink,
                        'user_id' => $content->user_id,
                        'status' => $content->status,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Latest 15 contents for category fetched successfully.',
                'category_id' => $category->id,
                'category_name' => $category->category_name,
                'data' => $contents,
            ], 200);
        } catch (\Exception $e) {
            Log::error('HomeCategoryContent Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category contents.',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
            ], 500);
        }
    }

    public function HomeContent()
    {
        try {
            $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
                ->where('status', 'active')
                ->latest()
                ->take(15)
                ->get()
                ->map(function ($content) {
                    return [
                        'id' => $content->id,
                        'category_id' => $content->category_id,
                        'subcategory_id' => $content->subcategory_id,
                        'category_name' => optional($content->category)->category_name,
                        'sub_category_name' => optional($content->subcategory)->name,
                        'heading' => $content->heading,
                        'author' => $content->author,
                        'date' => $content->date,
                        'sub_heading' => $content->sub_heading,
                        'body1' => $content->body1,
                        'image1' => $content->image1,
                        'advertising_image' => $content->advertising_image,
                        'tags' => $content->tags,
                        'created_at' => $content->created_at,
                        'updated_at' => $content->updated_at,
                        'imageLink' => $content->imageLink,
                        'advertisingLink' => $content->advertisingLink,
                        'user_id' => $content->user_id,
                        'status' => $content->status,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Latest 15 contents fetched successfully.',
                'data' => $contents,
            ], 200);
        } catch (\Exception $e) {
            Log::error('HomeContent Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contents.',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
            ], 500);
        }
    }

    public function landingPage6thPageBottomPortion()
    {
        // Get the 4th latest category (by created_at descending)
        $fourthLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(3)
            ->first();

        if (!$fourthLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Fourth latest category not found.'
            ]);
        }

        // Fetch contents: skip latest one, take next 4
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $fourthLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->skip(1)
            ->take(5)
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found after skipping the latest content for the 4th latest category.'
            ]);
        }

        // Transform the data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $fourthLatestCategory->id,
                'name' => $fourthLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function landingPage6thPageTopPortion()
    {
        // Get the 4th latest category (by created_at descending)
        $fourthLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(3)
            ->first();

        if (!$fourthLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Fourth latest category not found.'
            ]);
        }

        // Fetch all active contents for this category
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $fourthLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found for the 4th latest category.'
            ]);
        }

        // Transform the data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $fourthLatestCategory->id,
                'name' => $fourthLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function landingPage5thPageBottomPortion()
    {
        // Get the 4th latest category (by created_at descending)
        $fourthLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(3)
            ->first();

        if (!$fourthLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Fourth latest category not found.'
            ]);
        }

        // Fetch contents: skip latest one, take next 4
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $fourthLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->skip(1)
            ->take(5)
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found after skipping the latest content for the 4th latest category.'
            ]);
        }

        // Transform the data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $fourthLatestCategory->id,
                'name' => $fourthLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function landingPage5thPageTopPortion()
    {
        // Get the 4th latest category (by created_at descending)
        $fourthLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(3)
            ->first();

        if (!$fourthLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Fourth latest category not found.'
            ]);
        }

        // Fetch all active contents for this category
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $fourthLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found for the 4th latest category.'
            ]);
        }

        // Transform the data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $fourthLatestCategory->id,
                'name' => $fourthLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function landingPage4thPageBottomPortion()
    {
        // Get the 4th latest category (by created_at descending)
        $fourthLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(3)
            ->first();

        if (!$fourthLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Fourth latest category not found.'
            ]);
        }

        // Fetch contents: skip latest one, take next 4
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $fourthLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->skip(1)
            ->take(4)
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found after skipping the latest content for the 4th latest category.'
            ]);
        }

        // Transform the data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $fourthLatestCategory->id,
                'name' => $fourthLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function landingPage4thPageTopPortion()
    {
        // Get the 4th latest category (by created_at descending)
        $fourthLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(3)
            ->first();

        if (!$fourthLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Fourth latest category not found.'
            ]);
        }

        // Fetch all active contents for this category
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $fourthLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found for the 4th latest category.'
            ]);
        }

        // Transform the data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $fourthLatestCategory->id,
                'name' => $fourthLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function landingPage3rdPageBottomPortion()
    {
        // Get the 3rd latest category (by created_at descending)
        $thirdLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(2)
            ->first();

        if (!$thirdLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Third latest category not found.'
            ]);
        }

        // Fetch contents skipping the latest (skip 1) and take next 4
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $thirdLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->skip(1)  // skip the latest content
            ->take(4)  // get next 4 contents
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found after skipping the latest content for the 3rd latest category.'
            ]);
        }

        // Transform the data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $thirdLatestCategory->id,
                'name' => $thirdLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function landingPage3rdPageTopPortion()
    {
        // Get the third latest category by created_at
        $thirdLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(1)
            ->first();

        if (!$thirdLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Third latest category not found.'
            ]);
        }

        // Get 4 active contents for that category
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $thirdLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found for the 3rd latest category.'
            ]);
        }

        // Transform data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $thirdLatestCategory->id,
                'name' => $thirdLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function landingPage2ndPageBottomPortion()
    {
        // Get the 2nd latest category (by created_at descending)
        $secondLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(1)
            ->first();

        if (!$secondLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Second latest category not found.'
            ]);
        }

        // Fetch contents skipping the latest (skip 1) and take next 4
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $secondLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->skip(1)  // skip the latest content
            ->take(4)  // get next 4 contents
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found after skipping the latest content for the 2nd latest category.'
            ]);
        }

        // Transform the data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $secondLatestCategory->id,
                'name' => $secondLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function landingPage2ndPageTopPortion()
    {
        // Get the 2nd latest category (by created_at or id)
        $secondLatestCategory = Category::orderBy('created_at', 'desc')
            ->skip(1)
            ->first();

        if (!$secondLatestCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Second latest category not found.'
            ]);
        }

        // Fetch all active content for the 2nd latest category
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $secondLatestCategory->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($contents->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active content found for the 2nd latest category.'
            ]);
        }

        // Transform and return the data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'category' => [
                'id' => $secondLatestCategory->id,
                'name' => $secondLatestCategory->category_name,
            ],
            'data' => $transformed,
        ]);
    }

    public function showCategoryExcept8LatestContent($cat_id, Request $request)
    {
        try {
            $validated = $request->validate([
                'paginate_count' => 'nullable|integer|min:1',
            ]);

            $paginate_count = $validated['paginate_count'] ?? 10;

            // Get the IDs of the latest 8 contents
            $latestEightIds = Content::where('category_id', $cat_id)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->pluck('id');

            // Fetch paginated contents excluding those 8
            $query = Content::with(['category', 'subcategory'])
                ->where('category_id', $cat_id)
                ->where('status', 'active')
                ->whereNotIn('id', $latestEightIds)
                ->orderBy('created_at', 'desc');

            $contents = $query->paginate($paginate_count);

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

            $contents->setCollection($transformedData);

            return response()->json([
                'success' => true,
                'data' => $contents,
                'current_page' => $contents->currentPage(),
                'total_pages' => $contents->lastPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch paginated content after excluding latest 8: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch content.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showCategoryExcept5LatestContent($cat_id)
    {
        // Get next 3 contents after skipping top 5
        $contents = Content::with(['category', 'subcategory'])
            ->where('category_id', $cat_id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->skip(5)  // exclude latest 5
            ->take(3)  // get next 3 contents
            ->get();

        // Transform data
        $transformed = $contents->map(function ($item) {
            return [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'sub_category_id' => $item->subcategory_id,
                'category_name' => optional($item->category)->category_name,
                'sub_category_name' => optional($item->subcategory)->name,
                'heading' => $item->heading,
                'sub_heading' => $item->sub_heading,
                'author' => $item->author,
                'date' => $item->date,
                'tags' => $item->tags,
                'image1' => $item->image1 ? url($item->image1) : null,
                'imageLink' => $item->imageLink ? url($item->imageLink) : null,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $transformed,
        ]);
    }

    public function showCategoryExcept3LatestContent($cat_id)
    {
        // Get the IDs of the 3 latest contents to exclude them
        $latestThreeIds = Content::where('category_id', $cat_id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->pluck('id');

        // Fetch the next 4 contents excluding the top 3
        $otherContents = Content::where('category_id', $cat_id)
            ->where('status', 'active')
            ->whereNotIn('id', $latestThreeIds)
            ->orderBy('created_at', 'desc')
            ->take(2)
            ->get();

        // Add category and subcategory names to each item
        $transformed = $otherContents->map(function ($item) {
            $item->category_name = optional($item->category)->category_name;
            $item->sub_category_name = optional($item->subcategory)->name;
            return $item;
        });

        return response()->json([
            'status' => true,
            'data' => $transformed,
        ]);
    }

    public function showCategoryExceptLatestContent($cat_id)
    {
        // Get the latest content to exclude it
        $latestContent = Content::where('category_id', $cat_id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        // If no content exists in this category
        if (!$latestContent) {
            return response()->json([
                'status' => false,
                'message' => 'No content found for this category',
            ], 404);
        }

        // Get 2 other active contents (excluding the latest one)
        $otherContents = Content::with(['category', 'subcategory'])
            ->where('category_id', $cat_id)
            ->where('status', 'active')
            ->where('id', '!=', $latestContent->id)
            ->orderBy('created_at', 'desc')
            ->take(2)
            ->get();

        // Add category and subcategory names to each item
        $transformed = $otherContents->map(function ($item) {
            $item->category_name = optional($item->category)->category_name;
            $item->sub_category_name = optional($item->subcategory)->name;
            return $item;
        });

        return response()->json([
            'status' => true,
            'data' => $transformed,
        ]);
    }

    public function showCategoryLatestContent($cat_id)
    {
        // Get latest active content for the category, including relations
        $latestContent = Content::with(['category', 'subcategory'])
            ->where('category_id', $cat_id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestContent) {
            return response()->json([
                'status' => false,
                'message' => 'No content found for this category',
            ], 404);
        }

        // Transform response with category and subcategory names
        $transformed = [
            'id' => $latestContent->id,
            'category_id' => $latestContent->category_id,
            'sub_category_id' => $latestContent->subcategory_id,
            'category_name' => optional($latestContent->category)->category_name,
            'sub_category_name' => optional($latestContent->subcategory)->name,
            'heading' => $latestContent->heading,
            'sub_heading' => $latestContent->sub_heading,
            'author' => $latestContent->author,
            'date' => $latestContent->date,
            'tags' => $latestContent->tags,
            'image1' => $latestContent->image1 ? url($latestContent->image1) : null,
            'imageLink' => $latestContent->imageLink ? url($latestContent->imageLink) : null,
            'advertising_image' => $latestContent->advertising_image ? url($latestContent->advertising_image) : null,
            'advertisingLink' => $latestContent->advertisingLink ? url($latestContent->advertisingLink) : null,
            'body1' => $latestContent->body1,
        ];

        return response()->json([
            'status' => true,
            'data' => $transformed,
        ]);
    }

    public function showAllTags($slug, Request $request)
    {
        try {
            $validated = $request->validate([
                'paginate_count' => 'nullable|integer|min:1',
            ]);

            $paginate_count = $validated['paginate_count'] ?? 10;

            // Search for tag using LIKE (case insensitive)
            $query = Content::with(['category', 'subcategory'])
                ->where('tags', 'LIKE', '%' . $slug . '%');

            $contents = $query->orderBy('id', 'desc')->paginate($paginate_count);

            $transformedData = $contents->getCollection()->transform(function ($item) {
                return [
                    'id' => $item->id,
                    'heading' => $item->heading,
                    'sub_heading' => $item->sub_heading,
                    'author' => $item->author,
                    'date' => $item->date,
                    'category_id' => $item->category_id,
                    'sub_category_id' => $item->subcategory_id,
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

            // Replace collection with transformed data
            $contents->setCollection($transformedData);

            return response()->json([
                'success' => true,
                'data' => $contents,
                'current_page' => $contents->currentPage(),
                'total_pages' => $contents->lastPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Tag filtering failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch content by tag.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showContents()
    {
        $perPage = 10;

        $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
            ->latest()
            ->paginate($perPage);

        $transformed = $contents->getCollection()->map(function ($content) {
            return [
                'id' => $content->id,
                'category_id' => $content->category_id,
                'subcategory_id' => $content->subcategory_id,
                'category_name' => optional($content->category)->category_name,
                'sub_category_name' => optional($content->subcategory)->name,
                'heading' => $content->heading,
                'author' => $content->author,
                'date' => $content->date,
                'sub_heading' => $content->sub_heading,
                'body1' => $content->body1,
                'image1' => $content->image1,
                'advertising_image' => $content->advertising_image,
                'tags' => $content->tags,
                'created_at' => $content->created_at,
                'updated_at' => $content->updated_at,
                'imageLink' => $content->imageLink,
                'advertisingLink' => $content->advertisingLink,
                'user_id' => $content->user_id,
                'status' => $content->status,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Latest contents fetched successfully.',
            'data' => $transformed,
            'meta' => [
                'current_page' => $contents->currentPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
                'last_page' => $contents->lastPage(),
            ]
        ]);
    }

    public function landingPageTopPortion()
    {
        $latestActiveContent = Content::where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestActiveContent) {
            $latestActiveContent->makeHidden(['status']);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'latest' => $latestActiveContent,
            ],
        ]);
    }

    public function landingPageBottomPortion()
    {
        $categories = Category::with([
            'subcategories.contents' => function ($query) {
                $query
                    ->where('status', 'active')
                    ->latest()
                    ->take(5);  // Adjust limit per subcategory
            }
        ])->get();

        $data = $categories->map(function ($category) {
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'subcategories' => $category->subcategories->map(function ($subcategory) {
                    return [
                        'subcategory_id' => $subcategory->id,
                        'subcategory_name' => $subcategory->name,
                        'contents' => $subcategory->contents->makeHidden(['status']),
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function storeOrUpdateStatus(Request $request, $id)
    {
        // Validate the status field
        $request->validate([
            'status' => 'required|string|in:active,pending',  // adjust allowed values as needed
        ]);

        // Try to find existing content by ID
        $content = Content::find($id);

        if ($content) {
            // Update existing content status
            $content->status = $request->input('status');
            $content->save();

            return response()->json([
                'status' => true,
                'message' => 'Content status updated successfully.',
                'data' => $content,
            ], 200);
        } else {
            // Create new content with given id and status (optional)
            // Note: Usually ID is auto-increment and shouldn't be forced
            // If you want to create a new record without id, remove $id assignment

            $content = new Content();
            $content->id = $id;  // Only if your model supports manual ID assignment
            $content->status = $request->input('status');
            // You may need to fill other required fields here to avoid DB errors
            $content->save();

            return response()->json([
                'status' => true,
                'message' => 'Content created with status successfully.',
                'data' => $content,
            ], 201);
        }
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

    // public function index($cat_id, $sub_id, $id)
    // {
    //     try {
    //         $content = Content::where('category_id', $cat_id)
    //             ->where('subcategory_id', $sub_id)
    //             ->where('id', $id)
    //             ->first();

    //         // If content not found
    //         if (!$content) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'No Content Found.',
    //                 'data' => null,
    //             ], 404);
    //         }

    //         // Add full URLs for images
    //         $content->image1_url = $content->image1 ? url($content->image1) : null;
    //         $content->advertising_image_url = $content->advertising_image ? url($content->advertising_image) : null;

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
            $content = Content::with(['user:id,id,description,first_name,facebook_link,profile_pic,instagram_link,youtube_link,twitter_link'])
                ->where('category_id', $cat_id)
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

            // Format user's profilePic as full URL with camelCase
            if ($content->user && $content->user->profile_pic) {
                $content->user->profilePic = url('uploads/ProfilePics/' . $content->user->profile_pic);
            } else {
                $content->user->profilePic = null;
            }

            unset($content->user->profile_pic);  // optional: remove snake_case key

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

            // Role-based access control
            if (auth()->check()) {
                $user = auth()->user();
                $role = $user->roles;

                if ($role === 'author') {
                    // Author can only see their own content
                    $query->where('user_id', $user->id);
                } elseif ($role === 'user') {
                    // User role can't see any content
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'current_page' => 1,
                        'total_pages' => 0,
                        'per_page' => $paginate_count,
                        'total' => 0,
                        'message' => 'No contents available for your role.'
                    ], 200);
                }
                // Admin and editor see all content
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
                    'category_id' => $item->category_id,
                    'subcategory_id' => $item->subcategory_id,
                    'category_name' => optional($item->category)->category_name,
                    'sub_category_name' => optional($item->subcategory)->name,
                    'image1' => $item->image1 ? url($item->image1) : null,
                    'advertising_image' => $item->advertising_image ? url($item->advertising_image) : null,
                    'advertisingLink' => $item->advertisingLink ? url($item->advertisingLink) : null,
                    'imageLink' => $item->imageLink ? url($item->imageLink) : null,
                    'status' => $item->status,
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
            // 'status' => 'required|in:active,pending',
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
        try {
            // Ensure user is authenticated
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Validate vote input
            $validated = $request->validate([
                'vote' => 'required|in:1,-1',  // 1 = upvote, -1 = downvote
            ]);

            // Check if comment exists
            $comment = Comment::find($commentId);
            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found.'
                ], 404);
            }

            $userId = Auth::id();

            // Update or create vote
            $vote = CommentVote::updateOrCreate(
                [
                    'user_id' => $userId,
                    'comment_id' => $commentId
                ],
                [
                    'vote' => $validated['vote']
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Vote recorded successfully.',
                'data' => $vote
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in vote method: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to record vote.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getVotes($comment_id)
    {
        try {
            // Check if the comment exists
            $comment = Comment::find($comment_id);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found.'
                ], 404);
            }

            // Count upvotes and downvotes for the comment
            $upvotes = CommentVote::where('comment_id', $comment_id)->where('vote', 1)->count();
            $downvotes = CommentVote::where('comment_id', $comment_id)->where('vote', -1)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'comment_id' => $comment_id,
                    'upvotes' => $upvotes,
                    'downvotes' => $downvotes,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getVotes: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vote data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
