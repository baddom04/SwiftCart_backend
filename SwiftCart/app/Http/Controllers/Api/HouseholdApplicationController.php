<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\HouseholdApplication;
use App\Models\UserHousehold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HouseholdApplicationController extends Controller
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
            'household_id' => 'required|gte:1|integer|exists:households,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();
        $userID = Auth::user()->id;
        $householdID = $validated['household_id'];

        $exists = DB::table('household_applications')
            ->where('user_id', $userID)
            ->where('household_id', $householdID)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'This application already exists',
            ], 400);
        }

        $exists = DB::table('user_households')
            ->where('user_id', $userID)
            ->where('household_id', $householdID)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'The user is already in this household',
            ], 400);
        }

        HouseholdApplication::factory()->create([
            'user_id' => $userID,
            'household_id' => $householdID
        ]);

        return response()->json(['Message' => 'Application created successfully'], 200);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HouseholdApplication $application)
    {
        $authUser = Auth::user();

        if ($authUser->id !== $application->user->id && !$authUser->admin && $authUser->id !== $application->household->user->id) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        $application->delete();

        return response()->json(['Message' => 'Application deleted successfully'], 200);
    }

    public function accept_user(HouseholdApplication $application)
    {
        $authUser = Auth::user();

        if (!$authUser->admin && $authUser->id !== $application->household->user->id) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        UserHousehold::factory()->create(['user_id' => $application->user->id, 'household_id' => $application->household->id]);

        $application->delete();

        return response()->json(['Message' => 'Application accepted successfully'], 200);
    }
    public function get_applications(Request $request)
    {
        return HouseholdApplication::whereIn('household_id', Auth::user()->user_households->pluck('household_id'))->get();
    }
}
