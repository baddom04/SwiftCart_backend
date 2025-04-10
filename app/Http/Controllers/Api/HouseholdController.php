<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use App\Models\HouseholdApplication;
use App\Models\User;
use App\Models\UserHousehold;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HouseholdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $perPage = $request->query('per_page', 5);

        $query = Household::query();

        if (trim($search) !== '') {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('identifier', 'LIKE', "%{$search}%");
        }

        $households = $query->paginate($perPage);

        return HouseholdResource::collection($households);
    }
    public function list(User $user)
    {
        return HouseholdResource::collection($user->memberHouseholds);
    }
    public function list_users(Household $household)
    {
        return $household->users;
    }
    public function get_user_relationship(Household $household)
    {
        $authUser = Auth::user();
        $relationships = Household::getUserRelationship();

        if ($household->user->id === $authUser->id)
            return $relationships[2];

        if (UserHousehold::where('household_id', $household->id)
            ->where('user_id', $authUser->id)
            ->exists()
        ) {
            return $relationships[1];
        }

        if (HouseholdApplication::where('household_id', $household->id)
            ->where('user_id', $authUser->id)
            ->exists()
        ) {
            return $relationships[3];
        }

        return $relationships[0];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:20',
            'identifier' => 'required|string|unique:households|max:20',
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
            'identifier' => $validated['identifier'],
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
    public function show(Household $household)
    {
        return $household;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Household $household)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:20',
            'identifier' => [
                'required',
                'string',
                'max:20',
                Rule::unique('households')->ignore($household->id),
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->messages(),
            ], 400);
        }

        $validated = $validator->validated();
        $household->name = $validated['name'];
        $household->identifier = $validated['identifier'];
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
        $household->delete();

        return response()->json([
            'Message' => 'Household deleted successfully'
        ], 200);
    }

    public function removeMember(Household $household, User $user)
    {
        $authUser = Auth::user();

        if (
            !$authUser->admin &&
            $household->user->id !== $authUser->id &&
            $authUser->id !== $user->id
        ) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $isOwner = $household->user_id === $user->id;
        $isMember = $household->users()->where('users.id', $user->id)->exists();

        if (!$isOwner && !$isMember) {
            return response()->json(['error' => 'User not found in household.'], 404);
        }

        if (!$isOwner) {
            $household->users()->detach($user->id);
            return response()->json(['message' => 'User removed from household.'], 200);
        }

        $members = $household->users()->where('users.id', '!=', $user->id)->get();

        if ($members->isEmpty()) {
            $household->delete();
            return response()->json(['message' => 'Household deleted (only owner existed).'], 200);
        } else {
            $newOwner = $members->first();
            $household->user_id = $newOwner->id;
            $household->save();

            $household->users()->detach($user->id);

            return response()->json([
                'message' => 'Ownership transferred and user removed from household.',
                'new_owner_id' => $newOwner->id
            ], 200);
        }
    }
}
