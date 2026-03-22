<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('portal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('player_name')->index();
            $table->string('from_team')->nullable()->index();
            $table->enum('status', ['entered'])->default('entered')->index();
            $table->string('source')->nullable()->index(); // e.g. @VerbalCommits
            $table->timestamp('posted_at')->index();
            $table->timestamps();

            $table->unique(['player_name', 'from_team', 'posted_at', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_entries');
    }
};