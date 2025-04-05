<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Map;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MapController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        if ($store->map !== null) {
            return response()->json([
                'error' => 'The map to this store already exists'
            ], 400);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'x_size' => 'required|integer|min:2|max:100',
                'y_size' => 'required|integer|min:2|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $map = Map::factory()->create([
            'x_size' => $validated['x_size'],
            'y_size' => $validated['y_size'],
            'store_id' => $store->id,
        ]);

        return $map;
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        return $store->map;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store)
    {
        if ($store->map === null) {
            return response([
                'error' => 'The map to this store does not exist'
            ], 400)->json();
        }

        $validator = Validator::make(
            $request->all(),
            [
                'x_size' => 'required|integer|min:2|max:100',
                'y_size' => 'required|integer|min:2|max:100',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $store->map->y_size = $validated['y_size'];
        $store->map->x_size = $validated['x_size'];
        $store->map->save();

        $store->map->segments()->where(function ($query) use ($validated) {
            $query->where('x', '>=', $validated['x_size'])
                ->orWhere('y', '>=', $validated['y_size']);
        })->delete();

        return $store->map;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $store->map->delete();
        return response()->json(['Message' => 'Map deleted successfully'], 200);
    }
}
