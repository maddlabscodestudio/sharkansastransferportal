<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('portal_raw_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('portal_sources')->cascadeOnDelete();

            $table->string('external_post_id')->unique(); // tweet id or mock id
            $table->timestamp('posted_at')->index();
            $table->text('text');
            $table->string('permalink')->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_raw_posts');
    }
};