<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\UserHousehold;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HouseholdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();
        $currentUser = Auth::user();
        $household = Household::factory()->create([
            'name' => $validated['name'],
            'user_id' => $currentUser->id,
        ]);

        UserHousehold::factory()->create([
            'user_id' => $currentUser->id,
            'household_id' => $household->id,
        ]);

        return response()->json([
            'Message' => 'Household created successfully'
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Household $household)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $authUser = Auth::user();

        if ($authUser->id !== $household->user->id && !$authUser->admin) {
            return response()->json([
                'error' => 'Unauthorized. Only the account owner or an admin can update this household.'
            ], 403);
        }

        $validated = $validator->validated();
        $household->name = $validated['name'];
        $household->save();

        return response()->json([
            'Message' => 'Household updated successfully'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Household $household)
    {
        $authUser = Auth::user();

        if ($authUser->id !== $household->user->id && !$authUser->admin) {
            return response()->json([
                'error' => 'Unauthorized. Only the account owner or an admin can update this household.'
            ], 403);
        }

        $household->delete();

        return response()->json([
            'Message' => 'Household deleted successfully'
        ], 200);
    }
}
