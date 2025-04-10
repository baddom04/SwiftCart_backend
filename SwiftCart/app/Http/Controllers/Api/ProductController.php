<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Map;
use App\Models\MapSegment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                'description' => 'nullable|string|max:255',
                'price' => 'required|integer|between:0,9999999'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $product = Product::factory()->create([
            'name' => $validated['name'],
            'brand' => $validated['brand'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'map_segment_id' => $segment->id,
        ]);

        return $product;
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
                'description' => 'nullable|string|max:255',
                'price' => 'required|integer|between:0,9999999'
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
        $product->price = $validated['price'];
        $product->save();

        return $product;
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
    public function updateSegment(Request $request, MapSegment $segment, Product $product)
    {
        if ($product->map_segment->id !== $segment->id) {
            return response()->json(
                [
                    'errors' => 'The given product\'s segment id does not match the given segment\'s id'
                ],
                400
            );
        }


        $validator = Validator::make($request->all(), [
            'segment_id' => 'required|exists:map_segments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        $newSegmentId = $validator->validated()['segment_id'];

        $product->segment_id = $newSegmentId;
        $product->save();

        return response()->json([
            'Message' => 'Product segment updated successfully',
        ], 200);
    }
}
