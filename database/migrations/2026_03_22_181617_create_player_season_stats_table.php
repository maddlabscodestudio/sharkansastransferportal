<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_season_stats', function (Blueprint $table) {
            $table->id();

            // Internal reference (for now using name, later we’ll link to players table)
            $table->string('player_name');

            // Team mapping
            $table->string('team_key', 20)->nullable(); // e.g. FL
            $table->string('team_name')->nullable();    // e.g. Florida

            // External IDs (VERY important for deduping + updates)
            $table->unsignedBigInteger('sportsdataio_player_id')->index();
            $table->unsignedBigInteger('sportsdataio_team_id')->nullable()->index();
            $table->unsignedBigInteger('sportsdataio_stat_id')->nullable()->unique();

            // Season info
            $table->unsignedSmallInteger('season');
            $table->unsignedTinyInteger('season_type')->nullable();

            // Basic info
            $table->string('position', 10)->nullable();
            $table->unsignedSmallInteger('games')->nullable();
            $table->unsignedInteger('minutes')->nullable();

            // Core counting stats
            $table->unsignedInteger('points')->nullable();
            $table->unsignedInteger('rebounds')->nullable();
            $table->unsignedInteger('assists')->nullable();
            $table->unsignedInteger('steals')->nullable();
            $table->unsignedInteger('blocked_shots')->nullable();
            $table->unsignedInteger('turnovers')->nullable();

            // Shooting / efficiency
            $table->decimal('field_goals_percentage', 5, 2)->nullable();
            $table->decimal('three_pointers_percentage', 5, 2)->nullable();
            $table->decimal('free_throws_percentage', 5, 2)->nullable();
            $table->decimal('true_shooting_percentage', 5, 2)->nullable();
            $table->decimal('player_efficiency_rating', 6, 2)->nullable();
            $table->decimal('usage_rate_percentage', 5, 2)->nullable();

            // Timestamps from API + sync tracking
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();

            // Store full raw response (VERY useful for debugging / future fields)
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            // Prevent duplicates
            $table->unique(['sportsdataio_player_id', 'season', 'season_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_season_stats');
    }
};
