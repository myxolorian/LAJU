<?php

namespace Tests\Feature\Schema;

use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_no_club_id_column(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'club_id'));
        $this->assertTrue(Schema::hasColumns('users', ['name', 'email', 'phone', 'password']));
    }

    public function test_club_defaults_to_free_active_plan(): void
    {
        $club = Club::create(['name' => 'Garuda FC', 'sport_type' => 'sepak bola'])->refresh();

        $this->assertSame(SubscriptionPlan::Free, $club->subscription_plan);
        $this->assertSame(SubscriptionStatus::Active, $club->subscription_status);
    }

    public function test_one_user_can_belong_to_multiple_clubs_with_different_roles(): void
    {
        $user = User::factory()->create();
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();

        ClubMember::factory()->role(ClubRole::Admin)->create(['user_id' => $user->id, 'club_id' => $clubA->id]);
        ClubMember::factory()->role(ClubRole::Pelatih)->create(['user_id' => $user->id, 'club_id' => $clubB->id]);

        $memberships = $user->clubMembers()->get()->keyBy('club_id');

        $this->assertCount(2, $memberships);
        $this->assertSame(ClubRole::Admin, $memberships[$clubA->id]->role);
        $this->assertSame(ClubRole::Pelatih, $memberships[$clubB->id]->role);
        $this->assertEqualsCanonicalizing([$clubA->id, $clubB->id], $user->clubs->pluck('id')->all());
    }

    public function test_user_cannot_join_same_club_twice(): void
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();
        ClubMember::factory()->create(['user_id' => $user->id, 'club_id' => $club->id]);

        $this->expectException(QueryException::class);
        ClubMember::factory()->create(['user_id' => $user->id, 'club_id' => $club->id]);
    }

    public function test_membership_status_and_joined_at(): void
    {
        $member = ClubMember::factory()->status(MemberStatus::Pending)->create(['joined_at' => null])->refresh();

        $this->assertSame(MemberStatus::Pending, $member->status);
        $this->assertNull($member->joined_at);
    }

    public function test_team_belongs_to_club_and_name_is_unique_per_club(): void
    {
        $club = Club::factory()->create();
        Team::factory()->create(['club_id' => $club->id, 'name' => 'U-12']);

        // nama sama di klub lain boleh
        Team::factory()->create(['name' => 'U-12']);
        $this->assertSame(1, $club->teams()->count());

        $this->expectException(QueryException::class);
        Team::factory()->create(['club_id' => $club->id, 'name' => 'U-12']);
    }

    public function test_deleting_club_cascades_to_memberships_and_teams(): void
    {
        $club = Club::factory()->create();
        ClubMember::factory()->create(['club_id' => $club->id]);
        Team::factory()->create(['club_id' => $club->id]);

        $club->delete();

        $this->assertSame(0, ClubMember::count());
        $this->assertSame(0, Team::count());
    }
}
