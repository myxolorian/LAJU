<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot multi-tenant: satu akun (user) bisa punya peran berbeda di banyak klub.
     * Tabel operasional lanjutan (team_members, attendances, dues) mereferensi id baris ini,
     * bukan user_id, supaya data antar-klub tidak bocor.
     */
    public function up(): void
    {
        Schema::create('club_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);    // App\Enums\ClubRole: admin | pelatih | anggota
            $table->string('status', 20)->default('pending'); // App\Enums\MemberStatus
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'club_id']);
            $table->index(['club_id', 'role']);
            $table->index(['club_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_members');
    }
};
