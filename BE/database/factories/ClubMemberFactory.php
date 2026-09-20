<?php

namespace Database\Factories;

use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubMember>
 */
class ClubMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'club_id' => Club::factory(),
            'role' => ClubRole::Anggota,
            'status' => MemberStatus::Aktif,
            'joined_at' => now(),
        ];
    }

    public function role(ClubRole $role): static
    {
        return $this->state(['role' => $role]);
    }

    public function status(MemberStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
