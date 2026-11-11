<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('away_club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('ground_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('round');
            $table->dateTime('kicks_off_at');
            $table->string('status', 16)->default('scheduled');
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->unsignedTinyInteger('minute')->nullable();
            $table->timestamps();

            $table->unique(['season_id', 'home_club_id', 'away_club_id']);
            $table->index(['league_id', 'season_id', 'status']);
            $table->index('kicks_off_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
