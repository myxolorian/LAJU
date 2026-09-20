<?php

namespace App\Models;

use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use Database\Factories\ClubMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot users <-> clubs. Punya id sendiri karena tabel lain (team_members,
 * attendances, dues) akan mereferensi club_member_id.
 */
#[Fillable(['user_id', 'club_id', 'role', 'status', 'joined_at'])]
class ClubMember extends Pivot
{
    /** @use HasFactory<ClubMemberFactory> */
    use HasFactory;

    protected $table = 'club_members';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'role' => ClubRole::class,
            'status' => MemberStatus::class,
            'joined_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
