<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_events', function (Blueprint $table) {
            $table->id();
            $table->string('player_name')->index();
            $table->string('from_team')->nullable()->index();
            $table->string('status')->default('entered')->index();
            $table->timestamp('first_reported_at')->index();
            $table->integer('report_count')->default(1);
            $table->timestamps();
            $table->unique(['player_name', 'from_team', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_events');
    }
};