<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\FooterSection;
use App\Models\Page;

class FooterSectionController extends Controller
{
    public function index()
    {
        $sections = FooterSection::all();

        // Optionally include page data
        $sections = $sections->map(function ($section) {
            $section->page_data = Page::whereIn('name', $section->pages)->get();
            return $section;
        });

        return response()->json($sections);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'pages' => 'nullable|array',
        ]);

        $section = FooterSection::create([
            'title' => $request->title,
            'pages' => $request->pages,
        ]);

        return response()->json($section, 201);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'pages' => 'array',
        ]);

        $section = FooterSection::findOrFail($id);
        $section->update([
            'pages' => $request->pages,
        ]);

        return response()->json($section);
    }
}

