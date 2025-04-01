<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $perPage = $request->query('per_page', 5);

        $query = Store::query();

        $query->has('location')
            ->has('map')
            ->whereHas('map', function ($q) {
                $q->has('map_segments');
            });

        if (trim($search) !== '') {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhereHas('location', function ($query) use ($search) {
                    $query->where('country', 'LIKE', "%{$search}%")
                        ->orWhere('city', 'LIKE', "%{$search}%")
                        ->orWhere('zipcode', 'LIKE', "%{$search}%")
                        ->orWhere('street', 'LIKE', "%{$search}%")
                        ->orWhere('detail', 'LIKE', "%{$search}%");
                });
        }

        $stores = $query->paginate($perPage);
        $stores->load('location');

        return $stores;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->store !== null) {
            return response()->json([
                'error' => 'This user already has a store.'
            ]);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:50',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $store = Store::factory()->create([
            'name' => $validated['name'],
            'user_id' => $user->id,
        ]);

        return $store;
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        if ($store->location !== null)
            $store->load('location');

        if ($store->map !== null) {
            $store->load('map');
            $store->map->load('sections');
            $store->map->load('map_segments');
            $store->map->map_segments->load('products');
        }
        return $store;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:50',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();

        $store->name = $validated['name'];
        $store->save();

        return $store;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $store->delete();
        return response()->json(['Message' => 'Store deleted successfully'], 200);
    }

    public function get_my_store()
    {
        $user = Auth::user();

        if ($user->store === null)
            return response()->json(null, 204);

        $store = $user->store;

        if ($store->location !== null)
            $store->load('location');

        if ($store->map !== null) {
            $store->load('map');
            $store->map->load('sections');
            $store->map->load('map_segments');
            $store->map->map_segments->load('products');
        }
        return $store;
    }
}
