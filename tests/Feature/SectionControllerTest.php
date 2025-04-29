<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\Map;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SectionControllerTest extends TestCase
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

        // Create store and map
        $this->store = Store::factory()->create(['user_id' => $this->owner->id]);
        $this->map = Map::factory()->create([
            'store_id' => $this->store->id,
            'x_size'   => 4,
            'y_size'   => 4,
        ]);
    }

    /** @test */
    public function index_requires_auth_and_returns_sections()
    {
        Section::factory()->create(['map_id' => $this->map->id, 'name' => 'A']);
        Section::factory()->create(['map_id' => $this->map->id, 'name' => 'B']);

        // Unauthenticated
        $this->getJson(route('api.sections.index', $this->map))
            ->assertStatus(401);

        // Authenticated (any user)
        Sanctum::actingAs($this->owner);
        $this->getJson(route('api.sections.index', $this->map))
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'A'])
            ->assertJsonFragment(['name' => 'B']);
    }

    /** @test */
    public function store_requires_owner_and_validates_and_creates_section_and_prevents_duplicates()
    {
        $payload = ['name' => 'Section One'];

        // Unauthenticated
        $this->postJson(route('api.sections.store', $this->map), $payload)
            ->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->postJson(route('api.sections.store', $this->map), $payload)
            ->assertStatus(401);

        // Owner, missing name
        Sanctum::actingAs($this->owner);
        $this->postJson(route('api.sections.store', $this->map), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['name']]);

        // Successful creation
        $this->postJson(route('api.sections.store', $this->map), $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'Section One']);
        $this->assertDatabaseHas('sections', ['map_id' => $this->map->id, 'name' => 'Section One']);

        // Duplicate within same map
        $this->postJson(route('api.sections.store', $this->map), $payload)
            ->assertStatus(400)
            ->assertJsonStructure(['error']);

        // Duplicate allowed in different map
        $otherStore = Store::factory()->create(['user_id' => User::factory()->create()->id]);
        $otherMap = Map::factory()->create(['store_id' => $otherStore->id, 'x_size' => 3, 'y_size' => 3]);
        Sanctum::actingAs($otherStore->user);
        $this->postJson(route('api.sections.store', $otherMap), $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'Section One']);
    }

    /** @test */
    public function update_requires_owner_and_validates_and_updates_and_prevents_cross_map_and_duplicates()
    {
        $section = Section::factory()->create(['map_id' => $this->map->id, 'name' => 'OldName']);
        $payload = ['name' => 'NewName'];

        // Unauthenticated
        $this->putJson(route('api.sections.update', [$this->map, $section]), $payload)
            ->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->putJson(route('api.sections.update', [$this->map, $section]), $payload)
            ->assertStatus(401);

        // Wrong map
        $otherStore = Store::factory()->create(['user_id' => User::factory()->create()->id]);
        $otherMap = Map::factory()->create(['store_id' => $otherStore->id, 'x_size' => 3, 'y_size' => 3]);
        Sanctum::actingAs($otherStore->user);
        $this->putJson(route('api.sections.update', [$otherMap, $section]), $payload)
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The given section does not belong to the given map']);

        // Owner, missing name
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.sections.update', [$this->map, $section]), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['name']]);

        // Owner, duplicate name
        Section::factory()->create(['map_id' => $this->map->id, 'name' => 'Other']);
        $this->putJson(route('api.sections.update', [$this->map, $section]), ['name' => 'Other'])
            ->assertStatus(400)
            ->assertJsonStructure(['error']);

        // Successful update
        $this->putJson(route('api.sections.update', [$this->map, $section]), $payload)
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'NewName']);
        $this->assertDatabaseHas('sections', ['id' => $section->id, 'name' => 'NewName']);
    }

    /** @test */
    public function destroy_requires_owner_and_deletes_section_and_prevents_cross_map()
    {
        $section = Section::factory()->create(['map_id' => $this->map->id, 'name' => 'X']);
        $route = route('api.sections.destroy', [$this->map, $section]);

        // Unauthenticated
        $this->deleteJson($route)->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->deleteJson($route)->assertStatus(401);

        // Wrong map
        $otherStore = Store::factory()->create(['user_id' => User::factory()->create()->id]);
        $otherMap = Map::factory()->create(['store_id' => $otherStore->id, 'x_size' => 3, 'y_size' => 3]);
        Sanctum::actingAs($otherStore->user);
        $this->deleteJson(route('api.sections.destroy', [$otherMap, $section]))
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The given section does not belong to the given map']);

        // Owner deletes
        Sanctum::actingAs($this->owner);
        $this->deleteJson($route)
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Section deleted successfully']);
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }
}
