<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\Map;
use App\Models\MapSegment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $other;
    protected User $admin;
    protected Store $store;
    protected Map $map;
    protected MapSegment $segment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->admin = User::factory()->create(['admin' => true]);

        // Create store, map, and segment
        $this->store   = Store::factory()->create(['user_id' => $this->owner->id]);
        $this->map     = Map::factory()->create(['store_id' => $this->store->id, 'x_size' => 5, 'y_size' => 5]);
        $this->segment = MapSegment::factory()->create(['map_id' => $this->map->id, 'x' => 1, 'y' => 1, 'type' => 'shelf']);
    }

    /** @test */
    public function index_requires_auth_and_returns_products_for_map()
    {
        // Seed products
        $prod1 = Product::factory()->create(['map_segment_id' => $this->segment->id]);
        $prod2 = Product::factory()->create(['map_segment_id' => $this->segment->id]);

        // Unauthenticated
        $this->getJson(route('api.products.index', $this->map))
            ->assertStatus(401);

        // Authenticated
        Sanctum::actingAs($this->owner);
        $response = $this->getJson(route('api.products.index', $this->map));

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $prod1->id])
            ->assertJsonFragment(['id' => $prod2->id]);
    }

    /** @test */
    public function show_requires_auth_and_returns_product_with_segment()
    {
        $product = Product::factory()->create(['map_segment_id' => $this->segment->id]);

        // Unauthenticated
        $this->getJson(route('api.products.show', $product))
            ->assertStatus(401);

        // Authenticated
        Sanctum::actingAs($this->owner);
        $this->getJson(route('api.products.show', $product))
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $product->id, 'name' => $product->name])
            ->assertJsonStructure(['map_segment' => ['id', 'x', 'y', 'type', 'map_id']]);
    }

    /** @test */
    public function store_requires_owner_and_validates_and_creates_product()
    {
        $valid = ['name' => 'Item', 'brand' => 'Brand', 'description' => 'Desc', 'price' => 100];

        // Unauthenticated
        $this->postJson(route('api.products.store', $this->segment), $valid)
            ->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->postJson(route('api.products.store', $this->segment), $valid)
            ->assertStatus(401);

        // Owner, missing fields
        Sanctum::actingAs($this->owner);
        $this->postJson(route('api.products.store', $this->segment), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['name', 'brand', 'price']]);

        // Owner, invalid price
        $this->postJson(route('api.products.store', $this->segment), array_merge($valid, ['price' => -1]))
            ->assertStatus(400)
            ->assertJsonStructure(['error']);

        // Successful creation
        Sanctum::actingAs($this->owner);
        $this->postJson(route('api.products.store', $this->segment), $valid)
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'Item', 'brand' => 'Brand', 'price' => 100]);

        $this->assertDatabaseHas('products', ['name' => 'Item', 'map_segment_id' => $this->segment->id]);
    }

    /** @test */
    public function update_requires_owner_and_validates_and_updates_product()
    {
        $product = Product::factory()->create(['map_segment_id' => $this->segment->id]);
        $valid   = ['name' => 'New', 'brand' => 'NewBrand', 'description' => 'NewDesc', 'price' => 200];

        // Unauthenticated
        $this->putJson(route('api.products.update', [$this->segment, $product]), $valid)
            ->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->putJson(route('api.products.update', [$this->segment, $product]), $valid)
            ->assertStatus(401);

        // Wrong segment
        $otherSegment = MapSegment::factory()->create(['map_id' => $this->map->id, 'x' => 2, 'y' => 2, 'type' => 'empty']);
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.products.update', [$otherSegment, $product]), $valid)
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The given product does not belong to the given segment']);

        // Owner, invalid data
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.products.update', [$this->segment, $product]), ['name' => ''])
            ->assertStatus(400)
            ->assertJsonStructure(['error']);

        // Successful update
        $this->putJson(route('api.products.update', [$this->segment, $product]), $valid)
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'New', 'brand' => 'NewBrand', 'price' => 200]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New']);
    }

    /** @test */
    public function destroy_requires_owner_and_deletes_product()
    {
        $product = Product::factory()->create(['map_segment_id' => $this->segment->id]);
        $route   = route('api.products.destroy', [$this->segment, $product]);

        // Unauthenticated
        $this->deleteJson($route)->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->deleteJson($route)->assertStatus(401);

        // Wrong segment
        $otherSegment = MapSegment::factory()->create(['map_id' => $this->map->id, 'x' => 2, 'y' => 2, 'type' => 'wall']);
        Sanctum::actingAs($this->owner);
        $this->deleteJson(route('api.products.destroy', [$otherSegment, $product]))
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The given product does not belong to the given segment']);

        // Owner deletes
        $this->deleteJson($route)
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Product deleted successfully']);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function update_segment_requires_owner_and_validates_and_changes_segment()
    {
        $product = Product::factory()->create(['map_segment_id' => $this->segment->id]);
        $otherSegment = MapSegment::factory()->create(['map_id' => $this->map->id, 'x' => 2, 'y' => 2, 'type' => 'empty']);

        // Unauthenticated
        $this->putJson(route('api.products.updateSegment', [$this->segment, $product]), ['map_segment_id' => $otherSegment->id])
            ->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->putJson(route('api.products.updateSegment', [$this->segment, $product]), ['map_segment_id' => $otherSegment->id])
            ->assertStatus(401);

        // Wrong original segment
        $wrongSegment = MapSegment::factory()->create(['map_id' => $this->map->id, 'x' => 3, 'y' => 3, 'type' => 'wall']);
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.products.updateSegment', [$wrongSegment, $product]), ['segment_id' => $otherSegment->id])
            ->assertStatus(400)
            ->assertJsonStructure(['errors']);

        // Owner, invalid new segment
        $this->putJson(route('api.products.updateSegment', [$this->segment, $product]), [])
            ->assertStatus(400)
            ->assertJsonStructure(['errors']);

        // Successful segment change
        $this->putJson(route('api.products.updateSegment', [$this->segment, $product]), ['segment_id' => $otherSegment->id])
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Product segment updated successfully']);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'map_segment_id' => $otherSegment->id]);
    }
}
