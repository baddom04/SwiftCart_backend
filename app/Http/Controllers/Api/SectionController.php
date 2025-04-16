<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Map;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Map $map)
    {
        return $map->sections;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Map $map)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('sections')->where(function ($query) use ($map) {
                        return $query->where('map_id', $map->id);
                    })
                ],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $section = Section::factory()->create([
            'name' => $validated['name'],
            'map_id' => $map->id,
        ]);

        return $section;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Map $map, Section $section)
    {
        if ($section->map_id !== $map->id) {
            return response()->json([
                'error' => 'The given section does not belong to the given map',
            ], 400);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('sections')->ignore($section->id),
                ]
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $section->name = $validated['name'];
        $section->save();

        return $section;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Map $map, Section $section)
    {
        if ($section->map_id !== $map->id) {
            return response()->json([
                'error' => 'The given section does not belong to the given map',
            ], 400);
        }

        $section->delete();
        return response()->json(['Message' => 'Section deleted successfully'], 200);
    }
}
