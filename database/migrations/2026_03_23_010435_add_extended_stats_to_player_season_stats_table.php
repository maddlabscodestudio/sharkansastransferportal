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
        Schema::table('player_season_stats', function (Blueprint $table) {
            $table->unsignedInteger('field_goals_made')->nullable()->after('minutes');
            $table->unsignedInteger('field_goals_attempted')->nullable()->after('field_goals_made');

            $table->unsignedInteger('two_pointers_made')->nullable()->after('field_goals_attempted');
            $table->unsignedInteger('two_pointers_attempted')->nullable()->after('two_pointers_made');
            $table->decimal('two_pointers_percentage', 5, 2)->nullable()->after('two_pointers_attempted');

            $table->unsignedInteger('three_pointers_made')->nullable()->after('two_pointers_percentage');
            $table->unsignedInteger('three_pointers_attempted')->nullable()->after('three_pointers_made');

            $table->unsignedInteger('free_throws_made')->nullable()->after('three_pointers_attempted');
            $table->unsignedInteger('free_throws_attempted')->nullable()->after('free_throws_made');

            $table->unsignedInteger('offensive_rebounds')->nullable()->after('free_throws_attempted');
            $table->unsignedInteger('defensive_rebounds')->nullable()->after('offensive_rebounds');

            $table->unsignedInteger('personal_fouls')->nullable()->after('turnovers');

            $table->decimal('effective_field_goals_percentage', 5, 2)->nullable()->after('field_goals_percentage');
            $table->decimal('offensive_rebounds_percentage', 5, 2)->nullable()->after('usage_rate_percentage');
            $table->decimal('defensive_rebounds_percentage', 5, 2)->nullable()->after('offensive_rebounds_percentage');
            $table->decimal('total_rebounds_percentage', 5, 2)->nullable()->after('defensive_rebounds_percentage');
            $table->decimal('assists_percentage', 5, 2)->nullable()->after('total_rebounds_percentage');
            $table->decimal('steals_percentage', 5, 2)->nullable()->after('assists_percentage');
            $table->decimal('blocks_percentage', 5, 2)->nullable()->after('steals_percentage');
            $table->decimal('turnovers_percentage', 5, 2)->nullable()->after('blocks_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_season_stats', function (Blueprint $table) {
            $table->dropColumn([
                'field_goals_made',
                'field_goals_attempted',
                'two_pointers_made',
                'two_pointers_attempted',
                'two_pointers_percentage',
                'three_pointers_made',
                'three_pointers_attempted',
                'free_throws_made',
                'free_throws_attempted',
                'offensive_rebounds',
                'defensive_rebounds',
                'personal_fouls',
                'effective_field_goals_percentage',
                'offensive_rebounds_percentage',
                'defensive_rebounds_percentage',
                'total_rebounds_percentage',
                'assists_percentage',
                'steals_percentage',
                'blocks_percentage',
                'turnovers_percentage',
            ]);
        });
    }
};
