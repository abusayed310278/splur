<?php

namespace App\Http\Controllers;

use App\Models\SubCategory;
use Illuminate\Http\Request;

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

    // public function index()
    // {
    //     $subcategories = SubCategory::with('category:id,id,category_name') // eager load only needed fields
    //         ->select('id', 'category_id', 'name')
    //         ->paginate(10);

    //     return response()->json($subcategories);
    // }


    /**
     * Store a newly created subcategory in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
        ]);

        $subcategory = SubCategory::create($validated);

        return response()->json([
            'message' => 'Subcategory created successfully.',
            'data' => $subcategory,
        ], 201);
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
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
        ]);

        $subcategory->update($validated);

        return response()->json([
            'message' => 'Subcategory updated successfully.',
            'data' => $subcategory,
        ]);
    }

    /**
     * Remove the specified subcategory from storage.
     */
    public function destroy(SubCategory $subcategory)
    {
        $subcategory->delete();

        return response()->json([
            'message' => 'Subcategory deleted successfully.',
        ], 204);
    }
}
