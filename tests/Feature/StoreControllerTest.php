<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

class StoreControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $other;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->admin = User::factory()->create(['admin' => true]);
    }

    /** @test */
    public function index_requires_auth_and_only_returns_published_with_search()
    {
        // no auth
        $this->getJson(route('api.stores.index'))
            ->assertStatus(401);

        // seed stores
        Store::factory()->create(['name' => 'Pub One', 'published' => true,  'user_id' => $this->owner->id]);
        Store::factory()->create(['name' => 'Draft',   'published' => false, 'user_id' => $this->other->id]);
        Store::factory()->create(['name' => 'Pub Two', 'published' => true,  'user_id' => $this->admin->id]);

        // as any user
        Sanctum::actingAs($this->owner);
        // default returns both published
        $this->getJson(route('api.stores.index'))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Pub One'])
            ->assertJsonFragment(['name' => 'Pub Two'])
            ->assertJsonMissing(['name' => 'Draft']);

        // search filter
        $this->getJson(route('api.stores.index', ['search' => 'Two']))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Pub Two']);
    }

    /** @test */
    public function get_my_store_returns_204_if_none_and_returns_store_if_exists()
    {
        Sanctum::actingAs($this->owner);

        // none yet
        $this->getJson(route('api.stores.get_my_store'))
            ->assertStatus(204);

        // create store for owner
        $store = Store::factory()->create([
            'name'    => 'Owner Store',
            'user_id' => $this->owner->id,
        ]);

        $this->owner->load('store');

        $this->getJson(route('api.stores.get_my_store'))
            ->assertStatus(200)
            ->assertJsonFragment([
                'id'   => $store->id,
                'name' => 'Owner Store',
            ]);
    }

    /** @test */
    public function show_requires_auth_and_returns_store_with_relations()
    {
        $store = Store::factory()->create([
            'name'    => 'My Store',
            'user_id' => $this->owner->id,
            'published' => true,
        ]);

        // no auth
        $this->getJson(route('api.stores.show', $store))
            ->assertStatus(401);

        Sanctum::actingAs($this->other);

        $this->getJson(route('api.stores.show', $store))
            ->assertStatus(200)
            ->assertJsonFragment([
                'id'        => $store->id,
                'name'      => 'My Store',
                'published' => 1,
            ]);
    }

    /** @test */
    public function store_requires_auth_and_validates_and_prevents_duplicate()
    {
        // no auth
        $this->postJson(route('api.stores.store'), [])
            ->assertStatus(401);

        Sanctum::actingAs($this->owner);

        // missing name
        $this->postJson(route('api.stores.store'), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['name']]);

        // first creation
        $resp = $this->postJson(route('api.stores.store'), ['name' => 'New Store']);
        $resp->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Store'])
            ->assertJsonFragment(['user_id' => $this->owner->id]);

        $this->assertDatabaseHas('stores', [
            'name'    => 'New Store',
            'user_id' => $this->owner->id,
        ]);

        $this->owner->load('store');

        // duplicate for same user
        $this->postJson(route('api.stores.store'), ['name' => 'Another'])
            ->assertStatus(400)
            ->assertJson(['error' => 'This user already has a store.']);
    }

    /** @test */
    public function update_requires_owner_or_admin_and_validates_and_updates()
    {
        $store = Store::factory()->create([
            'name'      => 'Old Name',
            'user_id'   => $this->owner->id,
            'published' => false,
        ]);

        $payload = ['name' => 'Updated', 'published' => true];

        // no auth
        $this->putJson(route('api.stores.update', $store), $payload)
            ->assertStatus(401);

        // non-owner
        Sanctum::actingAs($this->other);
        $this->putJson(route('api.stores.update', $store), $payload)
            ->assertStatus(401);

        // owner with invalid payload
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.stores.update', $store), ['name' => ''])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['name']]);

        // successful update by owner
        $this->putJson(route('api.stores.update', $store), $payload)
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated', 'published' => true]);

        $this->assertDatabaseHas('stores', [
            'id'        => $store->id,
            'name'      => 'Updated',
            'published' => true,
        ]);

        // admin updating someone else's store
        $otherStore = Store::factory()->create([
            'name'      => 'Other Store',
            'user_id'   => $this->other->id,
            'published' => false,
        ]);
        Sanctum::actingAs($this->admin);
        $this->putJson(route('api.stores.update', $otherStore), [
            'name' => 'Admin Edit',
            'published' => true,
        ])->assertStatus(200)
            ->assertJsonFragment(['name' => 'Admin Edit']);
    }

    /** @test */
    public function destroy_requires_owner_or_admin_and_deletes_store()
    {
        $store = Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        // no auth
        $this->deleteJson(route('api.stores.destroy', $store))
            ->assertStatus(401);

        // non-owner
        Sanctum::actingAs($this->other);
        $this->deleteJson(route('api.stores.destroy', $store))
            ->assertStatus(401);

        // owner
        Sanctum::actingAs($this->owner);
        $this->deleteJson(route('api.stores.destroy', $store))
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Store deleted successfully']);
        $this->assertDatabaseMissing('stores', ['id' => $store->id]);

        // admin deleting another
        $store2 = Store::factory()->create(['user_id' => $this->other->id]);
        Sanctum::actingAs($this->admin);
        $this->deleteJson(route('api.stores.destroy', $store2))
            ->assertStatus(200);
    }
}
