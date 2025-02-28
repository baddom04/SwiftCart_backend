<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\HouseholdApplication;
use App\Models\User;
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
    public function store(Request $request, Household $household)
    {
        $userID = Auth::user()->id;
        $householdID = $household->id;

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
    public function get_sent_applications(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->admin && $authUser->id !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return HouseholdApplication::where('user_id', $user->id)->get();
    }
    public function get_sent_households(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->admin && $authUser->id !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        $householdIds = HouseholdApplication::where('user_id', $user->id)
            ->pluck('household_id')
            ->unique();

        $households = Household::whereIn('id', $householdIds)->get();

        return response()->json($households);
    }
    public function get_received_applications(Request $request, Household $household)
    {
        $authUser = Auth::user();

        if (!$authUser->admin && $authUser->id !== $household->user->id) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return HouseholdApplication::where('household_id', $household->id)->get();
    }
}
