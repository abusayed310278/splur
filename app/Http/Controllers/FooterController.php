<?php 
namespace App\Http\Controllers;

use App\Models\Footer;
use Illuminate\Http\Request;

class FooterController extends Controller
{
    public function index()
    {
        return response()->json(Footer::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'named_url' => 'required|string|max:255',
        ]);

        $footer = Footer::create($request->only('name', 'named_url'));

        return response()->json([
            'success' => true,
            'message' => 'Footer item created successfully.',
            'data' => $footer,
        ], 201);
    }

    public function show($id)
    {
        $footer = Footer::findOrFail($id);
        return response()->json($footer);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'named_url' => 'sometimes|string|max:255',
        ]);

        $footer = Footer::findOrFail($id);
        $footer->update($request->only('name', 'named_url'));

        return response()->json([
            'success' => true,
            'message' => 'Footer item updated successfully.',
            'data' => $footer,
        ]);
    }

    public function destroy($id)
    {
        Footer::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Footer item deleted successfully.',
        ]);
    }
}
