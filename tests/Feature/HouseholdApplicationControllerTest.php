<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Household;
use App\Models\UserHousehold;
use App\Models\HouseholdApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class HouseholdApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function store_creates_application_and_prevents_duplicates_and_membership()
    {
        $household = Household::factory()->create([
            'user_id' => $owner = User::factory()->create()->id,
        ]);

        // unauthenticated
        $this->postJson(route('api.household_applications.store', $household))
            ->assertStatus(401);

        // authenticated but first time
        Sanctum::actingAs($applicant = User::factory()->create());
        $this->postJson(route('api.household_applications.store', $household))
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Application created successfully']);

        $this->assertDatabaseHas('household_applications', [
            'user_id'      => $applicant->id,
            'household_id' => $household->id,
        ]);

        // duplicate application
        $this->postJson(route('api.household_applications.store', $household))
            ->assertStatus(400)
            ->assertExactJson(['error' => 'This application already exists']);

        // membership case
        UserHousehold::factory()->create([
            'user_id'      => $applicant->id,
            'household_id' => $household->id,
        ]);
        HouseholdApplication::query()->delete(); // reset applications

        $this->postJson(route('api.household_applications.store', $household))
            ->assertStatus(400)
            ->assertExactJson(['error' => 'The user is already in this household']);
    }

    /** @test */
    public function destroy_allows_applicant_owner_or_admin()
    {
        $owner      = User::factory()->create();
        $applicant  = User::factory()->create();
        $other      = User::factory()->create();
        $admin      = User::factory()->create(['admin' => true]);
        $household  = Household::factory()->create(['user_id' => $owner->id]);
        $application = HouseholdApplication::factory()->create([
            'user_id'      => $applicant->id,
            'household_id' => $household->id,
        ]);

        // unauthenticated
        $this->deleteJson(route('api.household_applications.destroy', $application))
            ->assertStatus(401);

        // unauthorized user
        Sanctum::actingAs($other);
        $this->deleteJson(route('api.household_applications.destroy', $application))
            ->assertStatus(401)
            ->assertExactJson(['error' => 'Unauthorized']);

        // as applicant
        Sanctum::actingAs($applicant);
        $this->deleteJson(route('api.household_applications.destroy', $application))
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Application deleted successfully']);
        $this->assertDatabaseMissing('household_applications', ['id' => $application->id]);

        // recreate for owner
        $application = HouseholdApplication::factory()->create([
            'user_id'      => $applicant->id,
            'household_id' => $household->id,
        ]);

        Sanctum::actingAs($owner);
        $this->deleteJson(route('api.household_applications.destroy', $application))
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Application deleted successfully']);

        // recreate for admin
        $application = HouseholdApplication::factory()->create([
            'user_id'      => $applicant->id,
            'household_id' => $household->id,
        ]);

        Sanctum::actingAs($admin);
        $this->deleteJson(route('api.household_applications.destroy', $application))
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Application deleted successfully']);
    }

    /** @test */
    public function accept_user_allows_owner_or_admin_and_creates_membership()
    {
        $owner      = User::factory()->create();
        $applicant  = User::factory()->create();
        $other      = User::factory()->create();
        $admin      = User::factory()->create(['admin' => true]);
        $household  = Household::factory()->create(['user_id' => $owner->id]);
        $application = HouseholdApplication::factory()->create([
            'user_id'      => $applicant->id,
            'household_id' => $household->id,
        ]);

        // unauthenticated
        $this->postJson(route('api.household_applications.accept_user', $application))
            ->assertStatus(401);

        // unauthorized
        Sanctum::actingAs($other);
        $this->postJson(route('api.household_applications.accept_user', $application))
            ->assertStatus(401)
            ->assertExactJson(['error' => 'Unauthorized']);

        // as owner
        Sanctum::actingAs($owner);
        $this->postJson(route('api.household_applications.accept_user', $application))
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Application accepted successfully']);
        $this->assertDatabaseHas('user_households', [
            'user_id'      => $applicant->id,
            'household_id' => $household->id,
        ]);
        $this->assertDatabaseMissing('household_applications', ['id' => $application->id]);

        // recreate for admin
        $newApplicant = User::factory()->create();
        $application = HouseholdApplication::factory()->create([
            'user_id'      => $newApplicant->id,
            'household_id' => $household->id,
        ]);

        Sanctum::actingAs($admin);
        $this->postJson(route('api.household_applications.accept_user', $application))
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Application accepted successfully']);
    }

    /** @test */
    public function get_sent_applications_and_households_require_owner_and_return_lists()
    {
        $user       = User::factory()->create();
        $other      = User::factory()->create();
        $admin      = User::factory()->create(['admin' => true]);
        $hh1        = Household::factory()->create(['user_id' => $user->id]);
        $hh2        = Household::factory()->create(['user_id' => $user->id]);
        $hhOther    = Household::factory()->create(['user_id' => $other->id]);

        HouseholdApplication::factory()->create([
            'user_id'      => $user->id,
            'household_id' => $hh1->id,
        ]);
        HouseholdApplication::factory()->create([
            'user_id'      => $user->id,
            'household_id' => $hh2->id,
        ]);
        HouseholdApplication::factory()->create([
            'user_id'      => $other->id,
            'household_id' => $hhOther->id,
        ]);

        // sent applications: unauthorized
        $this->getJson(route('api.household_applications.get_sent_applications', $user))
            ->assertStatus(401);
        Sanctum::actingAs($other);
        $this->getJson(route('api.household_applications.get_sent_applications', $user))
            ->assertStatus(401);

        // as user
        Sanctum::actingAs($user);
        $this->getJson(route('api.household_applications.get_sent_applications', $user))
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([['id', 'user_id', 'household_id']]);

        // as admin
        Sanctum::actingAs($admin);
        $this->getJson(route('api.household_applications.get_sent_applications', $user))
            ->assertStatus(200)
            ->assertJsonCount(2);

        // sent households: unauthorized
        Sanctum::actingAs($other);
        $this->getJson(route('api.household_applications.get_sent_households', $user))
            ->assertStatus(401);

        // as user
        Sanctum::actingAs($user);
        $this->getJson(route('api.household_applications.get_sent_households', $user))
            ->assertStatus(200)
            ->assertJsonCount(2);

        // as admin
        Sanctum::actingAs($admin);
        $this->getJson(route('api.household_applications.get_sent_households', $user))
            ->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function find_returns_application_or_404_and_requires_owner()
    {
        $user      = User::factory()->create();
        $other     = User::factory()->create();
        $admin     = User::factory()->create(['admin' => true]);
        $household = Household::factory()->create(['user_id' => $user->id]);

        // no application yet
        Sanctum::actingAs($user);
        $this->getJson(route('api.household_applications.find', [$user, $household]))
            ->assertStatus(404)
            ->assertExactJson(['message' => 'No matching application found']);

        // create application
        $application = HouseholdApplication::factory()->create([
            'user_id'      => $user->id,
            'household_id' => $household->id,
        ]);

        // unauthorized user
        Sanctum::actingAs($other);
        $this->getJson(route('api.household_applications.find', [$user, $household]))
            ->assertStatus(401);

        // as user
        Sanctum::actingAs($user);
        $this->getJson(route('api.household_applications.find', [$user, $household]))
            ->assertStatus(200)
            ->assertJson([
                'id'            => $application->id,
                'user_id'       => $user->id,
                'household_id'  => $household->id,
            ]);

        // as admin
        Sanctum::actingAs($admin);
        $this->getJson(route('api.household_applications.find', [$user, $household]))
            ->assertStatus(200);
    }

    /** @test */
    public function get_received_applications_and_users_require_owner_and_return_lists()
    {
        $owner      = User::factory()->create();
        $other      = User::factory()->create();
        $admin      = User::factory()->create(['admin' => true]);
        $app1       = User::factory()->create();
        $app2       = User::factory()->create();
        $household  = Household::factory()->create(['user_id' => $owner->id]);

        HouseholdApplication::factory()->create([
            'user_id'      => $app1->id,
            'household_id' => $household->id,
        ]);
        HouseholdApplication::factory()->create([
            'user_id'      => $app2->id,
            'household_id' => $household->id,
        ]);

        // received applications: unauthorized
        $this->getJson(route('api.household_applications.get_received_applications', $household))
            ->assertStatus(401);
        Sanctum::actingAs($other);
        $this->getJson(route('api.household_applications.get_received_applications', $household))
            ->assertStatus(401);

        // as owner
        Sanctum::actingAs($owner);
        $this->getJson(route('api.household_applications.get_received_applications', $household))
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([['id', 'user_id', 'household_id']]);

        // as admin
        Sanctum::actingAs($admin);
        $this->getJson(route('api.household_applications.get_received_applications', $household))
            ->assertStatus(200)
            ->assertJsonCount(2);

        // received users: unauthorized
        Sanctum::actingAs($other);
        $this->getJson(route('api.household_applications.get_received_users', $household))
            ->assertStatus(401);

        // as owner
        Sanctum::actingAs($owner);
        $this->getJson(route('api.household_applications.get_received_users', $household))
            ->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $app1->id])
            ->assertJsonFragment(['id' => $app2->id]);

        // as admin
        Sanctum::actingAs($admin);
        $this->getJson(route('api.household_applications.get_received_users', $household))
            ->assertStatus(200)
            ->assertJsonCount(2);
    }
}
