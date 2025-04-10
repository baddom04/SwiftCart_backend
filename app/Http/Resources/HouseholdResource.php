<?php

namespace App\Http\Resources;

use App\Models\HouseholdApplication;
use App\Models\UserHousehold;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class HouseholdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authUser = Auth::user();
        $relationships = \App\Models\Household::getUserRelationship();

        // Determine the relationship
        if ($this->user->id === $authUser->id) {
            $relationship = $relationships[2];
        } elseif (UserHousehold::where('household_id', $this->id)
            ->where('user_id', $authUser->id)
            ->exists()
        ) {
            $relationship = $relationships[1];
        } elseif (HouseholdApplication::where('household_id', $this->id)
            ->where('user_id', $authUser->id)
            ->exists()
        ) {
            $relationship = $relationships[3];
        } else {
            $relationship = $relationships[0];
        }

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'identifier'   => $this->identifier,
            'user_id'      => $this->user_id,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'relationship' => $relationship,
        ];
    }
}
