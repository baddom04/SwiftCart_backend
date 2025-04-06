<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Store $store)
    {
        if ($store->location !== null) {
            return response([
                'error' => 'The location to this store already exists'
            ], 400)->json();
        }

        $validator = Validator::make(
            $request->all(),
            [
                'country' => 'required|string',
                'zip_code' => 'required|string|size:4|regex:/^\d{4}$/',
                'city' => 'required|string',
                'street' => 'required|string',
                'detail' => 'string',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $location = Location::factory()->create([
            'country' => $validated['country'],
            'zip_code' => $validated['zip_code'],
            'city' => $validated['city'],
            'street' => $validated['street'],
            'detail' => $validated['detail'],
            'store_id' => $store->id,
        ]);

        return $location;
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        return $store->location;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store)
    {
        if ($store->location === null) {
            return response([
                'error' => 'The location to this store does not exist'
            ], 400)->json();
        }

        $validator = Validator::make(
            $request->all(),
            [
                'country' => 'required|string',
                'zip_code' => 'required|string|size:4|regex:/^\d{4}$/',
                'city' => 'required|string',
                'street' => 'required|string',
                'detail' => 'string',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $store->location->country = $validated['country'];
        $store->location->zip_code = $validated['zip_code'];
        $store->location->city = $validated['city'];
        $store->location->street = $validated['street'];
        $store->location->detail = $validated['detail'];
        $store->location->save();

        return $store->location;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $store->location->delete();
        return response()->json(['Message' => 'Location deleted successfully'], 200);
    }
}
