<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\models\Store;
use App\Models\Map;
use App\Models\MapSegment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class MapSegmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $other;
    protected User $admin;
    protected Store $store;
    protected Map $map;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->admin = User::factory()->create(['admin' => true]);

        // Create a store and its map for owner
        $this->store = Store::factory()->create(['user_id' => $this->owner->id]);
        $this->map = Map::factory()->create([
            'store_id' => $this->store->id,
            'x_size'   => 5,
            'y_size'   => 5,
        ]);
    }

    /** @test */
    public function index_requires_auth_and_returns_all_segments_with_products()
    {
        // Unauthenticated
        $this->getJson(route('api.map_segments.index', $this->map))
            ->assertStatus(401);

        // Seed segments and products
        $segment1 = MapSegment::factory()->create([
            'map_id' => $this->map->id,
            'x'      => 1,
            'y'      => 1,
            'type'   => 'shelf',
        ]);
        $segment2 = MapSegment::factory()->create([
            'map_id' => $this->map->id,
            'x'      => 2,
            'y'      => 2,
            'type'   => 'fridge',
        ]);
        Product::factory()->count(2)->create(['map_segment_id' => $segment1->id]);
        Product::factory()->count(1)->create(['map_segment_id' => $segment2->id]);

        // Authenticated owner
        Sanctum::actingAs($this->owner);
        $response = $this->getJson(route('api.map_segments.index', $this->map));

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => ['id', 'x', 'y', 'type', 'map_id', 'section_id', 'created_at', 'updated_at', 'products'],
            ]);
    }

    /** @test */
    public function store_requires_owner_and_validates_and_creates_segment()
    {
        $validData = ['x' => 3, 'y' => 3, 'type' => 'shelf', 'section_id' => null];

        // Unauthenticated
        $this->postJson(route('api.map_segments.store', $this->map), $validData)
            ->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->postJson(route('api.map_segments.store', $this->map), $validData)
            ->assertStatus(401);

        // Owner missing payload
        Sanctum::actingAs($this->owner);
        $this->postJson(route('api.map_segments.store', $this->map), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['x', 'y', 'type']]);

        // Owner out-of-range coordinates
        $this->postJson(route('api.map_segments.store', $this->map), ['x' => 6, 'y' => 6, 'type' => 'shelf'])
            ->assertStatus(400)
            ->assertJsonStructure(['error']);

        // Owner invalid type
        $this->postJson(route('api.map_segments.store', $this->map), ['x' => 1, 'y' => 1, 'type' => 'invalid'])
            ->assertStatus(400)
            ->assertJsonStructure(['error']);

        // Successful creation
        Sanctum::actingAs($this->owner);
        $this->postJson(route('api.map_segments.store', $this->map), $validData)
            ->assertStatus(201)
            ->assertJsonFragment(['x' => 3, 'y' => 3, 'type' => 'shelf']);

        $this->assertDatabaseHas('map_segments', array_merge($validData, ['map_id' => $this->map->id]));
    }

    /** @test */
    public function update_requires_owner_and_validates_and_updates_segment_and_deletes_products()
    {
        // Create a segment with products
        $segment = MapSegment::factory()->create([
            'map_id' => $this->map->id,
            'x'      => 1,
            'y'      => 1,
            'type'   => 'shelf',
        ]);
        Product::factory()->count(2)->create(['map_segment_id' => $segment->id]);

        $updateData = ['x' => 2, 'y' => 2, 'type' => 'empty', 'section_id' => null];

        // Unauthenticated
        $this->putJson(route('api.map_segments.update', [$this->map, $segment]), $updateData)
            ->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->putJson(route('api.map_segments.update', [$this->map, $segment]), $updateData)
            ->assertStatus(401);

        // Create a store for another user to test mismatch
        $otherUser = User::factory()->create();
        $otherStore = Store::factory()->create(['user_id' => $otherUser->id]);
        $otherMap   = Map::factory()->create([
            'store_id' => $otherStore->id,
            'x_size'   => 5,
            'y_size'   => 5,
        ]);

        Sanctum::actingAs($otherUser);
        $this->putJson(route('api.map_segments.update', [$otherMap, $segment]), $updateData)
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The given segment does not belong to the given map']);

        // Owner invalid data
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.map_segments.update', [$this->map, $segment]), ['x' => -1])
            ->assertStatus(400)
            ->assertJsonStructure(['error']);

        // Successful update and products deleted
        $this->putJson(route('api.map_segments.update', [$this->map, $segment]), $updateData)
            ->assertStatus(200)
            ->assertJsonFragment(['x' => 2, 'y' => 2, 'type' => 'empty']);

        $this->assertDatabaseHas('map_segments', ['id' => $segment->id, 'x' => 2, 'y' => 2, 'type' => 'empty']);
        $this->assertDatabaseCount('products', 0);
    }

    /** @test */
    public function destroy_requires_owner_and_deletes_segment()
    {
        $segment = MapSegment::factory()->create([
            'map_id' => $this->map->id,
            'x'      => 1,
            'y'      => 1,
            'type'   => 'empty',
        ]);

        $route = route('api.map_segments.destroy', [$this->map, $segment]);

        // Unauthenticated
        $this->deleteJson($route)->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->deleteJson($route)->assertStatus(401);

        // Owner deletes
        Sanctum::actingAs($this->owner);
        $this->deleteJson($route)
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'MapSegment deleted successfully']);

        $this->assertDatabaseMissing('map_segments', ['id' => $segment->id]);
    }
}
