<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_ground_id')->constrained('grounds')->cascadeOnDelete();
            $table->foreignId('to_ground_id')->constrained('grounds')->cascadeOnDelete();
            $table->decimal('road_km', 6, 1);
            $table->decimal('straight_km', 6, 1);
            $table->timestamps();

            $table->unique(['from_ground_id', 'to_ground_id']);
            $table->index(['from_ground_id', 'road_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distances');
    }
};
