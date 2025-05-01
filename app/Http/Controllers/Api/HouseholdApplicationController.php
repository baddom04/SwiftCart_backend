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

class HouseholdApplicationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Household $household)
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
     * Remove the specified resource from storage.
     */
    public function destroy(HouseholdApplication $application)
    {
        $authUser = Auth::user();

        if ($authUser->id !== $application->user->id && !$authUser->admin && $authUser->id !== $application->household->user->id) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
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
            ], 401);
        }

        UserHousehold::factory()->create(['user_id' => $application->user->id, 'household_id' => $application->household->id]);

        $application->delete();

        return response()->json(['Message' => 'Application accepted successfully'], 200);
    }
    public function get_sent_applications(User $user)
    {
        return HouseholdApplication::where('user_id', $user->id)->get();
    }
    public function get_sent_households(User $user)
    {
        $householdIds = HouseholdApplication::where('user_id', $user->id)
            ->pluck('household_id')
            ->unique();

        $households = Household::whereIn('id', $householdIds)->get();

        return $households;
    }
    public function get_received_applications(Request $request, Household $household)
    {
        return HouseholdApplication::where('household_id', $household->id)->get();
    }

    public function get_received_users(Household $household)
    {
        $userIds = HouseholdApplication::where('household_id', $household->id)
            ->pluck('user_id')
            ->unique();

        $users = User::whereIn('id', $userIds)->get();

        return response()->json($users);
    }
    public function find(User $user, Household $household)
    {
        $application = HouseholdApplication::where('household_id', $household->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$application) {
            return response()->json(['message' => 'No matching application found'], 404);
        }

        return $application;
    }
}
