<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Map;
use App\Models\MapSegment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Map $map)
    {
        return $map->products;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, MapSegment $segment)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:20',
                'brand' => 'required|string|max:20',
                'description' => 'required|string|max:255',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        Product::factory()->create([
            'name' => $validated['name'],
            'brand' => $validated['brand'],
            'description' => $validated['description'],
            'map_segment_id' => $segment->id,
        ]);

        return response()->json(['Message' => 'Product created successfully'], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load('map_segment');
        return $product;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MapSegment $segment, Product $product)
    {
        if ($segment->id !== $product->map_segment_id) {
            return response()->json([
                'error' => 'The given product does not belong to the given segment',
            ], 400);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:20',
                'brand' => 'required|string|max:20',
                'description' => 'required|string|max:255',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $product->name = $validated['name'];
        $product->brand = $validated['brand'];
        $product->description = $validated['description'];
        $product->save();

        return response()->json(['Message' => 'Product updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MapSegment $segment, Product $product)
    {
        if ($segment->id !== $product->map_segment_id) {
            return response()->json([
                'error' => 'The given product does not belong to the given segment',
            ], 400);
        }

        $product->delete();
        return response()->json(['Message' => 'Product deleted successfully'], 200);
    }
}
