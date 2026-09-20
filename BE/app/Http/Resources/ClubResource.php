<?php

namespace App\Http\Resources;

use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Club */
class ClubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sport_type' => $this->sport_type,
            'logo_url' => $this->logo_url,
            'subscription_plan' => $this->subscription_plan,
            'subscription_status' => $this->subscription_status,
        ];
    }
}
