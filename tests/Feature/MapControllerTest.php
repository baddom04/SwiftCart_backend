<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\Map;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class MapControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $other;
    protected User $admin;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        // Users
        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->admin = User::factory()->create(['admin' => true]);

        // Store owned by $owner
        $this->store = Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);
    }

    /** @test */
    public function show_returns_null_when_no_map_and_map_when_exists()
    {
        // Unauthenticated (assuming auth is required)
        $this->getJson(route('api.maps.show', $this->store))
            ->assertStatus(401);

        // Authenticated, no map exists
        Sanctum::actingAs($this->owner);
        $this->getJson(route('api.maps.show', $this->store))
            ->assertStatus(200);

        // Create a map
        $map = Map::factory()->create([
            'store_id' => $this->store->id,
            'x_size'   => 10,
            'y_size'   => 15,
        ]);

        // Authenticated, map exists
        $this->getJson(route('api.maps.show', $this->store))
            ->assertStatus(200)
            ->assertJsonFragment([
                'id'     => $map->id,
                'x_size' => 10,
                'y_size' => 15,
            ]);
    }

    /** @test */
    public function store_requires_owner_and_validates_and_creates_map()
    {
        $valid = ['x_size' => 5, 'y_size' => 7];

        // Unauthenticated
        $this->postJson(route('api.maps.store', $this->store), $valid)
            ->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->postJson(route('api.maps.store', $this->store), $valid)
            ->assertStatus(401);

        // Owner, missing fields
        Sanctum::actingAs($this->owner);
        $this->postJson(route('api.maps.store', $this->store), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['x_size', 'y_size']]);

        // Owner, invalid values
        $this->postJson(route('api.maps.store', $this->store), ['x_size' => 1, 'y_size' => 200])
            ->assertStatus(400)
            ->assertJsonStructure(['error']);

        // Successful creation
        $this->postJson(route('api.maps.store', $this->store), $valid)
            ->assertStatus(201)
            ->assertJsonFragment([
                'x_size'   => 5,
                'y_size'   => 7,
                'store_id' => $this->store->id,
            ]);

        $this->assertDatabaseHas('maps', array_merge($valid, ['store_id' => $this->store->id]));

        // Duplicate attempt
        $this->postJson(route('api.maps.store', $this->store), $valid)
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The map to this store already exists']);
    }

    /** @test */
    public function update_requires_owner_and_validates_and_updates_map()
    {
        $payload = ['x_size' => 8, 'y_size' => 9];

        // Owner tries update before map exists
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.maps.update', $this->store), $payload)
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The map to this store does not exist']);

        // Create map
        $map = Map::factory()->create([
            'store_id' => $this->store->id,
            'x_size'   => 10,
            'y_size'   => 10,
        ]);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->putJson(route('api.maps.update', $this->store), $payload)
            ->assertStatus(401);

        // Owner, invalid data
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.maps.update', $this->store), ['x_size' => 1])
            ->assertStatus(400)
            ->assertJsonStructure(['error']);

        // Successful update by owner
        $this->putJson(route('api.maps.update', $this->store), $payload)
            ->assertStatus(200)
            ->assertJsonFragment([
                'x_size' => 8,
                'y_size' => 9,
            ]);

        $this->assertDatabaseHas('maps', ['id' => $map->id, 'x_size' => 8, 'y_size' => 9]);

        // Admin can update
        Sanctum::actingAs($this->admin);
        $this->putJson(route('api.maps.update', $this->store), ['x_size' => 6, 'y_size' => 6])
            ->assertStatus(200)
            ->assertJsonFragment(['x_size' => 6, 'y_size' => 6]);
    }

    /** @test */
    public function destroy_requires_owner_and_deletes_map()
    {
        // Create a map
        $map = Map::factory()->create([
            'store_id' => $this->store->id,
            'x_size'   => 12,
            'y_size'   => 15,
        ]);

        $route = route('api.maps.destroy', $this->store);

        // Unauthenticated
        $this->deleteJson($route)->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->deleteJson($route)->assertStatus(401);

        // Owner deletes
        Sanctum::actingAs($this->owner);
        $this->deleteJson($route)
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Map deleted successfully']);

        $this->assertDatabaseMissing('maps', ['id' => $map->id]);
    }
}
