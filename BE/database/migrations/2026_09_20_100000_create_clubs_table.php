<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sport_type');
            $table->string('logo_url')->nullable();
            // Dibaca middleware kuota Free/Pro; nilai valid: App\Enums\SubscriptionPlan / SubscriptionStatus.
            $table->string('subscription_plan', 20)->default('free');
            $table->string('subscription_status', 20)->default('active');
            $table->timestamps();

            $table->index(['subscription_plan', 'subscription_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
