<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Content;
use App\Models\ContentLike;
use App\Models\ContentShare;
use App\Models\Genre;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;  // For file upload type checking
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;  // Add this at the top of your controller
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;  // If you need manual validation
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Exception;

class ContentController extends Controller
{
    // for postgresql
    // public function search(Request $request)
    // {
    //     $query = $request->input('q');

    //     // Check if the input is a valid date
    //     $isDate = false;
    //     try {
    //         $isDate = $query && Carbon::parse($query);
    //     } catch (Exception $e) {
    //         $isDate = false;
    //     }

    //     $contents = Content::with(['category', 'subcategory'])
    //         ->when($query, function ($q) use ($query, $isDate) {
    //             $q->where(function ($subQuery) use ($query, $isDate) {
    //                 $subQuery
    //                     ->where('author', 'ilike', "%{$query}%")
    //                     ->orWhere(DB::raw('tags::text'), 'ilike', "%{$query}%")
    //                     ->orWhere('heading', 'ilike', "%{$query}%")
    //                     ->orWhere('sub_heading', 'ilike', "%{$query}%")
    //                     ->orWhere('body1', 'ilike', "%{$query}%")
    //                     ->when($isDate, function ($dateQuery) use ($query) {
    //                         $dateQuery->orWhereDate('date', $query);
    //                     })
    //                     ->orWhereHas('category', function ($catQuery) use ($query) {
    //                         $catQuery->where('category_name', 'ilike', "%{$query}%");
    //                     })
    //                     ->orWhereHas('subcategory', function ($subQuery) use ($query) {
    //                         $subQuery->where('name', 'ilike', "%{$query}%");
    //                     });
    //             });
    //         })
    //         ->orderBy('created_at', 'desc')
    //         ->paginate(10);

    //     return response()->json($contents);
    // }

    // for mysql

    // public function search(Request $request)
    // {
    //     $query = $request->input('q');

    //     // Check if input is a valid date
    //     $isDate = false;
    //     try {
    //         $isDate = $query && Carbon::parse($query);
    //     } catch (Exception $e) {
    //         $isDate = false;
    //     }

    //     $lowerQuery = strtolower($query);

    //     $contents = Content::with(['category', 'subcategory'])
    //         ->when($query, function ($q) use ($lowerQuery, $isDate, $query) {
    //             $q->where(function ($subQuery) use ($lowerQuery, $isDate, $query) {
    //                 $subQuery
    //                     ->whereRaw('LOWER(author) LIKE ?', ["%$lowerQuery%"])
    //                     ->orWhereRaw('LOWER(tags) LIKE ?', ["%$lowerQuery%"])  // assume tags is a string or CSV
    //                     ->orWhereRaw('LOWER(heading) LIKE ?', ["%$lowerQuery%"])
    //                     ->orWhereRaw('LOWER(sub_heading) LIKE ?', ["%$lowerQuery%"])
    //                     ->orWhereRaw('LOWER(body1) LIKE ?', ["%$lowerQuery%"])
    //                     ->orWhereHas('category', function ($catQuery) use ($lowerQuery) {
    //                         $catQuery->whereRaw('LOWER(category_name) LIKE ?', ["%$lowerQuery%"]);
    //                     })
    //                     ->orWhereHas('subcategory', function ($subCatQuery) use ($lowerQuery) {
    //                         $subCatQuery->whereRaw('LOWER(name) LIKE ?', ["%$lowerQuery%"]);
    //                     });

    //                 if ($isDate) {
    //                     $subQuery->orWhereDate('date', $query);
    //                 }
    //             });
    //         })
    //         ->orderBy('created_at', 'desc')
    //         ->paginate(10);

    //     return response()->json($contents);
    // }

    // public function stats(Request $request, Content $content)
    // {
    //     // total comments for THIS content_id (from comments table)
    //     $commentsCountForThisContent = (int) Comment::where('content_id', $content->id)->count();

    //     // global totals across ALL contents (likes + shares in one query)
    //     $totals = Content::selectRaw('
    //     COALESCE(SUM(likes_count), 0)  AS total_likes,
    //     COALESCE(SUM(shares_count), 0) AS total_shares
    // ')->first();

    //     $totalCommentsAll = (int) Comment::count();

    //     return response()->json([
    //         'success' => true,
    //         'content_id' => (int) $content->id,
    //         'likes_count' => (int) $content->likes_count,  // from contents table
    //         'shares_count' => (int) $content->shares_count,  // from contents table
    //         'comments_count' => $commentsCountForThisContent,  // from comments table
    //         'totals' => [
    //             'total_likes' => (int) $totals->total_likes,
    //             'total_shares' => (int) $totals->total_shares,
    //             'total_comments' => $totalCommentsAll,
    //         ],
    //     ]);
    // }

    // POST /api/contents/{content}/like
    // public function toggleLike(Request $request, Content $content)
    // {
    //     $user = $request->user();
    //     abort_unless($user, 401);

    //     $liked = false;

    //     DB::transaction(function () use ($content, $user, &$liked) {
    //         $existing = ContentLike::where('content_id', $content->id)
    //             ->where('user_id', $user->id)
    //             ->lockForUpdate()
    //             ->first();

    //         if ($existing) {
    //             $existing->delete();
    //             $content->where('id', $content->id)->update([
    //                 'likes_count' => DB::raw('GREATEST(likes_count - 1, 0)')
    //             ]);
    //             $liked = false;
    //         } else {
    //             ContentLike::create([
    //                 'content_id' => $content->id,
    //                 'user_id' => $user->id,
    //             ]);
    //             $content->where('id', $content->id)->update([
    //                 'likes_count' => DB::raw('likes_count + 1')
    //             ]);
    //             $liked = true;
    //         }
    //     });

    //     $content->refresh();

    //     return response()->json([
    //         'success' => true,
    //         'liked' => $liked,
    //         'likes_count' => $content->likes_count,
    //     ]);
    // }

