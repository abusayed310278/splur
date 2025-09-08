<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of the subcategories.
     */
    public function index()
    {
        $subcategories = SubCategory::select('id', 'category_id', 'name')->paginate(10);
        return response()->json($subcategories);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'name' => 'required|string|max:255',
            ]);

            // Generate unique slug scoped to category_id
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug ?: 'subcategory';
            $count = 2;

            while (SubCategory::where('category_id', $validated['category_id'])
                    ->where('slug', $slug)
                    ->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            $validated['slug'] = $slug;

            $subcategory = SubCategory::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subcategory created successfully.',
                'data' => $subcategory,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subcategory.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified subcategory.
     */
    public function show(SubCategory $subcategory)
    {
        $subcategory->load('category');

        return response()->json($subcategory);
    }

    /**
     * Update the specified subcategory in storage.
     */
    public function update(Request $request, SubCategory $subcategory)
    {
        try {
            $validated = $request->validate([
                'category_id' => 'sometimes|exists:categories,id',
                'name' => 'sometimes|string|max:255',
            ]);

            // Generate unique slug scoped to category_id
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug ?: 'subcategory';
            $count = 2;

            while (SubCategory::where('category_id', $validated['category_id'])
                    ->where('slug', $slug)
                    ->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }

            $validated['slug'] = $slug;

            $subcategory->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subcategory updated successfully.',
                'data' => $subcategory,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subcategory update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified subcategory from storage.
     */
    public function destroy($id)
    {
        try {
            $subcategory = SubCategory::find($id);

            if (!$subcategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subcategory not found.',
                ], 404);
            }

            $subcategory->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subcategory deleted successfully.',
            ], 200);  // You can use 204 if no message is needed
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subcategory.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
