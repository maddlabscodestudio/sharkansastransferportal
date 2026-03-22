<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('portal_entries', function (Blueprint $table) {
            $table->foreignId('raw_post_id')
                ->nullable()
                ->constrained('portal_raw_posts')
                ->cascadeOnDelete()
                ->after('id');

            $table->index('raw_post_id');
        });
    }

    public function down(): void
    {
        Schema::table('portal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('raw_post_id');
        });
    }
};