    public function toggleLike(Request $request, Content $content)
    {
        // Handle auth up front so we don't swallow 401s in the catch block
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        try {
            $liked = false;

            DB::transaction(function () use ($content, $user, &$liked) {
                $existing = ContentLike::where('content_id', $content->id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    // Unlike
                    $existing->delete();
                    $content->where('id', $content->id)->update([
                        'likes_count' => DB::raw('GREATEST(likes_count - 1, 0)')
                    ]);
                    $liked = false;
                } else {
                    // Like
                    ContentLike::create([
                        'content_id' => $content->id,
                        'user_id' => $user->id,
                    ]);
                    $content->where('id', $content->id)->update([
                        'likes_count' => DB::raw('likes_count + 1')
                    ]);
                    $liked = true;
                }
            });

            $content->refresh();

            return response()->json([
                'success' => true,
                'message' => $liked ? 'Liked successfully.' : 'Unliked successfully.',
                'data' => [
                    'content_id' => (int) $content->id,
                    'liked' => $liked,
                    'likes_count' => (int) $content->likes_count,
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Like error', [
                'content_id' => $content->id ?? null,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to  like.',
                'error' => app()->environment('production') ? null : $e->getMessage(),
            ], 500);
        }
    }

    // POST /api/contents/{content}/share
    // public function share(Request $request, Content $content)
    // {
    //     DB::transaction(function () use ($content) {
    //         $content->where('id', $content->id)->update([
    //             'shares_count' => DB::raw('shares_count + 1')
    //         ]);
    //     });

    //     $content->refresh();

    //     return response()->json([
    //         'success' => true,
    //         'shares_count' => (int) $content->shares_count,
    //     ]);
    // }

    public function share(Request $request, Content $content)
    {
        try {
            DB::transaction(function () use ($content) {
                // atomic increment to avoid race conditions
                Content::whereKey($content->id)->update([
                    'shares_count' => DB::raw('shares_count + 1'),
                ]);
            });

            $content->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Share recorded successfully.',
                'data' => [
                    'content_id' => (int) $content->id,
                    'shares_count' => (int) $content->shares_count,
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('share error', [
                'content_id' => $content->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record share.',
                'error' => app()->environment('production') ? null : $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        // Check if input is a valid date
        $isDate = false;
        try {
            $isDate = $query && Carbon::parse($query);
        } catch (Exception $e) {
            $isDate = false;
        }

        $lowerQuery = strtolower($query);

        $contents = Content::with(['category', 'subcategory'])
            ->when($query, function ($q) use ($lowerQuery, $isDate, $query) {
                $q->where(function ($subQuery) use ($lowerQuery, $isDate, $query) {
                    $subQuery
                        ->whereRaw('LOWER(author) LIKE ?', ["%$lowerQuery%"])
                        ->orWhereRaw('LOWER(tags) LIKE ?', ["%$lowerQuery%"])
                        ->orWhereRaw('LOWER(heading) LIKE ?', ["%$lowerQuery%"])
                        ->orWhereRaw('LOWER(sub_heading) LIKE ?', ["%$lowerQuery%"])
                        ->orWhereRaw('LOWER(body1) LIKE ?', ["%$lowerQuery%"])
                        ->orWhereHas('category', function ($catQuery) use ($lowerQuery) {
                            $catQuery->whereRaw('LOWER(category_name) LIKE ?', ["%$lowerQuery%"]);
                        })
                        ->orWhereHas('subcategory', function ($subCatQuery) use ($lowerQuery) {
                            $subCatQuery->whereRaw('LOWER(name) LIKE ?', ["%$lowerQuery%"]);
                        });

                    if ($isDate) {
                        $subQuery->orWhereDate('date', $query);
                    }
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($content) {
                // Process image2 into cleaned URLs
                $imageArray = [];

                if (!empty($content->image2)) {
                    if (is_string($content->image2)) {
                        $decoded = json_decode($content->image2, true);
                        $imageArray = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($content->image2)) {
                        $imageArray = $content->image2;
                    }
                }

                $image2Urls = array_map(function ($img) {
                    if (Str::startsWith($img, ['http://', 'https://'])) {
                        return $img;
                    }
                    $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                    return url('uploads/content/' . ltrim($cleaned, '/'));
                }, $imageArray);

                return [
                    'id' => $content->id,
                    'heading' => $content->heading,
                    'author' => $content->author,
                    'category_id' => $content->category_id,
                    'subcategory_id' => $content->subcategory_id,
                    'category_name' => optional($content->category)->category_name,
                    'sub_category_name' => optional($content->subcategory)->name,
                    'date' => $content->date,
                    'tags' => $content->tags,
                    'image2' => $image2Urls,  // ✅ now included
                    'created_at' => $content->created_at,
                ];
            });

        return response()->json($contents);
    }

    public function allContents()
    {
        try {
            $perPage = request('per_page', 10);  // Default 10 per page

            $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            if ($contents->total() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No content found.',
                    'data' => [],
                ], 404);
            }

            $data = $contents->getCollection()->map(function ($content) {
                // Handle image2 like HomeContent
                // Handle image2 like HomeContent
                $image2Array = [];

                if (!empty($content->image2)) {
                    if (is_string($content->image2)) {
                        $decoded = json_decode($content->image2, true);
                        $image2Array = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($content->image2)) {
                        $image2Array = $content->image2;
                    }
                }

                $image2Urls = array_map(function ($img) {
                    if (Str::startsWith($img, ['http://', 'https://'])) {
                        return $img;
                    }
                    $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                    return url('uploads/content/' . ltrim($cleaned, '/'));
                }, $image2Array);

                return [
                    'id' => $content->id,
                    'category_id' => $content->category_id,
                    'subcategory_id' => $content->subcategory_id,
                    'category_name' => optional($content->category)->category_name,
                    'sub_category_name' => optional($content->subcategory)->name,
                    'heading' => $content->heading,
                    'author' => $content->author,
                    'date' => $content->date ? \Carbon\Carbon::parse($content->date)->format('m-d-Y') : null,
                    'sub_heading' => $content->sub_heading,
                    'body1' => $content->body1,
                    'image1' => $content->image1,
                    'image1_url' => $content->image1 ? url('uploads/content/' . $content->image1) : null,
                    'image2' => $image2Array,
                    'image2_url' => $image2Urls,
                    'advertising_image' => $content->advertising_image,
                    'advertising_image_url' => $content->advertising_image ? url('uploads/content/' . $content->advertising_image) : null,
                    'tags' => $content->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $content->tags) : null,
                    'imageLink' => $content->image_link,
                    'advertisingLink' => $content->advertising_link,
                    'user_id' => $content->user_id,
                    'status' => $content->status,
                    'meta_title' => $content->meta_title,
                    'meta_description' => $content->meta_description,
                    'created_at' => $content->created_at,
                    'updated_at' => $content->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'All contents fetched successfully.',
                'data' => $data,
                'meta' => [
                    'current_page' => $contents->currentPage(),
                    'per_page' => $contents->perPage(),
                    'total' => $contents->total(),
                    'last_page' => $contents->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Fetching all contents failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contents.',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
            ], 500);
        }
    }

    // public function dashboard(Request $request)
    // {
    //     $total_content = Content::count();

    //     $total_pending_content = Content::where('status', 'Published')->count();

    //     $total_author = User::where('role', 'author')->count();

    //     $total_user = User::where('role', 'user')->count();

    //     $total_subscriber = Subscriber::count();

    //     $perPage = min((int) $request->input('per_page', 10), 20);  // Max 20 per page
    //     $recent_content = Content::select('*')
    //         ->latest()
    //         ->paginate($perPage);

    //     // Process image2 for each item without changing pagination structure
    //     $recent_content->getCollection()->transform(function ($item) {
    //         $image2Array = [];

    //         if (!empty($item->image2)) {
    //             if (is_string($item->image2)) {
    //                 $decoded = json_decode($item->image2, true);
    //                 $image2Array = is_array($decoded) ? $decoded : [];
    //             } elseif (is_array($item->image2)) {
    //                 $image2Array = $item->image2;
    //             }
    //         }

    //         $image2Urls = array_map(function ($img) {
    //             if (Str::startsWith($img, ['http://', 'https://'])) {
    //                 return $img;
    //             }
    //             $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
    //             return url('uploads/content/' . ltrim($cleaned, '/'));
    //         }, $image2Array);

    //         // Attach processed fields to the model (optional: for frontend usage)
    //         $item->image2 = $image2Array;
    //         $item->image2_url = $image2Urls;

    //         return $item;
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'data' => [
    //             'total_content' => $total_content,
    //             'total_pending_content' => $total_pending_content,
    //             'total_author' => $total_author,
    //             'total_user' => $total_user,
    //             'total_subscriber' => $total_subscriber,
    //             'recent_content' => $recent_content
    //         ]
    //     ]);
    // }

    // this is effective method for viewPosts
    // public function viewPosts($user_id)
    // {
    //     try {
    //         // Get all content for this user
    //         $contents = Content::where('user_id', $user_id)
    //             ->orderBy('created_at', 'desc')
    //             ->get();

    //         if ($contents->isEmpty()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No posts found for this user.',
    //                 'data' => [],
    //             ], 404);
    //         }

    //         // Add full image URLs for each content record
    //         $contents->transform(function ($content) {
    //             $content->image1_url = $content->image1 ? url('uploads/content/' . $content->image1) : null;
    //             $content->advertising_image_url = $content->advertising_image ? url('uploads/content/' . $content->advertising_image) : null;

    //             // Add category and subcategory names directly to the content object
    //             $content->category_name = $content->category ? $content->category->category_name : null;
    //             $content->sub_category_name = $content->subcategory ? $content->subcategory->name : null;

    //             // Format date
    //             $content->date = $content->date ? Carbon::parse($content->date)->format('m-d-Y') : null;

    //             // Optionally hide original relationships
    //             unset($content->category, $content->subcategory);
    //             return $content;
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'User posts fetched successfully.',
    //             'data' => $contents,
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to fetch user posts: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'An error occurred while fetching posts.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // this is effective method fo HomeCategoryContent
    // public function HomeCategoryContent($cat_name)
    // {
    //     try {
    //         // Find the category by name (case-insensitive)
    //         $category = Category::where('category_name', 'like', $cat_name)->first();

    //         if (!$category) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Category not found.',
    //             ], 404);
    //         }

    //         // Get latest 15 contents for that category with related category and subcategory
    //         $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
    //             ->where('category_id', $category->id)
    //             ->where('status', 'active')
    //             ->latest()
    //             ->take(15)
    //             ->get()
    //             ->map(function ($content) {
    //                 return [
    //                     'id' => $content->id,
    //                     'category_id' => $content->category_id,
    //                     'subcategory_id' => $content->subcategory_id,
    //                     'category_name' => optional($content->category)->category_name,
    //                     'sub_category_name' => optional($content->subcategory)->name,
    //                     'heading' => $content->heading,
    //                     'author' => $content->author,
    //                     'date' => $content->date ? \Carbon\Carbon::parse($content->date)->format('m-d-Y') : null,
    //                     'sub_heading' => $content->sub_heading,
    //                     'body1' => $content->body1,
    //                     'image1' => $content->image1,
    //                     'advertising_image' => $content->advertising_image,
    //                     'tags' => $content->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $content->tags) : null,
    //                     'created_at' => $content->created_at,
    //                     'updated_at' => $content->updated_at,
    //                     'imageLink' => $content->imageLink,
    //                     'advertisingLink' => $content->advertisingLink,
    //                     'user_id' => $content->user_id,
    //                     'status' => $content->status,
    //                 ];
    //             });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Latest 15 contents for category fetched successfully.',
    //             'category_id' => $category->id,
    //             'category_name' => $category->category_name,
    //             'data' => $contents,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('HomeCategoryContent Error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch category contents.',
    //             'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function HomeCategoryContent($cat_name)
    // {
    //     try {
    //         $category = Category::where('category_name', 'like', $cat_name)->first();

    //         if (!$category) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Category not found.',
    //             ], 404);
    //         }

    //         $perPage = request('per_page', 10);  // allows dynamic pagination

    //         $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
    //             ->where('category_id', $category->id)
    //             ->where('status', 'active')
    //             ->latest()
    //             ->paginate($perPage);

    //         $data = [];

    //         foreach ($contents as $content) {
    //             $data[] = [
    //                 'id' => $content->id,
    //                 'category_id' => $content->category_id,
    //                 'subcategory_id' => $content->subcategory_id,
    //                 'category_name' => optional($content->category)->category_name,
    //                 'sub_category_name' => optional($content->subcategory)->name,
    //                 'heading' => $content->heading,
    //                 'author' => $content->author,
    //                 'date' => $content->date ? \Carbon\Carbon::parse($content->date)->format('m-d-Y') : null,
    //                 'sub_heading' => $content->sub_heading,
    //                 'body1' => $content->body1,
    //                 'image1' => $content->image1,
    //                 'advertising_image' => $content->advertising_image,
    //                 'tags' => $content->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $content->tags) : null,
    //                 'created_at' => $content->created_at,
    //                 'updated_at' => $content->updated_at,
    //                 'imageLink' => $content->imageLink,
    //                 'advertisingLink' => $content->advertisingLink,
    //                 'user_id' => $content->user_id,
    //                 'status' => $content->status,
    //             ];
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Category contents fetched successfully.',
    //             'category_id' => $category->id,
    //             'category_name' => $category->category_name,
    //             'data' => $data,
    //             'meta' => [
    //                 'current_page' => $contents->currentPage(),
    //                 'per_page' => $contents->perPage(),
    //                 'total' => $contents->total(),
    //                 'last_page' => $contents->lastPage(),
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('HomeCategoryContent Error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch category contents.',
    //             'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function HomeCategoryContent($cat_name)
    // {
    //     try {
    //         // Find the category by name (case-insensitive)
    //         $category = Category::where('category_name', 'like', $cat_name)->first();

    //         if (!$category) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Category not found.',
    //             ], 404);
    //         }

    //         $perPage = request('per_page', 10);

    //         $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
    //             ->where('category_id', $category->id)
    //             ->where('status', 'active')
    //             ->latest()
    //             ->paginate($perPage);

    //         $transformed = $contents->getCollection()->map(function ($content) {
    //             $tags = is_string($content->tags) ? json_decode($content->tags, true) : $content->tags;

    //             $cleanedTags = collect($tags)->map(function ($tag) {
    //                 return preg_replace('/[^a-zA-Z0-9\s]/', '', $tag);
    //             })->toArray();

    //             return [
    //                 'id' => $content->id,
    //                 'category_id' => $content->category_id,
    //                 'subcategory_id' => $content->subcategory_id,
    //                 'category_name' => optional($content->category)->category_name,
    //                 'sub_category_name' => optional($content->subcategory)->name,
    //                 'heading' => $content->heading,
    //                 'author' => $content->author,
    //                 'date' => $content->date ? \Carbon\Carbon::parse($content->date)->format('m-d-Y') : null,
    //                 'sub_heading' => $content->sub_heading,
    //                 'body1' => $content->body1,
    //                 'image1' => $content->image1,
    //                 'advertising_image' => $content->advertising_image,
    //                 'tags' => $cleanedTags,
    //                 'created_at' => $content->created_at,
    //                 'updated_at' => $content->updated_at,
    //                 'imageLink' => $content->imageLink,
    //                 'advertisingLink' => $content->advertisingLink,
    //                 'user_id' => $content->user_id,
    //                 'status' => $content->status,
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Latest contents for category fetched successfully.',
    //             'category_id' => $category->id,
    //             'category_name' => $category->category_name,
    //             'data' => $transformed,
    //             'meta' => [
    //                 'current_page' => $contents->currentPage(),
    //                 'per_page' => $contents->perPage(),
    //                 'total' => $contents->total(),
    //                 'last_page' => $contents->lastPage(),
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('HomeCategoryContent Error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch category contents.',
    //             'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function HomeContent()
    // {
    //     try {
    //         $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
    //             ->where('status', 'active')
    //             ->latest()
    //             // ->take(15)
    //             ->get()
    //             ->map(function ($content) {
    //                 return [
    //                     'id' => $content->id,
    //                     'category_id' => $content->category_id,
    //                     'subcategory_id' => $content->subcategory_id,
    //                     'category_name' => optional($content->category)->category_name,
    //                     'sub_category_name' => optional($content->subcategory)->name,
    //                     'heading' => $content->heading,
    //                     'author' => $content->author,
    //                     'date' => $content->date ? \Carbon\Carbon::parse($content->date)->format('m-d-Y') : null,
    //                     'sub_heading' => $content->sub_heading,
    //                     'body1' => $content->body1,
    //                     'image1' => $content->image1,
    //                     'advertising_image' => $content->advertising_image,
    //                     'tags' => $content->tags,
    //                     'created_at' => $content->created_at,
    //                     'updated_at' => $content->updated_at,
    //                     'imageLink' => $content->imageLink,
    //                     'advertisingLink' => $content->advertisingLink,
    //                     'user_id' => $content->user_id,
    //                     'status' => $content->status,
    //                 ];
    //             });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Latest 15 contents fetched successfully.',
    //             'data' => $contents,
    //             // 'pagination' => [
    //             //     'current_page' => $contents->currentPage(),
    //             //     'per_page' => $contents->perPage(),
    //             //     'total' => $contents->total(),
    //             //     'last_page' => $contents->lastPage(),
    //             // ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('HomeContent Error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch contents.',
    //             'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $total_content = Content::count();
        $total_pending_content = Content::where('status', 'Published')->count();
        $total_author = User::where('role', 'author')->count();
        $total_user = User::where('role', 'user')->count();
        $total_subscriber = Subscriber::count();

        // ✅ Limit per page to avoid memory issues
        $perPage = min((int) $request->input('per_page', 10), 100);  // hard limit to 10 items

        // ✅ Select all fields safely
        // $recent_content = Content::latest()->paginate($perPage);

        // ✅ Base query
        $recentQuery = Content::latest();

        // ✅ Restrict if user is an author
        if ($user->role === 'author') {
            $recentQuery->where('user_id', $user->id)->whereIn('status', ['Published', 'Draft']);
        }

        // ✅ Get paginated content
        $recent_content = $recentQuery->paginate($perPage);

        // ✅ Safely transform `image2`
        $recent_content->getCollection()->transform(function ($item) {
            unset(
                $item->body1,
                $item->sub_heading,
                $item->advertising_image,
                $item->advertisingLink,
                $item->image1,
                $item->author,
            );
            $image2Array = [];

            if (!empty($item->image2)) {
                // Safely decode JSON
                if (is_string($item->image2)) {
                    $decoded = json_decode($item->image2, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $image2Array = is_array($decoded) ? $decoded : [];
                    }
                } elseif (is_array($item->image2)) {
                    $image2Array = $item->image2;
                }
            }

            // ✅ Safely build URLs
            // $image2Urls = [];
            // foreach ($image2Array as $img) {
            //     if (Str::startsWith($img, ['http://', 'https://'])) {
            //         $image2Urls[] = $img;
            //     } else {
            //         $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
            //         $image2Urls[] = url('uploads/content/' . ltrim($cleaned, '/'));
            //     }
            // }

            // ✅ Attach processed data without breaking pagination
            $item->image2 = $image2Array;
            // $item->image2_url = $image2Urls;

            return $item;
        });

        // ✅ Final JSON Response (unchanged)
        return response()->json([
            'success' => true,
            'data' => [
                'total_content' => $total_content,
                'total_pending_content' => $total_pending_content,
                'total_author' => $total_author,
                'total_user' => $total_user,
                'total_subscriber' => $total_subscriber,
                'recent_content' => $recent_content
            ]
        ]);
    }

    public function viewPosts($user_id)
    {
        try {
            $perPage = request('per_page', 10);

            $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
                ->where('user_id', $user_id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            if ($contents->total() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No posts found for this user.',
                    'data' => [],
                ], 404);
            }

            // Map over the collection inside the paginator
            $data = $contents->getCollection()->map(function ($content) {
                $image2Array = [];

                if (!empty($content->image2)) {
                    if (is_string($content->image2)) {
                        $decoded = json_decode($content->image2, true);
                        $image2Array = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($content->image2)) {
                        $image2Array = $content->image2;
                    }
                }

                $image2Urls = array_map(function ($img) {
                    if (Str::startsWith($img, ['http://', 'https://'])) {
                        return $img;
                    }
                    $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                    return url('uploads/content/' . ltrim($cleaned, '/'));
                }, $image2Array);
                return [
                    'id' => $content->id,
                    'category_id' => $content->category_id,
                    'subcategory_id' => $content->subcategory_id,
                    'category_name' => optional($content->category)->category_name,
                    'sub_category_name' => optional($content->subcategory)->name,
                    'heading' => $content->heading,
                    'author' => $content->author,
                    'date' => $content->date ? \Carbon\Carbon::parse($content->date)->format('m-d-Y') : null,
                    'sub_heading' => $content->sub_heading,
                    // 'body1' => $content->body1,
                    'image1' => $content->image1,
                    'image1_url' => $content->image1 ? url('uploads/content/' . $content->image1) : null,
                    // Convert image2 to array if stored as JSON string in DB
                    'image2' => $image2Array,
                    // 'image2_url' => $content->image2 ? url('uploads/content/' . $content->image2) : null,
                    'advertising_image' => $content->advertising_image,
                    'advertising_image_url' => $content->advertising_image ? url('uploads/content/' . $content->advertising_image) : null,
                    'image2_url' => $image2Urls,
                    'tags' => $content->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $content->tags) : null,
                    'created_at' => $content->created_at,
                    'updated_at' => $content->updated_at,
                    'imageLink' => $content->imageLink,
                    'advertisingLink' => $content->advertisingLink,
                    'user_id' => $content->user_id,
                    'status' => $content->status,
                    'meta_title' => $content->meta_title,
                    'meta_description' => $content->meta_description,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'User posts fetched successfully.',
                'data' => $data,
                'meta' => [
                    'current_page' => $contents->currentPage(),
                    'per_page' => $contents->perPage(),
                    'total' => $contents->total(),
                    'last_page' => $contents->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user posts: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching posts.',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
            ], 500);
        }
    }

    // public function HomeCategoryContent($cat_name)
    // {
    //     try {
    //         $category = Category::where('category_name', 'like', $cat_name)->first();

    //         if (!$category) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Category not found.',
    //             ], 404);
    //         }

    //         $perPage = request('per_page', 10);

    //         $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
    //             ->where('category_id', $category->id)
    //             ->where('status', 'active')
    //             ->latest()
    //             ->paginate($perPage);

    //         $data = $contents->map(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'category_id' => $content->category_id,
    //                 'subcategory_id' => $content->subcategory_id,
    //                 'category_name' => optional($content->category)->category_name,
    //                 'sub_category_name' => optional($content->subcategory)->name,
    //                 'heading' => $content->heading,
    //                 'author' => $content->author,
    //                 'date' => $content->date ? \Carbon\Carbon::parse($content->date)->format('m-d-Y') : null,
    //                 'sub_heading' => $content->sub_heading,
    //                 'body1' => $content->body1,
    //                 'image1' => $content->image1,
    //                 'advertising_image' => $content->advertising_image,
    //                 'tags' => $content->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $content->tags) : null,
    //                 'created_at' => $content->created_at,
    //                 'updated_at' => $content->updated_at,
    //                 'imageLink' => $content->imageLink,
    //                 'advertisingLink' => $content->advertisingLink,
    //                 'user_id' => $content->user_id,
    //                 'status' => $content->status,
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Category contents fetched successfully.',
    //             'category_id' => $category->id,
    //             'category_name' => $category->category_name,
    //             'data' => $data,
    //             'meta' => [
    //                 'current_page' => $contents->currentPage(),
    //                 'per_page' => $contents->perPage(),
    //                 'total' => $contents->total(),
    //                 'last_page' => $contents->lastPage(),
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('HomeCategoryContent Error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch category contents.',
    //             'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // change today(05/08/2025)
    public function HomeCategoryContent(Request $request, $slug)
    {
        try {
            $category = Category::where('slug', 'like', $slug)->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.',
                ], 404);
            }

            $limit = (int) $request->query('limit', 10);  // default to 10 if not provided
            $limit = $limit > 0 ? $limit : 10;  // ensure valid value

            $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
                ->where('category_id', $category->id)
                ->withCount(['comments as comment_count'])
                ->where('status', 'Approved')
                ->latest()
                ->paginate($limit)
                ->through(function ($content) {
                    $image2Array = [];

                    if (!empty($content->image2)) {
                        if (is_string($content->image2)) {
                            $decoded = json_decode($content->image2, true);
                            $image2Array = is_array($decoded) ? $decoded : [];
                        } elseif (is_array($content->image2)) {
                            $image2Array = $content->image2;
                        }
                    }

                    $image2Urls = array_map(function ($img) {
                        if (Str::startsWith($img, ['http://', 'https://'])) {
                            return $img;
                        }
                        $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                        return url('uploads/content/' . ltrim($cleaned, '/'));
                    }, $image2Array);
                    return [
                        'id' => $content->id,
                        'category_id' => $content->category_id,
                        'subcategory_id' => $content->subcategory_id,
                        'category_name' => optional($content->category)->category_name,
                        'sub_category_name' => optional($content->subcategory)->name,
                        'heading' => $content->heading,
                        'author' => $content->author,
                        'date' => $content->date ? Carbon::parse($content->date)->format('m-d-Y') : null,
                        'sub_heading' => $content->sub_heading,
                        // 'body1' => $content->body1,
                        'image1' => $content->image1,
                        // Ensure image2 is always an array, even if null
                        'image2' => $image2Array,
                        'image2_url' => $image2Urls,
                        'advertising_image' => $content->advertising_image,
                        'tags' => $content->tags ? preg_replace('/[^A-Za-z0-9, ]/', '', $content->tags) : null,
                        'created_at' => $content->created_at,
                        'updated_at' => $content->updated_at,
                        'imageLink' => $content->imageLink,
                        'advertisingLink' => $content->advertisingLink,
                        'user_id' => $content->user_id,
                        'status' => $content->status,
                        'meta_title' => $content->meta_title,
                        'meta_description' => $content->meta_description,
                        'likes_count' => (int) $content->likes_count,  // from contents table
                        'shares_count' => (int) $content->shares_count,
                        'comment_count' => (int) $content->comment_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Category contents fetched successfully.',
                'category_id' => $category->id,
                'category_name' => $category->category_name,
                'data' => $contents->items(),
                'pagination' => [
                    'current_page' => $contents->currentPage(),
                    'per_page' => $contents->perPage(),
                    'total' => $contents->total(),
                    'last_page' => $contents->lastPage(),
                ]
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

    public function HomeContent(Request $request)
    {
        try {
            $limit = (int) $request->query('limit', 15);  // Default 15 items per page
            $limit = $limit > 0 ? $limit : 15;  // guard against invalid zero or negative

            // Laravel's paginator will automatically detect 'page' from query string

            $contents = Content::with(['category:id,category_name', 'subcategory:id,name'])
                ->where('status', 'Approved')
                ->withCount(['comments as comment_count'])
                ->latest()
                ->paginate($limit)  // dynamic limit
                ->through(function ($content) {
                    // Decode image2 if it's a JSON string
                    // Handle image2 like HomeContent
                    $image2Array = [];

                    if (!empty($content->image2)) {
                        if (is_string($content->image2)) {
                            $decoded = json_decode($content->image2, true);
                            $image2Array = is_array($decoded) ? $decoded : [];
                        } elseif (is_array($content->image2)) {
                            $image2Array = $content->image2;
                        }
                    }

                    $image2Urls = array_map(function ($img) {
                        if (Str::startsWith($img, ['http://', 'https://'])) {
                            return $img;
                        }
                        $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                        return url('uploads/content/' . ltrim($cleaned, '/'));
                    }, $image2Array);

                    return [
                        'id' => $content->id,
                        'category_id' => $content->category_id,
                        'subcategory_id' => $content->subcategory_id,
                        'category_name' => optional($content->category)->category_name,
                        'sub_category_name' => optional($content->subcategory)->name,
                        'heading' => $content->heading,
                        'author' => $content->author,
                        'date' => $content->date ? Carbon::parse($content->date)->format('m-d-Y') : null,
                        'sub_heading' => $content->sub_heading,
                        // 'body1' => $content->body1,
                        'image1' => $content->image1,
                        'image2' => $image2Array,
                        'image2_url' => $image2Urls,
                        'advertising_image' => $content->advertising_image,
                        'tags' => $content->tags ? preg_replace('/[^A-Za-z0-9, ]/', '', $content->tags) : null,
                        'created_at' => $content->created_at,
                        'updated_at' => $content->updated_at,
                        'imageLink' => $content->imageLink,
                        'advertisingLink' => $content->advertisingLink,
                        'user_id' => $content->user_id,
                        'status' => $content->status,
                        'meta_title' => $content->meta_title,
                        'meta_description' => $content->meta_description,
                        'likes_count' => (int) $content->likes_count,  // from contents table
                        'shares_count' => (int) $content->shares_count,
                        'comment_count' => (int) $content->comment_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Contents fetched successfully.',
                'data' => $contents->items(),
                'pagination' => [
                    'current_page' => $contents->currentPage(),
                    'per_page' => $contents->perPage(),
                    'total' => $contents->total(),
                    'last_page' => $contents->lastPage(),
                ]
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

    public function HomeContentById($slug)
    {
        try {
            $content = Content::where('slug', $slug)
                ->where('status', 'Approved')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'meta_title' => $content->meta_title,
                    'meta_description' => $content->meta_description,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('HomeContentById Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch content.',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
            ], 500);
        }
    }

    public function show($contentId)
    {
        try {
            $content = Content::with(['category', 'subcategory'])
                ->where('id', $contentId)
                ->first();

            if (!$content) {
                return response()->json([
                    'status' => false,
                    'message' => 'Content not found.',
                    'data' => null,
                ], 404);
            }

            // Transform content (similar to relatedContents)
            $content->image1_url = $content->image1 ? url($content->image1) : null;
            $content->advertising_image_url = $content->advertising_image ? url($content->advertising_image) : null;
            // Clean and format tags
            $content->tags = $content->tags ? preg_replace('/[^A-Za-z0-9, ]/', '', $content->tags) : null;
            $content->meta_title = $content->meta_title;
            $content->meta_description = $content->meta_description;

            // Handle image2
            $image2Array = [];
            if (!empty($content->image2)) {
                if (is_string($content->image2)) {
                    $decoded = json_decode($content->image2, true);
                    $image2Array = is_array($decoded) ? $decoded : [];
                } elseif (is_array($content->image2)) {
                    $image2Array = $content->image2;
                }
            }

            $image2Urls = array_map(function ($img) {
                if (Str::startsWith($img, ['http://', 'https://'])) {
                    return $img;
                }
                $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                return url('uploads/content/' . ltrim($cleaned, '/'));
            }, $image2Array);

            $content->image2_url = $image2Urls;

            // Format date
            $content->date = $content->created_at ? Carbon::parse($content->date)->format('m-d-Y') : null;

            $content->body1 = $content->body1;

            return response()->json([
                'status' => true,
                'message' => 'Content fetched successfully.',
                'data' => $content,
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching single content failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function HomeContent(Request $request)
    // {
    //     try {
    //         $validated = $request->validate([
    //             'per_page' => 'nullable|integer|min:1',
    //             'search' => 'nullable|string|max:255',
    //         ]);

    //         $perPage = $validated['per_page'] ?? 15;
    //         $search = $validated['search'] ?? null;

    //         $query = Content::with(['category:id,category_name', 'subcategory:id,name'])
    //             ->where('status', 'active')
    //             ->latest();

    //         if ($search) {
    //             $query->where('heading', 'like', '%' . $search . '%');
    //         }

    //         $paginated = $query->paginate($perPage);

    //         $mappedContents = $paginated->getCollection()->map(function ($content) {
    //             return [
    //                 'id' => $content->id,
    //                 'category_id' => $content->category_id,
    //                 'subcategory_id' => $content->subcategory_id,
    //                 'category_name' => optional($content->category)->category_name,
    //                 'sub_category_name' => optional($content->subcategory)->name,
    //                 'heading' => $content->heading,
    //                 'author' => $content->author,
    //                 'date' => $content->date ? \Carbon\Carbon::parse($content->date)->format('m-d-Y') : null,
    //                 'sub_heading' => $content->sub_heading,
    //                 'body1' => $content->body1,
    //                 'image1' => $content->image1,
    //                 'advertising_image' => $content->advertising_image,
    //                 'tags' => $content->tags ? preg_replace('/[^A-Za-z0-9, ]/', '', $content->tags) : null,
    //                 'created_at' => $content->created_at,
    //                 'updated_at' => $content->updated_at,
    //                 'imageLink' => $content->imageLink,
    //                 'advertisingLink' => $content->advertisingLink,
    //                 'user_id' => $content->user_id,
    //                 'status' => $content->status,
    //             ];
    //         });

    //         $paginated->setCollection($mappedContents);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Latest contents fetched successfully.',
    //             'data' => $paginated->items(),
    //             'current_page' => $paginated->currentPage(),
    //             'total_pages' => $paginated->lastPage(),
    //             'per_page' => $paginated->perPage(),
    //             'total' => $paginated->total(),
    //             'paginate_count' => $mappedContents->count(),
    //         ], 200);
    //     } catch (\Exception $e) {
    //         \Log::error('HomeContent Error: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch contents.',
    //             'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage(),
    //         ], 500);
    //     }
    // }

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
                'image2' => $item->image2 ? url($item->image2) : null,
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
                    'image2' => $item->image2 ? url($item->image2) : null,
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
            ->where('status', 'Approved')
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
            'date' => $latestContent->date ? \Carbon\Carbon::parse($latestContent->date)->format('m-d-Y') : null,
            'tags' => $latestContent->tags,
            'image1' => $latestContent->image1 ? url($latestContent->image1) : null,
            'imageLink' => $latestContent->imageLink ? url($latestContent->imageLink) : null,
            'advertising_image' => $latestContent->advertising_image ? url($latestContent->advertising_image) : null,
            'advertisingLink' => $latestContent->advertisingLink ? url($latestContent->advertisingLink) : null,
            'body1' => $latestContent->body1,
            'meta_title' => $latestContent->meta_title,
            'meta_description' => $latestContent->meta_description,
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
                // Decode and clean image2 array
                // Handle image2 like HomeContent
                $image2Array = [];

                if (!empty($item->image2)) {
                    if (is_string($item->image2)) {
                        $decoded = json_decode($item->image2, true);
                        $image2Array = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($item->image2)) {
                        $image2Array = $item->image2;
                    }
                }

                $image2Urls = array_map(function ($img) {
                    if (Str::startsWith($img, ['http://', 'https://'])) {
                        return $img;
                    }
                    $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                    return url('uploads/content/' . ltrim($cleaned, '/'));
                }, $image2Array);
                return [
                    'id' => $item->id,
                    'heading' => $item->heading,
                    'sub_heading' => $item->sub_heading,
                    'author' => $item->author,
                    'date' => Carbon::parse($item->date)->format('m-d-Y'),
                    'category_id' => $item->category_id,
                    'sub_category_id' => $item->subcategory_id,
                    // 'body1' => $item->body1,
                    'tags' => $item->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $item->tags) : null,
                    'category_name' => optional($item->category)->category_name,
                    'sub_category_name' => optional($item->subcategory)->name,
                    'image1' => $item->image1 ? url($item->image1) : null,
                    'image2' => $image2Array,
                    'image2_url' => $image2Urls,
                    'advertising_image' => $item->advertising_image ? url($item->advertising_image) : null,
                    'advertisingLink' => $item->advertisingLink ? url($item->advertisingLink) : null,
                    'imageLink' => $item->imageLink ? url($item->imageLink) : null,
                    'meta_title' => $item->meta_title,
                    'meta_description' => $item->meta_description,
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
                'date' => \Carbon\Carbon::parse($content->date)->format('m-d-Y'),
                'sub_heading' => $content->sub_heading,
                'body1' => $content->body1,
                'image1' => $content->image1,
                // Ensure image2 is always an array, even if null
                'image2' => is_array($content->image2) ? $content->image2 : [],
                'advertising_image' => $content->advertising_image,
                'image2_url' => is_array($content->image2_url)
                    ? array_map(fn($img) => url('uploads/content/' . $img), $content->image2_url)
                    : [],
                'tags' => $content->tags,
                'created_at' => $content->created_at,
                'updated_at' => $content->updated_at,
                'imageLink' => $content->imageLink,
                'advertisingLink' => $content->advertisingLink,
                'user_id' => $content->user_id,
                'status' => $content->status,
                'meta_title' => $content->meta_title,
                'meta_description' => $content->meta_description,
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

    // public function storeOrUpdateStatus(Request $request, $id)
    // {
    //     // Validate the status field
    //     $request->validate([
    //         'status' => 'required|string|in:active,pending',  // adjust allowed values as needed
    //     ]);

    //     // Try to find existing content by ID
    //     $content = Content::find($id);

    //     if ($content) {
    //         // Update existing content status
    //         $content->status = $request->input('status');
    //         $content->save();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Content status updated successfully.',
    //             'data' => $content,
    //         ], 200);
    //     } else {
    //         // Create new content with given id and status (optional)
    //         // Note: Usually ID is auto-increment and shouldn't be forced
    //         // If you want to create a new record without id, remove $id assignment

    //         $content = new Content();
    //         $content->id = $id;  // Only if your model supports manual ID assignment
    //         $content->status = $request->input('status');
    //         // You may need to fill other required fields here to avoid DB errors
    //         $content->save();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Content created with status successfully.',
    //             'data' => $content,
    //         ], 201);
    //     }
    // }

    public function storeOrUpdateStatus(Request $request, $id)
    {
        $user = auth()->user();

        // Unified allowed statuses
        $allStatuses = ['Draft', 'Review', 'Approved', 'Rejected', 'Archived', 'Published', 'Needs Revision'];
        $authorStatuses = ['Draft', 'Published'];

        $request->validate([
            'status' => 'required|string|in:' . implode(',', $allStatuses),
        ]);

        $requestedStatus = $request->input('status');

        // Only admins and editors can approve (set status to 'active')
        // Check role-based permission
        if (
            $user->role === 'author' &&
            !in_array($requestedStatus, $authorStatuses)
        ) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to set this status.',
            ], 403);
        }
        $content = Content::find($id);

        if ($content) {
            $content->status = $requestedStatus;
            $content->save();

            return response()->json([
                'status' => true,
                'message' => 'Content status updated successfully.',
                'data' => $content,
            ], 200);
        } else {
            // Optional: allow creation with manual ID (not usually recommended)
            $content = new Content();
            $content->id = $id;

            // Author can only create with status 'pending'
            $content->status = in_array($user->role, ['admin', 'editor']) ? $requestedStatus : 'Published';

            // Add required default values if needed
            $content->user_id = $user->id;  // just an example
            $content->heading = 'Untitled';  // fill other required fields as needed

            $content->save();

            return response()->json([
                'status' => true,
                'message' => 'Content created with status successfully.',
                'data' => $content,
            ], 201);
        }
    }

    public function relatedContents($cat_name, $sub_name, $contentId)
    {
        try {
            // Get latest 10 related contents (same category and subcategory, excluding current)
            // $contents = Content::with(['category', 'subcategory'])
            //     ->where('category_id', $cat_id)
            //     ->where('subcategory_id', $sub_id)
            //     ->where('id', '!=', $contentId)
            //     ->latest()
            //     ->take(10)
            //     ->get();

            // Latest 10 related contents (same category & subcategory names, excluding current)
            $contents = Content::with(['category', 'subcategory'])
                ->whereHas('category', function ($q) use ($cat_name) {
                    // Postgres case-insensitive exact match
                    $q->where('category_name', 'ILIKE', urldecode($cat_name));
                    // MySQL alternative:
                    // $q->whereRaw('LOWER(category_name) = ?', [mb_strtolower(urldecode($cat_name))]);
                })
                ->whereHas('subcategory', function ($q) use ($sub_name) {
                    $q->where('name', 'ILIKE', urldecode($sub_name));
                    // MySQL alternative:
                    // $q->whereRaw('LOWER(name) = ?', [mb_strtolower(urldecode($sub_name))]);
                })
                ->where('id', '!=', $contentId)
                ->latest()  // by created_at desc
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

                // Handle image2 and generate clean URLs
                $image2Array = [];
                if (!empty($content->image2)) {
                    if (is_string($content->image2)) {
                        $decoded = json_decode($content->image2, true);
                        $image2Array = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($content->image2)) {
                        $image2Array = $content->image2;
                    }
                }

                $image2Urls = array_map(function ($img) {
                    if (\Illuminate\Support\Str::startsWith($img, ['http://', 'https://'])) {
                        return $img;
                    }
                    $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                    return url('uploads/content/' . ltrim($cleaned, '/'));
                }, $image2Array);

                $content->image2_url = $image2Urls;

                // Format date
                $content->date = $content->created_at ? Carbon::parse($content->date)->format('m-d-Y') : null;
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

    public function index($cat_name, $sub_name, $id)
    {
        try {
            // $content = Content::with(['user:id,id,description,first_name,facebook_link,profile_pic,instagram_link,youtube_link,twitter_link'])
            //     ->where('category_id', $cat_id)
            //     ->where('subcategory_id', $sub_id)
            //     ->where('id', $id)
            //     ->first();

            // Fetch by category/subcategory NAMES + id
            $content = Content::with([
                'user:id,id,description,first_name,facebook_link,profile_pic,instagram_link,youtube_link,twitter_link',
                'category:id,category_name',
                'subcategory:id,name',
            ])
                ->withCount(['comments as comment_count'])  // so comment_count is populated
                ->where('id', $id)
                // Category by NAME (case-insensitive; Postgres ILIKE)
                ->whereHas('category', function ($q) use ($cat_name) {
                    $q->where('category_name', 'ILIKE', urldecode($cat_name));
                    // If you're on MySQL, use this instead:
                    // $q->whereRaw('LOWER(category_name) = ?', [mb_strtolower(urldecode($cat_name))]);
                })
                // Subcategory by NAME (case-insensitive)
                ->whereHas('subcategory', function ($q) use ($sub_name) {
                    $q->where('name', 'ILIKE', urldecode($sub_name));
                    // MySQL: $q->whereRaw('LOWER(name) = ?', [mb_strtolower(urldecode($sub_name))]);
                })
                ->first();

            // If content not found
            if (!$content) {
                return response()->json([
                    'status' => false,
                    'message' => 'No Content Found.',
                    'data' => null,
                ], 404);
            }
            // Clean image2
            $image2Array = [];
            if (!empty($content->image2)) {
                if (is_string($content->image2)) {
                    $decoded = json_decode($content->image2, true);
                    $image2Array = is_array($decoded) ? $decoded : [];
                } elseif (is_array($content->image2)) {
                    $image2Array = $content->image2;
                }
            }

            $image2Urls = array_map(function ($img) {
                if (Str::startsWith($img, ['http://', 'https://'])) {
                    return $img;
                }
                $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                return url('uploads/content/' . ltrim($cleaned, '/'));
            }, $image2Array);

            $content->image2_url = $image2Urls;

            // Advertising image
            $content->advertising_image_url = $content->advertising_image
                ? url('uploads/content/' . ltrim($content->advertising_image, '/'))
                : null;

            // Add category_name and sub_category_name
            $content->category_name = $content->category ? $content->category->category_name : null;
            $content->sub_category_name = $content->subcategory ? $content->subcategory->name : null;
            $content->likes_count = $content->likes_count ? $content->likes_count : null;
            $content->shares_count = $content->shares_count ? $content->shares_count : null;
            $content->comment_count = $content->comment_count ? $content->comment_count : null;

            // Format date
            $content->date = $content->date ? Carbon::parse($content->date)->format('m-d-Y') : null;

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

    // use Illuminate\Support\Facades\Auth;

    public function indexForSubCategoryForDashboard($cat_name, $sub_name, Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'paginate_count' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
            ]);

            $paginate_count = $validated['paginate_count'] ?? 10;
            $search = $validated['search'] ?? null;

            // Get authenticated user
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // // Base query with relationships
            // $query = Content::with(['category', 'subcategory'])
            //     ->where('category_id', $cat_id)
            //     ->where('subcategory_id', $sub_id);

            // Base query with relationships
            $query = Content::with(['category', 'subcategory'])
                // filter by CATEGORY NAME (case-insensitive)
                ->whereHas('category', function ($q) use ($cat_name) {
                    // Postgres:
                    $q->where('category_name', 'ILIKE', urldecode($cat_name));
                    // MySQL alternative:
                    // $q->whereRaw('LOWER(category_name) = ?', [mb_strtolower(urldecode($cat_name))]);
                })
                // filter by SUBCATEGORY NAME (case-insensitive)
                ->whereHas('subcategory', function ($q) use ($sub_name) {
                    // Postgres:
                    $q->where('name', 'ILIKE', urldecode($sub_name));
                    // MySQL alternative:
                    // $q->whereRaw('LOWER(name) = ?', [mb_strtolower(urldecode($sub_name))]);
                });

            // Role-based filtering
            if ($user->role === 'author') {
                $query->where('user_id', $user->id);  // Authors see only their own content
            }
            // Admins and Editors see all content

            // Search by heading
            if ($search) {
                $query->where('heading', 'like', '%' . $search . '%');
            }

            // Get paginated results
            $contents = $query->orderBy('id', 'desc')->paginate($paginate_count);

            // Transform each content item
            $transformedData = $contents->getCollection()->transform(function ($item) {
                // Decode image2
                $image2Array = [];
                if (!empty($item->image2)) {
                    if (is_string($item->image2)) {
                        $decoded = json_decode($item->image2, true);
                        $image2Array = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($item->image2)) {
                        $image2Array = $item->image2;
                    }
                }

                // Create clean image URLs
                $image2Urls = array_map(function ($img) {
                    if (Str::startsWith($img, ['http://', 'https://'])) {
                        return $img;
                    }
                    $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                    return url('uploads/content/' . ltrim($cleaned, '/'));
                }, $image2Array);

                return [
                    'id' => $item->id,
                    'heading' => $item->heading,
                    'sub_heading' => $item->sub_heading,
                    'author' => $item->author,
                    'date' => \Carbon\Carbon::parse($item->date)->format('m-d-Y'),
                    'body1' => $item->body1,
                    'tags' => $item->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $item->tags) : null,
                    'category_id' => $item->category_id,
                    'subcategory_id' => $item->subcategory_id,
                    'category_name' => optional($item->category)->category_name,
                    'sub_category_name' => optional($item->subcategory)->name,
                    'image1' => $item->image1 ? url($item->image1) : null,
                    'advertising_image' => $item->advertising_image ? url($item->advertising_image) : null,
                    'advertisingLink' => $item->advertisingLink ? url($item->advertisingLink) : null,
                    'image2' => $image2Array,
                    'image2_url' => $image2Urls,
                    'imageLink' => $item->imageLink ? url($item->imageLink) : null,
                    'status' => $item->status,
                    'meta_title' => $item->meta_title,
                    'meta_description' => $item->meta_description,
                ];
            });

            // Set the transformed data back on paginator
            $contents->setCollection($transformedData);

            // Return response
            return response()->json([
                'success' => true,
                'data' => $contents,
                'current_page' => $contents->currentPage(),
                'total_pages' => $contents->lastPage(),
                'per_page' => $contents->perPage(),
                'total' => $contents->total(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            \Log::error('Error in indexForSubCategoryForDashboard: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch content.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // public function indexForSubCategory($cat_id, $sub_id, Request $request)
    // {
    //     // return "ok";
    //     try {
    //         // Validate query parameters
    //         $validated = $request->validate([
    //             'paginate_count' => 'nullable|integer|min:1',
    //             'search' => 'nullable|string|max:255',
    //         ]);

    //         $paginate_count = $validated['paginate_count'] ?? 10;
    //         $search = $validated['search'] ?? null;

    //         // Base query with relationships
    //         $query = Content::with(['category', 'subcategory'])
    //             ->where('category_id', $cat_id)
    //             ->where('subcategory_id', $sub_id);

    //         // Optional search on heading
    //         if ($search) {
    //             $query->where('heading', 'like', '%' . $search . '%');
    //         }

    //         // Apply pagination
    //         $contents = $query->orderBy('id', 'desc')->paginate($paginate_count);

    //         // Transform only the items in the paginated result
    //         $transformedData = $contents->getCollection()->transform(function ($item) {
    //             return [
    //                 'id' => $item->id,
    //                 'heading' => $item->heading,
    //                 'sub_heading' => $item->sub_heading,
    //                 'author' => $item->author,
    //                 'date' => \Carbon\Carbon::parse($item->date)->format('m-d-Y'),
    //                 'body1' => $item->body1,
    //                 'tags' => $item->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $item->tags) : null,
    //                 'category_id' => $item->category_id,
    //                 'subcategory_id' => $item->subcategory_id,
    //                 'category_name' => optional($item->category)->category_name,
    //                 'sub_category_name' => optional($item->subcategory)->name,
    //                 'image1' => $item->image1 ? url($item->image1) : null,
    //                 'advertising_image' => $item->advertising_image ? url($item->advertising_image) : null,
    //                 'advertisingLink' => $item->advertisingLink ? url($item->advertisingLink) : null,
    //                 'imageLink' => $item->imageLink ? url($item->imageLink) : null,
    //                 'status' => $item->status,
    //             ];
    //         });

    //         // Replace the collection with transformed data
    //         $contents->setCollection($transformedData);

    //         // return $transformedData;

    //         // Return response matching the example format
    //         return response()->json([
    //             'success' => true,
    //             'data' => $contents,
    //             'current_page' => $contents->currentPage(),
    //             'total_pages' => $contents->lastPage(),
    //             'per_page' => $contents->perPage(),
    //             'total' => $contents->total(),
    //         ], Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         Log::error('Fetching contents by category and subcategory failed: ' . $e->getMessage());

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch contents.',
    //             'error' => $e->getMessage(),
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

    public function indexForSubCategory($slug1, $slug2, Request $request)
    {
        try {
            // Validate query parameters
            $validated = $request->validate([
                'paginate_count' => 'nullable|integer|min:1',
                'search' => 'nullable|string|max:255',
            ]);

            $perPage = $validated['paginate_count'] ?? 10;
            $search = $validated['search'] ?? null;

            // // Build the query
            // $query = Content::with(['category:id,category_name', 'subcategory:id,name'])
            //     ->where('category_id', $cat_id)
            //     ->where('subcategory_id', $sub_id);

            // Build the query: filter by category.name and subcategory.name (case-insensitive)
            $query = Content::with(['category:id,category_name', 'subcategory:id,name'])
                ->withCount(['comments as comment_count'])
                ->whereHas('category', function ($q) use ($cat_name) {
                    // Postgres (ILIKE). If you're on MySQL, swap to the LOWER() line below.
                    $q->where('category_name', 'ILIKE', urldecode($cat_name));
                    // $q->whereRaw('LOWER(category_name) = ?', [mb_strtolower(urldecode($cat_name))]); // MySQL
                })
                ->whereHas('subcategory', function ($q) use ($sub_name) {
                    $q->where('name', 'ILIKE', urldecode($sub_name));
                    // $q->whereRaw('LOWER(name) = ?', [mb_strtolower(urldecode($sub_name))]); // MySQL
                });

            if ($search) {
                $query->where('heading', 'like', '%' . $search . '%');
            }

            $contents = $query->orderBy('id', 'desc')->paginate($perPage);

            // Map the collection just like in viewPosts
            $transformed = $contents->getCollection()->map(function ($item) {
                // Decode image2
                $image2Array = [];
                if (!empty($item->image2)) {
                    if (is_string($item->image2)) {
                        $decoded = json_decode($item->image2, true);
                        $image2Array = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($item->image2)) {
                        $image2Array = $item->image2;
                    }
                }

                // Create clean image URLs
                $image2Urls = array_map(function ($img) {
                    if (Str::startsWith($img, ['http://', 'https://'])) {
                        return $img;
                    }
                    $cleaned = preg_replace('/[^A-Za-z0-9\-_.\/]/', '', $img);
                    return url('uploads/content/' . ltrim($cleaned, '/'));
                }, $image2Array);
                return [
                    'id' => $item->id,
                    'heading' => $item->heading,
                    'sub_heading' => $item->sub_heading,
                    'author' => $item->author,
                    'date' => $item->date ? \Carbon\Carbon::parse($item->date)->format('m-d-Y') : null,
                    // 'body1' => $item->body1,
                    'tags' => $item->tags ? preg_replace('/[^a-zA-Z0-9,\s]/', '', $item->tags) : null,
                    'category_id' => $item->category_id,
                    'subcategory_id' => $item->subcategory_id,
                    'category_name' => optional($item->category)->category_name,
                    'sub_category_name' => optional($item->subcategory)->name,
                    'image1' => $item->image1 ? url($item->image1) : null,
                    'image2' => $image2Array,
                    'image2_url' => $image2Urls,
                    'advertising_image' => $item->advertising_image ? url($item->advertising_image) : null,
                    'advertisingLink' => $item->advertisingLink ? url($item->advertisingLink) : null,
                    'imageLink' => $item->imageLink ? url($item->imageLink) : null,
                    'status' => $item->status,
                    'meta_title' => $item->meta_title,
                    'meta_description' => $item->meta_description,
                    'likes_count' => (int) $item->likes_count,  // from contents table
                    'shares_count' => (int) $item->shares_count,
                    'comment_count' => (int) $item->comment_count,
                ];
            });

            // Return the custom response without unnecessary pagination fields at root
            return response()->json([
                'success' => true,
                'data' => $transformed,
                'meta' => [
                    'current_page' => $contents->currentPage(),
                    'per_page' => $contents->perPage(),
                    'total' => $contents->total(),
                    'last_page' => $contents->lastPage(),
                ],
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

    private function normalizeToS3(string $url): string
    {
        $cdnBaseUrl = 'https://dsfua14fu9fn0.cloudfront.net/images';
        $s3BaseUrl = 'https://s3.amazonaws.com/splurjjimages/images';

        if (str_starts_with($url, $cdnBaseUrl)) {
            return str_replace($cdnBaseUrl, $s3BaseUrl, $url);
        }

        return $url;
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:sub_categories,id',
            'heading' => 'nullable|string',
            'author' => 'nullable|string',
            'date' => 'nullable|date',
            'sub_heading' => 'nullable|string',
            'body1' => 'nullable|string',
            'image2' => 'nullable',
            'tags' => 'nullable',
            'status' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
        ]);

        // Role-based status
        if (in_array($user->role, ['admin', 'editor'])) {
            $validated['status'] = in_array($request->status, ['Approved', 'Draft', 'Review', 'Rejected', 'Archived'])
                ? $request->status
                : 'Draft';
        } elseif ($user->role === 'author') {
            $validated['status'] = $request->status === 'Published' ? 'Published' : 'Draft';
        } else {
            $validated['status'] = 'Draft';
        }

        // Handle image uploads and URLs
        $uploadedImages = [];

        if ($request->has('image2')) {
            $imageInput = $request->image2;

            if (is_array($imageInput)) {
                foreach ($imageInput as $item) {
                    if ($item instanceof UploadedFile) {
                        $path = Storage::disk('s3')->put('images', $item);
                        $uploadedImages[] = Storage::disk('s3')->url($path);
                    } elseif (is_string($item) && filter_var($item, FILTER_VALIDATE_URL)) {
                        $uploadedImages[] = $this->normalizeToS3(trim($item));
                    }
                }
            } elseif ($imageInput instanceof UploadedFile) {
                $path = Storage::disk('s3')->put('images', $imageInput);
                $uploadedImages[] = Storage::disk('s3')->url($path);
            } elseif (is_string($imageInput) && filter_var($imageInput, FILTER_VALIDATE_URL)) {
                $uploadedImages[] = $this->normalizeToS3(trim($imageInput));
            }
        }

        $validated['image2'] = !empty($uploadedImages) ? json_encode($uploadedImages) : null;

        // Handle tags
        $tagsInput = $request->input('tags');
        $validated['tags'] = is_array($tagsInput)
            ? $tagsInput
            : array_filter(array_map('trim', explode(',', $tagsInput ?? '')));

        // Set user ID
        $validated['user_id'] = $user->id;

        // ✅ Generate Slug
        if (!empty($validated['heading'])) {
            $slug = Str::slug($validated['heading']);

            // Ensure uniqueness
            $originalSlug = $slug;
            $count = 1;
            while (Content::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            $validated['slug'] = $slug;
        }

        $content = Content::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Content created successfully.',
            'data' => $content,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $content = Content::findOrFail($id);

        if ($user->role === 'author' && $content->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.'
            ], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:sub_categories,id',
            'heading' => 'nullable|string',
            'author' => 'nullable|string',
            'date' => 'nullable|date',
            'sub_heading' => 'nullable|string',
            'body1' => 'nullable|string',
            'image2' => 'nullable',
            'tags' => 'nullable',
            'status' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
        ]);

        // Role-based status
        if (in_array($user->role, ['admin', 'editor'])) {
            $validated['status'] = in_array($request->status, ['Approved', 'Draft', 'Review', 'Rejected', 'Archived'])
                ? $request->status
                : $content->status;
        } elseif ($user->role === 'author') {
            $validated['status'] = $content->status === 'Approved'
                ? 'Approved'
                : ($request->status === 'Published' ? 'Published' : 'Draft');
        }

        // Handle image updates
        if ($request->has('image2')) {
            $imageInput = $request->image2;
            $uploadedImages = [];

            if (is_array($imageInput)) {
                foreach ($imageInput as $item) {
                    if ($item instanceof UploadedFile) {
                        $path = Storage::disk('s3')->put('images', $item);
                        $uploadedImages[] = Storage::disk('s3')->url($path);
                    } elseif (is_string($item) && filter_var($item, FILTER_VALIDATE_URL)) {
                        $uploadedImages[] = $this->normalizeToS3(trim($item));
                    }
                }
            } elseif ($imageInput instanceof UploadedFile) {
                $path = Storage::disk('s3')->put('images', $imageInput);
                $uploadedImages[] = Storage::disk('s3')->url($path);
            } elseif (is_string($imageInput) && filter_var($imageInput, FILTER_VALIDATE_URL)) {
                $uploadedImages[] = $this->normalizeToS3(trim($imageInput));
            }

            $validated['image2'] = !empty($uploadedImages)
                ? json_encode($uploadedImages)
                : $content->image2;
        }

        // Handle tags
        $tagsInput = $request->input('tags');
        $validated['tags'] = is_array($tagsInput)
            ? $tagsInput
            : array_filter(array_map('trim', explode(',', $tagsInput ?? '')));

        // ✅ Generate Slug
        if (!empty($validated['heading'])) {
            $slug = Str::slug($validated['heading']);

            // Ensure uniqueness
            $originalSlug = $slug;
            $count = 1;
            while (Content::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            $validated['slug'] = $slug;
        }

        $content->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Content updated successfully.',
            'data' => $content,
        ]);
    }

    public function destroy($id)
    {
        try {
            $content = Content::findOrFail($id);

            // Delete image2 if exists (array or JSON)
            if ($content->image2) {
                $images = json_decode($content->image2, true);

                if (is_array($images)) {
                    foreach ($images as $img) {
                        // Parse path from full URL
                        $parsedUrl = parse_url($img);
                        $path = ltrim($parsedUrl['path'], '/');  // remove leading slash

                        Storage::disk('s3')->delete($path);
                    }
                }
            }

            // Finally delete content
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
