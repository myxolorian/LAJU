<?php

namespace App\Http\Resources;

use App\Models\ClubMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClubMember */
class ClubMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'role' => $this->role,
            'status' => $this->status,
            'joined_at' => $this->joined_at,
            'club' => new ClubResource($this->whenLoaded('club')),
        ];
    }
}
