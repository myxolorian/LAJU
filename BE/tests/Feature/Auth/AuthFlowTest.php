<?php

namespace Tests\Feature\Auth;

use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'club_name' => 'Garuda Muda FC',
            'sport_type' => 'sepak bola',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'password' => 'rahasia-banget-123',
            'password_confirmation' => 'rahasia-banget-123',
        ], $overrides);
    }

    public function test_register_club_creates_club_user_and_admin_membership_end_to_end(): void
    {
        $response = $this->postJson('/api/auth/register-club', $this->payload());

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'club_members']])
            ->assertJsonPath('user.email', 'budi@example.com')
            ->assertJsonPath('user.club_members.0.role', 'admin')
            ->assertJsonPath('user.club_members.0.status', 'aktif')
            ->assertJsonPath('user.club_members.0.club.name', 'Garuda Muda FC')
            ->assertJsonPath('user.club_members.0.club.subscription_plan', 'free');

        $this->assertSame(1, Club::count());
        $this->assertSame(1, User::count());

        $member = ClubMember::sole();
        $this->assertSame(ClubRole::Admin, $member->role);
        $this->assertSame(MemberStatus::Aktif, $member->status);
        $this->assertNotNull($member->joined_at);
        $this->assertSame(User::sole()->id, $member->user_id);
        $this->assertSame(Club::sole()->id, $member->club_id);

        // token dari register langsung bisa dipakai
        $this->withToken($response->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.club_members.0.role', 'admin');
    }

    public function test_register_club_is_atomic_when_membership_insert_fails(): void
    {
        ClubMember::creating(function () {
            throw new \RuntimeException('boom');
        });

        $this->withoutExceptionHandling();

        try {
            $this->postJson('/api/auth/register-club', $this->payload());
            $this->fail('Exception diharapkan dilempar.');
        } catch (\RuntimeException) {
            // diharapkan
        }

        $this->assertSame(0, Club::count(), 'klub yatim tanpa admin tidak boleh ada');
        $this->assertSame(0, User::count());
        $this->assertSame(0, ClubMember::count());
    }

    public function test_register_club_validates_input(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->postJson('/api/auth/register-club', $this->payload(['password_confirmation' => 'beda']))
            ->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->postJson('/api/auth/register-club', $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->postJson('/api/auth/register-club', [])
            ->assertUnprocessable()->assertJsonValidationErrors(['club_name', 'sport_type', 'name', 'email', 'password']);

        $this->assertSame(0, Club::count());
    }

    public function test_login_returns_token_and_user(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'password']);

        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email']])
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_rejects_wrong_credentials(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'password']);

        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'salah'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->postJson('/api/auth/login', ['email' => 'tidak-ada@example.com', 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_user_with_all_club_memberships(): void
    {
        $user = User::factory()->create();
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();
        ClubMember::factory()->role(ClubRole::Admin)->create(['user_id' => $user->id, 'club_id' => $clubA->id]);
        ClubMember::factory()->role(ClubRole::Pelatih)->create(['user_id' => $user->id, 'club_id' => $clubB->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/auth/me')->assertOk();

        $response->assertJsonCount(2, 'user.club_members')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonMissingPath('user.password');

        $this->assertEqualsCanonicalizing(
            ['admin', 'pelatih'],
            collect($response->json('user.club_members'))->pluck('role')->all(),
        );
    }

    public function test_me_does_not_leak_memberships_of_other_users(): void
    {
        $me = User::factory()->create();
        ClubMember::factory()->create(); // user & klub lain

        $this->actingAs($me, 'sanctum')->getJson('/api/auth/me')
            ->assertOk()->assertJsonCount(0, 'user.club_members');
    }

    public function test_logout_revokes_the_token(): void
    {
        $token = $this->postJson('/api/auth/register-club', $this->payload())->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
