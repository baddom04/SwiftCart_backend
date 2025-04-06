<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroceryResource;
use App\Models\Grocery;
use App\Models\Household;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GroceryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Household $household)
    {
        return GroceryResource::collection($household->groceries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Household $household)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:20',
            'quantity'    => 'nullable|gte:0|integer|lte:999',
            'unit' => ['nullable', Rule::in(Grocery::getUnitTypes())],
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        if (isset($validated['quantity']) && !isset($validated['unit']) || !isset($validated['quantity']) && isset($validated['unit'])) {
            return response()->json([
                'error' => 'Either both unit and quantity should be set, or none of them.',
            ], 400);
        }

        Grocery::factory()->create([
            'name' => $validated['name'],
            'quantity' => $validated['quantity'],
            'description' => $validated['description'],
            'unit' => $validated['unit'],
            'household_id' => $household->id,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Grocery created successfully'
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Household $household, Grocery $grocery)
    {
        return $grocery;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Household $household, Grocery $grocery)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:20',
            'quantity'    => 'nullable|gte:0|integer|lte:999',
            'unit' => ['nullable', Rule::in(Grocery::getUnitTypes())],
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        if (isset($validated['quantity']) && !isset($validated['unit']) || !isset($validated['quantity']) && isset($validated['unit'])) {
            return response()->json([
                'error' => 'Either both unit and quantity should be set, or none of them.',
            ], 400);
        }

        if (isset($validated['name'])) {
            $grocery['name'] = $validated['name'];
        }
        if (isset($validated['description'])) {
            $grocery['description'] = $validated['description'];
        }
        if (isset($validated['unit'])) {
            $grocery['unit'] = $validated['unit'];
        }
        if (isset($validated['quantity'])) {
            $grocery['quantity'] = $validated['quantity'];
        }

        $grocery->save();

        return response()->json([
            'message' => 'Grocery updated successfully'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Household $household, Grocery $grocery)
    {
        $grocery->delete();

        return response()->json([
            'message' => 'Grocery deleted successfully'
        ], 200);
    }
}
