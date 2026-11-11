<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->unsignedTinyInteger('minute');
            $table->unsignedTinyInteger('home_score');
            $table->unsignedTinyInteger('away_score');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['fixture_id', 'minute']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
