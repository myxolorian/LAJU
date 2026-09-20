<?php

namespace App\Actions;

use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterClub
{
    /**
     * Buat klub baru + akun pemilik + keanggotaan admin dalam satu transaksi,
     * supaya tidak pernah ada klub tanpa admin (atau user tanpa klub) bila salah satu langkah gagal.
     *
     * @param  array{club_name: string, sport_type: string, name: string, email: string, phone?: ?string, password: string}  $data
     * @return array{club: Club, user: User, membership: ClubMember}
     */
    public function __invoke(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $club = Club::create([
                'name' => $data['club_name'],
                'sport_type' => $data['sport_type'],
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);

            $membership = ClubMember::create([
                'user_id' => $user->id,
                'club_id' => $club->id,
                'role' => ClubRole::Admin,
                'status' => MemberStatus::Aktif,
                'joined_at' => now(),
            ]);

            return compact('club', 'user', 'membership');
        });
    }
}
