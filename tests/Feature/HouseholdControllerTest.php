<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Household;
use App\Models\UserHousehold;
use App\Models\HouseholdApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class HouseholdControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function store_requires_auth_and_validates_and_creates_household()
    {
        // without auth
        $this->postJson(route('api.households.store'), [])
            ->assertStatus(401);

        // with auth but missing fields
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson(route('api.households.store'), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['name', 'identifier']]);

        // successful creation
        $payload = ['name' => 'My Home', 'identifier' => 'HOME123'];
        $this->postJson(route('api.households.store'), $payload)
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Household created successfully']);

        $this->assertDatabaseHas('households', array_merge($payload, ['user_id' => $user->id]));
        $this->assertDatabaseHas('user_households', ['user_id' => $user->id]);
    }

    /** @test */
    public function index_returns_paginated_households_and_filters_search()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Household::factory()->create(['name' => 'Foo Home', 'identifier' => 'FOO1', 'user_id' => 1]);
        Household::factory()->create(['name' => 'Bar Home', 'identifier' => 'BAR2', 'user_id' => 1]);

        $this->getJson(route('api.households.index'))
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(2, 'data');

        // search filter
        $this->getJson(route('api.households.index', ['search' => 'Foo']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Foo Home']);
    }

    /** @test */
    public function show_returns_household_and_requires_auth()
    {
        $user = User::factory()->create();
        $household = Household::factory()->create(['user_id' => $user->id]);
        // no auth
        $this->getJson(route('api.households.show', $household))
            ->assertStatus(401);

        // with auth
        Sanctum::actingAs($user);
        $this->getJson(route('api.households.show', $household))
            ->assertStatus(200)
            ->assertJson([
                'id'         => $household->id,
                'name'       => $household->name,
                'identifier' => $household->identifier,
            ]);
    }

    /** @test */
    public function get_user_relationship_varies_by_role()
    {
        // Create four users
        $owner     = User::factory()->create();
        $member    = User::factory()->create();
        $applicant = User::factory()->create();
        $outsider  = User::factory()->create();

        // Create a household owned by $owner
        $household = Household::factory()->create(['user_id' => $owner->id]);

        // Mark $member as a household member
        UserHousehold::factory()->create([
            'user_id'      => $member->id,
            'household_id' => $household->id,
        ]);

        // Mark $applicant as having applied
        HouseholdApplication::factory()->create([
            'user_id'      => $applicant->id,
            'household_id' => $household->id,
        ]);

        // Helper to fetch and return the raw response body
        $getRel = fn($user) => tap(
            $this->actingAs($user)->getJson(route('api.households.get_user_relationship', $household)),
            fn($response) => $response->assertStatus(200)
        )->getContent();

        // Owner should get index 2
        $this->assertEquals(
            Household::getUserRelationship()[2],
            $getRel($owner)
        );

        // Member should get index 1
        $this->assertEquals(
            Household::getUserRelationship()[1],
            $getRel($member)
        );

        // Applicant should get index 3
        $this->assertEquals(
            Household::getUserRelationship()[3],
            $getRel($applicant)
        );

        // Outsider should get index 0
        $this->assertEquals(
            Household::getUserRelationship()[0],
            $getRel($outsider)
        );
    }


    /** @test */
    public function list_households_for_user_requires_owner_and_returns_list()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $hh1 = Household::factory()->create(['user_id' => $user->id]);
        $hh2 = Household::factory()->create(['user_id' => $user->id]);
        Household::factory()->create(['user_id' => $other->id]);
        UserHousehold::factory()->create([
            'user_id'      => $user->id,
            'household_id' => $hh1->id,
        ]);
        UserHousehold::factory()->create([
            'user_id'      => $user->id,
            'household_id' => $hh2->id,
        ]);

        // unauthorized
        Sanctum::actingAs($other);
        $this->getJson(route('api.households.list', $user))
            ->assertStatus(401);

        // as owner
        Sanctum::actingAs($user);
        $this->getJson(route('api.households.list', $user))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function update_requires_owner_and_validates_and_updates()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $hh = Household::factory()->create([
            'user_id'   => $owner->id,
            'name'      => 'Old',
            'identifier' => 'OLDID'
        ]);

        // unauthorized
        Sanctum::actingAs($other);
        $this->putJson(route('api.households.update', $hh), [])
            ->assertStatus(401);

        // validation fail
        Sanctum::actingAs($owner);
        $this->putJson(route('api.households.update', $hh), ['name' => '', 'identifier' => ''])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['name', 'identifier']]);

        // successful update
        $data = ['name' => 'New', 'identifier' => 'NEWID'];
        $this->putJson(route('api.households.update', $hh), $data)
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Household updated successfully']);

        $this->assertDatabaseHas('households', array_merge(['id' => $hh->id], $data));
    }

    /** @test */
    public function destroy_requires_owner_and_deletes_household()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $hh = Household::factory()->create(['user_id' => $owner->id]);

        // unauthorized
        Sanctum::actingAs($other);
        $this->deleteJson(route('api.households.destroy', $hh))
            ->assertStatus(401);

        // successful delete
        Sanctum::actingAs($owner);
        $this->deleteJson(route('api.households.destroy', $hh))
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Household deleted successfully']);

        $this->assertDatabaseMissing('households', ['id' => $hh->id]);
    }

    /** @test */
    public function remove_member_behaves_correctly_for_owner_member_and_admin()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $admin = User::factory()->create(['admin' => true]);

        $hh = Household::factory()->create(['user_id' => $owner->id]);
        UserHousehold::factory()->create(['user_id' => $member->id, 'household_id' => $hh->id]);

        // outsider cannot remove
        Sanctum::actingAs($outsider);
        $this->deleteJson(route('api.households.removeMember', [$hh, $member]))
            ->assertStatus(401);

        // member removing self
        Sanctum::actingAs($member);
        $this->deleteJson(route('api.households.removeMember', [$hh, $member]))
            ->assertStatus(200)
            ->assertExactJson(['message' => 'User removed from household.']);

        // owner removing member
        UserHousehold::factory()->create(['user_id' => $member->id, 'household_id' => $hh->id]);
        Sanctum::actingAs($owner);
        $this->deleteJson(route('api.households.removeMember', [$hh, $member]))
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'User removed from household.']);

        // owner removing self and transferring or deleting
        $hh2 = Household::factory()->create(['user_id' => $owner->id]);
        // only owner is member -> deletion
        Sanctum::actingAs($owner);
        $this->deleteJson(route('api.households.removeMember', [$hh2, $owner]))
            ->assertStatus(200)
            ->assertExactJson(['message' => 'Household deleted (only owner existed).']);

        // with another member -> transfer ownership
        $hh3 = Household::factory()->create(['user_id' => $owner->id]);
        $newMember = User::factory()->create();
        UserHousehold::factory()->create(['user_id' => $newMember->id, 'household_id' => $hh3->id]);
        Sanctum::actingAs($owner);
        $resp = $this->deleteJson(route('api.households.removeMember', [$hh3, $owner]));
        $resp->assertStatus(200)
            ->assertJsonStructure(['message', 'new_owner_id']);
        $this->assertEquals($newMember->id, Household::find($hh3->id)->user_id);

        // admin can remove anyone
        $hh4 = Household::factory()->create(['user_id' => $owner->id]);
        UserHousehold::factory()->create(['user_id' => $member->id, 'household_id' => $hh4->id]);
        Sanctum::actingAs($admin);
        $this->deleteJson(route('api.households.removeMember', [$hh4, $member]))
            ->assertStatus(200)
            ->assertExactJson(['message' => 'User removed from household.']);
    }
}
