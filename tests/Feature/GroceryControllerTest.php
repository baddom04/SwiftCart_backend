<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Household;
use App\Models\Grocery;
use App\Models\UserHousehold;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class GroceryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $member;
    protected User $outsider;
    protected User $admin;
    protected Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->owner    = User::factory()->create();
        $this->member   = User::factory()->create();
        $this->outsider = User::factory()->create();
        $this->admin    = User::factory()->create(['admin' => true]);

        // Create household owned by $owner
        $this->household = Household::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        // Attach $member to household
        UserHousehold::factory()->create([
            'user_id'      => $this->member->id,
            'household_id' => $this->household->id,
        ]);
        // Attach $owner to household
        UserHousehold::factory()->create([
            'user_id' => $this->owner->id,
            'household_id' => $this->household->id,
        ]);
    }

    /** @test */
    public function index_requires_authentication_and_membership()
    {
        // Unauthenticated
        $this->getJson(route('api.groceries.index', $this->household))
            ->assertStatus(401);

        // Authenticated but not a member
        Sanctum::actingAs($this->outsider);
        $this->getJson(route('api.groceries.index', $this->household))
            ->assertStatus(401);
    }

    /** @test */
    public function index_returns_groceries_for_member_and_admin()
    {
        // Seed some groceries
        Grocery::factory()->count(3)->create([
            'household_id' => $this->household->id,
            'user_id'      => $this->member->id,
        ]);

        // As member
        Sanctum::actingAs($this->member);
        $this->getJson(route('api.groceries.index', $this->household))
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'quantity', 'unit', 'description', 'household_id', 'user_id']
                ]
            ]);

        // As admin
        Sanctum::actingAs($this->admin);
        $this->getJson(route('api.groceries.index', $this->household))
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function store_requires_membership_and_validates_and_creates()
    {
        $valid = [
            'name'        => 'Apples',
            'quantity'    => 2,
            'unit'        => 'kilogram',
            'description' => 'Fresh green apples',
        ];

        // Unauthenticated
        $this->postJson(route('api.groceries.store', $this->household), $valid)
            ->assertStatus(401);

        // Authenticated outsider
        Sanctum::actingAs($this->outsider);
        $this->postJson(route('api.groceries.store', $this->household), $valid)
            ->assertStatus(401);

        // Authenticated member but missing required name
        Sanctum::actingAs($this->member);
        $this->postJson(route('api.groceries.store', $this->household), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['name']]);

        // Quantity without unit
        $this->postJson(route('api.groceries.store', $this->household), [
            'name'     => 'Bananas',
            'quantity' => 5,
        ])->assertStatus(400)
            ->assertExactJson(['error' => 'Either both unit and quantity should be set, or none of them.']);

        // Unit without quantity
        $this->postJson(route('api.groceries.store', $this->household), [
            'name' => 'Oranges',
            'unit' => 'liter',
        ])->assertStatus(400)
            ->assertExactJson(['error' => 'Either both unit and quantity should be set, or none of them.']);

        // Successful creation
        $this->postJson(route('api.groceries.store', $this->household), $valid)
            ->assertStatus(200)
            ->assertExactJson(['message' => 'Grocery created successfully']);

        $this->assertDatabaseHas('groceries', array_merge($valid, [
            'household_id' => $this->household->id,
            'user_id'      => $this->member->id,
        ]));
    }

    /** @test */
    public function show_requires_membership_and_returns_item()
    {
        $grocery = Grocery::factory()->create([
            'household_id' => $this->household->id,
            'user_id'      => $this->member->id,
            'name'         => 'Milk',
        ]);

        // Unauthenticated
        $this->getJson(route('api.groceries.show', [$this->household, $grocery]))
            ->assertStatus(401);

        // Authenticated outsider
        Sanctum::actingAs($this->outsider);
        $this->getJson(route('api.groceries.show', [$this->household, $grocery]))
            ->assertStatus(401);

        // Authenticated member
        Sanctum::actingAs($this->member);
        $this->getJson(route('api.groceries.show', [$this->household, $grocery]))
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Milk']);
    }

    /** @test */
    public function destroy_requires_membership_and_deletes_item()
    {
        $grocery = Grocery::factory()->create([
            'household_id' => $this->household->id,
            'user_id'      => $this->member->id,
        ]);

        // Outsider cannot delete
        Sanctum::actingAs($this->outsider);
        $this->deleteJson(route('api.groceries.destroy', [$this->household, $grocery]))
            ->assertStatus(401);

        // Member can delete
        Sanctum::actingAs($this->member);
        $this->deleteJson(route('api.groceries.destroy', [$this->household, $grocery]))
            ->assertStatus(200)
            ->assertExactJson(['message' => 'Grocery deleted successfully']);

        $this->assertDatabaseMissing('groceries', ['id' => $grocery->id]);
    }

    /** @test */
    public function update_requires_owner_or_admin_and_validates_and_updates()
    {
        // Created by owner
        $ownGrocery = Grocery::factory()->create([
            'household_id' => $this->household->id,
            'user_id'      => $this->owner->id,
            'name'         => 'Bread',
            'quantity'     => 1,
            'unit'         => 'liter',
        ]);

        // Outsider
        Sanctum::actingAs($this->outsider);
        $this->putJson(route('api.groceries.update', [$this->household, $ownGrocery]), ['name' => 'New'])
            ->assertStatus(401);

        // Member but not owner
        Sanctum::actingAs($this->member);
        $this->putJson(route('api.groceries.update', [$this->household, $ownGrocery]), ['name' => 'New'])
            ->assertStatus(401);

        // Owner with invalid name
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.groceries.update', [$this->household, $ownGrocery]), ['name' => ''])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['name']]);

        // Owner with quantity/unit mismatch
        $this->putJson(route('api.groceries.update', [$this->household, $ownGrocery]), [
            'name'     => 'Bread',
            'quantity' => 2,
        ])->assertStatus(400)
            ->assertExactJson(['error' => 'Either both unit and quantity should be set, or none of them.']);

        // Successful update by owner
        $this->putJson(route('api.groceries.update', [$this->household, $ownGrocery]), [
            'name'     => 'Sourdough',
            'quantity' => 2,
            'unit'     => 'pieces',
        ])->assertStatus(200)
            ->assertExactJson(['message' => 'Grocery updated successfully']);

        $this->assertDatabaseHas('groceries', [
            'id'       => $ownGrocery->id,
            'name'     => 'Sourdough',
            'quantity' => 2,
            'unit'     => 'pieces',
        ]);

        // Now admin updates someone else's grocery
        $memberGrocery = Grocery::factory()->create([
            'household_id' => $this->household->id,
            'user_id'      => $this->member->id,
            'name'         => 'Eggs',
        ]);

        Sanctum::actingAs($this->admin);
        $this->putJson(route('api.groceries.update', [$this->household, $memberGrocery]), [
            'name' => 'Free-range Eggs',
        ])->assertStatus(200)
            ->assertExactJson(['message' => 'Grocery updated successfully']);
    }
}
