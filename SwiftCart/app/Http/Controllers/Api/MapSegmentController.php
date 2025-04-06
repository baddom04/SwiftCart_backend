<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Map;
use App\Models\MapSegment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MapSegmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Map $map)
    {
        $map->load('map_segments');
        $map->map_segments->load('products');
        return $map->map_segments;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Map $map)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'section_id' => 'nullable|exists:sections,id',
                'x' => ['required', 'integer', 'min:0', "max:{$map->x_size}",],
                'y' => ['required', 'integer', 'min:0', "max:{$map->y_size}",],
                'type' => ['required', 'string', Rule::in(MapSegment::getSegmentTypes())],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $segment = MapSegment::factory()->create([
            'section_id' => $validated['section_id'],
            'x' => $validated['x'],
            'y' => $validated['y'],
            'type' => $validated['type'],
            'map_id' => $map->id,
        ]);

        return $segment;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Map $map, MapSegment $segment)
    {
        if ($segment->map_id !== $map->id) {
            return response()->json([
                'error' => 'The given segment does not belong to the given map',
            ], 400);
        }

        Log::info("Segment update");

        $validator = Validator::make(
            $request->all(),
            [
                'section_id' => 'nullable|exists:sections,id',
                'x' => ['required', 'integer', 'min:0', "max:{$map->x_size}",],
                'y' => ['required', 'integer', 'min:0', "max:{$map->y_size}",],
                'type' => ['required', 'string', Rule::in(MapSegment::getSegmentTypes())],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $segment->section_id = $validated['section_id'];
        $segment->x = $validated['x'];
        $segment->y = $validated['y'];
        $segment->type = $validated['type'];
        $segment->save();

        return $segment;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Map $map, MapSegment $segment)
    {
        if ($segment->map_id !== $map->id) {
            return response()->json([
                'error' => 'The given segment does not belong to the given map',
            ], 400);
        }

        $segment->delete();
        return response()->json(['Message' => 'MapSegment deleted successfully'], 200);
    }
}
