<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('portal_sources', function (Blueprint $table) {
            $table->id();
            $table->string('handle')->unique(); // without @
            $table->boolean('is_enabled')->default(true);

            // metadata we want from day 1
            $table->integer('tier')->default(2)->index();           // 1,2,3
            $table->string('sport')->default('basketball')->index(); // basketball|multi
            $table->boolean('needs_sport_filter')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_sources');
    }
};