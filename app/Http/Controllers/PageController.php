<?php

namespace App\Http\Controllers;
use App\Models\Page;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        return Page::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'body' => 'required',
            'status' => 'required|in:Draft,Published',
        ]);

        return Page::create($request->all());
    }

    public function show($id)
    {
        return Page::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);
        $page->update($request->all());

        return $page;
    }

    public function destroy($id)
    {
        Page::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
