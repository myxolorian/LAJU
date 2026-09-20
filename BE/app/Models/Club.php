<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sport_type', 'logo_url', 'subscription_plan', 'subscription_status'])]
class Club extends Model
{
    /** @use HasFactory<ClubFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'subscription_plan' => SubscriptionPlan::class,
            'subscription_status' => SubscriptionStatus::class,
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClubMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_members')
            ->using(ClubMember::class)
            ->withPivot(['id', 'role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}
