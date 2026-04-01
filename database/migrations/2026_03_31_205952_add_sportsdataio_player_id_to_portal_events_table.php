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
        Schema::table('portal_events', function (Blueprint $table) {
            $table->unsignedBigInteger('sportsdataio_player_id')->nullable()->after('from_team');
            $table->index('sportsdataio_player_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_events', function (Blueprint $table) {
            $table->dropIndex(['sportsdataio_player_id']);
            $table->dropColumn('sportsdataio_player_id');
        });
    }
};
