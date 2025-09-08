<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // GET /api/categories
    // public function index(Request $request)
    // {
    //     try {
    //         $categories = Category::select('category_name','id')
    //             ->when($request->search, function ($query, $search) {
    //                 return $query->where('category_name', 'like', "%{$search}%");
    //             })
    //             ->latest()
    //             ->paginate(10);

    //         return response()->json([
    //             'success' => true,
    //             'data'    => $categories
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch categories',
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $categories = Category::with(['subcategories' => function ($query) {
                $query->select('id', 'category_id', 'name');
            }])
                ->select('id', 'category_name', 'category_icon')
                ->when($request->search, function ($query, $search) {
                    return $query->where('category_name', 'like', "%{$search}%");
                })
                // ->latest()
                ->orderBy('id', 'asc')
                ->paginate(10);

            $result = $categories->map(function ($category) {
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->category_name,
                    'category_icon' => $category->category_icon
                        ? url($category->category_icon)
                        : null,
                    'subcategories' => $category->subcategories->map(function ($sub) {
                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                        ];
                    })->values(),  // Even if empty, it will return an array
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/categories/{id}
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    // POST /api/categories
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first',
            ], 401);
        }

        try {
            $validated = $request->validate([
                'category_name' => 'required|string|max:255|unique:categories,category_name',
                'category_icon' => 'nullable|image',
            ]);

            $iconPath = null;
            if ($request->hasFile('category_icon')) {
                $file = $request->file('category_icon');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('category_icons'), $filename);
                $iconPath = 'category_icons/' . $filename;
                $validated['category_icon'] = $iconPath;
            }

            // Generate unique slug
            $slug = Str::slug($validated['category_name']);
            $originalSlug = $slug;
            $count = 2;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
            $validated['slug'] = $slug;

            $category = Category::create($validated);

            // Append full URL to the category_icon in response
            $category->category_icon = $iconPath ? url($iconPath) : null;

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // PUT /api/categories/{id}
    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);

            $validated = $request->validate([
                'category_name' => 'required|string|max:255|unique:categories,category_name,' . $id,
                'category_icon' => 'nullable|image',
            ]);

            $iconPath = $category->category_icon;  // default to old icon

            if ($request->hasFile('category_icon')) {
                // Delete old icon if it exists
                if ($category->category_icon && file_exists(public_path($category->category_icon))) {
                    unlink(public_path($category->category_icon));
                }

                // Store new icon
                $file = $request->file('category_icon');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('category_icons'), $filename);
                $iconPath = 'category_icons/' . $filename;
                $validated['category_icon'] = $iconPath;
            }

            // Generate unique slug
            $slug = Str::slug($validated['category_name']);
            $originalSlug = $slug;
            $count = 2;
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
            $validated['slug'] = $slug;

            $category->update($validated);

            // Add full URL to icon in response
            $category->category_icon = $iconPath ? url($iconPath) : null;

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DELETE /api/categories/{id}
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            // Delete the category_icon file if exists
            if ($category->category_icon && file_exists(public_path($category->category_icon))) {
                unlink(public_path($category->category_icon));
            }
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
