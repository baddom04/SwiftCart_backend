<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function register_creates_user_and_returns_token()
    {
        $payload = [
            'name'     => 'John Doe',
            'email'    => 'john@example.com',
            'password' => 'secret123',
        ];

        $response = $this->postJson(route('api.register'), $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function register_validation_errors_on_missing_fields()
    {
        $response = $this->postJson(route('api.register'), []);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error' => ['name', 'email', 'password']
            ]);
    }

    /** @test */
    public function register_validation_error_on_duplicate_email()
    {
        $payload = [
            'name'     => 'John Doe',
            'email'    => 'john@example.com',
            'password' => 'secret123',
        ];

        $this->postJson(route('api.register'), $payload);
        $response = $this->postJson(route('api.register'), $payload);

        $response->assertStatus(400)
            ->assertJsonStructure([
                'error' => ['email']
            ]);
    }

    /** @test */
    public function login_with_valid_credentials_returns_token()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson(route('api.login'), [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    /** @test */
    public function login_with_invalid_credentials_fails()
    {
        $response = $this->postJson(route('api.login'), [
            'email'    => 'doesnot@exist.test',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401)
            ->assertExactJson(['error' => 'Invalid credentials']);
    }

    /** @test */
    public function logout_revokes_current_token()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.logout'));

        $response->assertStatus(200);

        // No tokens for this user now
        $this->assertCount(0, $user->tokens);
    }

    /** @test */
    public function user_endpoint_returns_authenticated_user()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.user'));

        $response->assertStatus(200)
            ->assertJson([
                'id'    => $user->id,
                'email' => $user->email,
            ]);
    }

    /** @test */
    public function update_changes_user_and_validates_inputs()
    {
        $user = User::factory()->create([
            'name'  => 'Old Name',
            'email' => 'old@example.com',
        ]);
        Sanctum::actingAs($user);

        // Successful update
        $data = ['name' => 'New Name', 'email' => 'new@example.com'];
        $this->putJson(route('api.users.update', $user), $data)
            ->assertStatus(200)
            ->assertExactJson(['message' => 'User updated successfully']);

        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'name'  => 'New Name',
            'email' => 'new@example.com',
        ]);

        // Validation error
        $this->putJson(route('api.users.update', $user), ['email' => 'not-an-email'])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['email']]);
    }

    /** @test */
    public function update_password_checks_current_and_sets_new()
    {
        $user = User::factory()->create([
            'password' => Hash::make('currentpass'),
        ]);
        Sanctum::actingAs($user);

        // Wrong current password
        $this->putJson(route('api.users.update_password', $user), [
            'current_password' => 'wrongpass',
            'new_password'     => 'newsecret',
        ])->assertStatus(403)
            ->assertExactJson(['error' => 'The current password is incorrect.']);

        // Successful change
        $this->putJson(route('api.users.update_password', $user), [
            'current_password' => 'currentpass',
            'new_password'     => 'newsecret',
        ])->assertStatus(200)
            ->assertExactJson(['Message' => 'Password updated successfully']);

        $this->assertTrue(
            Hash::check('newsecret', $user->fresh()->password),
            'Password was not updated in DB'
        );
    }

    /** @test */
    public function destroy_deletes_own_account_and_tokens()
    {
        $user = User::factory()->create();
        // give him a token so we can verify it's gone
        Sanctum::actingAs($user);
        $user->createToken('test-token');

        $response = $this->deleteJson(route('api.users.destroy', $user));

        $response->assertStatus(200)
            ->assertExactJson(['message' => 'User deleted successfully']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertCount(0, $user->tokens);
    }

    /** @test */
    public function cannot_destroy_other_users_account()
    {
        $user      = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);

        $this->deleteJson(route('api.users.destroy', $user))
            ->assertStatus(401);
    }
}
