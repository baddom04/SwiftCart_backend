<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Store;
use Illuminate\Http\Request;
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
                'detail' => 'nullable|string',
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
                'detail' => 'nullable|string',
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

    public function getCountries()
    {
        $countries = Location::distinct()->pluck('country');

        return response()->json([
            'countries' => $countries
        ]);
    }
    public function getCities(Request $request)
    {
        $country = $request->query('country');

        if (!$country) {
            return response()->json([
                'error' => 'Missing required query parameter: country'
            ], 400);
        }

        $cities = Location::where('country', $country)
            ->distinct()
            ->pluck('city');

        return response()->json([
            'cities' => $cities
        ]);
    }
    public function getStreets(Request $request)
    {
        $country = $request->query('country');
        $city    = $request->query('city');

        if (!$country || !$city) {
            return response()->json([
                'error' => 'Missing required query parameters: country and/or city'
            ], 400);
        }

        $streets = Location::where('country', $country)
            ->where('city', $city)
            ->distinct()
            ->pluck('street');

        return response()->json([
            'streets' => $streets
        ]);
    }
    public function getDetails(Request $request)
    {
        $country = $request->query('country');
        $city    = $request->query('city');
        $street  = $request->query('street');

        if (!$country || !$city || !$street) {
            return response()->json([
                'error' => 'Missing required query parameters: country, city, and/or street'
            ], 400);
        }

        $details = Location::where('country', $country)
            ->where('city', $city)
            ->where('street', $street)
            ->distinct()
            ->pluck('detail');

        return response()->json([
            'details' => $details
        ]);
    }
}
