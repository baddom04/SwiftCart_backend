<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Store;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class LocationControllerTest extends TestCase
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

        // Store owned by owner
        $this->store = Store::factory()->create([
            'user_id' => $this->owner->id,
        ]);
    }

    /** @test */
    public function show_requires_auth_and_returns_location_or_null()
    {
        // Unauthenticated
        $this->getJson(route('api.locations.show', $this->store))
            ->assertStatus(401);

        // Authenticated, no location yet
        Sanctum::actingAs($this->owner);
        $this->getJson(route('api.locations.show', $this->store))
            ->assertStatus(200);
    }

    /** @test */
    public function store_requires_owner_and_validates_and_creates_location()
    {
        $payload = [
            'country' => 'Wonderland',
            'zip_code' => '1234',
            'city'    => 'Heartville',
            'street'  => 'Queen St',
            'detail'  => 'To the castle',
        ];

        // Unauthenticated
        $this->postJson(route('api.locations.store', $this->store), $payload)
            ->assertStatus(401);

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->postJson(route('api.locations.store', $this->store), $payload)
            ->assertStatus(401);

        // Owner with missing fields
        Sanctum::actingAs($this->owner);
        $this->postJson(route('api.locations.store', $this->store), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['country', 'zip_code', 'city', 'street']]);

        // Owner successful create
        Sanctum::actingAs($this->owner);
        $resp = $this->postJson(route('api.locations.store', $this->store), $payload);
        $resp->assertStatus(201)
            ->assertJsonFragment(['country' => 'Wonderland', 'zip_code' => '1234']);

        $this->assertDatabaseHas('locations', array_merge($payload, ['store_id' => $this->store->id]));

        $this->store->load('location');
        // Duplicate attempt (location already exists)
        $this->postJson(route('api.locations.store', $this->store), $payload)
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The location to this store already exists']);
    }

    /** @test */
    public function update_requires_owner_and_validates_and_updates_location()
    {
        $payload = [
            'country' => 'Wonderland',
            'zip_code' => '1234',
            'city'    => 'Heartville',
            'street'  => 'Queen St',
            'detail'  => 'To the castle',
        ];

        // Owner tries update before creation
        Sanctum::actingAs($this->owner->fresh());
        $this->putJson(route('api.locations.update', $this->store), $payload)
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The location to this store does not exist']);

        // Create location
        Location::factory()->create(array_merge($payload, ['store_id' => $this->store->id]));

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->putJson(route('api.locations.update', $this->store), $payload)
            ->assertStatus(401);

        // Owner invalid data
        Sanctum::actingAs($this->owner);
        $this->putJson(route('api.locations.update', $this->store), ['country' => ''])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['country', 'zip_code', 'city', 'street']]);

        // Owner successful update
        Sanctum::actingAs($this->owner);
        $newData = ['country' => 'Realmland', 'zip_code' => '4321', 'city' => 'Real City', 'street' => 'King St', 'detail' => null];
        $this->putJson(route('api.locations.update', $this->store), $newData)
            ->assertStatus(200)
            ->assertJsonFragment(['country' => 'Realmland', 'zip_code' => '4321', 'city' => 'Real City']);

        $this->assertDatabaseHas('locations', array_merge($newData, ['store_id' => $this->store->id]));
    }

    /** @test */
    public function destroy_requires_owner_and_deletes_location()
    {
        $data = ['country' => 'Wonderland', 'zip_code' => '1234', 'city' => 'Heartville', 'street' => 'Queen St', 'detail' => ''];
        Location::factory()->create(array_merge($data, ['store_id' => $this->store->id]));

        // Non-owner
        Sanctum::actingAs($this->other);
        $this->deleteJson(route('api.locations.destroy', $this->store))
            ->assertStatus(401);

        // Owner
        Sanctum::actingAs($this->owner);
        $this->deleteJson(route('api.locations.destroy', $this->store))
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Location deleted successfully']);

        $this->assertDatabaseMissing('locations', ['store_id' => $this->store->id]);
    }

    /** @test */
    public function search_endpoints_require_auth_and_return_distinct_values()
    {
        $store = Store::factory()->create([
            'user_id' => $this->admin->id,
        ]);
        $store2 = Store::factory()->create([
            'user_id' => $this->other->id,
        ]);
        // Seed locations
        Location::factory()->create(['country' => 'A', 'zip_code' => '0001', 'city' => 'X', 'street' => 'S1', 'detail' => 'D1', 'store_id' => $this->store->id]);
        Location::factory()->create(['country' => 'A', 'zip_code' => '0002', 'city' => 'X', 'street' => 'S2', 'detail' => 'D2', 'store_id' => $store->id]);
        Location::factory()->create(['country' => 'B', 'zip_code' => '0003', 'city' => 'Y', 'street' => 'S3', 'detail' => 'D3', 'store_id' => $store2->id]);

        // No auth
        $this->getJson(route('api.locations.getCountries'))->assertStatus(401);

        // Authenticated
        Sanctum::actingAs($this->owner);
        // Countries
        $this->getJson(route('api.locations.getCountries'))
            ->assertStatus(200)
            ->assertJsonStructure(['countries'])
            ->assertJsonCount(2, 'countries');

        // Cities missing param
        $this->getJson(route('api.locations.getCities'))->assertStatus(400);
        // Cities
        $this->getJson(route('api.locations.getCities', ['country' => 'A']))
            ->assertStatus(200)
            ->assertJsonStructure(['cities'])
            ->assertJsonCount(1, 'cities');

        // Streets missing params
        $this->getJson(route('api.locations.getStreets'))->assertStatus(400);
        // Streets
        $this->getJson(route('api.locations.getStreets', ['country' => 'A', 'city' => 'X']))
            ->assertStatus(200)
            ->assertJsonStructure(['streets'])
            ->assertJsonCount(2, 'streets');

        // Details missing params
        $this->getJson(route('api.locations.getDetails'))->assertStatus(400);
        // Details
        $this->getJson(route('api.locations.getDetails', ['country' => 'A', 'city' => 'X', 'street' => 'S1']))
            ->assertStatus(200)
            ->assertJsonStructure(['details'])
            ->assertJsonCount(1, 'details');
    }
}
