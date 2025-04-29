<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Household;
use App\Models\Grocery;
use App\Models\UserHousehold;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $member;
    protected User $outsider;
    protected User $admin;
    protected Household $household;
    protected Grocery $grocery;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->owner    = User::factory()->create();
        $this->member   = User::factory()->create();
        $this->outsider = User::factory()->create();
        $this->admin    = User::factory()->create(['admin' => true]);

        // Create a household owned by $owner
        $this->household = Household::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        // Attach $member to household
        UserHousehold::factory()->create([
            'user_id'      => $this->member->id,
            'household_id' => $this->household->id,
        ]);

        // Create a grocery item in that household by the member
        $this->grocery = Grocery::factory()->create([
            'household_id' => $this->household->id,
            'user_id'      => $this->member->id,
        ]);
    }

    /** @test */
    public function index_requires_auth_and_membership()
    {
        // Unauthenticated
        $this->getJson(route('api.comments.index', [$this->household, $this->grocery]))
            ->assertStatus(401);

        // Authenticated but not a member
        Sanctum::actingAs($this->outsider);
        $this->getJson(route('api.comments.index', [$this->household, $this->grocery]))
            ->assertStatus(401);
    }

    /** @test */
    public function index_returns_all_comments_with_user_relation()
    {
        // Seed two comments by different users
        $c1 = Comment::factory()->create([
            'grocery_id' => $this->grocery->id,
            'user_id'    => $this->member->id,
            'content'    => 'First comment',
        ]);
        $c2 = Comment::factory()->create([
            'grocery_id' => $this->grocery->id,
            'user_id'    => $this->owner->id,
            'content'    => 'Second comment',
        ]);

        // As member
        Sanctum::actingAs($this->member);
        $response = $this->getJson(route('api.comments.index', [$this->household, $this->grocery]));

        $response->assertStatus(200)
            ->assertJsonCount(2) // two comments
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'content',
                    'grocery_id',
                    'user_id',
                    'created_at',
                    'updated_at',
                    'user' => ['id', 'name', 'email'] // loaded relation
                ]
            ])
            ->assertJsonFragment(['content' => 'First comment'])
            ->assertJsonFragment(['content' => 'Second comment']);
    }

    /** @test */
    public function store_requires_auth_membership_and_validates_and_creates()
    {
        $payload = ['content' => 'Hello world'];

        // Unauthenticated
        $this->postJson(route('api.comments.store', [$this->household, $this->grocery]), $payload)
            ->assertStatus(401);

        // Outsider
        Sanctum::actingAs($this->outsider);
        $this->postJson(route('api.comments.store', [$this->household, $this->grocery]), $payload)
            ->assertStatus(401);

        // Member but missing content
        Sanctum::actingAs($this->member);
        $this->postJson(route('api.comments.store', [$this->household, $this->grocery]), [])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['content']]);

        // Content too long
        $long = str_repeat('a', 300);
        $this->postJson(route('api.comments.store', [$this->household, $this->grocery]), ['content' => $long])
            ->assertStatus(400)
            ->assertJsonStructure(['error' => ['content']]);

        // Successful creation
        Sanctum::actingAs($this->member);
        $this->postJson(route('api.comments.store', [$this->household, $this->grocery]), $payload)
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Comment created successfully']);

        $this->assertDatabaseHas('comments', [
            'grocery_id' => $this->grocery->id,
            'user_id'    => $this->member->id,
            'content'    => 'Hello world',
        ]);
    }

    /** @test */
    public function destroy_requires_owner_or_admin_and_soft_deletes_comment()
    {
        // Create a comment by member
        $comment = Comment::factory()->create([
            'grocery_id' => $this->grocery->id,
            'user_id'    => $this->member->id,
            'content'    => 'A comment',
        ]);

        $route = route(
            'api.comments.destroy',
            [$this->household, $this->grocery, $comment]
        );

        // Unauthenticated
        $this->deleteJson($route)
            ->assertStatus(401);

        // Non-owner member
        $otherMember = User::factory()->create();
        UserHousehold::factory()->create([
            'user_id'      => $otherMember->id,
            'household_id' => $this->household->id,
        ]);
        Sanctum::actingAs($otherMember);
        $this->deleteJson($route)
            ->assertStatus(401);

        // Owner of the comment can delete
        Sanctum::actingAs($this->member);
        $this->deleteJson($route)
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Comment deleted successfully']);
        $this->assertDatabaseHas('comments', [
            'id'      => $comment->id,
            'content' => '[Comment deleted]',
        ]);

        // Admin can delete another comment
        $c2 = Comment::factory()->create([
            'grocery_id' => $this->grocery->id,
            'user_id'    => $this->member->id,
            'content'    => 'Another one',
        ]);
        $route2 = route(
            'api.comments.destroy',
            [$this->household, $this->grocery, $c2]
        );
        Sanctum::actingAs($this->admin);
        $this->deleteJson($route2)
            ->assertStatus(200)
            ->assertExactJson(['Message' => 'Comment deleted successfully']);
        $this->assertDatabaseHas('comments', [
            'id'      => $c2->id,
            'content' => '[Comment deleted]',
        ]);
    }
}
